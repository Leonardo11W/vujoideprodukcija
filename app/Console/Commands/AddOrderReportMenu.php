<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\MenuBuilder\Models\MenuBuilder;

class AddOrderReportMenu extends Command
{
    protected $signature = 'menu:add-order-report';
    protected $description = 'Add Order Reports menu item to the sidebar menu';

    public function handle()
    {
        $this->info('Adding Order Reports menu item...');

        // Find the REPORTS static menu item (check both translated and non-translated)
        $reportsStaticMenu = MenuBuilder::where(function($query) {
                $query->where('title', 'sidebar.reports')
                      ->orWhere('title', 'like', '%reports%');
            })
            ->where('menu_item_type', 'static')
            ->where('menu_type', 'sidebar')
            ->first();

        if (!$reportsStaticMenu) {
            $this->warn('REPORTS static menu item not found. Creating Order Reports menu without parent update.');
        }

        // Check if Order Reports menu already exists
        $existingOrderReport = MenuBuilder::where('route', 'backend.reports.order-report')
            ->where('menu_type', 'sidebar')
            ->first();

        if ($existingOrderReport) {
            $this->info('Order Reports menu already exists. Updating...');
            
            // Update existing menu
            $existingOrderReport->update([
                'start_icon' => 'fa-solid fa-chart-pie',
                'title' => 'sidebar.orders_report',
                'route' => 'backend.reports.order-report',
                'active' => ['app/order-report'],
                'permission' => ['view_product_orders_report'],
                'order' => 22,
                'status' => 1,
            ]);
            
            $this->info('✅ Order Reports menu updated!');
        } else {
            // Create new menu item
            $orderReportMenu = MenuBuilder::create([
                'menu_type' => 'sidebar',
                'menu_item_type' => 'route',
                'start_icon' => 'fa-solid fa-chart-pie',
                'title' => 'sidebar.orders_report',
                'route' => 'backend.reports.order-report',
                'active' => ['app/order-report'],
                'permission' => ['view_product_orders_report'],
                'order' => 22,
                'status' => 1,
                'is_route' => true,
            ]);
            
            $this->info('✅ Order Reports menu created!');
        }

        // Update REPORTS static menu permissions to include view_product_orders_report
        if ($reportsStaticMenu) {
            $currentPermissions = $reportsStaticMenu->permission ?? [];
            if (!in_array('view_product_orders_report', $currentPermissions)) {
                $currentPermissions[] = 'view_product_orders_report';
                $reportsStaticMenu->update(['permission' => $currentPermissions]);
                $this->info('✅ Updated REPORTS static menu permissions');
            }
        }

        // Clear menu cache
        MenuBuilder::flushCache();
        \Cache::forget('menu.builder');
        $this->info('✅ Menu cache cleared');

        // Ensure manager role has view_product_orders_report permission
        $managerRole = \Spatie\Permission\Models\Role::where('name', 'manager')->first();
        if ($managerRole) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'view_product_orders_report', 'guard_name' => 'web']
            );
            
            if (!$managerRole->hasPermissionTo('view_product_orders_report')) {
                $managerRole->givePermissionTo($permission);
                $this->info('✅ Added view_product_orders_report permission to manager role');
            } else {
                $this->info('✅ Manager role already has view_product_orders_report permission');
            }
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('✅ Permission cache cleared');

        // Clear all user permission caches
        $users = \App\Models\User::role('manager')->get();
        foreach ($users as $user) {
            $user->forgetCachedPermissions();
        }
        $this->info('✅ Cleared permission cache for all manager users');

        $this->info('🎉 Order Reports menu added successfully!');
        return 0;
    }
}
