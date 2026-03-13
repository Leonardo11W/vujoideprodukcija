<?php

use Illuminate\Support\Facades\Route;
use Modules\Employee\Http\Controllers\Backend\API\EmployeeController;

Route::get('employee-list', [EmployeeController::class, 'employeeList']);
Route::get('employee-detail', [EmployeeController::class, 'employeeDetail']);
Route::get('get-rating', [EmployeeController::class, 'getRating']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('staff-list', [EmployeeController::class, 'staffList']);
    Route::get('employee-reviews', [EmployeeController::class, 'employeeList']);
    Route::post('save-rating', [EmployeeController::class, 'saveRating']);
    Route::post('delete-rating', [EmployeeController::class, 'deleteRating']);
    Route::post('add-staff', [EmployeeController::class, 'storeStaff']);
    Route::post('update-staff/{id}', [EmployeeController::class, 'updateStaff']);
    Route::get('staff-detail/{param}', [EmployeeController::class, 'staffDetail'])->where('param', '.*');
    Route::delete('delete-staff/{id}', [EmployeeController::class, 'destroyStaff']);
});
