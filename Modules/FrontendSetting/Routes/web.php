<?php

use Illuminate\Support\Facades\Route;
use Modules\FrontendSetting\Http\Controllers\FrontendSettingController;
use Modules\FrontendSetting\Http\Controllers\WhyChooseSettingController;
use Modules\FrontendSetting\Http\Controllers\VideoSectionController;
use Modules\Category\Http\Controllers\Backend\API\CategoryController;


Route::group([
    'prefix' => 'app',
    'middleware' => ['web', 'auth']
], function () {

    // === Frontend Settings Pages ===
    Route::get('frontend-setting/{page?}', [FrontendSettingController::class, 'frontendSettings'])
        ->name('frontendsetting.index');

    Route::prefix('frontend')->group(function () {
        Route::get('landing-page', [FrontendSettingController::class, 'landingPageLayout'])
            ->name('frontendsetting.landingpage');

        Route::get('footer-page', [FrontendSettingController::class, 'footerPage'])
            ->name('frontendsetting.footerpage');
    });

    // === AJAX Endpoints (not duplicated with api.php now) ===
    Route::prefix('api')->group(function () {
        Route::post('landing-layout-page', [FrontendSettingController::class, 'landingLayoutPage'])
            ->name('landing_layout_page');

        Route::post('landing-page-settings-updates', [FrontendSettingController::class, 'landingpageSettingsUpdates'])
            ->name('landing_page_settings_updates');

        Route::match(['get', 'post'], 'layout-frontend-page', [FrontendSettingController::class, 'layoutPage'])
            ->name('layout_frontend_page');

        Route::post('footer-page-settings', [FrontendSettingController::class, 'footerpagesettings'])
            ->name('footer_page_settings');
    });

    Route::post('heading-page-settings', [FrontendSettingController::class, 'updateHeadingSettings'])
        ->name('heading_page_settings');

    // === Other AJAX helpers ===
    Route::get('/ajax/branches', [FrontendSettingController::class, 'getBranches'])->name('ajax.branches');
    Route::post('/fetch-category-names', [FrontendSettingController::class, 'fetchCategoryNames'])->name('fetch.names');
    Route::post('/get-packages', [FrontendSettingController::class, 'getPackages'])->name('get.packages');
    Route::post('/get-products', [FrontendSettingController::class, 'getProducts'])->name('get.products');

    Route::get('/admin/why-choose', [WhyChooseSettingController::class, 'show'])->name('why_choose_setting.show');
    Route::post('/admin/why-choose', [WhyChooseSettingController::class, 'store'])->name('why_choose_setting.store');
    Route::delete('/admin/why-choose-feature/{id}', [WhyChooseSettingController::class, 'destroy'])->name('why_choose_feature.delete');
    Route::post('video-section/store', [VideoSectionController::class, 'store'])->name('video_section.store');

});

// In RouteServiceProvider or routes file
Route::prefix('app/api')->middleware('api')->group(function () {
    Route::match(['get','post'], 'get-landing-layout-page-config', [FrontendSettingController::class, 'getLandingLayoutPageConfig'])
         ->name('getLandingLayoutPageConfig');
    Route::post('save-landing-layout-page-config', [FrontendSettingController::class, 'saveLandingLayoutPageConfig'])
         ->name('saveLandingLayoutPageConfig');
});
Route::get('service-list', [CategoryController::class, 'serviceSectionList'])->name('service-section-list');

