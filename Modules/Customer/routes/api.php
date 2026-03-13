<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Backend\API\CustomersController;
use Modules\Customer\Http\Controllers\Backend\CustomersController as BackendCustomersController;

Route::get('customer-list', [CustomersController::class, 'customerList']);
Route::get('customer-detail/{id}', [CustomersController::class, 'customerDetail']);

// Protected routes - require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('customer-add', [BackendCustomersController::class, 'store']);
    Route::post('customer-edit/{id}', [BackendCustomersController::class, 'update']);
    Route::post('customer-delete/{id}', [BackendCustomersController::class, 'destroy']);
});
