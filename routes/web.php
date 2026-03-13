<?php

use App\Http\Controllers\Backend\BackendController;
use App\Http\Controllers\Backend\BackupController;
use App\Http\Controllers\Backend\BranchController;
use App\Http\Controllers\Backend\NotificationsController;
use App\Http\Controllers\Backend\InquiryController;
use App\Http\Controllers\Backend\SettingController;
use App\Http\Controllers\Backend\UserController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RolePermission;
use App\Http\Controllers\SearchController;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Modules\Frontend\Http\Controllers\Backend\FrontendsController;
use Modules\Frontend\Http\Controllers\Backend\AuthController;
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

// Auth Routes
require __DIR__ . '/auth.php';

// CSRF Token Route
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

Route::get('storage-link', function () {
    return Artisan::call('storage:link');
});

Route::group(['middleware' => ['auth','admin']], function () {});

Route::group(['middleware' => 'auth','admin'], function () {

    Route::get('/app', function () {


        if (auth()->user()->hasRole('employee')) {
            return redirect(RouteServiceProvider::EMPLOYEE_LOGIN_REDIRECT);
        } else {
            return redirect(RouteServiceProvider::HOME);
        }
    });
});

Route::group(['middleware' => ['auth','admin']], function () {
    Route::get('notification-list', [NotificationsController::class, 'notificationList'])->name('notification.list');
    Route::get('notification-counts', [NotificationsController::class, 'notificationCounts'])->name('notification.counts');
});

