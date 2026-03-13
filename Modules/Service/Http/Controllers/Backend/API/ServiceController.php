<?php

namespace Modules\Service\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Http\Resources\ServiceDetailsResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Modules\Category\Models\Category;
use Modules\Service\Http\Requests\ServiceRequest;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Service\Models\ServiceEmployee;
use Modules\Service\Models\ServiceGallery;
use Modules\Service\Transformers\ServiceResource;
use Modules\Commission\Models\Commission;

class ServiceController extends Controller
{
    public function assign_employee_list($id)
    {
        $service_user = ServiceEmployee::with('staff')->where('service_id', $id)->get();

        $service_user = $service_user->each(function ($data) {
            $data['name'] = $data->staff->name;
            $data['avatar'] = $data->staff->avatar;

            return $data;
        });

        return $this->sendResponse($service_user, __('service.stff_service'));
    }

    public function assign_employee_update($id, Request $request)
    {
        ServiceEmployee::where('service_id', $id)->delete();
        foreach ($request->staffs as $key => $value) {
            ServiceEmployee::create([
                'service_id' => $id,
                'employee_id' => $value['employee_id'],
            ]);
        }

        return $this->sendResponse($id, __('service.stff_service_update'));
    }

    // =========Service Staff Assign list and Assign update ======= //

    public function assign_branch_list($id)
    {
        $service_branch = ServiceBranches::with('branch')->where('service_id', $id)->get();
        $service_branch = $service_branch->each(function ($data) {
            $data['name'] = $data->branch->name;

            return $data;
        });

        return $this->sendResponse($service_branch, __('service.branch_service'));
    }

    public function assign_branch_update($id, Request $request)
    {
        $service = Service::findOrFail($id);
        
        // Use transaction to prevent data loss
        \DB::transaction(function () use ($id, $request, $service) {
            ServiceBranches::where('service_id', $id)->delete();
            
            foreach ($request->branches as $key => $value) {
                // Validate and sanitize price
                $servicePrice = isset($value['service_price']) ? max(0, floatval($value['service_price'])) : 0;
                $durationMin = isset($value['duration_min']) ? max(0, floatval($value['duration_min'])) : $service->duration_min;
                
                ServiceBranches::create([
                    'service_id' => $id,
                    'branch_id' => $value['branch_id'],
                    'service_price' => $servicePrice,
                    'duration_min' => $durationMin,
                ]);
            }
        });

        return $this->sendResponse($id, __('service.branch_service_update'));
    }

    public function ServiceGallery(Request $request)
    {
        $serviceId = $request->input('service_id');

        // Retrieve service-wise gallery
        if ($serviceId) {
            $service = Service::find($serviceId);

            if (! $service) {
                return response()->json([
                    'status' => false,
                    'message' => __('service.service_notfound'),
                ], 404);
            }

            $data = ServiceGallery::where('service_id', $serviceId)->get();

            $gallery = ['gallery' => $data, 'service' => $service];

            return response()->json([
                'status' => true,
                'data' => $gallery,
                'message' => __('service.service_gal_retrived'),
            ], 200);
        }

        // Retrieve all gallery
        $allData = ServiceGallery::all();

        return response()->json([
            'status' => true,
            'data' => $allData,
            'message' => __('service.servie_gallery'),
        ], 200);
    }

    public function uploadGalleryImages(Request $request, $id)
    {
        $gallery = collect($request->gallery, true);

        $images = ServiceGallery::where('service_id', $id)->whereNotIn('id', $gallery->pluck('id'))->get();

        foreach ($images as $key => $value) {
            $value->clearMediaCollection('gallery_images');
            $value->delete();
        }

        foreach ($gallery as $key => $value) {
            if ($value['id'] == 'null') {
                $serviceGallery = ServiceGallery::create([
                    'service_id' => $id,
                ]);

                $serviceGallery->addMedia($value['file'])->toMediaCollection('gallery_images');

                $serviceGallery->full_url = $serviceGallery->getFirstMediaUrl('gallery_images');
                $serviceGallery->save();
            }
        }

        return $this->sendResponse($id, __('service.service_gallery_update'));
    }

    public function serviceList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $branchId = $request->input('branch_id');

