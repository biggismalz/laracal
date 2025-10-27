<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking Received</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #f5f7fb; margin: 0; color: #111827; }
        .wrapper { max-width: 520px; margin: 0 auto; padding: 4rem 1.5rem; }
        .card { background: #ffffff; border-radius: 16px; padding: 2.75rem 2.25rem; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12); }
        h1 { margin: 0 0 1rem; font-size: clamp(2rem, 4vw, 2.5rem); }
        p { line-height: 1.6; color: #4b5563; }
        .cta { margin-top: 2.5rem; }
        a.button { display: inline-block; padding: 0.9rem 1.4rem; border-radius: 12px; background: #111827; color: #fff; font-weight: 600; text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        a.button:hover { transform: translateY(-1px); box-shadow: 0 16px 32px rgba(17, 24, 39, 0.2); }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1>Thanks, your booking is pending payment</h1>
            <p>We've saved your booking for <strong>{{ $booking->service->name }}</strong> on <strong>{{ $booking->scheduled_start->timezone(config('app.timezone'))->format('l j F, H:i') }}</strong>.</p>
            <p>Once Stripe confirms the payment we'll send a confirmation email and update your dashboard. If you closed the payment window accidentally you can retry using the link in your email receipt.</p>
            <div class="cta">
                <a class="button" href="{{ route('booking.show') }}">Book another slot</a>
            </div>
        </div>
    </div>
</body>
</html>
