<?php

namespace App\Services\Availability;

use App\Models\AvailabilityOverride;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\Service;
use App\Enums\BookingStatus;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class AvailabilityQuery
{
    public function rulesForService(Service $service, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): Collection
    {
        return AvailabilityRule::query()
            ->where(function ($query) use ($service) {
                $query->whereNull('service_id')
                    ->orWhere('service_id', $service->id);
            })
            ->get();
    }

    public function overridesForService(Service $service, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): Collection
    {
        return AvailabilityOverride::query()
            ->whereDate('date', '>=', $rangeStart->toDateString())
            ->whereDate('date', '<=', $rangeEnd->toDateString())
            ->where(function ($query) use ($service) {
                $query->whereNull('service_id')
                    ->orWhere('service_id', $service->id);
            })
            ->get();
    }

    public function bookingsForService(Service $service, CarbonImmutable $rangeStart, CarbonImmutable $rangeEnd): Collection
    {
        return Booking::query()
            ->where('service_id', $service->id)
            ->whereNotIn('status', [
                BookingStatus::Cancelled->value,
                BookingStatus::PaymentFailed->value,
            ])
            ->where(function ($query) use ($rangeStart, $rangeEnd) {
                $query
                    ->whereBetween('scheduled_start', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('scheduled_end', [$rangeStart, $rangeEnd])
                    ->orWhere(function ($subQuery) use ($rangeStart, $rangeEnd) {
                        $subQuery
                            ->where('scheduled_start', '<', $rangeStart)
                            ->where('scheduled_end', '>', $rangeEnd);
                    });
            })
            ->get();
    }
}
