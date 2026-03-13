<?php

use Illuminate\Support\Facades\Route;
use Modules\BussinessHour\Http\Controllers\Backend\BussinessHoursController;

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
Route::group(['prefix' => 'app/bussinesshours', 'as' => 'backend.bussinesshours.'], function () {
    Route::get('index_list', [BussinessHoursController::class, 'index_list'])->name('index_list');
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
     *  Backend BussinessHours Routes
     *
     * ---------------------------------------------------------------------
     */

    Route::group(['prefix' => 'bussinesshours', 'as' => 'bussinesshours.'], function () {
        // Remove index_list from here so it's public
        // Route::get('index_list', [BussinessHoursController::class, 'index_list'])->name('index_list');
    });
    Route::resource('bussinesshours', BussinessHoursController::class);
});
