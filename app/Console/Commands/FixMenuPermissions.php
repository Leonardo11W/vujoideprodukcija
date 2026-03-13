<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\MenuBuilder\Models\MenuBuilder;

class FixMenuPermissions extends Command
{
    protected $signature = 'fix:menu-permissions';
    protected $description = 'Fix menu permissions in database to match config';

    public function handle()
    {
        // 1. Fix Frontend Settings
        $item = MenuBuilder::where('route', 'frontendsetting.index')->first();
        if ($item) {
            $this->info("Found Frontend Settings item (ID: {$item->id}).");
            $item->permission = ['setting_frontend'];
            $item->save();
            $this->info("Updated to: ['setting_frontend']");
        }
        // 2. Fix Users Inquiries
        $item = MenuBuilder::where('route', 'backend.inquiries.index')->first();
        if ($item) {
            $this->info("Found Users Inquiries item (ID: {$item->id}).");
            $item->permission = ['view_inquiry'];
            $item->save();
            $this->info("Updated to: ['view_inquiry']");
        }

        // 3. Fix Settings (System Settings)
        $item = MenuBuilder::where('route', 'backend.settings')->first();
        if ($item) {
            $this->info("Found Settings item (ID: {$item->id}).");
             $this->info("Current Permission: " . json_encode($item->permission));
             // Should be 'system_settings'
             if ($item->permission !== ['system_settings']) {
                 $item->permission = ['system_settings'];
                 $item->save();
                 $this->info("Updated to: ['system_settings']");
             }
        }
        
        // Clear caches
        MenuBuilder::flushCache();
        \Cache::forget('menu.builder'); // Double check
        \Cache::forget('spatie.permission.cache');
        $this->info("Caches cleared.");
    }
}
