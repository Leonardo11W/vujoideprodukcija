<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermission extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        // Page Title
        $this->module_title = 'Permission';

        // module name
        $this->module_name = 'permission';
    }

    public function index()
    {
        // Ensure all common setting permissions exist
        $this->ensureSettingPermissionsExist();
        
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $roles = Role::get();
        $modules = config('constant.MODULES');
        $permissions = Permission::get();
        $module_action = 'List';

        return view('permission-role.permissions', compact('roles', 'permissions', 'module_title', 'module_name', 'module_action', 'modules'));
    }
    
    /**
     * Ensure all common setting permissions exist
     */
    private function ensureSettingPermissionsExist()
    {
        $commonSettingPermissions = [
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
        
        foreach ($commonSettingPermissions as $permName) {
            try {
                Permission::firstOrCreate(
                    ['name' => $permName, 'guard_name' => 'web'],
                    ['is_fixed' => false]
                );
            } catch (\Exception $e) {
                \Log::warning('Failed to ensure setting permission exists', [
                    'permission' => $permName,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function store(Request $request, Role $role_id)
    {
        if (env('IS_DEMO')) {
            return redirect()->back()->with('error', __('messages.permission_denied'));
        }

        \Log::info('🔐 Starting permission update for role', [
            'role_id' => $role_id->id,
            'role_name' => $role_id->name,
            'requested_permissions' => $request->permission ?? []
        ]);

        // Clear permission cache BEFORE making changes
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Log::info('✅ Cleared global permission cache');

        // Get current permissions before revoking
        $currentPermissions = $role_id->permissions->pluck('name')->toArray();
        \Log::info('📋 Current role permissions before update', [
            'role' => $role_id->name,
            'permissions' => $currentPermissions
        ]);

        $permissions = Permission::get()->pluck('name')->toArray();
        $role_id->revokePermissionTo($permissions);
        \Log::info('🗑️ Revoked all permissions from role', ['role' => $role_id->name]);

        $newPermissions = [];
        $permissionIds = [];
        if (isset($request->permission) && is_array($request->permission)) {
            foreach ($request->permission as $permission => $roles) {
                try {
                    $pr = Permission::firstOrCreate(
                        ['name' => $permission, 'guard_name' => 'web'],
                        ['is_fixed' => false]
                    );
                    $permissionIds[] = $pr->id;
                    $newPermissions[] = $permission;
                } catch (\Exception $e) {
                    \Log::warning('Failed to create permission', [
                        'permission' => $permission,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Also ensure common setting permissions exist
        $commonSettingPermissions = [
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
        
        foreach ($commonSettingPermissions as $permName) {
            try {
                Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            } catch (\Exception $e) {
                \Log::warning('Failed to ensure setting permission exists', [
                    'permission' => $permName,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Use sync() instead of syncWithoutDetaching to ensure old permissions are removed
        if (!empty($permissionIds)) {
            $role_id->permissions()->sync($permissionIds);
            \Log::info('✅ Synced permissions to role', [
                'role' => $role_id->name,
                'permission_ids' => $permissionIds,
                'permission_names' => $newPermissions
            ]);
        } else {
            // If no permissions selected, ensure all are removed
            $role_id->permissions()->sync([]);
            \Log::info('✅ Removed all permissions from role (none selected)', ['role' => $role_id->name]);
        }

        \Log::info('➕ Added new permissions to role', [
            'role' => $role_id->name,
            'permissions' => $newPermissions
        ]);

        // Note: Removed auto-add logic for employee Reports permissions
        // This was causing permissions to be re-enabled even when users tried to disable them.
        // Permissions are now only set based on what's checked in the form.

        // Refresh role to get updated permissions
        $role_id->refresh();
        $finalPermissions = $role_id->permissions->pluck('name')->toArray();
        \Log::info('📊 Final role permissions after update', [
            'role' => $role_id->name,
            'permissions' => $finalPermissions,
            'permission_count' => count($finalPermissions)
        ]);

        // Clear permission cache again AFTER making changes
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        \Log::info('✅ Cleared global permission cache again');
        
        // Clear all users with this role from permission cache
        $usersWithRole = \App\Models\User::role($role_id->name)->get();
        \Log::info('👥 Found users with role', [
            'role' => $role_id->name,
            'user_count' => $usersWithRole->count(),
            'user_ids' => $usersWithRole->pluck('id')->toArray()
        ]);

        foreach ($usersWithRole as $user) {
            $user->forgetCachedPermissions();
            \Log::info('🔄 Cleared permission cache for user', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->roles->pluck('name')->toArray()
            ]);
            
            // Verify permissions are cleared by checking fresh
            $user->refresh();
            $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
            \Log::info('✅ User permissions after cache clear', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'has_view_booking' => $user->can('view_booking'),
                'has_view_service' => $user->can('view_service'),
                'all_permissions' => $userPermissions
            ]);
        }

        \Artisan::call('cache:clear');
        \Log::info('✅ Cleared application cache');

        \Log::info('🎉 Permission update completed successfully', [
            'role' => $role_id->name,
            'final_permissions' => $finalPermissions
        ]);

        return redirect()->route('backend.permission-role.list')->withSuccess(__('permission-role.save_form'));
    }

    public function reset_permission($role_id)
    {
        $message = __('messages.reset_form', ['form' => __('page.lbl_role')]);
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            $role = Role::find($role_id);

            $permissions = Permission::get()->pluck('name')->toArray();

            if ($role) {
                $role->permissions()->detach();
                
                // Clear all users with this role from permission cache
                $usersWithRole = \App\Models\User::role($role->name)->get();
                foreach ($usersWithRole as $user) {
                    $user->forgetCachedPermissions();
                }
            }

            // Clear permission cache again after changes
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            \Artisan::call('cache:clear');
        } catch (\Exception $th) {
        }

        return response()->json(['status' => true, 'message' => $message]);
    }
}
