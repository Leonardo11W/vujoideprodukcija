<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Get the first accessible route for the authenticated user
     *
     * @return string|null
     */
    public static function getFirstAccessibleRoute()
    {
        if (!Auth::check()) {
            \Log::info('PermissionHelper: User not authenticated');
            return null;
        }

        $user = Auth::user();

        if (!$user) {
            \Log::info('PermissionHelper: Auth::user() returned null');
            return null;
        }

        // Ensure permissions are loaded
        $user->load('roles.permissions');

        // Define routes in order of preference with their required permissions
        // Based on the actual routes and permissions from the project's menu configuration
        // NOTE: Skipping 'backend.home' since this method is used for finding ALTERNATIVE routes
        // when dashboard access is denied. Dashboard route is handled separately in the controller.
        $routes = [
            'backend.bookings.index' => ['view_booking'], // Calendar Bookings
            'backend.branch.index' => ['view_branch'],
            'backend.bookings.datatable_view' => ['view_booking'], // Bookings table view
            'backend.services.index' => ['view_service'],
            'backend.categories.index' => ['view_service_category'],
            'backend.categories.index_nested' => ['view_service_subcategory'],
            'backend.employees.index' => ['view_staff'],
            'backend.customers.index' => ['view_customer'],
            'backend.employees.review' => ['view_review'],
            'backend.tax.index' => ['view_tax'],
            'backend.earnings.index' => ['view_earning'],
            'backend.promotions.index' => ['promotion_coupon'],
            'backend.reports.daily-booking-report' => ['reports_daily_booking_report'],
            'backend.reports.overall-booking-report' => ['reports_overall_booking_report'],
            'backend.reports.payout-report' => ['reports_payout_report'],
            'backend.reports.staff-report' => ['reports_staff_report'],
            'backend.reports.order-report' => ['view_product_orders_report'],
            'backend.settings' => ['system_settings'],
            'frontendsetting.index' => ['setting_frontend'],
            'backend.inquiries.index' => ['view_inquiry'],
            'backend.pages.index' => ['view_page'],
            'backend.notifications.index' => ['view_notification_list'],
            'backend.notification-templates.index' => ['view_notification_template'],
            'backend.app-banners.index' => ['view_app_banner'],
            'backend.permission-role.list' => ['view_role_permissions'],
            'backend.city.index' => ['view_location'],
            'backend.state.index' => ['view_location'],
            'backend.country.index' => ['view_location'],
        ];

        // Check each route until we find one the user can access
        foreach ($routes as $routeName => $permissions) {
            if ($user->can($permissions)) {
                return $routeName;
            }
        }

        // If no specific route is accessible, return null
        // This will fall back to a default behavior or error page
        return null;
    }

    /**
     * Get the URL for the first accessible route
     *
     * @return string|null
     */
    public static function getFirstAccessibleUrl()
    {
        $route = self::getFirstAccessibleRoute();

        if ($route) {
            return route($route);
        }

        return null;
    }

    /**
     * Get the first accessible route INCLUDING dashboard
     * Use this when you want to find ANY accessible route, not just alternatives to dashboard
     *
     * @return string|null
     */
    public static function getFirstAccessibleRouteIncludingDashboard()
    {
        if (!Auth::check()) {
            \Log::info('PermissionHelper: User not authenticated');
            return null;
        }

        $user = Auth::user();

        if (!$user) {
            \Log::info('PermissionHelper: Auth::user() returned null');
            return null;
        }

        // Ensure permissions are loaded
        $user->load('roles.permissions');

        // Define ALL routes in order of preference with their required permissions
        $routes = [
            'backend.home' => ['view_dashboard'],
            'backend.bookings.index' => ['view_booking'], // Calendar Bookings
            'backend.branch.index' => ['view_branch'],
            'backend.bookings.datatable_view' => ['view_booking'], // Bookings table view
            'backend.services.index' => ['view_service'],
            'backend.categories.index' => ['view_service_category'],
            'backend.categories.index_nested' => ['view_service_subcategory'],
            'backend.employees.index' => ['view_staff'],
            'backend.customers.index' => ['view_customer'],
            'backend.employees.review' => ['view_review'],
            'backend.tax.index' => ['view_tax'],
            'backend.earnings.index' => ['view_earning'],
            'backend.promotions.index' => ['promotion_coupon'],
            'backend.reports.daily-booking-report' => ['reports_daily_booking_report'],
            'backend.reports.overall-booking-report' => ['reports_overall_booking_report'],
            'backend.reports.payout-report' => ['reports_payout_report'],
            'backend.reports.staff-report' => ['reports_staff_report'],
            'backend.reports.order-report' => ['view_product_orders_report'],
            'backend.settings' => ['system_settings'],
            'frontendsetting.index' => ['setting_frontend'],
            'backend.inquiries.index' => ['view_inquiry'],
            'backend.pages.index' => ['view_page'],
            'backend.notifications.index' => ['view_notification_list'],
            'backend.notification-templates.index' => ['view_notification_template'],
            'backend.app-banners.index' => ['view_app_banner'],
            'backend.permission-role.list' => ['view_role_permissions'],
            'backend.city.index' => ['view_location'],
            'backend.state.index' => ['view_location'],
            'backend.country.index' => ['view_location'],
        ];

        // Check each route until we find one the user can access
        foreach ($routes as $routeName => $permissions) {
            if ($user->can($permissions)) {
                return $routeName;
            }
        }

        // If no specific route is accessible, return null
        return null;
    }

    /**
     * Check if user has any accessible routes
     *
     * @return bool
     */
    public static function hasAccessibleRoutes()
    {
        return self::getFirstAccessibleRoute() !== null;
    }
}
