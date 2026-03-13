<?php

use App\Http\Controllers\Backend\UserController;
use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\Backend\ServicesController;

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
     *  Backend Services Routes
     *
     * ---------------------------------------------------------------------
     */
    // Services Routes
    Route::group(['prefix' => 'services', 'as' => 'services.'], function () {
        Route::get('/index_list', [ServicesController::class, 'index_list'])->name('index_list');
        Route::get('/index_data', [ServicesController::class, 'index_data'])->name('index_data');
        Route::get('/trashed', [ServicesController::class, 'trashed'])->name('trashed');
        Route::patch('/trashed/{id}', [ServicesController::class, 'restore'])->name('restore');

        Route::get('/index_list_data', [ServicesController::class, 'index_list_data'])->name('index_list_data');

        // Assign Staff
        Route::get('/assign-employee/{id}', [ServicesController::class, 'assign_employee_list'])->name('assign_employee_list');
        Route::post('/assign-employee/{id}', [ServicesController::class, 'assign_employee_update'])->name('assign_employee_update');
        Route::get('/assign-employee-offcanvas/{id}', [ServicesController::class, 'assign_employee_offcanvas'])->name('assign_employee_offcanvas');

        // Assign Branch
        Route::get('/assign-branch/{id}', [ServicesController::class, 'assign_branch_list'])->name('assign_branch_list');
        Route::post('/assign-branch/{id}', [ServicesController::class, 'assign_branch_update'])->name('assign_branch_update');

        // Gallery Images
        Route::get('/gallery-images/{id}', [ServicesController::class, 'getGalleryImages']);
        Route::post('/gallery-images/{id}', [ServicesController::class, 'uploadGalleryImages']);
        Route::post('bulk-action', [ServicesController::class, 'bulk_action'])->name('bulk_action');
        Route::post('update-status/{id}', [ServicesController::class, 'update_status'])->name('update_status');
        Route::get('export', [ServicesController::class, 'export'])->name('export');
        Route::post('uniqueServices', [ServicesController::class, 'uniqueServices'])->name('uniqueServices');
        Route::get('get-subcategories', [ServicesController::class, 'getSubcategories'])->name('get_subcategories');
        Route::get('edit-form/{id}', [ServicesController::class, 'getEditForm'])->name('get_edit_form');
        Route::get('get-service-data/{id}', [ServicesController::class, 'getServiceData'])->name('get_service_data');
    });
    Route::resource('services', ServicesController::class);
    Route::get('services/{service}/edit-form', [ServicesController::class, 'getEditForm'])->name('backend.services.edit_form');

    // Service Packages
    Route::group(['prefix' => 'service', 'as' => 'service.'], function () {
        Route::get('/category_service_list', [ServicesController::class, 'categort_services_list']);
        Route::get('/index_list', [ServicesController::class, 'index_list'])->name('index_list');
        Route::get('/user-list', [UserController::class, 'user_list'])->name('user_list');
    });
});
