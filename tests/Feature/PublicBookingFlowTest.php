<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicBookingFlowTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertDatabaseHas('bookings', [
            'service_id' => $service->id,
            'customer_email' => 'demo@example.com',
            'status' => BookingStatus::PendingPayment->value,
        ]);
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
    }
}
