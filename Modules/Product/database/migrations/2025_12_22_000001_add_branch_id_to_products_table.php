<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('unit_id');
                // add foreign key if branches table exists
                if (Schema::hasTable('branches')) {
                    $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'branch_id')) {
                // drop foreign key if exists
                try {
                    $table->dropForeign(['branch_id']);
                } catch (\Exception $e) {
                    // ignore if the foreign key does not exist
                }

                $table->dropColumn('branch_id');
            }
        });
    }
};
