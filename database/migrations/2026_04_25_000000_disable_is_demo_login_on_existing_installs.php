<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Isključi demo login (predpopunjene vjerodajnice + blok "Demo accounts" na /admin/login).
     */
    public function up(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }
        DB::table('settings')->where('name', 'is_demo_login')->update(['val' => '0']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('settings')) {
            return;
        }
        // Ne vraćamo automatski na 1 (sigurnosno); po potrebi ručno u postavkama
    }
};
