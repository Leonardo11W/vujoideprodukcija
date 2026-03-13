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
        // Drop the table if it exists with incorrect structure
        Schema::dropIfExists('frontend_settings');

        // Create the table with correct structure
        Schema::create('frontend_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->string('key')->nullable();
            $table->tinyInteger('status')->nullable()->default('0');
            $table->text('value')->nullable();
            $table->timestamps();
            $table->softDeletes(); // Handles 'deleted_at' properly
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        // Insert default values only if they don't exist
        if (DB::table('frontend_settings')->count() === 0) {
            DB::table('frontend_settings')->insert([
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_1',
                    'status' => 1,
                    'value' => json_encode([
                        'section_1' => 1,
                        'title' => 'Your Prouct, Our Priority - Book Today!',
                        'enable_search' => 'on',
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_2',
                    'status' => 1,
                    'value' => json_encode([
                        'section_2' => 1,
                        'book_now_id' => 'on',
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_3',
                    'status' => 1,
                    'value' => json_encode([
                        'section_3' => 1,
                        'branch_id' => 'Our Popular Branch',
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_4',
                    'status' => 1,
                    'value' => json_encode([
                        "section_4" => 1,
                        "category_id" => "Hair Styling",
                        "sub_category_id" => "sub_category",
                        "select_category" => "Hair Styling"
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_5',
                    'status' => 1,
                    'value' => json_encode([
                        'section_5' => 1,
                        'package_id' => 'on',
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_6',
                    'status' => 1,
                    'value' => json_encode([
                        'section_6' => 1,
                        'membership_id' => 'on',
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_7',
                    'status' => 1,
                    'value' => json_encode([
                        'section_7' => 1,
                        'expert_id' => 'on',
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_8',
                    'status' => 1,
                    'value' => json_encode([
                        'section_8' => 1,
                        'product_id' => 'Our Popular Product',

                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_9',
                    'status' => 1,
                    'value' => json_encode([
                        'section_9' => 1,
                        'title_id' => 'Our Popular Product',
                        'subtitle_id' => 'Our Popular Product',
                        "description_id" => "From effortless booking to personalized recommendations, we're here to elevate every aspect of your journey."
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_10',
                    'status' => 1,
                    'value' => json_encode([
                        'section_10' => 1,
                        'customer_id' => 'on',
                    ]),
                ],
                [
                    'type' => 'landing-page-setting',
                    'key' => 'section_11',
                    'status' => 1,
                    'value' => json_encode([
                        'section_11' => 1,
                        'title_id' => 'Daily tips to remember',
                        'subtitle_id' => 'Daily tips to remember',
                        'select_blog_id' => 'Daily tips to remember',
                    ]),
                ],
                [
                    'type' => 'header-menu-setting',
                    'key' => 'header-menu-setting',
                    'status' => '1',
                    'value' => json_encode([
                        "header_setting" => 1,

                        "enable_search" => 1,
                        "enable_language" => 1,
                        "enable_darknight_mode" => 1,
                        "header_offer_section" => 1,
                        "header_offer_title" => "Limited Offer Sign up and receive 20% bonus discount on checkout",
                    ]),
                ],
                [
                    'type' => 'footer-setting',
                    'key' => 'footer-setting',
                    'status' => '1',
                    'value' => json_encode([
                        "footer_setting" => 1,

                    ]),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};