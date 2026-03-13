<?php

namespace Modules\Employee\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Modules\Commission\Models\EmployeeCommission;
use Modules\Employee\Http\Requests\EmployeeRequest;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Transformers\EmployeeDetailResource;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Employee\Transformers\EmployeeReviewResource;
use Modules\Service\Models\ServiceEmployee;
use Modules\Wallet\Models\Wallet;
use App\Models\UserProfile;


class EmployeeController extends Controller
{
    public function employeeList(Request $request)
    {
        $branchId = $request->input('branch_id');
        $perPage = $request->input('per_page', 10);

        $employee = User::role('employee')->with(['media', 'branches', 'services'])->where('status', 1);
        if ($branchId) {
            $employee = $employee->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }
        if (! empty($request->service_ids)) {
            $ids = ServiceEmployee::whereIn('service_id', explode(' ', $request->service_ids))->pluck('employee_id');
            $employee = $employee->whereIn('id', $ids);
        }
        if (! empty($request->order_by) && $request->order_by == 'top') {
            $employee = $employee->withCount(['services' => function ($q) {
                $q->where('status', 1);
            }])
                ->orderByDesc('services_count');
        }
        $employee = $employee->paginate($perPage);
        $responseData = EmployeeResource::collection($employee);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('employee.employee_list'),
        ], 200);
    }

    public function employeeDetail(Request $request)
    {
        $branchId = $request->input('branch_id');
        $employeeId = $request->input('employee_id');
        // dd($request->all());

        $with = ['media', 'services.service.category', 'profile'];

        if ($branchId && $employeeId) {
            $employee = User::role('employee')->with($with)->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->find($employeeId);
        } elseif ($branchId) {
            $employee = User::role('employee')->with($with)->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })->first();
        } elseif ($employeeId) {
            $employee = User::role('employee')->with($with)->find($employeeId);
        } else {
            return response()->json(['status' => false, 'message' => __('employee.branch_employee_id')]);
        }
        if ($employee) {
            if ($branchId) {
                $employee->branch_name = Branch::where('id', $branchId)->value('name');
            } else {
                $employee->branch_name = $employee->branches->pluck('name')->first();
            }
            return response()->json(['status' => true, 'data' => new EmployeeDetailResource($employee), 'message' => __('employee.employee_detail')]);
        } else {
            return response()->json(['status' => false, 'message' => __('employee.employee_notfound')]);
        }
    }

    public function staffList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        // Use Sanctum guard explicitly to check for authenticated user
        // Fallback to request user if Sanctum guard doesn't return user
        $authUser = auth('sanctum')->user() ?? $request->user('sanctum');

        if ($request->filled('branch_id')) {
            $branchIds = collect([$request->branch_id]);
        } else {
            if (! $authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated.'
                ], 401);
            }

            $branchIds = $authUser->branches->pluck('id');
        }

        if ($branchIds->isEmpty()) {
            return response()->json([
                'status' => true,
                'data' => [],
                'message' => __('employee.employee_list')
            ], 200);
        }

        $employees = User::role('employee')
            ->with(['media', 'commissions.mainCommission', 'profile'])
            ->withCount(['services' => function ($q) {
                $q->where('status', 1);
            }])
            ->whereHas('branches', function ($query) use ($branchIds) {
                $query->whereIn('branch_id', $branchIds);
            })
            // Exclude logged-in user from staff list
            ->when($authUser, function ($q) use ($authUser) {
                $q->where('id', '!=', $authUser->id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $statusValue = $request->status;

                // Handle string values: active/inactive
                if (strtolower($statusValue) === 'active' || $statusValue === '1' || $statusValue === 1 || $statusValue === true || $statusValue === 'true') {
                    $q->where('status', 1);
                } elseif (strtolower($statusValue) === 'inactive' || $statusValue === '0' || $statusValue === 0 || $statusValue === false || $statusValue === 'false') {
                    $q->where('status', 0);
                } else {
                    // Try to parse as boolean for backward compatibility
                    $status = filter_var($statusValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    if (! is_null($status)) {
                        $q->where('status', $status ? 1 : 0);
                    }
                }
            })
            ->when($request->filled('gender'), function ($q) use ($request) {
                $q->where('gender', $request->gender);
            })
            ->paginate($perPage);

        // Transform data to return only required fields
        $staffData = $employees->getCollection()->map(function ($employee) {
            // Get first commission with mainCommission relationship
            $commission = $employee->commissions->first();
            $commissionValue = $commission && $commission->mainCommission
                ? $commission->mainCommission->title
                : null;

            // Get profile data
            $profile = $employee->profile;

            // Extract country code from phone number (handles intl-tel-input plugin formats)
            $phoneCode = null;
            if ($employee->mobile) {
                $mobile = trim($employee->mobile);

                if (preg_match('/^\+(\d{1,4})/', $mobile, $matches)) {
                    $phoneCode = '+' . $matches[1];
                } elseif (preg_match('/^(\d{1,4})[- ]/', $mobile, $matches)) {
                    $phoneCode = '+' . $matches[1];
                } elseif (preg_match('/^(\d{1,4})(\d{6,})/', $mobile, $matches)) {
                    $potentialCode = $matches[1];
                    $remainingDigits = $matches[2];

                    if (strlen($potentialCode) <= 3) {
                        $codeNum = (int)$potentialCode;

                        if (strlen($potentialCode) == 1 && in_array($potentialCode, ['1', '7'])) {
                            $phoneCode = '+' . $potentialCode;
                        } elseif (strlen($potentialCode) == 2 && $codeNum >= 20 && $codeNum <= 99) {
                            $phoneCode = '+' . $potentialCode;
                        } elseif (strlen($potentialCode) == 3 && $codeNum >= 200 && $codeNum <= 999) {
                            $phoneCode = '+' . $potentialCode;
                        }
                    }
                }
            }

            return [
                'staff_id' => $employee->id,
                'staff_name' => $employee->full_name,
                'staff_email' => $employee->email,
                'staff_contact_number' => $employee->mobile,
                'staff_phone_code' => $phoneCode,
                'staff_image_url' => $employee->media->pluck('original_url')->first() ?? null,
                'staff_total_service' => $employee->services_count ?? 0,
                'staff_status' => $employee->status == 1 ? true : false,
                'staff_gender' => $employee->gender,
                'is_staff_verified' => !is_null($employee->email_verified_at),
                'staff_commission' => $commissionValue,
                'staff_about' => $profile->about_self ?? null,
                'staff_expert' => $profile->expert ?? null,
                'rating_star' => $employee->rating->avg('rating'),
                'facebook_link' => $profile->facebook_link ?? null,
                'instagram_link' => $profile->instagram_link ?? null,
                'twitter_link' => $profile->twitter_link ?? null,
                'dribbble_link' => $profile->dribbble_link ?? null,
                'is_banned' => $employee->is_banned == 1 ? true : false,
                'is_manager' => $employee->is_manager == 1 ? true : false,


            ];
        });

        // Create paginated response structure
        // $paginatedData = [
        //     'current_page' => $employees->currentPage(),
        //     'data' => $staffData->values()->all(),
        //     'first_page_url' => $employees->url(1),
        //     'from' => $employees->firstItem(),
        //     'last_page' => $employees->lastPage(),
        //     'last_page_url' => $employees->url($employees->lastPage()),
        //     'next_page_url' => $employees->nextPageUrl(),
        //     'path' => $employees->path(),
        //     'per_page' => $employees->perPage(),
        //     'prev_page_url' => $employees->previousPageUrl(),
        //     'to' => $employees->lastItem(),
        //     'total' => $employees->total(),
        // ];

        return response()->json([
            'status' => true,
            'data' => $staffData,
            'message' => __('employee.employee_list')
        ], 200);
    }

    public function staffDetail(Request $request, $param = null)
    {
        // Parse staff_id from route parameter format: staff_id=13
        $id = null;

        if ($param) {
            // Check if param is in format "staff_id=13"
            if (preg_match('/staff_id=(\d+)/', $param, $matches)) {
                $id = $matches[1];
            } elseif (is_numeric($param)) {
                // Fallback: if param is just a number, use it directly
                $id = $param;
            }
        }

        // Fallback to query parameter if route param didn't work
        if (!$id) {
            $id = $request->input('staff_id');
        }

        if (!$id) {
            return response()->json([
                'status' => false,
                'message' => 'Staff ID is required.'
            ], 400);
        }

        $manager = auth()->user();
        $branchIds = $manager->branches->pluck('id');

        if ($branchIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to any branches.'
            ], 403);
        }

        $employee = User::role('employee')
            ->with(['branches', 'media'])
            ->findOrFail($id);

        $employeeBranchIds = $employee->branches->pluck('id');
        $hasAccess = $employeeBranchIds->intersect($branchIds)->isNotEmpty();

        if (!$hasAccess) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to view this staff member.'
            ], 403);
        }

        // Get wallet balance
        $walletBalance = Wallet::where('user_id', $employee->id)->value('amount') ?? 0;

        // Get services with category
        $serviceEmployees = ServiceEmployee::where('employee_id', $id)
            ->with(['service.category'])
            ->get();

        // Transform services to match schema
        $serviceData = $serviceEmployees->map(function ($serviceEmployee) {
            $service = $serviceEmployee->service;

            if (!$service) {
                return null;
            }

            return [
                'service_id' => $service->id,
                'service_title' => $service->name ?? '',
                'service_category' => $service->category->name ?? '',
                'service_duration_minutes' => $service->duration_min ?? 0,
                'service_staff_count' => ServiceEmployee::where('service_id', $service->id)->count(),
                'service_price' => $service->default_price ?? 0,
            ];
        })->filter()->values();

        // Get avatar URL
        $avatarUrl = $employee->getFirstMediaUrl('profile_image');
        if (empty($avatarUrl)) {
            $avatarUrl = asset(config('app.avatar_base_path') . 'avatar.png');
        }

        // Build response matching the schema
        $responseData = [
            'staff_id' => $employee->id,
            'staff_name' => $employee->full_name ?? $employee->name ?? '',
            'staff_email' => $employee->email ?? '',
            'staff_wallet_balance' => (float) $walletBalance,
            'staff_status' => $employee->status == 1,
            'staff_verified' => !is_null($employee->email_verified_at),
            'staff_avatar_url' => $avatarUrl,
            'rating_star' => $employee->rating->avg('rating'),
            'facebook_link' => $employee->profile->facebook_link ?? null,
            'instagram_link' => $employee->profile->instagram_link ?? null,
            'twitter_link' => $employee->profile->twitter_link ?? null,
            'dribbble_link' => $employee->profile->dribbble_link ?? null,
            'is_banned' => $employee->is_banned == 1 ? true : false,
            'is_manager' => $employee->is_manager == 1 ? true : false,
            'service_data' => $serviceData->toArray(),
        ];

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => 'Staff detail fetched successfully.'
        ], 200);
    }


    public function saveRating(Request $request)
    {
        $user = auth()->user();
        $rating_data = $request->all();
        $rating_data['user_id'] = $user->id;
        $result = EmployeeRating::updateOrCreate(['id' => $request->id], $rating_data);

        $message = __('employee.rating_update');
        if ($result->wasRecentlyCreated) {
            $message = __('employee.rating_add');
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function deleteRating(Request $request)
    {
        $user = auth()->user();
        $rating = EmployeeRating::where('id', $request->id)->where('user_id', $user->id)->first();
        if ($rating == null) {
            $message = __('employee.rating_notfound');

            return response()->json(['status' => false, 'message' => $message]);
        }
        $message = __('employee.rating_delete');
        $rating->delete();

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function getRating(Request $request)
    {
        $employee_id = $request->employee_id;
        $perPage = $request->input('per_page');

        if (! empty($request->branch_id)) {
            $branch_employee = BranchEmployee::where('branch_id', $request->branch_id)->pluck('employee_id');
            $reviewsQuery = EmployeeRating::whereIn('employee_id', $branch_employee)->orderBy('updated_at', 'desc');
        } else {
            $reviewsQuery = EmployeeRating::where('employee_id', $employee_id)->orderBy('updated_at', 'desc');
        }

        if ($perPage === 'all') {
            $reviews = $reviewsQuery->get();
        } else {
            $reviews = $reviewsQuery->paginate($perPage);
        }
        $review = EmployeeReviewResource::collection($reviews);

        return response()->json([
            'status' => true,
            'data' => $review,
            'message' => __('employee.review_list'),
        ], 200);
    }

    public function storeStaff(EmployeeRequest $request)
    {
        $manager = auth()->user();

        $branchIds = $manager->branches->pluck('id');

        if ($branchIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to any branches.'
            ], 403);
        }

        // Automatically use manager's branch from session if not provided
        $branchId = null;
        if ($request->has('branch_id')) {
            // Validate that the branch_id belongs to the manager's branches
            if (!$branchIds->contains($request->branch_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this branch.'
                ], 403);
            }
            $branchId = $request->branch_id;
        } else {
            // Use the first branch from manager's branches
            $branchId = $branchIds->first();
        }

        // Map API field names to database field names
        $data = $request->all();

        if ($request->has('show_in_calender') || $request->has('show_in_calendar')) {
            $showInCalendar = $request->has('show_in_calender')
                ? $request->boolean('show_in_calender')
                : $request->boolean('show_in_calendar');
            $data['show_in_calender'] = $showInCalendar ? true : false;
        } else {
            $data['show_in_calender'] = true;
        }

        // Map phone_number to mobile
        if ($request->has('phone_number')) {
            $data['mobile'] = $request->phone_number;
            unset($data['phone_number']);
        }

        // Map Gender to gender
        if ($request->has('Gender')) {
            $data['gender'] = $request->Gender;
            unset($data['Gender']);
        }

        // Map Expert to expert (will be handled in profile)
        // Map select_commision to commission_id (will be handled later)
        // Map services to service_id (will be handled later)

        // Remove fields that shouldn't be in user table
        unset($data['confirm_password']);
        unset($data['services']);
        unset($data['select_commision']);
        unset($data['Expert']);
        unset($data['about_self']);
        unset($data['profile_image']);

        $data['password'] = Hash::make($data['password']);

        // Always verify employee email by default
        $data = Arr::add($data, 'email_verified_at', Carbon::now());
        $data['status'] = 1;

        $employee = User::create($data);

        // Create wallet for the employee
        if ($employee) {
            $wallet = [
                'title' => $employee->first_name . ' ' . $employee->last_name,
                'user_id' => $employee->id,
                'amount' => 0,
            ];
            Wallet::create($wallet);
        }

        // Create or update profile
        $profile = [
            'about_self' => $request->about_self ?? $request->input('about_self') ?? null,
            'expert' => $request->Expert ?? $request->expert ?? null,
            'facebook_link' => $request->facebook_link ?? null,
            'instagram_link' => $request->instagram_link ?? null,
            'twitter_link' => $request->twitter_link ?? null,
            'dribbble_link' => $request->dribbble_link ?? null,
            'show_in_calender' => $request->has('show_in_calender') || $request->has('show_in_calendar')
                ? ($request->has('show_in_calender') ? $request->boolean('show_in_calender') : $request->boolean('show_in_calendar'))
                : 1,
        ];

        $employee->profile()->updateOrCreate([], $profile);

        // Handle profile image
        if ($request->has('profile_image') && $request->file('profile_image')) {
            storeMediaFile($employee, $request->file('profile_image'), 'profile_image');
        }

        // Assign employee role
        $roles = ['employee'];
        if ($request->is_manager) {
            array_push($roles, 'manager');
        }
        $employee->syncRoles($roles);

        // Ensure employee has default permissions
        if (in_array('employee', $roles)) {
            \App\Helpers\AuthHelper::ensureEmployeeDefaultPermissions($employee);
        }

        // Clear cache
        Artisan::call('cache:clear');

        // Assign to branch (automatically from manager's session)
        if ($branchId) {
            $branch_data = [
                'employee_id' => $employee->id,
                'branch_id' => $branchId,
            ];
            BranchEmployee::create($branch_data);
        }

        // Assign services (handle both 'services' array and 'service_id')
        $services = null;
        if ($request->has('services') && !empty($request->services)) {
            $services = is_array($request->services) ? $request->services : (is_string($request->services) ? json_decode($request->services, true) : []);
        } elseif ($request->has('service_id') && $request->service_id !== null) {
            $services = is_array($request->service_id) ? $request->service_id : explode(',', $request->service_id);
        }

        if ($services && !empty($services)) {
            foreach ($services as $serviceId) {
                if (!empty($serviceId)) {
                    $service_data = [
                        'employee_id' => $employee->id,
                        'service_id' => $serviceId,
                    ];
                    ServiceEmployee::create($service_data);
                }
            }
        }

        // Assign commission if provided (handle both 'select_commision' and 'commission_id')
        $commissionId = $request->select_commision ?? $request->commission_id ?? null;
        if ($commissionId) {
            $commission_data = [
                'employee_id' => $employee->id,
                'commission_id' => $commissionId,
            ];
            EmployeeCommission::updateOrCreate($commission_data, $commission_data);
        }

        // Load relationships for response
        $employee->load(['media', 'branches', 'services']);
        $employee->loadCount('services');

        return response()->json([
            'status' => true,
            'data' => new EmployeeResource($employee),
            'message' => 'Staff created successfully.'
        ], 201);
    }

    public function updateStaff(EmployeeRequest $request, $id)
    {
        $manager = auth()->user();
        $branchIds = $manager->branches->pluck('id');
        if ($branchIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to any branches.'
            ], 403);
        }
        // dd($request->all());

        $employee = User::role('employee')->findOrFail($id);

        $employeeBranchIds = $employee->branches->pluck('branch_id');
        $hasAccess = $employeeBranchIds->intersect($branchIds)->isNotEmpty();

        // If manager wants to move staff into one of their branches, allow
        if (!$hasAccess && $request->filled('branch_id') && $branchIds->contains($request->branch_id)) {
            $hasAccess = true;
        }

        // If still no access and manager didn't provide branch_id, default to first managed branch
        if (!$hasAccess && !$request->filled('branch_id') && $branchIds->count() > 0) {
            $request->merge(['branch_id' => $branchIds->first()]);
            $hasAccess = true;
        }

        if (!$hasAccess) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to edit this staff member.'
            ], 403);
        }

        if ($request->has('branch_id')) {
            if (!$branchIds->contains($request->branch_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'You do not have access to this branch.'
                ], 403);
            }
        }

        // Map API field names to database field names
        $request_data = $request->except(['profile_image', 'confirm_password', 'services', 'select_commision', 'Expert', 'about_self']);

        // Map phone_number to mobile
        if ($request->has('phone_number')) {
            $request_data['mobile'] = $request->phone_number;
        }

        // Map Gender to gender
        if ($request->has('Gender')) {
            $request_data['gender'] = $request->Gender;
        }

        // Handle password update
        if (isset($request->password) && $request->password !== 'undefined' && !empty($request->password)) {
            $request_data['password'] = Hash::make($request_data['password']);
        } else {
            unset($request_data['password']);
        }

        // Ensure status is set
        if ($request->has('status')) {
            $request_data['status'] = $request->status ? 1 : 0;
        } else {
            $request_data['status'] = $employee->status;
        }

        if ($request->has('show_in_calender') || $request->has('show_in_calendar')) {
            $showInCalendar = $request->has('show_in_calender')
                ? $request->boolean('show_in_calender')
                : $request->boolean('show_in_calendar');
            $request_data['show_in_calender'] = $showInCalendar ? 1 : 0;
        }

        $employee->update($request_data);

        // Update profile
        $profile = [
            'about_self' => $request->about_self ?? $request->input('about_self') ?? null,
            'expert' => $request->Expert ?? $request->expert ?? null,
            'facebook_link' => $request->facebook_link ?? null,
            'instagram_link' => $request->instagram_link ?? null,
            'twitter_link' => $request->twitter_link ?? null,
            'dribbble_link' => $request->dribbble_link ?? null,
            'show_in_calendar' => $request->has('show_in_calender') || $request->has('show_in_calendar')
                ? ($request->has('show_in_calender') ? $request->boolean('show_in_calender') : $request->boolean('show_in_calendar'))
                : 1,
        ];

        $employee->profile()->updateOrCreate([], $profile);

        // Handle profile image
        if ($request->has('profile_image') && $request->file('profile_image')) {
            storeMediaFile($employee, $request->file('profile_image'), 'profile_image');
        }

        // Update roles
        $roles = ['employee'];
        if ($request->is_manager) {
            array_push($roles, 'manager');
        }
        $employee->syncRoles($roles);

        // Clear cache
        Artisan::call('cache:clear');

        // Update branch assignment only when a non-empty branch_id is provided
        if ($request->filled('branch_id')) {
            // Remove existing branch assignments for this employee
            BranchEmployee::where('employee_id', $id);

            // Add new branch assignment
            $branch_data = [
                'employee_id' => $id,
                'branch_id' => $request->branch_id,
            ];
            BranchEmployee::create($branch_data);
        }

        // Update service assignments (handle both 'services' array and 'service_id')
        if ($request->has('services') || $request->has('service_id')) {
            // Remove existing service assignments
            ServiceEmployee::where('employee_id', $id);

            $services = null;
            if ($request->has('services') && !empty($request->services)) {
                $services = is_array($request->services) ? $request->services : (is_string($request->services) ? json_decode($request->services, true) : []);
            } elseif ($request->has('service_id') && $request->service_id !== null) {
                $services = is_array($request->service_id) ? $request->service_id : explode(',', $request->service_id);
            }

            if ($services && !empty($services)) {
                foreach ($services as $serviceId) {
                    if (!empty($serviceId)) {
                        $service_data = [
                            'employee_id' => $id,
                            'service_id' => $serviceId,
                        ];
                        ServiceEmployee::create($service_data);
                    }
                }
            }
        }

        // Update commission if provided (handle both 'select_commision' and 'commission_id')
        if ($request->has('select_commision') || $request->has('commission_id')) {
            $commissionId = $request->select_commision ?? $request->commission_id ?? null;
            if ($commissionId) {
                $commission_data = [
                    'employee_id' => $id,
                    'commission_id' => $commissionId,
                ];
                EmployeeCommission::updateOrCreate($commission_data, $commission_data);
            } else {
                EmployeeCommission::where('employee_id', $id);
            }
        }

        // Load relationships for response
        $employee->load(['media', 'branches', 'services']);
        $employee->loadCount('services');

        return response()->json([
            'status' => true,
            'data' => new EmployeeResource($employee),
            'message' => 'Staff updated successfully.'
        ], 200);
    }

    /**
     * Delete staff (manager scoped)
     */
    public function destroyStaff($id)
    {
        $manager = auth()->user();
        $branchIds = $manager->branches->pluck('id');

        if ($branchIds->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to any branches.'
            ], 403);
        }

        $employee = User::role('employee')->with('branches')->findOrFail($id);

        $employeeBranchIds = $employee->branches->pluck('id');
        $hasAccess = $employeeBranchIds->intersect($branchIds)->isNotEmpty();

        if (!$hasAccess) {
            return response()->json([
                'status' => false,
                'message' => 'You do not have access to delete this staff member.'
            ], 403);
        }

        // Clean up related records
        BranchEmployee::where('employee_id', $id)->delete();
        ServiceEmployee::where('employee_id', $id)->delete();
        EmployeeCommission::where('employee_id', $id)->delete();

        $employee->delete();

        Artisan::call('cache:clear');

        return response()->json([
            'status' => true,
            'message' => 'Staff deleted successfully.'
        ], 200);
    }
}
