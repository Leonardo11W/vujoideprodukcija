<?php

namespace Modules\Logistic\Http\Controllers\Backend\API;

use Illuminate\Routing\Controller;
use Modules\Logistic\Http\Requests\LogisticRequest;
use Modules\Logistic\Models\Logistic;

class LogisticController extends Controller
{
    /**
     * Add a new logistics provider
     *
     * @param  \Modules\Logistic\Http\Requests\LogisticRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addLogistics(LogisticRequest $request)
    {
        // Prepare data for creation
        $data = [
            'name' => $request->input('logistics_name'),
            'status' => $request->input('logistics_status') ? 1 : 0,
        ];

        // Create the logistic
        $logistic = Logistic::create($data);

        // Handle image upload if provided
        if ($request->hasFile('logistics_image')) {
            storeMediaFile($logistic, $request->file('logistics_image'), 'feature_image');
        }

        // Load media for response
        $logistic->load('media');

        return response()->json([
            'status' => true,
            'message' => 'Logistics added successfully',
            // 'data' => [
            //     'id' => $logistic->id,
            //     'logistics_name' => $logistic->name,
            //     'logistics_status' => (bool) $logistic->status,
            //     'logistics_image' => $logistic->feature_image,
            //     'created_at' => $logistic->created_at,
            //     'updated_at' => $logistic->updated_at,
            // ]
        ], 201);
    }
}

