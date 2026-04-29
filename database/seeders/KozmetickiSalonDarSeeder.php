<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Category\Models\Category;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\World\Models\City;
use Modules\World\Models\Country;
use Modules\World\Models\State;

class KozmetickiSalonDarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates "Kozmetički salon Dar" branch with address, business hours, and services.
     * Run: php artisan db:seed --class=KozmetickiSalonDarSeeder --force
     */
    public function run()
    {
        // Skip if branch already exists
        if (Branch::where('slug', 'kozmeticki-salon-dar')->exists()) {
            $this->command->info('Kozmetički salon Dar already exists. Skipping.');
            return;
        }

        $userId = User::first()?->id ?? \DB::table('users')->value('id');
        if (!$userId) {
            throw new \RuntimeException('KozmetickiSalonDarSeeder requires at least one user. Run AuthTableSeeder first.');
        }

        $country = Country::where('name', 'like', '%Croatia%')->first() ?? Country::first();
        $state = $country ? State::where('country_id', $country->id)->first() : null;
        $city = $state ? City::where('state_id', $state->id)->first() : null;

        if (!$country || !$state || !$city) {
            $country = Country::firstOrCreate(['name' => 'Croatia'], ['status' => 1]);
            $state = State::firstOrCreate(
                ['name' => 'Grad Zagreb', 'country_id' => $country->id],
                ['status' => 1]
            );
            $city = City::firstOrCreate(
                ['name' => 'Zagreb', 'state_id' => $state->id],
                ['status' => 1]
            );
        }

        $branch = Branch::create([
            'name' => 'Kozmetički salon Dar',
            'slug' => 'kozmeticki-salon-dar',
            'description' => 'Premium kozmetički salon',
            'contact_email' => 'info@kozmetickisalondar.hr',
            'contact_number' => '+385 1 234 5678',
            'payment_method' => ['cash', 'debit_card', 'credit_card'],
            'branch_for' => 'unisex',
            'status' => 1,
            'manager_id' => null,
        ]);

        $addressData = [
            'user_id' => $userId,
            'address_line_1' => 'Trg bana Jelačića 1',
            'address_line_2' => '',
            'postal_code' => '10000',
            'city' => $city->id,
            'state' => $state->id,
            'country' => $country->id,
            'latitude' => 45.8132,
            'longitude' => 15.9775,
        ];
        $branch->address()->save(new Address($addressData));

        $days = [
            ['day' => 'monday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'tuesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'wednesday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'thursday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'friday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'saturday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => false, 'breaks' => []],
            ['day' => 'sunday', 'start_time' => '09:00:00', 'end_time' => '18:00:00', 'is_holiday' => true, 'breaks' => []],
        ];
        foreach ($days as $day) {
            $day['branch_id'] = $branch->id;
            BussinessHour::create($day);
        }

        $category = Category::firstOrCreate(
            ['slug' => 'kozmetika'],
            ['name' => 'Kozmetika', 'parent_id' => null, 'status' => 1]
        );

        $services = [
            ['slug' => 'manikir', 'name' => 'Manikir', 'description' => 'Nježna njega noktiju', 'duration_min' => 30, 'price' => 25],
            ['slug' => 'pedikir', 'name' => 'Pedikir', 'description' => 'Njega stopala i noktiju na nogama', 'duration_min' => 45, 'price' => 35],
            ['slug' => 'masaza', 'name' => 'Masaža', 'description' => 'Opuštajuća masaža tijela', 'duration_min' => 60, 'price' => 50],
            ['slug' => 'tretman-lica', 'name' => 'Tretman lica', 'description' => 'Profesionalni tretman lica', 'duration_min' => 45, 'price' => 40],
            ['slug' => 'depilacija', 'name' => 'Depilacija', 'description' => 'Depilacija voskom', 'duration_min' => 30, 'price' => 20],
        ];

        foreach ($services as $svc) {
            $service = Service::firstOrCreate(
                ['slug' => $svc['slug']],
                [
                    'name' => $svc['name'],
                    'description' => $svc['description'],
                    'duration_min' => $svc['duration_min'],
                    'default_price' => $svc['price'],
                    'category_id' => $category->id,
                    'sub_category_id' => null,
                    'status' => 1,
                ]
            );

            ServiceBranches::firstOrCreate(
                ['service_id' => $service->id, 'branch_id' => $branch->id],
                [
                    'service_price' => $svc['price'],
                    'duration_min' => $svc['duration_min'],
                ]
            );
        }

        \Artisan::call('cache:clear');
        $this->command->info('Kozmetički salon Dar created successfully.');
    }
}
