<?php

namespace App\Models;

use App\Enums\AvailabilityOverrideType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'date',
        'start_time',
        'end_time',
        'type',
        'timezone',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => AvailabilityOverrideType::class,
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
