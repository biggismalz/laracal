<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\View\View;

class BookingPageController extends Controller
{
    public function show(): View
    {
        $services = Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(function (Service $service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'description' => $service->description,
                    'duration_minutes' => $service->duration_minutes,
                    'price_cents' => $service->price_cents,
                    'deposit_cents' => $service->deposit_cents,
                    'currency' => $service->currency,
                    'buffer_before_minutes' => $service->buffer_before_minutes,
                    'buffer_after_minutes' => $service->buffer_after_minutes,
                ];
            })
            ->values();

        return view('booking', [
            'services' => $services,
            'defaultDate' => now()->timezone(config('app.timezone'))->toDateString(),
        ]);
    }
}
