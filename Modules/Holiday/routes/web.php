<?php

use Illuminate\Support\Facades\Route;
use Modules\Holiday\Http\Controllers\Backend\HolidaysController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
 *
 * Backend Routes
 *
 * --------------------------------------------------------------------
 */
// Make index_list public for all users
Route::group(['prefix' => 'app/holidays', 'as' => 'backend.holidays.'], function () {
    Route::get('index_list', [HolidaysController::class, 'index_list'])->name('index_list');
});

Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth','admin']], function () {
    /*
     * These routes need view-backend permission
     * (good if you want to allow more than one group in the backend,
     * then limit the backend features by different roles or permissions)
     *
     * Note: Administrator has all permissions so you do not have to specify the administrator role everywhere.
     */

    /*
     *
     *  Backend Holidays Routes
     *
     * ---------------------------------------------------------------------
     */

    Route::group(['prefix' => 'holidays', 'as' => 'holidays'], function () {
        // Remove index_list from here so it's public
        // Route::get('index_list', [HolidaysController::class, 'index_list'])->name('index_list');
        Route::get('/is-holiday', [HolidaysController::class, 'isHoliday'])->name('is_holiday');

    });
    Route::resource('holidays', HolidaysController::class);
});