Route::group(['prefix' => 'app', 'middleware' => 'auth','admin'], function () {
    // Language Switch
    Route::get('language/{language}', [LanguageController::class, 'switch'])->name('language.switch');
    Route::post('set-user-setting', [BackendController::class, 'setUserSetting'])->name('backend.setUserSetting');

    Route::group(['as' => 'backend.', 'middleware' => ['auth']], function () {
        Route::get('get_search_data', [SearchController::class, 'get_search_data'])->name('get_search_data');
        Route::get('users-inquiries', [BackendController::class, 'UsersInquiries'])->name('users-inquiries.index');

        // Sync Role & Permission

        Route::get('/permission-role', [RolePermission::class, 'index'])->name('permission-role.list')->middleware('password.confirm');
        Route::post('/permission-role/store/{role_id}', [RolePermission::class, 'store'])->name('permission-role.store');
        Route::get('/permission-role/reset/{role_id}', [RolePermission::class, 'reset_permission'])->name('permission-role.reset');
        // Role & Permissions Crud
        Route::resource('permission', PermissionController::class);
        Route::resource('role', RoleController::class);

        Route::group(['prefix' => 'module', 'as' => 'module.'], function () {
            Route::get('index_data', [ModuleController::class, 'index_data'])->name('index_data');
            Route::post('update-status/{id}', [ModuleController::class, 'update_status'])->name('update_status');
        });

        Route::resource('module', ModuleController::class);

        /*
          *
          *  Settings Routes
          *
          * ---------------------------------------------------------------------
          */
        Route::group(['middleware' => ['auth']], function () {
            Route::get('settings/{vue_capture?}', [SettingController::class, 'index'])->name('settings')->where('vue_capture', '^(?!storage).*$');
            Route::get('settings-data', [SettingController::class, 'index_data']);
            Route::post('settings', [SettingController::class, 'store'])->name('settings.store');
            Route::post('setting-update', [SettingController::class, 'update'])->name('setting.update');
            Route::get('clear-cache', [SettingController::class, 'clear_cache'])->name('clear-cache');
            Route::post('verify-email', [SettingController::class, 'verify_email'])->name('verify-email');
        });

        /*
        *
        *  Notification Routes
        *
        * ---------------------------------------------------------------------
        */
        Route::group(['prefix' => 'notifications', 'as' => 'notifications.'], function () {
            Route::get('/', [NotificationsController::class, 'index'])->name('index');
            Route::get('/markAllAsRead', [NotificationsController::class, 'markAllAsRead'])->name('markAllAsRead');
            Route::delete('/deleteAll', [NotificationsController::class, 'deleteAll'])->name('deleteAll');
            Route::get('/{id}', [NotificationsController::class, 'show'])->name('show');
        });

        /*
        *
        *  Inquiry Routes
        *
        * ---------------------------------------------------------------------
        */
        Route::group(['prefix' => 'inquiries', 'as' => 'inquiries.'], function () {
            Route::get('/', [InquiryController::class, 'index'])->name('index');
            Route::get('/index_list', [InquiryController::class, 'index_list'])->name('index_list');
            Route::get('/index_data', [InquiryController::class, 'index_data'])->name('index_data');
            Route::post('/bulk-action', [InquiryController::class, 'bulk_action'])->name('bulk_action');
            Route::delete('/deleteAll', [InquiryController::class, 'deleteAll'])->name('deleteAll');
            Route::get('/{id}', [InquiryController::class, 'show'])->name('show');
            Route::delete('/{id}', [InquiryController::class, 'destroy'])->name('destroy');
        });

        /*
        *
        *  Backup Routes
        *
        * ---------------------------------------------------------------------
        */
        Route::group(['prefix' => 'backups', 'as' => 'backups.'], function () {
            Route::get('/', [BackupController::class, 'index'])->name('index');
            Route::get('/create', [BackupController::class, 'create'])->name('create');
            Route::get('/download/{file_name}', [BackupController::class, 'download'])->name('download');
            Route::get('/delete/{file_name}', [BackupController::class, 'delete'])->name('delete');
        });

        Route::get('daily-booking-report', [ReportsController::class, 'daily_booking_report'])->name('reports.daily-booking-report');
        Route::get('daily-booking-report-index-data', [ReportsController::class, 'daily_booking_report_index_data'])->name('reports.daily-booking-report.index_data');
        Route::get('overall-booking-report', [ReportsController::class, 'overall_booking_report'])->name('reports.overall-booking-report');
        Route::get('overall-booking-report-index-data', [ReportsController::class, 'overall_booking_report_index_data'])->name('reports.overall-booking-report.index_data');
        Route::get('payout-report', [ReportsController::class, 'payout_report'])->name('reports.payout-report');
        Route::get('payout-report-index-data', [ReportsController::class, 'payout_report_index_data'])->name('reports.payout-report.index_data');
        Route::get('staff-report', [ReportsController::class, 'staff_report'])->name('reports.staff-report');
        Route::get('staff-report-index-data', [ReportsController::class, 'staff_report_index_data'])->name('reports.staff-report.index_data');

        Route::get('order-report', [ReportsController::class, 'order_report'])->name('reports.order-report');
        Route::get('order-report-index-data', [ReportsController::class, 'order_report_index_data'])->name('reports.order-report.index_data');

        // Review Routes
        Route::get('daily-booking-report-review', [ReportsController::class, 'daily_booking_report_review'])->name('reports.daily-booking-report-review');
        Route::get('overall-booking-report-review', [ReportsController::class, 'overall_booking_report_review'])->name('reports.overall-booking-report-review');
        Route::get('payout-report-review', [ReportsController::class, 'payout_report_review'])->name('reports.payout-report-review');
        Route::get('staff-report-review', [ReportsController::class, 'staff_report_review'])->name('reports.staff-report-review');
        Route::get('order_booking_report_review', [ReportsController::class, 'order_booking_report_review'])->name('reports.order_booking_report_review');
    });

    Route::prefix('faq')->name('faq.')->group(function () {
        Route::get('/', [FaqController::class, 'index'])->middleware(['auth'])->name('index');
        Route::get('index_data', [FaqController::class, 'index_data'])->name('index_data');
        Route::get('create', [FaqController::class, 'create'])->name('create');
        Route::get('edit/{id}', [FaqController::class, 'edit'])->name('edit');
        Route::post('store', [FaqController::class, 'store'])->name('store');
        Route::delete('delete/{id}', [FaqController::class, 'delete'])->name('delete');
        Route::post('update-status/{id}', [FaqController::class, 'updateStatus'])->name('update_status');
    });


    Route::prefix('blog')->name('blog.')->middleware(['auth'])->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('index_data', [BlogController::class, 'index_data'])->name('index_data');
        Route::get('create', [BlogController::class, 'create'])->name('create');
        Route::get('edit/{id}', [BlogController::class, 'edit'])->name('edit');
        Route::post('store', [BlogController::class, 'store'])->name('store');
        Route::delete('delete/{id}', [BlogController::class, 'delete'])->name('delete');
        Route::post('update-status/{id}', [BlogController::class, 'updateStatus'])->name('update_status');
    });

    Route::post('blogs/{id}/update-status', [BlogController::class, 'updateStatus'])->name('backend.blog.update_status');
    /*
    *
    * Backend Routes
    * These routes need view-backend permission
    * --------------------------------------------------------------------
    */

    Route::middleware(['checkInstallation','admin'])->group(function () {

        Route::group(['as' => 'backend.', 'middleware' => ['auth']], function () {
            /**
             * Backend Dashboard
             * Namespaces indicate folder structure.
             */
            Route::get('/', [BackendController::class, 'index'])->name('home');

            Route::post('set-current-branch/{branch_id}', [BackendController::class, 'setCurrentBranch'])->name('set-current-branch');
            Route::post('reset-branch', [BackendController::class, 'resetBranch'])->name('reset-branch');
            Route::post('set-my-work', [BackendController::class, 'setMyWork'])->name('set-my-work');
            Route::post('reset-my-work', [BackendController::class, 'resetMyWork'])->name('reset-my-work');

            Route::group(['prefix' => ''], function () {
                Route::get('dashboard', [BackendController::class, 'index'])->name('dashboard');
                Route::get('staff-dashboard', [BackendController::class, 'index'])->name('staff.dashboard');

                /**
                 * Branch Routes
                 */
                Route::group(['prefix' => 'branch', 'as' => 'branch.'], function () {
                    Route::get('index_list', [BranchController::class, 'index_list'])->name('index_list');
                    Route::get('assign/{id}', [BranchController::class, 'assign_list'])->name('assign_list');
                    Route::post('assign/{id}', [BranchController::class, 'assign_update'])->name('assign_update');
                    Route::get('index_data', [BranchController::class, 'index_data'])->name('index_data');
                    Route::get('trashed', [BranchController::class, 'trashed'])->name('trashed');
                    Route::patch('trashed/{id}', [BranchController::class, 'restore'])->name('restore');
                    // Branch Gallery Images
                    Route::get('gallery-images/{id}', [BranchController::class, 'getGalleryImages']);
                    Route::post('gallery-images/{id}', [BranchController::class, 'uploadGalleryImages']);
                    Route::post('bulk-action', [BranchController::class, 'bulk_action'])->name('bulk_action');
                    Route::post('update-status/{id}', [BranchController::class, 'update_status'])->name('update_status');
                    Route::post('update-select-value/{id}/{action_type}', [BranchController::class, 'update_select'])->name('update_select');
                    Route::post('branch-setting', [BranchController::class, 'UpdateBranchSetting'])->name('branch_setting');
                    Route::get('export', [BranchController::class, 'export'])->name('export');
                });
                Route::get('branch-info', [BranchController::class, 'branchData'])->name('branchData');
                Route::resource('branch', BranchController::class);

                /*
                *
                *  Users Routes
                *
                * ---------------------------------------------------------------------
                */
                Route::group(['prefix' => 'users', 'as' => 'users.'], function () {
                    Route::get('user-list', [UserController::class, 'user_list'])->name('user_list');
                    Route::get('emailConfirmationResend/{id}', [UserController::class, 'emailConfirmationResend'])->name('emailConfirmationResend');
                    Route::post('create-customer', [UserController::class, 'create_customer'])->name('create_customer');
                    Route::post('information', [UserController::class, 'update'])->name('information');
                    Route::post('change-password', [UserController::class, 'change_password'])->name('change_password');
                });
            });
            Route::get('my-profile/{vue_capture?}', [UserController::class, 'myProfile'])->name('my-profile')->where('vue_capture', '^(?!storage).*$');
            Route::get('my-info', [UserController::class, 'authData'])->name('authData');
            Route::post('my-profile/change-password', [UserController::class, 'change_password'])->name('change_password');
        });
    });
});

Route::get('/blogs', function () {
    return view('blog');
})->name('blogs');

Route::get('blogs/index_data', [BlogController::class, 'index_data'])->name('blogs.index_data');

Route::post('login', [AuthController::class, 'loginUser'])->name('login');
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::post('/store-booking-progress', [FrontendsController::class, 'storeBookingProgress'])->name('store.booking.progress');

Route::post('/notifications/mark-all-read', function () {
    auth()->user()->unreadNotifications->markAsRead();
    return back();
})->name('notifications.markAllRead')->middleware('auth');

Route::get('/notifications', function () {
    $notifications = auth()->user()->notifications()->latest()->paginate(20);
    return view('notifications.index', compact('notifications'));
})->name('notifications.index')->middleware('auth');

Route::get('/privacy-policy', [FrontendsController::class, 'privacy'])->name('privacy');

// Dynamic CMS page route with prefix to avoid conflicts
Route::get('/page/{slug}', [\Modules\Page\Http\Controllers\PageController::class, 'show'])->name('page.show');
