<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Delivery\Models\DeliveryZone;

class DeliveryZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $deliveryZones = [
            [
                'name' => 'Local Delivery',
                'charge' => 5.00,
                'delivery_time' => '1-2 days',
                'description' => 'Local area delivery within city limits',
                'status' => 1,
            ],
            [
                'name' => 'Standard Delivery',
                'charge' => 10.00,
                'delivery_time' => '3-5 days',
                'description' => 'Standard delivery to nearby areas',
                'status' => 1,
            ],
            [
                'name' => 'Express Delivery',
                'charge' => 15.00,
                'delivery_time' => '1 day',
                'description' => 'Express delivery for urgent orders',
                'status' => 1,
            ],
            [
                'name' => 'Free Delivery',
                'charge' => 0.00,
                'delivery_time' => '5-7 days',
                'description' => 'Free delivery for orders over $50',
                'status' => 1,
            ],
        ];

        foreach ($deliveryZones as $zone) {
            DeliveryZone::create($zone);
        }
    }
} 