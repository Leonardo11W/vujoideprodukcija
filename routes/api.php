<?php

use App\Http\Controllers\Auth\API\AuthController;
use App\Http\Controllers\Backend\API\AddressController;
use App\Http\Controllers\Backend\API\BranchController;
use App\Http\Controllers\Backend\API\DashboardController;
use App\Http\Controllers\Backend\API\NotificationsController;
use App\Http\Controllers\Backend\API\SettingController;
use App\Http\Controllers\Backend\API\UserApiController;
use App\Models\Expert;
use App\Http\Controllers\Backend\API\ReportsController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum'], function () {

    // Manager Reports
    Route::get('staff-payout', [\App\Http\Controllers\Backend\API\ManagerReportController::class, 'staffPayout']);
    Route::post('payout-request', [\App\Http\Controllers\Backend\API\ManagerReportController::class, 'payoutRequest']);
    Route::get('staff-service', [\App\Http\Controllers\Backend\API\ManagerReportController::class, 'staffService']);
    Route::get('report', [\App\Http\Controllers\Backend\API\ManagerReportController::class, 'dailyBookingReport']);
    Route::get('manager-earning', [\App\Http\Controllers\Backend\API\ManagerReportController::class, 'managerEarning']);
    Route::post('manager-payout', [\App\Http\Controllers\Backend\API\ManagerReportController::class, 'managerPayout']);
});

Route::get('branch-list', [BranchController::class, 'branchList']);
Route::get('user-detail', [AuthController::class, 'userDetails']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('social-login', 'socialLogin');
    Route::post('forgot-password', 'forgotPassword');
    Route::get('logout', 'logout');
});

Route::get('dashboard-detail', [DashboardController::class, 'dashboardDetail']);
Route::get('manager-dashboard-detail', [DashboardController::class, 'managerDashboardDetail']);
Route::get('booking-dashboard', [DashboardController::class, 'bookingDashboard']);
Route::get('branch-configuration', [BranchController::class, 'branchConfig']);
Route::get('branch-detail', [BranchController::class, 'branchDetails']);
Route::get('branch-service', [BranchController::class, 'branchService']);
Route::get('branch-review', [BranchController::class, 'branchReviews']);
Route::get('branch-employee', [BranchController::class, 'branchEmployee']);
Route::get('branch-gallery', [BranchController::class, 'branchGallery']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('branch/assign/{id}', [BranchController::class, 'assign_update']);
    Route::apiResource('branch', BranchController::class);
    Route::apiResource('user', UserApiController::class);
    Route::apiResource('setting', SettingController::class);
    Route::apiResource('notification', NotificationsController::class);
    Route::get('notification-list', [NotificationsController::class, 'notificationList']);
    Route::get('manager-notification-list', [NotificationsController::class, 'managerNotificationList']);
    Route::get('manager-reviews-list', [BranchController::class, 'managerReviewsList']);
    Route::post('mark-notification-read', [NotificationsController::class, 'markNotificationRead']);
    Route::get('gallery-list', [DashboardController::class, 'globalGallery']);
    Route::get('search-list', [DashboardController::class, 'searchList']);
    Route::post('update-profile', [AuthController::class, 'updateProfile']);
    Route::get('staff-report-list', [ReportsController::class, 'staffServiceReportList']);
    Route::get('manager-report-dashboard', [ReportsController::class, 'managerReportDashboard']);
    Route::get('daily-booking-report-list', [ReportsController::class, 'dailyBookingReportList']);
    Route::get('order-report-list', [ReportsController::class, 'orderReportList']);
    Route::get('staff-payout-list', [ReportsController::class, 'staffPayoutList']);
    Route::post('save-payout', [ReportsController::class, 'savePayout']);

    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('delete-account', [AuthController::class, 'deleteAccount']);

    Route::post('add-address', [AddressController::class, 'store']);
    Route::get('address-list', [AddressController::class, 'AddressList']);
    Route::get('remove-address', [AddressController::class, 'RemoveAddress']);
    Route::post('edit-address', [AddressController::class, 'EditAddress']);

    Route::get('manager-app-configurations', [SettingController::class, 'managerAppConfigurations']);
    Route::post('verify-slot', [BranchController::class, 'verifySlot']);
});
Route::post('app-configuration', [SettingController::class, 'appConfiguraton']);

Route::get('/experts', function (Request $request) {
    $minRating = $request->input('min_rating', 0);
    $experts =
        class_exists('App\\Models\\Expert')
        ? Expert::where('rating', '>=', $minRating)->get()
        : [];
    return response()->json($experts);
});
