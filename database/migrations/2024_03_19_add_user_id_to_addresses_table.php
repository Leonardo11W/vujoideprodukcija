<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (!Schema::hasColumn('addresses', 'user_id')) {
                // First add the column as nullable
                $table->unsignedBigInteger('user_id')->after('id')->nullable();
            }
        });

        // Update existing records to use the first user's ID
        $firstUserId = DB::table('users')->value('id');
        if ($firstUserId) {
            DB::table('addresses')->whereNull('user_id')->update(['user_id' => $firstUserId]);
        }

        // Now make the column required and add foreign key
        Schema::table('addresses', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            if (Schema::hasColumn('addresses', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
}; 