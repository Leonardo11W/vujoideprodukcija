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
        Schema::create('why_choose_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('why_choose_id');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('why_choose_id')->references('id')->on('why_choose')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('why_choose_features');
    }
};
