<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VideoSectionsTableSeeder extends Seeder
{
    public function run()
    {
     
        DB::table('video_sections')->delete();

      
        Storage::disk('public')->makeDirectory('video_section');

       
        $fileName = 'video_section.jpg';
        $sourcePath = database_path('seeders/images/video_section/' . $fileName);
        $storagePath = 'video_section/' . $fileName;
 
        if (file_exists($sourcePath)) {
            Storage::disk('public')->put(
                $storagePath,
                file_get_contents($sourcePath)
            );
        }


        DB::table('video_sections')->insert([
            'video_img'   => $storagePath,
            'video_type'  => 'youtube',
            'video_url'   => 'https://www.youtube.com/watch?v=urPq7Qq0lXk',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }
}
