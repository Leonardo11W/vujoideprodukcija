<?php

namespace App\Helpers;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AuthHelper
{
    public static function authSession()
    {
        $session = new \App\Models\User;
        if (Session::has('auth_user')) {
            $session = Session::get('auth_user');
        } else {
            $user = Auth::user();
            Session::put('auth_user', $user);
            $session = Session::get('auth_user');
        }

        return $session;
    }

    public static function checkMenuRoleAndPermission($menu)
    {
        if (Auth::check()) {
            if ($menu->data('role') == null && auth()->user()->hasRole('admin')) {
                return true;
            }

            if ($menu->data('permission') == null && $menu->data('role') == null) {
                return true;
            }

            if ($menu->data('role') != null) {
                if (auth()->user()->hasAnyRole(explode(',', $menu->data('role')))) {
                    return true;
                }
            }

            if ($menu->data('permission') != null) {
                if (auth()->user()->can($menu->data('permission'))) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function checkRolePermission($role, $permission)
    {
        try {
            if ($role->hasPermissionTo($permission)) {
                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public static function demoUserPermission()
    {
        if (Auth::user()->hasRole('demo_admin')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Ensure employee has default permissions
     * This method ensures that employees get the default permissions
     * even if they were created before permissions were assigned to the role
     *
     * @param \App\Models\User $user
     * @return void
     */
    public static function ensureEmployeeDefaultPermissions($user)
    {
        if (!$user->hasRole('employee')) {
            return;
        }

        // Default permissions for employees - VIEW ONLY (no add, edit, delete)
        $defaultPermissions = [
            'view_dashboard',
            'view_booking',
            'view_service',
            'view_earning',
            'view_review',
            'view_reports', // Reports module - View only by default
            // Reports sub-permissions - enabled by default for employees
            'reports_daily_booking_report',
            'reports_staff_report',
            'reports_payout_report',
            'reports_overall_booking_report',
        ];

        // Get the employee role to sync permissions
        $employeeRole = \Spatie\Permission\Models\Role::where('name', 'employee')->first();
        
        if ($employeeRole) {
            // Ensure role has all default permissions
            foreach ($defaultPermissions as $permission) {
                $permissionModel = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission]);
                if (!$employeeRole->hasPermissionTo($permission)) {
                    $employeeRole->givePermissionTo($permission);
                }
            }
            
            // Clear permission cache to ensure changes take effect
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
}
