<?php

use Illuminate\Support\Facades\Route;
use Modules\Booking\Http\Controllers\Backend\API\BookingsController;
use Modules\Booking\Http\Controllers\Backend\API\PaymentController;

Route::get('booking-status', [BookingsController::class, 'statusList']);
Route::get('booking-invoice-download', [Modules\Booking\Http\Controllers\Backend\BookingsController::class, 'downloadInvoice'])->name('bookings.downloadinvoice');
Route::group(['middleware' => 'auth:sanctum', 'as' => 'backend.'], function () {
    Route::get('booking-list', [BookingsController::class, 'bookingList']);
    Route::get('booking-creation-data', [BookingsController::class, 'bookingCreationData']);
    Route::get('get-booking-slot', [BookingsController::class, 'getBookingSlot']);
    Route::post('calculate-booking-price', [BookingsController::class, 'calculateBookingPrice']);
    Route::post('calculate-booking-price/{booking_id}', [BookingsController::class, 'calculateBookingPrice']);
    Route::apiResource('bookings', BookingsController::class);
    Route::post('booking-update', [BookingsController::class, 'update']);
    Route::post('update-status/{id}', [BookingsController::class, 'updateStatus']);

    Route::get('booking-detail', [BookingsController::class, 'bookingDetail']);
    Route::get('search-booking', [BookingsController::class, 'searchBookings']);
    Route::post('save-booking', [BookingsController::class, 'store'])->name('save-booking');
    Route::post('save-booking-manager', [BookingsController::class, 'saveBookingManager']);
    Route::post('save-payment', [PaymentController::class, 'savePayment']);
});
