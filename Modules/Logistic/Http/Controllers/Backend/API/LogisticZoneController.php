<?php

namespace Modules\Logistic\Http\Controllers\Backend\API;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logistic\Http\Requests\ApiShippingZoneRequest;
use Modules\Logistic\Models\Logistic;
use Modules\Logistic\Models\LogisticZone;
use Modules\Logistic\Models\LogisticZoneCity;
use Modules\Logistic\Transformers\LogisticZoneResource;

class LogisticZoneController extends Controller
{
    public function logisticzoneList(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query_data = LogisticZone::with('cities');

        if ($request->has('address_id') && $request->address_id != '') {
            $user_address = Address::where('id', $request->address_id)->first();

            if ($user_address) {
                $query_data->whereHas('cities', function ($query) use ($user_address) {
                    $query->where('city_id', $user_address->city);
                });
            } else {
                return response()->json([
                    'status' => true,
                    'message' => __('product.user_address_not_found'),
                ], 200);
            }
        }

        $logisticZone = $query_data->paginate($perPage);

        $logisticZoneCollection = LogisticZoneResource::collection($logisticZone);

        $logisticZonedata = LogisticZone::with('cities')->paginate($perPage);
        $alllogisticZoneCollection = LogisticZoneResource::collection($logisticZonedata);

        return response()->json([
            'status' => true,
            'data' => $logisticZoneCollection,
            'zone_list' => $alllogisticZoneCollection,
            'message' => __('product.logistic_zone_list'),
        ], 200);
    }

    /**
     * Shipping zone list in flat schema format.
     *
     * Endpoint: GET /api/shipping-zone-list
     */
    public function shippingZoneList(Request $request)
    {
        $zones = LogisticZone::with(['logistic', 'cities'])->get();

        $items = [];

        foreach ($zones as $zone) {
            // Combine all city names for this zone into a single string
            $cityNames = $zone->cities->pluck('name')->filter()->unique()->values()->implode(', ');

            $items[] = [
                'shipping_zone_id' => (string) $zone->id,
                'shipping_zone_name' => $zone->name,
                'logistic_id' => (string) $zone->logistic_id,
                'logistic_name' => optional($zone->logistic)->name,
                'logistic_image_url' => optional($zone->logistic)->feature_image,
                'city' => $cityNames,
                'standard_delivery_charges' => (float) ($zone->standard_delivery_charge ?? 0),
                'standard_delivery_time_days' => $this->parseStandardDeliveryDays($zone->standard_delivery_time),
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $items,
            'message' => 'Shipping zone list retrieved successfully.',
        ], 200);
    }

    /**
     * Parse a standard delivery time string like "1 - 3 days" to an integer days value.
     */
    protected function parseStandardDeliveryDays(?string $time): ?int
    {
        if (empty($time)) {
            return null;
        }

        // Match ranges like "1 - 3 days" → 3
        if (preg_match('/(\d+)\s*-\s*(\d+)/', $time, $matches)) {
            return (int) $matches[2];
        }

        // Match single numbers like "3 days" → 3
        if (preg_match('/(\d+)/', $time, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Add a new shipping zone via API.
     *
     * Endpoint: POST /api/add-shipping-zone
     */
    public function addShippingZone(ApiShippingZoneRequest $request)
    {
        // Resolve logistic (accept id or name)
        $logisticInput = $request->input('shipping_zone_logistic');
        if (is_numeric($logisticInput)) {
            $logistic = Logistic::find($logisticInput);
        } else {
            $logistic = Logistic::where('name', $logisticInput)->first();
        }

        if (! $logistic) {
            return response()->json([
                'status' => false,
                'message' => 'Selected logistics provider not found.',
            ], 404);
        }

        // Prepare payload for LogisticZone
        $data = [
            'name' => $request->input('shipping_zone_name'),
            'description' => $request->input('shipping_zone_address'),
            'mobile' => $request->input('shipping_zone_phone'),
            'logistic_id' => $logistic->id,
            'country_id' => $request->input('shipping_zone_country'),
            'state_id' => $request->input('shipping_zone_state'),
            'standard_delivery_charge' => $request->input('shipping_zone_standard_delivery_charge', 0),
            'standard_delivery_time' => $request->input('shipping_zone_standard_delivery_time', '1 Day'),
        ];

        // Create zone
        $zone = LogisticZone::create($data);

        // Attach cities (array of ids)
        $cityIds = (array) $request->input('shipping_zone_cities', []);
        foreach ($cityIds as $cityId) {
            LogisticZoneCity::create([
                'logistic_id' => $zone->logistic_id,
                'logistic_zone_id' => $zone->id,
                'city_id' => $cityId,
            ]);
        }

        // Load relations for response
        $zone->load(['logistic', 'cities']);

        return response()->json([
            'status' => true,
            'message' => 'Shipping zone added successfully.',
            // 'data' => [
            //     'id' => $zone->id,
            //     'shipping_zone_name' => $zone->name,
            //     'shipping_zone_address' => $zone->description,
            //     'shipping_zone_phone' => $zone->mobile,
            //     'shipping_zone_logistic' => $zone->logistic?->name,
            //     'shipping_zone_country' => $zone->country_id,
            //     'shipping_zone_state' => $zone->state_id,
            //     'shipping_zone_cities' => $zone->cities->pluck('id'),
            //     'shipping_zone_standard_delivery_charge' => $zone->standard_delivery_charge,
            //     'shipping_zone_standard_delivery_time' => $zone->standard_delivery_time,
            // ],
        ], 201);
    }
}
