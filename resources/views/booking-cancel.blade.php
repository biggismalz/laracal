<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Cancelled</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; background: #f9fafb; margin: 0; color: #111827; }
        .wrapper { max-width: 520px; margin: 0 auto; padding: 4rem 1.5rem; }
        .card { background: #ffffff; border-radius: 16px; padding: 2.75rem 2.25rem; box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12); }
        h1 { margin: 0 0 1rem; font-size: clamp(2rem, 4vw, 2.5rem); }
        p { line-height: 1.6; color: #4b5563; }
        .cta { margin-top: 2.5rem; display: flex; gap: 1rem; flex-wrap: wrap; }
        a.button { display: inline-block; padding: 0.9rem 1.4rem; border-radius: 12px; font-weight: 600; text-decoration: none; transition: transform 0.2s ease, box-shadow 0.2s ease; }
        a.button.primary { background: #111827; color: #fff; }
        a.button.primary:hover { transform: translateY(-1px); box-shadow: 0 16px 32px rgba(17, 24, 39, 0.2); }
        a.button.secondary { background: #f3f4f6; color: #111827; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1>Payment was cancelled</h1>
            <p>Your appointment for <strong>{{ $booking->service->name }}</strong> hasn't been confirmed yet. If you still want the slot on <strong>{{ $booking->scheduled_start->timezone(config('app.timezone'))->format('l j F, H:i') }}</strong>, please restart the checkout process.</p>
            <p>If you ran into an issue with Stripe just let us know and we'll be happy to help.</p>
            <div class="cta">
                <a class="button primary" href="{{ route('booking.show') }}">Choose a different slot</a>
                <a class="button secondary" href="mailto:{{ config('mail.from.address') }}">Contact support</a>
            </div>
        </div>
    </div>
</body>
</html>
