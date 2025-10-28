<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Mail\Bookings\BookingPendingPaymentMail;
use App\Mail\Bookings\NewBookingNotificationMail;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected array $checkoutCalls = [];

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.secret' => 'sk_test_123456']);

        Mail::fake();

        $this->mock(
            \App\Services\Payments\StripeCheckoutSession::class,
            function ($mock) {
                $mock->shouldReceive('create')
                    ->andReturnUsing(function (Booking $booking, string $successUrl, string $cancelUrl) {
                        $this->checkoutCalls[] = compact('booking', 'successUrl', 'cancelUrl');

                        $booking->update([
                            'stripe_checkout_session_id' => 'cs_test_' . $booking->id,
                        ]);

                        return [
                            'id' => 'cs_test_' . $booking->id,
                            'url' => 'https://stripe.test/checkout/' . $booking->id,
                        ];
                    });
            }
        );
    }

    public function test_slots_endpoint_returns_available_slots(): void
    {
        $service = Service::factory()->create([
            'duration_minutes' => 60,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ]);

        AvailabilityRule::factory()->for($service)->create([
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $monday = CarbonImmutable::now()->addWeek()->startOfWeek();

        $response = $this->getJson(route('booking.slots', [
            'service' => $service,
            'date' => $monday->toDateString(),
        ]));

        $response->assertOk();
        $response->assertJson(fn ($json) =>
            $json->has('data', 3)
                ->where('data.0.label', '09:00 - 10:00')
        );
    }

    public function test_slots_endpoint_hides_past_times_for_today(): void
    {
        $now = CarbonImmutable::create(2025, 1, 6, 10, 30, 0, config('app.timezone'));
        Carbon::setTestNow($now);

        $service = Service::factory()->create([
            'duration_minutes' => 60,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
        ]);

        AvailabilityRule::factory()->for($service)->create([
            'day_of_week' => $now->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->getJson(route('booking.slots', [
            'service' => $service,
            'date' => $now->toDateString(),
        ]));

        $response->assertOk();
        $response->assertJson(fn ($json) =>
            $json->has('data', 1)
                ->where('data.0.label', '11:00 - 12:00')
        );

        Carbon::setTestNow();
    }

    public function test_booking_can_be_created_for_available_slot(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $service = Service::factory()->create([
            'duration_minutes' => 60,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'price_cents' => 15000,
            'deposit_cents' => 5000,
        ]);

        $targetDate = CarbonImmutable::now()->addWeek()->startOfWeek();

        AvailabilityRule::factory()->for($service)->create([
            'day_of_week' => $targetDate->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $scheduledStart = $targetDate->setTime(9, 0);

        $payload = [
            'service_id' => $service->id,
            'scheduled_start' => $scheduledStart->toIso8601String(),
            'customer_name' => 'Demo Customer',
            'customer_email' => 'demo@example.com',
            'customer_phone' => '07123 456789',
            'customer_notes' => 'Please focus on interior detailing.',
            'payment_option' => 'deposit',
        ];

        $response = $this->postJson(route('booking.store'), $payload);

        $response->assertCreated();
        $response->assertJson(fn ($json) =>
            $json->where('checkout_session_id', 'cs_test_1')
                ->where('checkout_url', 'https://stripe.test/checkout/1')
                ->etc()
        );

        $this->assertDatabaseHas('bookings', [
            'service_id' => $service->id,
            'customer_email' => 'demo@example.com',
            'status' => BookingStatus::PendingPayment->value,
            'stripe_checkout_session_id' => 'cs_test_1',
        ]);

        $this->assertCount(1, $this->checkoutCalls);

        $savedBooking = Booking::where('customer_email', 'demo@example.com')->first();

        Mail::assertSent(BookingPendingPaymentMail::class, fn (BookingPendingPaymentMail $mail) => $mail->booking->is($savedBooking));
        Mail::assertSent(NewBookingNotificationMail::class, fn (NewBookingNotificationMail $mail) => $mail->booking->is($savedBooking));
    }

    public function test_cancel_route_releases_pending_booking(): void
    {
        $service = Service::factory()->create([
            'duration_minutes' => 60,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'price_cents' => 18000,
        ]);

        $targetDate = CarbonImmutable::now()->addWeek()->startOfWeek();

        AvailabilityRule::factory()->for($service)->create([
            'day_of_week' => $targetDate->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $booking = Booking::factory()->create([
            'service_id' => $service->id,
            'scheduled_start' => $targetDate->setTime(9, 0),
            'scheduled_end' => $targetDate->setTime(10, 0),
            'status' => BookingStatus::PendingPayment,
        ]);

        $response = $this->get(route('booking.cancel', $booking));
        $response->assertOk();

        $booking->refresh();

        $this->assertEquals(BookingStatus::Cancelled, $booking->status);
        $this->assertNotNull($booking->cancelled_at);

        $slotsResponse = $this->getJson(route('booking.slots', [
            'service' => $service,
            'date' => $targetDate->toDateString(),
        ]));

        $slotsResponse->assertOk();
        $slotsResponse->assertJson(fn ($json) =>
            $json->has('data', 3)
        );
    }

    public function test_booking_creation_fails_if_slot_taken(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $service = Service::factory()->create([
            'duration_minutes' => 60,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'price_cents' => 18000,
        ]);

        $targetDate = CarbonImmutable::now()->addWeek()->startOfWeek();
        $scheduledStart = $targetDate->setTime(9, 0);

        AvailabilityRule::factory()->for($service)->create([
            'day_of_week' => $targetDate->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        Booking::factory()->create([
            'service_id' => $service->id,
            'scheduled_start' => $scheduledStart,
            'scheduled_end' => $scheduledStart->addHour(),
        ]);

        $response = $this->postJson(route('booking.store'), [
            'service_id' => $service->id,
            'scheduled_start' => $scheduledStart->toIso8601String(),
            'customer_name' => 'Second Customer',
            'customer_email' => 'second@example.com',
            'payment_option' => 'full',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Selected slot is no longer available. Please choose a different time.']);
        $this->assertCount(0, $this->checkoutCalls);
        Mail::assertNothingSent();
    }

    public function test_adjacent_booking_is_allowed(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $service = Service::factory()->create([
            'duration_minutes' => 60,
            'buffer_before_minutes' => 0,
            'buffer_after_minutes' => 0,
            'price_cents' => 15000,
            'deposit_cents' => 5000,
        ]);

        $targetDate = CarbonImmutable::now()->addWeek()->startOfWeek();

        AvailabilityRule::factory()->for($service)->create([
            'day_of_week' => $targetDate->dayOfWeek,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        Booking::factory()->create([
            'service_id' => $service->id,
            'scheduled_start' => $targetDate->setTime(9, 0),
            'scheduled_end' => $targetDate->setTime(10, 0),
            'status' => BookingStatus::PendingPayment,
        ]);

        $payload = [
            'service_id' => $service->id,
            'scheduled_start' => $targetDate->setTime(10, 0)->toIso8601String(),
            'customer_name' => 'Second Customer',
            'customer_email' => 'second@example.com',
            'payment_option' => 'full',
        ];

        $response = $this->postJson(route('booking.store'), $payload);

        $response->assertCreated();
        $this->assertCount(1, $this->checkoutCalls);
        Mail::assertSent(BookingPendingPaymentMail::class);
    }
}