        $services = Service::with(['media', 'branches', 'employee']);
        if ($request->has('branch_id')) {
            $services = $services->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }

        if ($request->has('search')) {
            $services->where('name', 'like', "%{$request->search}%");
        }

        if ($request->has('min_price') && $request->has('max_price')) {
            $services->whereBetween('default_price', [$request->min_price, $request->max_price]);
        }

        if ($request->has('employee_id') && $request->employee_id != '') {
            $services = $services->whereHas('employee', function ($query) use ($request) {
                $query->whereIn('employee_id', explode(',', $request->employee_id));
            });
        }
        if ($request->has('category_id') && $request->category_id != '') {
            $parentIds = Category::whereIn('parent_id', explode(',', $request->category_id))->pluck('id');
            $services->where(function ($query) use ($parentIds, $request) {
                $query->whereIn('sub_category_id', $parentIds)
                      ->orWhere('category_id', $request->category_id);
            });
        }

        if ($request->has('subcategory_id') && $request->subcategory_id != '') {
            $services->whereIn('sub_category_id', explode(',', $request->subcategory_id));
        }
        $services = $services->paginate($perPage);

    // Convert items to an array and modify the `default_price` and `duration_min`
    $items = $services->items();
    foreach ($items as $service) {
        $service->resolveBranchSpecificData($branchId);
         $service->staff_count = $service->employee->count();
         $service->branch_count = $service->branches->count();
    }

