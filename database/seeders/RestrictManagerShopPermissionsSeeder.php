<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RestrictManagerShopPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionsToRevoke = [
            // Brands
            'add_product_brand',
            'edit_product_brand',
            'delete_product_brand',
            // Categories
            'add_product_category',
            'edit_product_category',
            'delete_product_category',
            // Subcategories
            'add_product_subcategory',
            'edit_product_subcategory',
            'delete_product_subcategory',
            // Units
            'add_product_units',
            'edit_product_units',
            'delete_product_units',
            // Tags
            'add_tag',
            'edit_tag',
            'delete_tag',
        ];

        $manager = Role::where('name', 'manager')->first();
        if ($manager) {
            foreach ($permissionsToRevoke as $permissionName) {
                if ($manager->hasPermissionTo($permissionName)) {
                    $manager->revokePermissionTo($permissionName);
                }
            }
        }
    }
}
