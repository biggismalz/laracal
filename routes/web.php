<?php

use App\Enums\BookingStatus;
use App\Http\Controllers\BookingPageController;
use App\Http\Controllers\PublicBookingController;
use App\Models\Booking;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/book', [BookingPageController::class, 'show'])
    ->name('booking.show');

Route::get('/book/services/{service}/slots', [PublicBookingController::class, 'slots'])
    ->name('booking.slots');

Route::post('/bookings', [PublicBookingController::class, 'store'])
    ->name('booking.store');

Route::get('/bookings/{booking}/success', function (Booking $booking) {
    return view('booking-success', ['booking' => $booking->fresh('service')]);
})->name('booking.success');

Route::get('/bookings/{booking}/cancel', function (Booking $booking) {
    if ($booking->status === BookingStatus::PendingPayment) {
        $booking->update([
            'status' => BookingStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    return view('booking-cancel', ['booking' => $booking->fresh('service')]);
})->name('booking.cancel');
