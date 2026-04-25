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
use Modules\Booking\Models\BookingService;
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

        $requestDate = $request->input('date');
        $slotDurationSetting = setting('slot_duration') ?? '00:15';
        $employeeIdInt = (int) $employee_id;

        for ($day = 1; $day <= 7; $day++) {
            $date = $startOfWeek->copy()->addDays($day - 1)->format('Y-m-d');

            $rawSlot = $branchSlotByDay->get($day, [
                'day' => $day,
                'start_time' => null,
                'end_time' => null,
                'is_holiday' => 1,
                'breaks' => [],
            ]);
            if (! is_array($rawSlot)) {
                $slot = $rawSlot->toArray();
            } else {
                $slot = $rawSlot;
            }

            $availableSlotsPayload = null;
            $isRequestDayInWeek = false;
            if (! empty($requestDate) && $employeeIdInt > 0) {
                $target = Carbon::parse($requestDate);
                if ($target->dayOfWeekIso === $day) {
                    $isRequestDayInWeek = true;
                    $availableSlotsPayload = $this->buildAvailableSlotsForDate(
                        $requestDate,
                        $slot,
                        (int) $branch_id,
                        $employeeIdInt,
                        (int) $serviceDuration,
                        (string) $slotDurationSetting
                    );
                }
            }

            // Za dan u tjednu koji odgovara ?date= klijentu — uvijek niz (prazan = nema slobodnih);
            // klijent rješenjem lažno slobodnih oslanja se na taj niz, ne na null.
            $availableSlotsOut = $availableSlotsPayload;
            if ($isRequestDayInWeek) {
                $availableSlotsOut = is_array($availableSlotsPayload) ? $availableSlotsPayload : [];
            }

            $workingDays->push([
                'day' => $slot['day'] ?? $day,
                'start_time' => $slot['start_time'] ?? null,
                'end_time' => $slot['end_time'] ?? null,
                'is_festival_holiday' => $holidayDaysInWeek->contains($day) ? 1 : 0,
                'is_day_off' => ($slot['is_holiday'] ?? 1) == 1 ? 1 : 0,
                'date' => $date,
                'breaks' => $slot['breaks'] ?? [],
                'available_slots' => $availableSlotsOut,
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
        $employeeId = (int) $request->input('employee_id', 0);
        $branchId = (int) $request->input('branch_id', 0);
        $startDateTime = $request->input('start_date_time');
        $newDuration = (int) $request->input('service_duration', 0);
        if ($newDuration <= 0) {
            $newDuration = $this->parseSlotDurationStringToMinutes((string) (setting('slot_duration') ?? '00:15'), 15);
        }

        if ($employeeId <= 0 || $branchId <= 0 || empty($startDateTime)) {
            return response()->json(['status' => false, 'message' => __('branch.invalid_action')]);
        }

        $newStart = Carbon::parse($startDateTime);
        $newEnd = $newStart->copy()->addMinutes($newDuration);

        $rows = BookingService::query()
            ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
            ->where('bookings.branch_id', $branchId)
            ->where('booking_services.employee_id', $employeeId)
            ->whereDate('booking_services.start_date_time', $newStart->toDateString())
            ->where('bookings.status', '!=', 'cancelled')
            ->get(['booking_services.start_date_time', 'booking_services.duration_min']);

        $stepFallback = $this->parseSlotDurationStringToMinutes((string) (setting('slot_duration') ?? '00:15'), 15);
        foreach ($rows as $row) {
            $s = Carbon::parse($row->start_date_time);
            $d = (int) ($row->duration_min ?? 0);
            if ($d <= 0) {
                $d = $stepFallback;
            }
            $e = $s->copy()->addMinutes($d);
            if ($newStart->lt($e) && $newEnd->gt($s)) {
                return response()->json(['status' => false, 'message' => __('branch.branch_reserved')]);
            }
        }

        return response()->json(['status' => true, 'message' => '']);
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

    /**
     * Start times where a booking of length $serviceDurationMin fits in working hours, outside breaks, and not overlapping existing bookings.
     */
    protected function buildAvailableSlotsForDate(
        string $dateYmd,
        array $slot,
        int $branchId,
        int $employeeId,
        int $serviceDurationRequest,
        string $slotDurationSetting
    ): array {
        $stepMinutes = $this->parseSlotDurationStringToMinutes($slotDurationSetting, 15);
        $serviceDurationMin = (int) $serviceDurationRequest;
        if ($serviceDurationMin <= 0) {
            $serviceDurationMin = $stepMinutes;
        }

        $startTimeStr = $slot['start_time'] ?? null;
        $endTimeStr = $slot['end_time'] ?? null;
        if (empty($startTimeStr) || empty($endTimeStr) || (int) ($slot['is_holiday'] ?? 0) == 1) {
            return [];
        }

        $day = Carbon::parse($dateYmd)->startOfDay();
        $dayPrefix = $day->format('Y-m-d');
        $start = Carbon::parse($dayPrefix.' '.trim($startTimeStr));
        $end = Carbon::parse($dayPrefix.' '.trim($endTimeStr));
        if ($end->lte($start)) {
            return [];
        }

        $rows = BookingService::query()
            ->join('bookings', 'booking_services.booking_id', '=', 'bookings.id')
            ->where('bookings.branch_id', $branchId)
            ->where('booking_services.employee_id', $employeeId)
            ->whereDate('booking_services.start_date_time', $dateYmd)
            ->where('bookings.status', '!=', 'cancelled')
            ->get(['booking_services.start_date_time', 'booking_services.duration_min']);

        $busy = [];
        foreach ($rows as $row) {
            $s = Carbon::parse($row->start_date_time);
            $d = (int) ($row->duration_min ?? 0);
            if ($d <= 0) {
                $d = $stepMinutes;
            }
            $busy[] = [$s, $s->copy()->addMinutes($d)];
        }

        $breaks = $slot['breaks'] ?? [];
        $out = [];
        $cursor = $start->copy();

        while ($cursor->copy()->addMinutes($serviceDurationMin)->lte($end)) {
            $slotStart = $cursor->copy();
            $slotEnd = $cursor->copy()->addMinutes($serviceDurationMin);

            if ($slotEnd->gt($end)) {
                break;
            }

            $inBreak = false;
            foreach ((array) $breaks as $br) {
                if (! is_array($br)) {
                    continue;
                }
                $bs = $br['start_break'] ?? null;
                $be = $br['end_break'] ?? null;
                if (empty($bs) || empty($be)) {
                    continue;
                }
                $breakStart = Carbon::parse($dayPrefix.' '.trim($bs));
                $breakEnd = Carbon::parse($dayPrefix.' '.trim($be));
                if ($slotStart->lt($breakEnd) && $slotEnd->gt($breakStart)) {
                    $inBreak = true;
                    break;
                }
            }
            if ($inBreak) {
                $cursor->addMinutes($stepMinutes);
                continue;
            }

            $overlaps = false;
            foreach ($busy as $b) {
                /** @var Carbon $b0 */
                $b0 = $b[0];
                $b1 = $b[1];
                if ($slotStart->lt($b1) && $slotEnd->gt($b0)) {
                    $overlaps = true;
                    break;
                }
            }
            if (! $overlaps) {
                $out[] = [
                    'value' => $slotStart->format('Y-m-d H:i:s'),
                    'label' => $slotStart->format('H:i'),
                    'disabled' => false,
                ];
            }
            $cursor->addMinutes($stepMinutes);
        }

        return $out;
    }

    protected function parseSlotDurationStringToMinutes(string $slotDurationSetting, int $default): int
    {
        if ($slotDurationSetting === '' || $slotDurationSetting === '0' || $slotDurationSetting === '00:00') {
            return $default;
        }
        $parts = array_map('intval', array_pad(explode(':', $slotDurationSetting), 3, 0));
        $h = $parts[0] ?? 0;
        $m = $parts[1] ?? 0;
        $s = $parts[2] ?? 0;
        $total = $h * 60 + $m + (int) round($s / 60);
        if ($total <= 0) {
            return $default;
        }

        return $total;
    }
}
