<x-mail::message>
@php
    $chargeAmount = number_format($booking->amount_charged_cents / 100, 2);
    $start = $booking->scheduled_start->timezone(config('app.timezone'));
@endphp

# New booking awaiting payment

<x-mail::panel>
<strong>Service:</strong> {{ $service->name }}<br>
<strong>When:</strong> {{ $start->format('l j F, H:i') }} ({{ $start->timezoneName }})<br>
<strong>Client:</strong> {{ $booking->customer_name }} ({{ $booking->customer_email }})<br>
<strong>Phone:</strong> {{ $booking->customer_phone ?: 'n/a' }}<br>
<strong>Amount to charge:</strong> {{ $chargeAmount }} {{ $booking->currency }} ({{ ucfirst(str_replace('_', ' ', $booking->payment_option->value)) }})
</x-mail::panel>

@isset($booking->customer_notes)
> {{ $booking->customer_notes }}
@endisset

<x-mail::button :url="route('booking.success', $booking)">
View Booking
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
