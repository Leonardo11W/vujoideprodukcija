<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\FrontendSetting\Http\Controllers\FrontendSettingController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by RouteServiceProvider and assigned "api" prefix.
|
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('frontendsetting', fn (Request $request) => $request->user())->name('frontendsetting');
});

// Landing layout API routes (used via AJAX from Blade)
Route::middleware('api')->group(function () {
    Route::match(['get','post'], 'get-landing-layout-page-config', [FrontendSettingController::class, 'getLandingLayoutPageConfig'])
         ->name('getLandingLayoutPageConfig');
    Route::post('save-landing-layout-page-config', [FrontendSettingController::class, 'saveLandingLayoutPageConfig'])
         ->name('saveLandingLayoutPageConfig');
});
