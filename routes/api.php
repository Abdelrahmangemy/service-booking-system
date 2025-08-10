<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\Admin\ReportController;

Route::middleware('api.logger')->group(function () {
    Route::post('register', [AuthController::class,'register']);
    Route::post('login', [AuthController::class,'login']);

    Route::get('services', [ServiceController::class,'index']);
    Route::get('services/{service}', [ServiceController::class,'show']);
});

Route::middleware(['auth:api', 'api.logger'])->group(function(){
    // Service management (providers)
    Route::apiResource('services', ServiceController::class)->except(['index','show']);

    // Availability
    Route::get('availabilities', [AvailabilityController::class,'index']);
    Route::post('availabilities', [AvailabilityController::class,'store']);
    Route::delete('availabilities/{availability}', [AvailabilityController::class,'destroy']);

    // Bookings
    Route::get('bookings', [BookingController::class,'index']);
    Route::post('bookings', [BookingController::class,'store'])->middleware('throttle:10,1');
    Route::post('bookings/{booking}/confirm', [BookingController::class,'confirm']);
    Route::post('bookings/{booking}/cancel', [BookingController::class,'cancel']);

    // Admin Reports
    Route::get('admin/reports/bookings', [ReportController::class,'bookings']);
    Route::get('admin/reports/bookings/export', [ReportController::class,'exportBookings']);
});
