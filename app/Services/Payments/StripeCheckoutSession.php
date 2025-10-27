<?php

namespace App\Services\Payments;

use App\Enums\PaymentOption;
use App\Models\Booking;
use Stripe\StripeClient;

class StripeCheckoutSession
{
    public function __construct(
        protected StripeClient $client,
    ) {
    }

    /**
     * Create a Stripe Checkout Session for the provided booking.
     *
     * @return array{id: string, url: string}
     */
    public function create(Booking $booking, string $successUrl, string $cancelUrl): array
    {
        $service = $booking->service;

        $descriptionParts = [
            $service->description,
            'Scheduled for ' . $booking->scheduled_start->timezone(config('app.timezone'))->format('M j, Y H:i'),
        ];

        if ($booking->payment_option === PaymentOption::Deposit) {
            $descriptionParts[] = 'Deposit payment';
        }

        $session = $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'customer_email' => $booking->customer_email,
            'metadata' => [
                'booking_id' => (string) $booking->id,
                'service_id' => (string) $service->id,
                'payment_option' => $booking->payment_option->value,
            ],
            'line_items' => [
                [
                    'quantity' => 1,
                    'price_data' => [
                        'currency' => strtolower($booking->currency),
                        'unit_amount' => $booking->amount_charged_cents,
                        'product_data' => [
                            'name' => $service->name,
                            'description' => trim(collect($descriptionParts)->filter()->implode(' · ')),
                        ],
                    ],
                ],
            ],
        ]);

        $booking->update([
            'stripe_checkout_session_id' => $session->id ?? null,
            'stripe_payment_intent_id' => $session->payment_intent ?? null,
        ]);

        return [
            'id' => $session->id,
            'url' => $session->url,
        ];
    }
}
