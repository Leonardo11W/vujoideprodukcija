<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\MenuBuilder\Models\MenuBuilder;

class FixProductMenu extends Command
{
    protected $signature = 'menu:fix-product';
    protected $description = 'Fix Product menu structure - create parent menu and all children including categories and subcategories';

    public function handle()
    {
        $this->info('Fixing Product menu structure...');

        // Find or create SHOP static menu item
        $shopStaticMenu = MenuBuilder::where('title', 'sidebar.shop')
            ->where('menu_item_type', 'static')
            ->where('menu_type', 'sidebar')
            ->first();

        if (!$shopStaticMenu) {
            $this->info('Creating SHOP static menu...');
            $shopStaticMenu = MenuBuilder::create([
                'menu_type' => 'sidebar',
                'menu_item_type' => 'static',
                'title' => 'sidebar.shop',
                'permission' => ['view_product', 'view_product_variations', 'view_product_orders', 'view_product_orders_report', 'view_product_brand', 'view_product_category', 'view_product_subcategory', 'view_product_units', 'view_tag'],
                'order' => 8,
                'status' => 1,
            ]);
            $this->info('✅ SHOP static menu created!');
        }

        // Find or create Product parent menu
        $productParent = MenuBuilder::where('route', 'backend.products.index')
            ->where('menu_item_type', 'parent')
            ->where('menu_type', 'sidebar')
            ->first();

        if (!$productParent) {
            $this->info('Creating Product parent menu...');
            $productParent = MenuBuilder::create([
                'menu_type' => 'sidebar',
                'menu_item_type' => 'parent',
                'start_icon' => 'fa-solid fa-store',
                'title' => 'sidebar.product',
                'route' => 'backend.products.index',
                'permission' => ['view_product', 'view_product_brand', 'view_product_category', 'view_product_subcategory', 'view_product_units', 'view_tag'],
                'order' => 8,
                'status' => 1,
                'is_route' => true,
            ]);
            $this->info('✅ Product parent menu created!');
        }

        // Define all product menu items
        $productMenus = [
            [
                'title' => 'sidebar.all_product',
                'route' => 'backend.products.index',
                'active' => ['app/products'],
                'permission' => ['view_product'],
                'order' => 0,
            ],
            [
                'title' => 'sidebar.brand',
                'route' => 'backend.brands.index',
                'active' => ['app/brands'],
                'permission' => ['view_product_brand'],
                'order' => 1,
            ],
            [
                'title' => 'sidebar.categories',
                'route' => 'backend.products-categories.index',
                'active' => ['app/products-categories'],
                'permission' => ['view_product_category'],
                'order' => 2,
            ],
            [
                'title' => 'sidebar.sub_categories',
                'route' => 'backend.products-categories.index_nested',
                'active' => ['app/products-sub-categories'],
                'permission' => ['view_product_subcategory'],
                'order' => 3,
            ],
            [
                'title' => 'sidebar.units',
                'route' => 'backend.units.index',
                'active' => ['app/units'],
                'permission' => ['view_product_units'],
                'order' => 4,
            ],
            [
                'title' => 'sidebar.tag',
                'route' => 'backend.tags.index',
                'active' => ['app/tags'],
                'permission' => ['view_tag'],
                'order' => 5,
            ],
        ];

        // Create or update each product menu item
        foreach ($productMenus as $menuData) {
            $existing = MenuBuilder::where('route', $menuData['route'])
                ->where('menu_type', 'sidebar')
                ->first();

            if ($existing) {
                // Update existing menu
                $existing->update(array_merge($menuData, [
                    'menu_type' => 'sidebar',
                    'menu_item_type' => 'route',
                    'parent_id' => $productParent->id,
                    'status' => 1,
                    'is_route' => true,
                ]));
                $this->info("✅ Updated: {$menuData['title']}");
            } else {
                // Create new menu
                MenuBuilder::create(array_merge($menuData, [
                    'menu_type' => 'sidebar',
                    'menu_item_type' => 'route',
                    'parent_id' => $productParent->id,
                    'status' => 1,
                    'is_route' => true,
                ]));
                $this->info("✅ Created: {$menuData['title']}");
            }
        }

        // Clear menu cache
        MenuBuilder::flushCache();
        \Cache::forget('menu.builder');
        $this->info('✅ Menu cache cleared');

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->info('✅ Permission cache cleared');

        $this->info('🎉 Product menu structure fixed!');
        return 0;
    }
}