        $serviceCollection = ServiceResource::collection($services);
        $responseData = $serviceCollection->map(function ($item) {
            return $item->resource->toArray(request());
        });
        $responseData = $serviceCollection->toArray(request());

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('service.service_list'),
        ], 200);
    }

    public function serviceDetails(Request $request)
    {
        $services = Service::where('status', 1)->with(['category', 'sub_category', 'branches']);

        if ($request->has('service_id')) {
            $services->where('id', $request->service_id);
        }

        if ($request->has('category_id')) {
            $services->where('category_id', $request->category_id);
        }

        if ($request->has('sub_category_id')) {
            $services->where('sub_category_id', $request->sub_category_id);
        }

        if ($request->has('branch_id')) {
            $branchId = $request->branch_id;
            $services->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->with(['branches' => function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            }]);
        }
        if ($request->has('name')) {
            $keyword = $request->input('name');
            $services->where('name', 'LIKE', '%'.$keyword.'%');
        }
        $filteredServices = $services->get();

        if ($request->has('branch_id')) {
            foreach ($filteredServices as $service) {
                $service->resolveBranchSpecificData($request->branch_id);
            }
        }

        if ($filteredServices->isEmpty()) {
            return response()->json(['status' => false, 'message' => __('service.service_notfound')]);
        } else {
            return response()->json(['status' => true, 'data' => $filteredServices, 'message' => __('service.service_detail')]);
        }
    }

    public function searchServices(Request $request)
    {
        $searchQuery = $request->query('query');

        if (! $searchQuery) {
            return response()->json(['message' => __('service.service_search')], 400);
        }

        $services = Service::where(function ($query) use ($searchQuery) {
            $query->where('name', 'like', '%'.$searchQuery.'%')
                ->orWhere('description', 'like', '%'.$searchQuery.'%')
                ->orWhere('category', 'like', '%'.$searchQuery.'%');
        })->get();

        return response()->json($services);
    }

    public function ServiceListDetails(Request $request)
    {
        $serviceId = $request->input('service_id');
        $branchId = $request->input('branch_id');
        $service = Service::with(['media', 'category','sub_category','branches.branch', 'employee.employee.employeeprofile', 'gallery'])->where('id',$serviceId)->first();

        if ($service) {
            if ($branchId) {
                $service->resolveBranchSpecificData($branchId);
            }
            $service->staff_count = $service->employee->count();
            $service->branch_count = $service->branches->count();
        }

        $responseData = new ServiceDetailsResource($service);

        return response()->json([
            'status' => true,
            'data' =>$responseData ,
            'message' => __('service.service_list'),
        ], 200);
    }

    /**
     * Store a newly created service
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeService(Request $request)
    {
        // Map API field names to database field names
        $data = [
            'name' => $request->input('name'),
            'duration_min' => $request->input('service_duration') ?? $request->input('duration_min'),
            'default_price' => $request->input('default_price'),
            'category_id' => $request->input('category') ?? $request->input('category_id'),
            'sub_category_id' => $request->input('Subcategory') ?? $request->input('subcategory') ?? $request->input('sub_category_id'),
            'description' => $request->input('descriptoin') ?? $request->input('description'),
            'status' => $request->has('status') ? ($request->input('status') ? 1 : 0) : 1,
        ];

        // Validate required fields
        $validator = \Validator::make($data, [
            'name' => 'required|string|max:255',
            'duration_min' => 'required|integer|min:1',
            'default_price' => 'required|numeric|min:0',
            'category_id' => 'required|integer|exists:categories,id',
            'sub_category_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create service (slug will be auto-generated by HasSlug trait)
        $service = Service::create($data);

        // Handle feature image upload if provided
        if ($request->hasFile('feature_image')) {
            storeMediaFile($service, $request->file('feature_image'), 'feature_image');
        }

        // Clear cache
        Artisan::call('cache:clear');

        // Response with only the requested keys
        $data = [
            'name' => $service->name,
            'service_duration' => (int) $service->duration_min,
            'default_price' => (float) $service->default_price,
            'category' => (int) $service->category_id,
            'Subcategory' => $service->sub_category_id ? (int) $service->sub_category_id : null,
            'description' => $service->description,
            'status' => (bool) $service->status,
        ];

        return response()->json([
            'status' => true,
            'data' => $data,
            'message' => 'Service created successfully.'
        ], 201);
    }

    /**
     * Update an existing service
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateService(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        // Map API field names to database field names
        $data = [];

        if ($request->has('name')) {
            $data['name'] = $request->input('name');
        }

        if ($request->has('service_duration') || $request->has('duration_min')) {
            $data['duration_min'] = $request->input('service_duration') ?? $request->input('duration_min');
        }

        if ($request->has('default_price')) {
            $data['default_price'] = $request->input('default_price');
        }

        if ($request->has('category') || $request->has('category_id')) {
            $data['category_id'] = $request->input('category') ?? $request->input('category_id');
        }

        if ($request->has('Subcategory') || $request->has('subcategory') || $request->has('sub_category_id')) {
            $subCategoryId = $request->input('Subcategory') ?? $request->input('subcategory') ?? $request->input('sub_category_id');
            $data['sub_category_id'] = $subCategoryId ?: null;
        }

        if ($request->has('descriptoin') || $request->has('description')) {
            $data['description'] = $request->input('descriptoin') ?? $request->input('description');
        }

        if ($request->has('status')) {
            $data['status'] = $request->input('status') ? 1 : 0;
        }

        // Validate fields if provided
        $validator = \Validator::make($data, [
            'name' => 'sometimes|required|string|max:255',
            'duration_min' => 'sometimes|required|integer|min:1',
            'default_price' => 'sometimes|required|numeric|min:0',
            'category_id' => 'sometimes|required|integer|exists:categories,id',
            'sub_category_id' => 'nullable|integer|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update service
        $service->update($data);

        // Handle feature image upload if provided
        if ($request->hasFile('feature_image')) {
            storeMediaFile($service, $request->file('feature_image'), 'feature_image');
        }

        // Clear cache
        Artisan::call('cache:clear');

        // Load relationships for response
        $service->load(['media', 'category', 'sub_category', 'branches']);

        return response()->json([
            'status' => true,
            'data' => new ServiceResource($service),
            'message' => 'Service updated successfully.'
        ], 200);
    }

    /**
     * Get service list and commission list with only id and name
     * Endpoint: service-commission-list
     */
    public function serviceCommissionList()
    {
        // Get services with only id and name
        $services = Service::select('id', 'name')
            ->where('status', 1)
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                ];
            });

        // Get commissions with only id and title (as name)
        $commissions = Commission::select('id', 'title')
            ->where('status', 1)
            ->orderBy('title', 'asc')
            ->get()
            ->map(function ($commission) {
                return [
                    'id' => $commission->id,
                    'name' => $commission->title,
                ];
            });

        return response()->json([
            'status' => true,
            'data' => [
                'service_list' => $services,
                'commission_list' => $commissions,
            ],
            'message' => 'Service and commission list retrieved successfully.',
        ], 200);
    }
}
