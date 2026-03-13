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
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->integer('auther_id')->nullable();
            $table->string('title')->nullable();
            $table->boolean('status')->default(1)->comment('1 => Active, 2 => InActive');
            $table->string('image')->nullable();
            $table->longText('description')->nullable();
            $table->integer('total_view')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
