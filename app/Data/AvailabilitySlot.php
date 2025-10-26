<?php

namespace App\Data;

use Carbon\CarbonImmutable;

class AvailabilitySlot
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly int $serviceId,
        public readonly bool $isBookable,
    ) {
    }

    public function overlaps(CarbonImmutable $start, CarbonImmutable $end): bool
    {
        return $this->start < $end && $this->end > $start;
    }
}
