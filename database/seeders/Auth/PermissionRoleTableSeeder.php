<?php

namespace Database\Seeders\Auth;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Class PermissionRoleTableSeeder.
 */
class PermissionRoleTableSeeder extends Seeder
{
    /**
     * Run the database seed.
     *
     * @return void
     */
    public function run()
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Schema::disableForeignKeyConstraints();
        $admin = Role::firstOrCreate(['name' => 'admin'], ['title' => 'Admin', 'is_fixed' => true]);
        $manager = Role::firstOrCreate(['name' => 'manager'], ['title' => 'Manager', 'is_fixed' => true]);
        $employee = Role::firstOrCreate(['name' => 'employee'], ['title' => 'Staff', 'is_fixed' => true]);
        $expert = Role::firstOrCreate(['name' => 'expert'], ['title' => 'Expert', 'is_fixed' => true]);
        $user = Role::firstOrCreate(['name' => 'user'], ['title' => 'Customer', 'is_fixed' => true]);
     

        $modules = config('constant.MODULES');

        foreach ($modules as $key => $module) {
            $permissions = ['view', 'add', 'edit', 'delete'];
            $module_name = strtolower(str_replace(' ', '_', $module['module_name']));
            foreach ($permissions as $key => $value) {
                $permission_name = $value . '_' . $module_name;
                Permission::firstOrCreate(['name' => $permission_name, 'is_fixed' => true]);
            }
            if (isset($module['more_permission']) && is_array($module['more_permission'])) {
                foreach ($module['more_permission'] as $key => $value) {
                    $permission_name = $module_name . '_' . $value;
                    Permission::firstOrCreate(['name' => $permission_name, 'is_fixed' => true]);
                }
            }
        }

        // Create standalone permissions not tied to modules
        Permission::firstOrCreate(['name' => 'view_dashboard', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'view_earning', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'view_payout', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'system_settings', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'view_inquiry', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'view_notification', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'edit_notification', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'add_notification', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'delete_notification', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'view_notification_list', 'is_fixed' => true]);

        Permission::firstOrCreate(['name' => 'orders_order', 'guard_name' => 'web', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'view_product_orders', 'guard_name' => 'web', 'is_fixed' => true]);
        Permission::firstOrCreate(['name' => 'view_product_orders_report', 'guard_name' => 'web', 'is_fixed' => true]);

        // Create setting-related permissions
        $settingPermissions = [
            'setting_general',
            'setting_misc',
            'setting_quick_booking',
            'setting_custom_code',
            'setting_customization',
            'setting_mail',
            'setting_notification',
            'setting_intigrations',
            'setting_custom_fields',
            'setting_currency',
            'setting_commission',
            'setting_holiday',
            'setting_bussiness_hours',
            'setting_payment_method',
            'setting_language',
            'setting_menu_builder',
        ];
        
        foreach ($settingPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'is_fixed' => true]);
        }

        // Assign Permissions to Roles
        $admin->givePermissionTo(Permission::get());
        $manager->givePermissionTo([
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
            'view_service_category',
            'view_service_subcategory',
            'view_earning',
            'view_payout',
            'view_branch',
            'view_booking',
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
            // 'edit_location',
            // 'delete_location',
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
        ]);

        // Employee role - VIEW ONLY permissions (no add, edit, delete)
        $employee->givePermissionTo([
            'view_dashboard',
            'view_booking',
            'view_service',
            'view_earning',
            'view_review',
        ]);

        Schema::enableForeignKeyConstraints();
    }
}
