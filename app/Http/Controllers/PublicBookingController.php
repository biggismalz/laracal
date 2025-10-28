<?php

namespace App\Http\Controllers;

use App\Data\AvailabilitySlot;
use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use App\Mail\Bookings\BookingPendingPaymentMail;
use App\Mail\Bookings\NewBookingNotificationMail;
use App\Models\Booking;
use App\Models\Service;
use App\Services\Availability\SlotGenerator;
use App\Services\Payments\StripeCheckoutSession;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicBookingController extends Controller
{
    public function slots(Service $service, Request $request, SlotGenerator $slotGenerator): JsonResponse
    {
        abort_unless($service->is_active, 404);

        $validated = $request->validate([
            'date' => ['required', 'date'],
        ]);

        $date = CarbonImmutable::parse($validated['date'], config('app.timezone'))->startOfDay();

        $now = now(config('app.timezone'));

        $slots = $slotGenerator->generate(
            serviceId: $service->id,
            rangeStart: $date,
            rangeEnd: $date->endOfDay(),
        )->filter(function (AvailabilitySlot $slot) use ($date, $now) {
            if (! $date->isSameDay($now)) {
                return true;
            }

            return $slot->start->greaterThanOrEqualTo($now);
        })->map(fn (AvailabilitySlot $slot) => [
            'start' => $slot->start->toIso8601String(),
            'end' => $slot->end->toIso8601String(),
            'label' => $slot->start->format('H:i') . ' - ' . $slot->end->format('H:i'),
        ])->values();

        return response()->json(['data' => $slots]);
    }

    public function store(Request $request, SlotGenerator $slotGenerator, StripeCheckoutSession $checkoutSession): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'scheduled_start' => ['required', 'date', 'after:now'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'payment_option' => ['required', Rule::in(array_map(fn (PaymentOption $option) => $option->value, PaymentOption::cases()))],
        ]);

        $service = Service::query()->findOrFail($validated['service_id']);

        abort_unless($service->is_active, 404);

        $scheduledStart = CarbonImmutable::parse($validated['scheduled_start']);
        $scheduledEnd = $scheduledStart->addMinutes($service->duration_minutes);

        $availableSlots = $slotGenerator->generate(
            serviceId: $service->id,
            rangeStart: $scheduledStart->startOfDay(),
            rangeEnd: $scheduledStart->endOfDay(),
        );

        $matchingSlot = $availableSlots->first(fn (AvailabilitySlot $slot) => $slot->start->equalTo($scheduledStart));

        if (! $matchingSlot) {
            return response()->json(['message' => 'Selected slot is no longer available. Please choose a different time.'], 422);
        }

        $hasConflict = Booking::query()
            ->where('service_id', $service->id)
            ->whereNotIn('status', [
                BookingStatus::Cancelled->value,
                BookingStatus::PaymentFailed->value,
            ])
            ->where(function ($query) use ($scheduledStart, $scheduledEnd) {
                $query
                    ->whereBetween('scheduled_start', [$scheduledStart, $scheduledEnd])
                    ->orWhereBetween('scheduled_end', [$scheduledStart, $scheduledEnd])
                    ->orWhere(function ($subQuery) use ($scheduledStart, $scheduledEnd) {
                        $subQuery
                            ->where('scheduled_start', '<', $scheduledStart)
                            ->where('scheduled_end', '>', $scheduledEnd);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return response()->json(['message' => 'Another booking was made for this time. Please select a different slot.'], 422);
        }

        $paymentOption = PaymentOption::from($validated['payment_option']);

        if ($paymentOption === PaymentOption::Deposit && $service->deposit_cents === null) {
            $paymentOption = PaymentOption::Full;
        }

        $amountToCharge = $paymentOption === PaymentOption::Deposit
            ? ($service->deposit_cents ?? $service->price_cents)
            : $service->price_cents;

        $booking = Booking::create([
            'service_id' => $service->id,
            'status' => BookingStatus::PendingPayment,
            'payment_option' => $paymentOption,
            'scheduled_start' => $scheduledStart,
            'scheduled_end' => $scheduledEnd,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'customer_notes' => $validated['customer_notes'] ?? null,
            'list_price_cents' => $service->price_cents,
            'amount_charged_cents' => $amountToCharge,
            'amount_paid_cents' => 0,
            'currency' => $service->currency,
        ]);

        if (! config('services.stripe.secret')) {
            return response()->json([
                'message' => 'Stripe is not configured. Please try again later.',
            ], 503);
        }

        $booking->load('service');

        $session = $checkoutSession->create(
            booking: $booking,
            successUrl: route('booking.success', ['booking' => $booking]),
            cancelUrl: route('booking.cancel', ['booking' => $booking]),
        );

        Mail::to($booking->customer_email)->send(new BookingPendingPaymentMail($booking));

        if ($notify = config('mail.notify_address')) {
            Mail::to($notify)->send(new NewBookingNotificationMail($booking));
        }

        return response()->json([
            'message' => 'Booking saved. We will direct you to payment shortly.',
            'booking_id' => $booking->id,
            'status' => $booking->status->value,
            'checkout_session_id' => $session['id'],
            'checkout_url' => $session['url'],
        ], 201);
    }
}
