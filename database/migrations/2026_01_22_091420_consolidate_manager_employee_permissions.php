<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration consolidates ALL permissions from PermissionRoleTableSeeder
     * for manager and employee roles. It creates all necessary permissions and
     * assigns them to the correct roles, so old clients only need to run migrate.
     */
    public function up(): void
    {
        // Create all permissions that manager and employee roles need
        $allPermissions = [
            // Dashboard and core permissions
            'view_dashboard',
            'view_earning',

            // Booking permissions
            'view_booking',
            'add_booking',
            'edit_booking',

            // Service permissions
            'view_service',
            'add_service',
            'edit_service',
            'delete_service',
            'service_gallery',

            // Staff permissions
            'view_staff',
            'add_staff',
            'edit_staff',
            'delete_staff',

            // Customer permissions
            'view_customer',

            // Report permissions
            'view_reports',
            'reports_daily_booking_report',
            'reports_overall_booking_report',
            'reports_payout_report',
            'reports_staff_report',

            // Category permissions
            'view_category',
            'view_subcategory',

            // Payout permissions
            'view_payout',

            // Branch permissions
            'view_branch',

            // Table view permission
            'booking_booking_tableview',

            // Tax permissions
            'view_tax',

            // Product permissions
            'view_product',
            'add_product',
            'edit_product',
            'delete_product',
            'product_gallary',
            'product_stock',
            'view_product_brand',
            'view_product_category',
            'view_product_subcategory',
            'view_product_units',
            'view_product_variations',
            'add_product_variations',
            'edit_product_variations',
            'delete_product_variations',
            'status_product_variations',

            // Tag and location permissions
            'view_tag',
            'view_location',
            'add_location',
            'view_logistics',
            'edit_logistics',
            'view_logistic_zone',
            'edit_logistic_zone',

            // Review permissions
            'view_review',

            // System settings
            'system_settings',
            'setting_holiday',
            'setting_bussiness_hours',

            // FAQ permissions
            'view_faq',
            'add_faq',
            'edit_faq',
            'delete_faq',

            // Promotion permissions
            'view_promotion',
            'add_promotion',
            'edit_promotion',
            'delete_promotion',
            'promotion_coupon',

            // Notification permissions
            'view_notification',
            'view_notification_list',

            // Product order permissions
            'view_product_orders',
            'view_product_orders_report',
        ];

        // Create permissions if they don't exist
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName],
                ['name' => $permissionName, 'guard_name' => 'web', 'is_fixed' => true]
            );
        }

        // Get or create roles
        $manager = Role::firstOrCreate(['name' => 'manager'], ['title' => 'Manager', 'is_fixed' => true]);
        $employee = Role::firstOrCreate(['name' => 'employee'], ['title' => 'Staff', 'is_fixed' => true]);
   

        // Manager permissions (full access to most features)
        $managerPermissions = [
            'view_dashboard',
            'view_booking',
            'add_booking',
            'edit_booking',
            'view_service',
            'add_service',
            'edit_service',
            'delete_service',
            'service_gallery',
            'view_staff',
            'add_staff',
            'edit_staff',
            'delete_staff',
            'view_customer',
            'view_reports',
            'reports_daily_booking_report',
            'reports_overall_booking_report',
            'reports_payout_report',
            'reports_staff_report',
            'view_category',
            'view_subcategory',
            'view_earning',
            'view_payout',
            'view_branch',
            'view_booking', // duplicate but included as in seeder
            'booking_booking_tableview',
            'view_tax',
            'view_product',
            'add_product',
            'edit_product',
            'delete_product',
            'product_gallary',
            'product_stock',
            'view_product_brand',
            'view_product_category',
            'view_product_subcategory',
            'view_product_units',
            'view_product_variations',
            'add_product_variations',
            'edit_product_variations',
            'delete_product_variations',
            'view_tag',
            'view_location',
            'add_location',
            'view_logistics',
            'edit_logistics',
            'view_logistic_zone',
            'edit_logistic_zone',
            'view_review',
            'system_settings',
            'setting_holiday',
            'setting_bussiness_hours',
            'view_faq',
            'add_faq',
            'edit_faq',
            'delete_faq',
            'view_promotion',
            'add_promotion',
            'edit_promotion',
            'delete_promotion',
            'promotion_coupon',
            'view_notification',
            'view_notification_list',
            'view_product_orders',
            'view_product_orders_report',
        ];

        // Employee permissions (view-only access)
        $employeePermissions = [
            'view_dashboard',
            'view_booking',
            'view_service',
            'booking_booking_tableview', // Calendar view
            'view_earning',
            'view_review',
        ];

        // Assign permissions to manager role
        if ($manager) {
            foreach ($managerPermissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && !$manager->hasPermissionTo($permission)) {
                    $manager->givePermissionTo($permission);
                }
            }
        }

        // Assign permissions to employee role
        if ($employee) {
            foreach ($employeePermissions as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && !$employee->hasPermissionTo($permission)) {
                    $employee->givePermissionTo($permission);
                }
            }
        }

        // Ensure admin has all permissions (safety measure)
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo(Permission::all());
        }
    }

    /**
     * Reverse the migrations.
     *
     * Removes ALL the consolidated permissions from manager and employee roles.
     * Note: Permissions themselves are not deleted as they may be used elsewhere.
     */
    public function down(): void
    {
        // Remove permissions from roles
        $manager = Role::where('name', 'manager')->first();
        $employee = Role::where('name', 'employee')->first();

        $allPermissionsToRemove = [
            // Dashboard and core permissions
            'view_dashboard',
            'view_earning',

            // Booking permissions
            'view_booking',
            'add_booking',
            'edit_booking',

            // Service permissions
            'view_service',
            'add_service',
            'edit_service',
            'delete_service',
            'service_gallery',

            // Staff permissions
            'view_staff',
            'add_staff',
            'edit_staff',
            'delete_staff',

            // Customer permissions
            'view_customer',

            // Report permissions
            'view_reports',
            'reports_daily_booking_report',
            'reports_overall_booking_report',
            'reports_payout_report',
            'reports_staff_report',

            // Category permissions
            'view_category',
            'view_subcategory',

            // Payout permissions
            'view_payout',

            // Branch permissions
            'view_branch',

            // Table view permission
            'booking_booking_tableview',

            // Tax permissions
            'view_tax',

            // Product permissions
            'view_product',
            'add_product',
            'edit_product',
            'delete_product',
            'product_gallary',
            'product_stock',
            'view_product_brand',
            'view_product_category',
            'view_product_subcategory',
            'view_product_units',
            'view_product_variations',
            'add_product_variations',
            'edit_product_variations',
            'delete_product_variations',
            'status_product_variations',

            // Tag and location permissions
            'view_tag',
            'view_location',
            'add_location',
            'view_logistics',
            'edit_logistics',
            'view_logistic_zone',
            'edit_logistic_zone',

            // Review permissions
            'view_review',

            // System settings
            'system_settings',
            'setting_holiday',
            'setting_bussiness_hours',

            // FAQ permissions
            'view_faq',
            'add_faq',
            'edit_faq',
            'delete_faq',

            // Promotion permissions
            'view_promotion',
            'add_promotion',
            'edit_promotion',
            'delete_promotion',
            'promotion_coupon',

            // Notification permissions
            'view_notification',
            'view_notification_list',

            // Product order permissions
            'view_product_orders',
            'view_product_orders_report',
        ];

        if ($manager) {
            foreach ($allPermissionsToRemove as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && $manager->hasPermissionTo($permission)) {
                    $manager->revokePermissionTo($permission);
                }
            }
        }

        if ($employee) {
            $employeePermissionsToRemove = [
                'view_dashboard',
                'view_booking',
                'view_service',
                'booking_booking_tableview',
                'view_earning',
                'view_review',
            ];
            foreach ($employeePermissionsToRemove as $permissionName) {
                $permission = Permission::where('name', $permissionName)->first();
                if ($permission && $employee->hasPermissionTo($permission)) {
                    $employee->revokePermissionTo($permission);
                }
            }
        }

        // Note: We don't delete the permissions themselves as they may be used elsewhere
    }
};
