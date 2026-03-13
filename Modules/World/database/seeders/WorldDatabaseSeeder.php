<?php

namespace Modules\World\database\seeders;

use Illuminate\Database\Seeder;

class WorldDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CitySeederTableSeeder::class);
        $this->call(CountrySeederTableSeeder::class);
        $this->call(StateSeederTableSeeder::class);
    }
}
