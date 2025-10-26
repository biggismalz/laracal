<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DemoSchedulingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = Service::updateOrCreate(
            ['slug' => 'full-detail'],
            [
                'name' => 'Full Vehicle Detail',
                'description' => 'Complete interior and exterior detailing with ceramic protection.',
                'duration_minutes' => 180,
                'price_cents' => 35000,
                'deposit_cents' => 7500,
                'currency' => 'GBP',
                'buffer_before_minutes' => 30,
                'buffer_after_minutes' => 30,
                'is_active' => true,
            ],
        );

        // Mon-Fri from 09:00 to 17:00.
        foreach ([1, 2, 3, 4, 5] as $day) {
            AvailabilityRule::updateOrCreate(
                [
                    'service_id' => $service->id,
                    'day_of_week' => $day,
                ],
                [
                    'start_time' => '09:00:00',
                    'end_time' => '17:00:00',
                    'timezone' => config('app.timezone'),
                    'capacity' => 1,
                    'is_active' => true,
                ],
            );
        }

        // Block out the upcoming Saturday.
        AvailabilityOverride::updateOrCreate(
            [
                'service_id' => $service->id,
                'date' => CarbonImmutable::now()->next('saturday')->toDateString(),
            ],
            [
                'type' => 'closed',
                'notes' => 'Workshop maintenance day.',
            ],
        );

        // Seed an example confirmed booking for tomorrow.
        $start = CarbonImmutable::now()->addDay()->startOfDay()->setTime(9, 0);

        Booking::updateOrCreate(
            [
                'service_id' => $service->id,
                'scheduled_start' => $start,
            ],
            [
                'scheduled_end' => $start->addMinutes($service->duration_minutes),
                'status' => BookingStatus::Confirmed,
                'payment_option' => PaymentOption::Deposit,
                'customer_name' => 'Demo Customer',
                'customer_email' => 'demo@example.com',
                'customer_phone' => '01234 567890',
                'customer_notes' => 'Please focus on pet hair removal.',
                'list_price_cents' => $service->price_cents,
                'amount_charged_cents' => $service->deposit_cents ?? $service->price_cents,
                'amount_paid_cents' => $service->deposit_cents ?? $service->price_cents,
                'currency' => $service->currency,
                'paid_at' => $start->subDay(),
            ],
        );
    }
}
