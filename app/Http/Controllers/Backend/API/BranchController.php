<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchDetailResource;
use App\Http\Resources\BranchEmployeeResource;
use App\Http\Resources\BranchGalleryResource;
use App\Http\Resources\BranchResource;
use App\Http\Resources\ServiceResource;
use App\Models\Branch;
use App\Models\BranchGallery;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Employee\Models\BranchEmployee;
use Modules\Employee\Models\EmployeeRating;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Employee\Transformers\EmployeeReviewResource;
use Modules\Holiday\Models\Holiday;
use Modules\Service\Models\ServiceBranches;
use Modules\Tax\Models\Tax;
use Carbon\Carbon;
use Modules\Booking\Trait\BookingTrait;

class BranchController extends Controller
{
    use BookingTrait;

    public function branchList(Request $request)
    {
        $user = auth('sanctum')->user();
        $perPage = $request->input('per_page', 10);

        $branches = Branch::with(['businessHours', 'address', 'media', 'holidays'])
            ->where('status', 1)
            ->when($user && $user->hasRole('manager'), function ($query) use ($user) {
                $query->whereHas('branchEmployee', function ($q) use ($user) {
                    $q->where('employee_id', $user->id);
                });
            })
            ->paginate($perPage);

        if ($branches->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => __('branch.branch_isempty')
            ]);
        }

        return response()->json([
            'status' => true,
            'data' => BranchResource::collection($branches),
            'message' => __('branch.branch_list'),
        ], 200);
    }



    public function branchDetails(Request $request)
    {
        $branchId = $request->branch_id;
        $branch = Branch::with('businessHours', 'media', 'gallerys', 'holidays', 'address')->find($branchId);

        $employeeIds = BranchEmployee::where('branch_id', $branchId)
            ->distinct()
            ->pluck('employee_id');

        $averageRating = EmployeeRating::whereIn('employee_id', $employeeIds)->avg('rating');

        $branch['average_rating'] = $averageRating;

        $branch['total_review'] = EmployeeRating::whereIn('employee_id', $employeeIds)->count();

        if ($branch) {
            $branchDetails = new BranchDetailResource($branch);

            return response()->json(['status' => true, 'data' => $branchDetails, 'message' => __('branch.branch_details')]);
        } else {
            return response()->json(['status' => false, 'message' => __('branch.branch_notfound')]);
        }
    }

    public function branchService(Request $request)
    {
        $branchId = $request->input('branch_id');

        // $branchServices = ServiceBranches::where('branch_id', $branchId)->get();
        $branch = Branch::find($branchId);

        if (! $branch) {
            return response()->json(['status' => true, 'message' => __('branch.branch_noservice')]);
        }

        $services = $branch->services()->with('branches')->get();
        foreach ($services as $service) {
            $service->resolveBranchSpecificData($branchId);
        }

        $serviceDetails = ServiceResource::collection($services);

        return response()->json(['status' => true, 'data' => $serviceDetails, 'message' => __('branch.branch_service')]);
    }

    public function branchReviews(Request $request)
    {
        $branchId = $request->branch_id;

        $perPage = $request->input('per_page', 10);

        $employeeIds = BranchEmployee::where('branch_id', $branchId)
            ->distinct()
            ->pluck('employee_id');

        $reviews = EmployeeRating::with('user')
            ->whereIn('employee_id', $employeeIds);

        $reviews = $reviews->orderBy('updated_at', 'desc')->paginate($perPage);
        $review = EmployeeReviewResource::collection($reviews);

        return response()->json([
            'status' => true,
            'data' => $review,
            'message' => __('branch.branch_review'),
        ]);
    }

    public function branchEmployee(Request $request)
    {
        $branchId = $request->input('branch_id');

        $perPage = $request->input('per_page', 10);

        $branchEmployees = BranchEmployee::where('branch_id', $branchId)->pluck('employee_id');
        $employee = User::with(['media', 'branches', 'services'])->where('status', 1);
        $employee = $employee->whereIn('id', $branchEmployees);
        $employee = $employee->paginate($perPage);
        $responseData = EmployeeResource::collection($employee);

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('employee.employee_list'),
        ], 200);

        return response()->json(['status' => true, 'data' => BranchEmployeeResource::collection($employeeDetails), 'message' => __('branch.branch_employee')]);
    }

    public function branchGallery(Request $request)
    {
        $branchId = $request->input('branch_id');

        $branchGalleries = BranchGallery::where('branch_id', $branchId)->get();

        if ($branchGalleries->isEmpty()) {
            return response()->json(['status' => true, 'message' => __('branch.branch_nogallery')]);
        }

        $galleryData = BranchGalleryResource::collection($branchGalleries);

        return response()->json(['status' => true, 'data' => $galleryData, 'message' => __('branch.branch_gallery')]);
    }

    public function assign_list($id)
    {
        $branch_user = BranchEmployee::with('employee')->where('branch_id', $id)->get();
        $branch_user = $branch_user->each(function ($data) {
            $data['name'] = $data->employee->name;
            $data['avatar'] = $data->employee->avatar;

            return $data;
        });

        return response()->json(['status' => true, 'data' => $branch_user]);
    }

    public function assign_update($id, Request $request)
    {
        BranchEmployee::where('branch_id', $id)->delete();
        foreach ($request->users as $key => $value) {
            BranchEmployee::create([
                'branch_id' => $id,
                'employee_id' => $value['employee_id'],
                'is_primary' => $value['is_primary'],
            ]);
        }

        return response()->json(['status' => true, 'message' => __('branch.branch_update')]);
    }

    public function branchConfig(Request $request)
    {
        $branch_id = $request->branch_id;
        $employee_id = $request->employee_id;
        $serviceDuration = $request->service_duration ?? 0;

        $branch_slot = BussinessHour::where('branch_id', $branch_id)->get();
        $holidays = Holiday::where('branch_id', $branch_id)->get();
        $is_festival_holiday = 0;
        $current_date = Carbon::now()->format('Y-m-d');

        // Check if today is a holiday
        if ($holidays->contains(function ($holiday) use ($current_date) {
            return $holiday->date === $current_date;
        })) {
            $is_festival_holiday = 1;
        }

        // Get start and end of current week (Monday to Sunday)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY);

        // Get holiday days in current week, convert Sunday (0) to 7
        $holidayDaysInWeek = $holidays->filter(function ($holiday) use ($startOfWeek, $endOfWeek) {
            return Carbon::parse($holiday->date)->between($startOfWeek, $endOfWeek);
        })->map(function ($holiday) {
            $dayOfWeek = Carbon::parse($holiday->date)->dayOfWeek;
            return $dayOfWeek === 0 ? 7 : $dayOfWeek; // Convert Sunday (0) to 7
        })->unique()->values();

        // Key branch slots by day of week, convert Sunday (0) to 7
        $branchSlotByDay = collect($branch_slot)->keyBy(function ($slot) {
            $day = is_numeric($slot['day']) ? intval($slot['day']) : Carbon::parse($slot['day'])->dayOfWeek;
            return $day === 0 ? 7 : $day;
        });

        // Prepare working days (Monday to Sunday → 1 to 7)
        $workingDays = collect();

        for ($day = 1; $day <= 7; $day++) {
            $date = $startOfWeek->copy()->addDays($day - 1)->format('Y-m-d');

            $slot = $branchSlotByDay->get($day, [
                'day' => $day,
                'start_time' => null,
                'end_time' => null,
                'is_holiday' => 1,
                'breaks' => [],
            ]);

            $workingDays->push([
                'day' => $slot['day'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                // 'is_holiday' => $slot['is_holiday'],
                'is_festival_holiday' => $holidayDaysInWeek->contains($day) ? 1 : 0,
                'is_day_off' => $slot['is_holiday'] == 1 ? 1 : 0,
                'date' => $date,
                'breaks' => $slot['breaks'],
            ]);
        }

        $branch_tax = Tax::active()
            ->whereNull('module_type')
            ->orWhere('module_type', 'services')
            ->where('status', 1)->get()
            ->map(function ($tax) {
                return [
                    'name' => $tax->title,
                    'type' => $tax->type,
                    'percent' => $tax->type == 'percent' ? $tax->value : 0,
                    'tax_amount' => $tax->type != 'percent' ? $tax->value : 0,
                ];
            })
            ->toArray();
        $tax = $branch_tax;

        $today = Carbon::now()->format('Y-m-d');

        $futureHolidays = Holiday::where('branch_id', $branch_id)
            ->whereDate('date', '>=', $today)
            ->orderBy('date')
            ->pluck('date')
            ->values()
            ->toArray();



        $response = [
            'slot' => $workingDays,
            'tax' => $tax,
            'slot_duration' => setting('slot_duration'),
            'is_festival_holiday' => $is_festival_holiday,
            'holiday_days' => $futureHolidays,
        ];

        return response()->json(['status' => true, 'data' => $response], 200);
    }

    public function verifySlot(Request $request)
    {
        $employee_id = $request->employee_id;
        $start_date_time = $request->start_date_time;

        $booking = Booking::with('bookingService')->where('start_date_time', $start_date_time)
            ->whereHas('bookingService', function ($query) use ($employee_id) {
                $query->where('employee_id', $employee_id);
            });
        if ($booking->count() > 0) {
            return response()->json(['status' => false, 'message' => __('branch.branch_reserved')]);
        } else {
            return response()->json(['status' => true, 'message' => '']);
        }
    }
    public function managerReviewsList(Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
                'data' => [],
            ], 401);
        }

        $limit = (int) $request->input('limit', 10);
        if ($limit <= 0) {
            $limit = 10;
        }

        $page = (int) $request->input('page', 1);
        if ($page <= 0) {
            $page = 1;
        }

        $employeeIdsQuery = BranchEmployee::query();

        if ($user->hasRole('manager')) {
            $employeeIdsQuery->whereIn('branch_id', function ($q) use ($user) {
                $q->select('branch_id')
                    ->from('branch_employee')
                    ->where('employee_id', $user->id);
            });
        } else {
            $employeeIdsQuery->where('employee_id', $user->id);
        }

        $employeeIds = $employeeIdsQuery->distinct()->pluck('employee_id');

        $paginator = EmployeeRating::query()
            ->with(['user', 'employee'])
            ->whereIn('employee_id', $employeeIds)
            ->orderBy('updated_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $data = $paginator->getCollection()->map(function ($review) {
            $reviewer = $review->user;
            $staff = $review->employee;

            return [
                'review_id' => (int) $review->id,
                'reviewer_name' => $reviewer->full_name ?? $reviewer->name ?? default_user_name(),
                'reviewer_profile_image' => $reviewer->profile_image ?? default_user_avatar(),
                'reviewer_is_verified' => !is_null(optional($reviewer)->email_verified_at),
                'rating' => (float) ($review->rating ?? 0),
                'review_date' => $review->created_at ? Carbon::parse($review->created_at)->format('d M, y') : '',
                'review_comment' => (string) ($review->review_msg ?? ''),
                'review_for' => [
                    'staff_id' => (int) ($staff->id ?? $review->employee_id),
                    'staff_name' => $staff->full_name ?? $staff->name ?? '',
                    'staff_profile_image' => $staff->profile_image ?? default_user_avatar(),
                    'staff_is_verified' => !is_null(optional($staff)->email_verified_at),
                ],
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Reviews list fetched successfully.',
            'data' => $data,
        ], 200);
    }
}
