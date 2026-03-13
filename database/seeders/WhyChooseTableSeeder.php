<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WhyChooseTableSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        DB::table('why_choose')->delete();

        $items = [
            [
                'id'          => 1,
                'file_name'   => 'why_choose.png',
                'title'       => 'Why choose Frezka',
                'subtitle'    => 'Frezka',
                'description' => 'With an intuitive booking system, expert selection, and exclusive offers, our all-in-one platform ensures seamless operations while enhancing customer loyalty.',
            ],
        ];

        foreach ($items as $item) {
            $publicPath  = public_path('why_choose/' . $item['file_name']);
            $storagePath = 'why_choose/' . $item['file_name'];

            // Copy from public → storage
            if (file_exists($publicPath)) {
                Storage::disk('public')->put(
                    $storagePath,
                    file_get_contents($publicPath)
                );
            }

            DB::table('why_choose')->insert([
                'id'          => $item['id'],
                'image'       => $storagePath, // relative path inside storage
                'title'       => $item['title'],
                'subtitle'    => $item['subtitle'],
                'description' => $item['description'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        Schema::enableForeignKeyConstraints();
    }
}
