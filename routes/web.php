<?php

use App\Http\Controllers\BookingPageController;
use App\Http\Controllers\PublicBookingController;
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
