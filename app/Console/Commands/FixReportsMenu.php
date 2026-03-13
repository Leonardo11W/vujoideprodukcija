<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\MenuBuilder\Models\MenuBuilder;

class FixReportsMenu extends Command
{
    protected $signature = 'menu:fix-reports';
    protected $description = 'Fix REPORTS menu structure - create static menu and all children';

    public function handle()
    {
        $this->info('Fixing REPORTS menu structure...');

        // Clean up any duplicate REPORTS static menus first
        $duplicateReports = MenuBuilder::where('title', 'sidebar.reports')
            ->where('menu_item_type', 'static')
            ->where('menu_type', 'sidebar')
            ->get();

        if ($duplicateReports->count() > 1) {
            $this->info('Found ' . $duplicateReports->count() . ' duplicate Reports static menus. Cleaning up...');
            // Keep the first one, delete the rest
            $keepId = $duplicateReports->first()->id;
            MenuBuilder::where('title', 'sidebar.reports')
                ->where('menu_item_type', 'static')
                ->where('menu_type', 'sidebar')
                ->where('id', '!=', $keepId)
                ->delete();
            $this->info('✅ Cleaned up duplicate Reports static menus');
        }

        // Find the REPORTS static menu (should be only one now)
        $reportsStatic = MenuBuilder::where('title', 'sidebar.reports')
            ->where('menu_item_type', 'static')
            ->where('menu_type', 'sidebar')
            ->first();

        if (!$reportsStatic) {
            $this->info('Creating REPORTS static menu...');
            $reportsStatic = MenuBuilder::create([
                'menu_type' => 'sidebar',
                'menu_item_type' => 'static',
                'title' => 'sidebar.reports',
                'permission' => ['view_reports'],
                'order' => 17,
                'status' => 1,
            ]);
            $this->info('✅ REPORTS static menu created!');
        } else {
            // Update permissions to use view_reports permission
            $reportsStatic->update(['permission' => ['view_reports']]);
            $this->info('✅ Updated REPORTS static menu to use view_reports permission');
        }

        // Define all report menu items
        $reportMenus = [
            [
                'start_icon' => 'fa-solid fa-file-invoice-dollar',
                'title' => 'sidebar.daily_bookings',
                'route' => 'backend.reports.daily-booking-report',
                'active' => ['app/daily-booking-report'],
                'permission' => ['reports_daily_booking_report'],
                'order' => 18,
            ],
            [
                'start_icon' => 'fa-solid fa-chart-line',
                'title' => 'sidebar.overall_bookings',
                'route' => 'backend.reports.overall-booking-report',
                'active' => ['app/overall-booking-report'],
                'permission' => ['reports_overall_booking_report'],
                'order' => 19,
            ],
            [
                'start_icon' => 'fa-solid fa-chart-bar',
                'title' => 'sidebar.staffs_payouts',
                'route' => 'backend.reports.payout-report',
                'active' => ['app/payout-report'],
                'permission' => ['reports_payout_report'],
                'order' => 20,
            ],
            [
                'start_icon' => 'fa-solid fa-clipboard-user',
                'title' => 'sidebar.staffs_services',
                'route' => 'backend.reports.staff-report',
                'active' => ['app/staff-report'],
                'permission' => ['reports_staff_report'],
                'order' => 21,
            ],
            [
                'start_icon' => 'fa-solid fa-chart-pie',
                'title' => 'sidebar.orders_report',
                'route' => 'backend.reports.order-report',
                'active' => ['app/order-report'],
                'permission' => ['view_product_orders_report'],
                'order' => 22,
            ],
        ];

        // Create or update each report menu item
        foreach ($reportMenus as $menuData) {
            $existing = MenuBuilder::where('route', $menuData['route'])
                ->where('menu_type', 'sidebar')
                ->first();

            if ($existing) {
                // Update existing menu - always update permission to ensure it's correct
                $existing->update(array_merge($menuData, [
                    'menu_type' => 'sidebar',
                    'menu_item_type' => 'route',
                    'parent_id' => $reportsStatic->id,
                    'status' => 1,
                    'is_route' => true,
                ]));
                $this->info("✅ Updated: {$menuData['title']} with permission: " . json_encode($menuData['permission']));
            } else {
                // Create new menu
                MenuBuilder::create(array_merge($menuData, [
                    'menu_type' => 'sidebar',
                    'menu_item_type' => 'route',
                    'parent_id' => $reportsStatic->id,
                    'status' => 1,
                    'is_route' => true,
                ]));
                $this->info("✅ Created: {$menuData['title']}");
            }
        }
        
        // Fix any menu items that have the wrong permission name (reports_employee_report -> reports_staff_report)
        $wrongPermissionMenus = MenuBuilder::where('route', 'backend.reports.staff-report')
            ->where('menu_type', 'sidebar')
            ->get();
        
        foreach ($wrongPermissionMenus as $menu) {
            $permissions = $menu->permission ?? [];
            $updated = false;
            
            if (is_array($permissions)) {
                $newPermissions = [];
                foreach ($permissions as $perm) {
                    if ($perm === 'reports_employee_report') {
                        $newPermissions[] = 'reports_staff_report';
                        $updated = true;
                    } else {
                        $newPermissions[] = $perm;
                    }
                }
                
                if ($updated) {
                    $menu->update(['permission' => $newPermissions]);
                    $this->info("✅ Fixed permission for menu ID {$menu->id}: reports_employee_report -> reports_staff_report");
                }
            }
        }

        // Clear menu cache
        MenuBuilder::flushCache();
        \Cache::forget('menu.builder');
        $this->info('✅ Menu cache cleared');

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('✅ Permission cache cleared');

        $this->info('🎉 REPORTS menu structure fixed!');
        return 0;
    }
}
