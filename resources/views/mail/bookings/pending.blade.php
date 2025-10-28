<x-mail::message>
@php
    $chargeAmount = number_format($booking->amount_charged_cents / 100, 2);
    $paymentLabel = $booking->payment_option->value === 'deposit' ? 'Deposit due now' : 'Amount due now';
@endphp

# Thanks {{ $booking->customer_name }}!

We've pencilled you in for **{{ $service->name }}** on **{{ $booking->scheduled_start->timezone(config('app.timezone'))->format('l j F, H:i') }}**.

<x-mail::panel>
<strong>Duration:</strong> {{ $service->duration_minutes }} minutes<br>
<strong>{{ $paymentLabel }}:</strong> {{ $chargeAmount }} {{ $booking->currency }}<br>
<strong>Notes:</strong> {{ $booking->customer_notes ?: 'n/a' }}
</x-mail::panel>

We'll confirm everything as soon as the payment succeeds. If the payment was interrupted you can try again from the booking page using the same details.

<x-mail::button :url="route('booking.success', $booking)">
View Booking Details
</x-mail::button>

Need to update or cancel? Reply to this email or call us and we'll help.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
