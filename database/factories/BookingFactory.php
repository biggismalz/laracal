<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $service = Service::factory()->create();
        $start = CarbonImmutable::now()->addDays($this->faker->numberBetween(1, 10))->setTime(9, 0);
        $end = $start->addMinutes($service->duration_minutes);
        $paymentOption = $service->deposit_cents ? PaymentOption::Deposit : PaymentOption::Full;
        $charge = $paymentOption === PaymentOption::Deposit ? ($service->deposit_cents ?? $service->price_cents) : $service->price_cents;

        return [
            'service_id' => $service->id,
            'status' => $paymentOption === PaymentOption::Deposit ? BookingStatus::PendingPayment : BookingStatus::Confirmed,
            'payment_option' => $paymentOption,
            'scheduled_start' => $start,
            'scheduled_end' => $end,
            'customer_name' => $this->faker->name(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_notes' => $this->faker->optional()->sentence(),
            'list_price_cents' => $service->price_cents,
            'amount_charged_cents' => $charge,
            'amount_paid_cents' => $paymentOption === PaymentOption::Full ? $charge : 0,
            'currency' => $service->currency,
            'stripe_checkout_session_id' => null,
            'stripe_payment_intent_id' => null,
        ];
    }
}
