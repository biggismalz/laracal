<?php

namespace App\Services\Availability;

use App\Data\AvailabilitySlot;
use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class SlotGenerator
{
    public function __construct(
        protected readonly AvailabilityQuery $query,
    ) {
    }

    /**
     * Generate available slots for a service within a date range.
     */
    public function generate(int $serviceId, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): Collection
    {
        $service = Service::query()->findOrFail($serviceId);

        $rules = $this->query->rulesForService($service, $rangeStart, $rangeEnd);
        $overrides = $this->query->overridesForService($service, $rangeStart, $rangeEnd);
        $bookings = $this->query->bookingsForService($service, $rangeStart, $rangeEnd);

        $slots = collect();

        $period = CarbonPeriod::create($rangeStart->startOfDay(), '1 day', $rangeEnd->endOfDay());

        foreach ($period as $periodDay) {
            $day = CarbonImmutable::createFromMutable($periodDay)->setTimezone($rangeStart->timezoneName ?? config('app.timezone'));

            $dailyRules = $rules->filter(fn (AvailabilityRule $rule) => (int) $day->dayOfWeek === $rule->day_of_week);
            $dailyOverrides = $overrides->filter(fn (AvailabilityOverride $override) => $override->date->toDateString() === $day->toDateString());

            $daySlots = $this->buildSlotsForDay(
                service: $service,
                day: $day,
                rules: $dailyRules,
                overrides: $dailyOverrides,
            );

            $slots = $slots->merge($daySlots);
        }

        return $this->excludeCollisions($slots, $bookings, $service);
    }

    protected function buildSlotsForDay(
        Service $service,
        CarbonImmutable $day,
        Collection $rules,
        Collection $overrides,
    ): Collection {
        $baseSlots = collect();

        foreach ($rules as $rule) {
            if (! $rule->is_active) {
                continue;
            }

            $ruleTimezone = $rule->timezone ?? config('app.timezone');
            $start = $day->setTimezone($ruleTimezone)->setTimeFromTimeString($rule->start_time);
            $end = $day->setTimezone($ruleTimezone)->setTimeFromTimeString($rule->end_time);

            if ($start >= $end) {
                continue;
            }

            $baseSlots = $baseSlots->merge(
                $this->expandWindowIntoSlots($service, $start, $end)
            );
        }

        $closedOverrides = $overrides->filter(fn (AvailabilityOverride $override) => $override->type->value === 'closed');

        foreach ($closedOverrides as $override) {
            [$overrideStart, $overrideEnd] = $this->resolveOverrideWindow($override, $day);

            $baseSlots = $baseSlots->reject(fn (AvailabilitySlot $slot) => $slot->overlaps($overrideStart, $overrideEnd));
        }

        $additionalSlots = collect();

        $openOverrides = $overrides->filter(fn (AvailabilityOverride $override) => $override->type->value === 'open');

        foreach ($openOverrides as $override) {
            [$overrideStart, $overrideEnd] = $this->resolveOverrideWindow($override, $day);

            $additionalSlots = $additionalSlots->merge(
                $this->expandWindowIntoSlots($service, $overrideStart, $overrideEnd)
            );
        }

        return $baseSlots
            ->merge($additionalSlots)
            ->sortBy(fn (AvailabilitySlot $slot) => $slot->start->timestamp)
            ->unique(fn (AvailabilitySlot $slot) => $slot->start->timestamp . '-' . $slot->end->timestamp)
            ->values();
    }

    protected function expandWindowIntoSlots(Service $service, CarbonImmutable $windowStart, CarbonImmutable $windowEnd): Collection
    {
        $slots = collect();
        $current = $windowStart->copy();

        while ($current->addMinutes($service->duration_minutes) <= $windowEnd) {
            $start = $current;
            $end = $current->addMinutes($service->duration_minutes);

            $slots->push(new AvailabilitySlot(
                start: $start,
                end: $end,
                serviceId: $service->id,
                isBookable: true,
            ));

            $current = $current->addMinutes($service->duration_minutes + $service->buffer_after_minutes + $service->buffer_before_minutes);
        }

        return $slots;
    }

    protected function excludeCollisions(Collection $slots, Collection $bookings, Service $service): Collection
    {
        $bufferBefore = $service->buffer_before_minutes;
        $bufferAfter = $service->buffer_after_minutes;

        return $slots->filter(function (AvailabilitySlot $slot) use ($bookings, $bufferBefore, $bufferAfter) {
            $slotStart = $slot->start->subMinutes($bufferBefore);
            $slotEnd = $slot->end->addMinutes($bufferAfter);

            foreach ($bookings as $booking) {
                $bookingStart = CarbonImmutable::parse($booking->scheduled_start);
                $bookingEnd = CarbonImmutable::parse($booking->scheduled_end);

                if ($slotEnd > $bookingStart && $slotStart < $bookingEnd) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    protected function resolveOverrideWindow(AvailabilityOverride $override, CarbonImmutable $day): array
    {
        $timezone = $override->timezone ?? $day->timezoneName ?? config('app.timezone');
        $dayInTimezone = $day->setTimezone($timezone);

        $start = $override->start_time
            ? $dayInTimezone->setTimeFromTimeString($override->start_time)
            : $dayInTimezone->startOfDay();

        $end = $override->end_time
            ? $dayInTimezone->setTimeFromTimeString($override->end_time)
            : $dayInTimezone->endOfDay();

        return [$start, $end];
    }
}
