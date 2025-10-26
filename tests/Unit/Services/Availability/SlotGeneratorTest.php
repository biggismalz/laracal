<?php

namespace Tests\Unit\Services\Availability;

use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Service;
use App\Services\Availability\AvailabilityQuery;
use App\Services\Availability\SlotGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlotGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private SlotGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.timezone' => 'Europe/London']);

        $this->generator = new SlotGenerator(new AvailabilityQuery());
    }

    public function test_it_generates_slots_from_weekly_rule(): void
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
            'timezone' => 'Europe/London',
        ]);

        $start = CarbonImmutable::create(2025, 1, 6, 0, 0, 0, 'Europe/London');
        $end = $start->endOfDay();

        $slots = $this->generator->generate($service->id, $start, $end);

        $this->assertCount(3, $slots);
        $this->assertEquals(['09:00', '10:00', '11:00'], $slots->map(fn ($slot) => $slot->start->format('H:i'))->all());
    }

    public function test_existing_booking_blocks_slots(): void
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
            'timezone' => 'Europe/London',
        ]);

        Booking::create([
            'service_id' => $service->id,
            'status' => BookingStatus::Confirmed->value,
            'payment_option' => PaymentOption::Full->value,
            'scheduled_start' => '2025-01-06 10:00:00',
            'scheduled_end' => '2025-01-06 11:00:00',
            'customer_name' => 'Jane Doe',
            'customer_email' => 'jane@example.com',
            'list_price_cents' => $service->price_cents,
            'amount_charged_cents' => $service->price_cents,
            'amount_paid_cents' => $service->price_cents,
            'currency' => $service->currency,
        ]);

        $start = CarbonImmutable::create(2025, 1, 6, 0, 0, 0, 'Europe/London');
        $end = $start->endOfDay();

        $slots = $this->generator->generate($service->id, $start, $end);

        $this->assertCount(2, $slots);
        $this->assertEquals(['09:00', '11:00'], $slots->map(fn ($slot) => $slot->start->format('H:i'))->all());
    }

    public function test_closed_override_removes_slots(): void
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
            'timezone' => 'Europe/London',
        ]);

        AvailabilityOverride::create([
            'service_id' => $service->id,
            'date' => '2025-01-06',
            'type' => 'closed',
            'notes' => 'Bank holiday',
        ]);

        $start = CarbonImmutable::create(2025, 1, 6, 0, 0, 0, 'Europe/London');
        $end = $start->endOfDay();

        $this->assertCount(1, AvailabilityOverride::all());
        $this->assertEquals($service->id, AvailabilityOverride::first()->service_id);
        $this->assertEquals('2025-01-06', AvailabilityOverride::first()->date->toDateString());
        $this->assertCount(1, (new AvailabilityQuery())->overridesForService($service, $start, $end));

        $slots = $this->generator->generate($service->id, $start, $end);

        $this->assertCount(0, $slots);
    }

    public function test_open_override_adds_additional_window(): void
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
            'timezone' => 'Europe/London',
        ]);

        AvailabilityOverride::create([
            'service_id' => $service->id,
            'date' => '2025-01-06',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'type' => 'open',
            'notes' => 'Special late availability',
        ]);

        $start = CarbonImmutable::create(2025, 1, 6, 0, 0, 0, 'Europe/London');
        $end = $start->endOfDay();

        $this->assertCount(1, (new AvailabilityQuery())->overridesForService($service, $start, $end));

        $slots = $this->generator->generate($service->id, $start, $end);

        $this->assertEquals(
            ['09:00', '10:00', '11:00', '14:00', '15:00'],
            $slots->map(fn ($slot) => $slot->start->format('H:i'))->all()
        );
    }
}
