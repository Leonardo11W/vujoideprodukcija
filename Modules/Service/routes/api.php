<?php
use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\Backend\API\ServiceController;

Route::get('service-list', [ServiceController::class, 'serviceList']);
Route::get('get-service-details', [ServiceController::class, 'ServiceListDetails']);
Route::get('service-commission-list', [ServiceController::class, 'serviceCommissionList']);

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::get('service/staff/{id}', [ServiceController::class, 'assign_employee_list']);
    Route::post('service/staff/{id}', [ServiceController::class, 'assign_employee_update']);

    Route::get('service/branch/{id}', [ServiceController::class, 'assign_branch_list']);
    Route::post('service/branch/{id}', [ServiceController::class, 'assign_branch_update']);

    // Gallery Images
    Route::get('service-gallery', [ServiceController::class, 'ServiceGallery']);
    Route::post('/gallery-images/{id}', [ServiceController::class, 'uploadGalleryImages']);

    // Service Add/Edit endpoints
    Route::post('add-service', [ServiceController::class, 'storeService']);
    Route::post('update-service/{id}', [ServiceController::class, 'updateService']);

    Route::apiResource('service', ServiceController::class);

    Route::post('service-detail', [ServiceController::class, 'serviceDetails']);
    Route::get('search-service', [ServiceController::class, 'searchServices']);
});

// Quick Booking: Get experts by branch and service
Route::get('/quick-booking/experts-list', function (\Illuminate\Http\Request $request) {
    $branchId = $request->query('branch_id');
    $serviceId = $request->query('service_id');
    $experts = \App\Models\User::role('employee')
        ->whereHas('branches', function($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })
        ->whereHas('services', function($q) use ($serviceId) {
            $q->where('service_id', $serviceId);
        })
        ->with('rating')
        ->get()
        ->map(function($user) {
            // Calculate average rating from the 'rating' relationship
            $avgRating = 0;
            if ($user->rating && $user->rating->count() > 0) {
                $avgRating = round($user->rating->avg('rating'), 1);
            }
            return [
                'id' => $user->id,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'image_path' => $user->profile_image,
                'rating' => $avgRating,
                'speciality' => $user->speciality ?? '',
            ];
        });
    return response()->json(['success' => true, 'experts' => $experts]);
});
?>


