<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;

class DiagnoseUserAccess extends Command
{
    protected $signature = 'diagnose:user-access {email=manager@salon.com}';
    protected $description = 'Diagnose user access and route middleware';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::role('manager')->first();
            if ($user) {
                $this->info("User with email $email not found. Found alternative manager: " . $user->email);
            } else {
                $this->error("No manager user found.");
                return;
            }
        } else {
            $this->info("Checking user: " . $user->email);
        }

        // 1. Roles
        $this->info("\n--- Roles ---");
        foreach ($user->roles as $role) {
            $this->info("- " . $role->name . " (Guard: " . $role->guard_name . ")");
        }

        // 2. Direct Permissions
        $this->info("\n--- Direct Permissions ---");
        foreach ($user->permissions as $perm) {
            $this->info("- " . $perm->name);
        }

        // 3. Capability Checks
        $this->info("\n--- Capabilities ---");
        $this->info("can('setting_frontend'): " . ($user->can('setting_frontend') ? 'YES' : 'NO'));
        $this->info("can('system_settings'): " . ($user->can('system_settings') ? 'YES' : 'NO'));
        $this->info("can('setting_general'): " . ($user->can('setting_general') ? 'YES' : 'NO'));
        $this->info("can('setting_holiday'): " . ($user->can('setting_holiday') ? 'YES' : 'NO'));
        $this->info("can('setting_bussiness_hours'): " . ($user->can('setting_bussiness_hours') ? 'YES' : 'NO'));
        try {
            $this->info("hasPermissionTo('setting_frontend'): " . ($user->hasPermissionTo('setting_frontend') ? 'YES' : 'NO'));
        } catch (PermissionDoesNotExist $e) {
            $this->info("hasPermissionTo('setting_frontend'): N/A (permission not seeded)");
        }


        // 4. Route Middleware
        $this->info("\n--- Route Middleware (backend.settings) ---");
        $route = Route::getRoutes()->getByName('backend.settings');
        if ($route) {
            $this->info("URI: " . $route->uri());
            $this->info("Middleware: " . implode(', ', $route->gatherMiddleware()));
            $this->info("Action: " . $route->getActionName());
        } else {
            $this->error("Route 'backend.settings' not found.");
        }
        
        // 5. Check 'admin' middleware definition
        $this->info("\n--- 'admin' Middleware Class ---");
        $kernel = app()->make(\Illuminate\Contracts\Http\Kernel::class);
        // Uses reflection to get routeMiddleware
        // Or just print hardcoded knowledge if strict
        $params = $kernel->getRouteMiddleware();
        $this->info("Maps to: " . ($params['admin'] ?? 'NOT FOUND'));
    }
}
