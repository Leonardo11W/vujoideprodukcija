<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProductReviewSeeder extends Seeder
{
    public function run()
    {
        $reviews = [];

        for ($i = 1; $i <= 10; $i++) {
            $productId = rand(2, 110);
            $userId = rand(1, 5); // Adjust based on your user table
            $variationId = rand(1, 10); // Adjust based on your variation data

            $reviews[] = [
                'user_id' => $userId,
                'product_id' => $productId,
                'product_variation_id' => $variationId,
                'rating' => rand(1, 5),
                'review_msg' => 'This is a sample review for product #' . $productId,
                'updated_by' => null,
                'deleted_by' => null,
                'created_by' => $userId,
                'deleted_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        DB::table('product_review')->insert($reviews);
    }
}
