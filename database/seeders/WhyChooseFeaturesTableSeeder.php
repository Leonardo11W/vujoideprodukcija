<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WhyChooseFeaturesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Clear table
        DB::table('why_choose_features')->delete();

        // Ensure folder exists in storage
        Storage::disk('public')->makeDirectory('why_choose_features');

   
        // Records
        $features = [
            [
                'id' => 10,
                'why_choose_id' => 1,
                'title' => 'Book in seconds',
                'subtitle' => 'Quick & Easy Booking',
                'image' => 'quick_easy_booking.jpg',
            ],
            [
                'id' => 11,
                'why_choose_id' => 1,
                'title' => 'Enhance Client Satisfaction',
                'subtitle' => 'Delight your clients',
                'image' => 'appointment_booking.jpg',
            ],
            [
                'id' => 12,
                'why_choose_id' => 1,
                'title' => 'Discover trends with analytics',
                'subtitle' => 'Grow your business',
                'image' => 'Discover_trends_with_analytics.jpg',
            ],
        ];

        // Insert with timestamps
        foreach ($features as $feature) {
            $publicPath  = public_path('why_choose_features/' . $feature['image']);
            $storagePath = 'why_choose_features/' . $feature['image'];
          

            if (file_exists($publicPath)) {
                Storage::disk('public')->put(
                    $storagePath,
                    file_get_contents($publicPath)
                );
            }

            DB::table('why_choose_features')->insert([
                'id' => $feature['id'],
                'why_choose_id' => $feature['why_choose_id'],
                'title' => $feature['title'],
                'subtitle' => $feature['subtitle'],
                'image' => 'why_choose_features/' . $feature['image'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
