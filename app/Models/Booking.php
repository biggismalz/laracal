<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'status',
        'payment_option',
        'scheduled_start',
        'scheduled_end',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_notes',
        'list_price_cents',
        'amount_charged_cents',
        'amount_paid_cents',
        'currency',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'paid_at',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'payment_option' => PaymentOption::class,
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'list_price_cents' => 'integer',
        'amount_charged_cents' => 'integer',
        'amount_paid_cents' => 'integer',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function markAsPaid(): void
    {
        $this->status = BookingStatus::Confirmed;
        $this->amount_paid_cents = $this->amount_charged_cents;
        $this->paid_at = now();
    }
}
