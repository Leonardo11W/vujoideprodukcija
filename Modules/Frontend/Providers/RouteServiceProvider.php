<?php

namespace Modules\Frontend\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'frontend');

        Blade::component('frontend::components.partials.horizontal_nav', 'horizontal_nav');
        Blade::component('frontend::components.partials.logo', 'logo');
        Blade::component('frontend::components.section.banner', 'banner');
        Blade::component('frontend::components.section.quick_booking', 'quick_booking');
        Blade::component('frontend::components.section.refer_section', 'refer_section');
        Blade::component('frontend::components.section.category_section', 'category_section');
        Blade::component('frontend::components.section.branch_section', 'branch_section');
        Blade::component('frontend::components.section.package_section', 'package_section');
        Blade::component('frontend::components.section.membership_section', 'membership_section');
        Blade::component('frontend::components.section.expert_section', 'expert_section');
        Blade::component('frontend::components.section.faq_section', 'faq_section');
        Blade::component('frontend::components.section.testimonial_section', 'testimonial_section');
        Blade::component('frontend::components.section.blog_section', 'blog_section');
        Blade::component('frontend::components.section.product_section', 'product_section');
        Blade::component('frontend::components.card.branch_card', 'branch_card');
        Blade::component('frontend::components.section.breadcrumb', 'breadcrumb');
        Blade::component('frontend::components.section.booking_section', 'booking_section');
        Blade::component('frontend::components.card.booking_card', 'booking_card');
        Blade::component('frontend::components.card.blog_card', 'blog_card');
        Blade::component('frontend::components.card.category_card', 'category_card');
        Blade::component('frontend::components.card.wishlist_card', 'wishlist_card');
        Blade::component('frontend::components.card.expert_card', 'expert_card');
        Blade::component('frontend::components.card.choose_expert_card', 'choose_expert_card');
        Blade::component('frontend::components.card.product_card', 'product_card');
        Blade::component('frontend::components.card.testimonial_card', 'testimonial_card');
        Blade::component('frontend::components.section.bookingdetails_section', 'bookingdetails_section');
        Blade::component('frontend::components.card.service_card', 'service_card');
        Blade::component('frontend::components.card.search_service_card', 'search_service_card');
        Blade::component('frontend::components.section.service_section', 'service_section');
        Blade::component('frontend::components.section.branchdetails_section', 'branchdetails_section');
        Blade::component('frontend::components.section.branchreview_section', 'branchreview_section');
        Blade::component('frontend::components.card.branchgallery_card', 'branchgallery_card');
        Blade::component('frontend::components.section.branchgallery_section', 'branchgallery_section');
        Blade::component('frontend::components.section.expertdetails_section', 'expertdetails_section');
        Blade::component('frontend::components.section.expertreview_section', 'expertreview_section');
        Blade::component('frontend::components.card.subcategory_card', 'subcategory_card');
        Blade::component('frontend::components.section.subcategory_section', 'subcategory_section');
        Blade::component('frontend::components.section.usepoint_section', 'usepoint_section');
        Blade::component('frontend::components.section.removepoint_section', 'removepoint_section');
        Blade::component('frontend::components.section.profile_section', 'profile_section');
        Blade::component('frontend::components.section.balance_section', 'balance_section');
        Blade::component('frontend::components.section.history_section', 'history_section');
        Blade::component('frontend::components.section.banklist_section', 'banklist_section');
        Blade::component('frontend::components.section.referral_section', 'referral_section');
        Blade::component('frontend::components.section.howitwork_section', 'howitwork_section');
        Blade::component('frontend::components.section.loyaltypoint_section', 'loyaltypoint_section');
        Blade::component('frontend::components.section.mymembership_section', 'mymembership_section');
        Blade::component('frontend::components.section.changepassword_section', 'changepassword_section');
        Blade::component('frontend::components.section.mypackage_section', 'mypackage_section');
        Blade::component('frontend::components.section.cart_section', 'cart_section');
        Blade::component('frontend::components.section.ordersummary_section', 'ordersummary_section');
        Blade::component('frontend::components.card.package_card', 'package_card');
        Blade::component('frontend::components.section.payment_section', 'payment_section');
        Blade::component('frontend::components.section.myorder_section', 'myorder_section');
        Blade::component('frontend::components.card.myorder_card', 'myorder_card');
        Blade::component('frontend::components.section.address_section', 'address_section');
        Blade::component('frontend::components.section.mywishlist_section', 'mywishlist_section');
        Blade::component('frontend::components.section.shop_section', 'shop_section');
        Blade::component('frontend::components.section.productdetails_section', 'productdetails_section');
        Blade::component('frontend::components.section.saleproduct_section', 'saleproduct_section');
        Blade::component('frontend::components.section.groupproduct_section', 'groupproduct_section');
        Blade::component('frontend::components.section.checkout_section', 'checkout_section');
        Blade::component('frontend::components.section.location_section', 'location_section');
        Blade::component('frontend::components.section.leave_section', 'leave_section');
        Blade::component('frontend::components.section.blogdetails_section', 'blogdetails_section');
        Blade::component('frontend::components.section.about_section', 'about_section');
        Blade::component('frontend::components.section.ratenow_section', 'ratenow_section');
        Blade::component('frontend::components.card.membership_card', 'membership_card');
        Blade::component('frontend::components.section.mybooking_section', 'mybooking_section');
        Blade::component('frontend::components.card.mybooking_card', 'mybooking_card');
        Blade::component('frontend::components.card.category_card', 'category_card');
        Blade::component('frontend::components.section.contact_banner_section', 'contact_banner_section');
    }


    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('Modules/Frontend/routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->group(base_path('Modules/Frontend/routes/api.php'));
    }
}
