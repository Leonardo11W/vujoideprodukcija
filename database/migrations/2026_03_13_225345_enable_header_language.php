<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $setting = DB::table('frontend_settings')
            ->where('type', 'header-menu-setting')
            ->where('key', 'header-menu-setting')
            ->first();

        if ($setting && $setting->value) {
            $decoded = $setting->value;
            while (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            if (is_array($decoded)) {
                $decoded['enable_language'] = 1;
                DB::table('frontend_settings')
                    ->where('id', $setting->id)
                    ->update(['value' => json_encode($decoded)]);
            }
        }
    }

    public function down(): void
    {
        // No rollback - keeping language enabled is desired
    }
};
