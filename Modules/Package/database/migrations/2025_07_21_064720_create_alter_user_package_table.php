<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable()->change();
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('user_packages', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id')->nullable(false)->change();
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
        });
    }
};
