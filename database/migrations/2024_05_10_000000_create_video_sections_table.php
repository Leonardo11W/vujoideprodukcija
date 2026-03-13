<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('video_sections', function (Blueprint $table) {
            $table->id();
            $table->string('video_img')->nullable();
            $table->string('video_type')->nullable();
            $table->string('video_url')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('video_sections');
    }
}; 