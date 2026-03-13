<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds notification template and app banner permissions
     * and assigns them ONLY to admin role.
     */
    public function up(): void
    {
        // Clear permission cache
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Notification Template
            'edit_notification_template',

            // App Banner
            'view_app_banner',
            'add_app_banner',
            'edit_app_banner',
            'delete_app_banner',
        ];

        // Create permissions if not exists
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web'],
                ['is_fixed' => true]
            );
        }

        // Assign ONLY to admin
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }


    public function down(): void
    {
        $permissions = [
            'edit_notification_template',
            'view_app_banner',
            'add_app_banner',
            'edit_app_banner',
            'delete_app_banner',
        ];

        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            foreach ($permissions as $permission) {
                if ($admin->hasPermissionTo($permission)) {
                    $admin->revokePermissionTo($permission);
                }
            }
        }
    }
    // Note: We don't delete the permissions themselves as they may be used elsewhere

};
