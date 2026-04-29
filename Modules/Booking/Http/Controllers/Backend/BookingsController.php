<?php

namespace Modules\Booking\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Branch;
use Modules\Employee\Models\BranchEmployee;
use Carbon\Carbon;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Modules\Booking\Http\Requests\BookingRequest;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingProduct;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\UserPackage;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Booking\Trait\BookingTrait;
use Modules\Booking\Trait\PaymentTrait;
use Modules\Booking\Transformers\BookingResource;
use Modules\Constant\Models\Constant;
use Modules\Product\Trait\ProductTrait;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceEmployee;
use Modules\Tax\Models\Tax;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Modules\Promotion\Models\UserCouponRedeem;
use Modules\Package\Models\PackageService;
use Modules\Package\Models\UserPackageRedeem;
use Modules\Package\Models\UserPackageServices;

class BookingsController extends Controller
{
    use Authorizable;
    use BookingTrait;
    use PaymentTrait;
    use ProductTrait;

    protected string $exportClass = '\App\Exports\BookingsExport';

    public function __construct()
    {
        // Page Title
        $this->module_title = 'booking.title';

        // module name
        $this->module_name = 'bookings';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        view()->share([
            'module_title' => $this->module_title,
            'module_name' => $this->module_name,
            'module_icon' => $this->module_icon,
        ]);
        $this->middleware(['permission:view_booking'])->only('index', 'datatable_view');
        // edit method allows both view_booking and edit_booking (handled in method itself, no middleware to allow both)
        $this->middleware(['permission:edit_booking'])->only('update');
        $this->middleware(['permission:add_booking'])->only('store');
        $this->middleware(['permission:delete_booking'])->only('destroy');
    }

    /**
     * Override callAction to allow view_booking permission for edit method
     */
    public function callAction($method, $parameters)
    {
        // For edit method, allow both view_booking and edit_booking permissions
        if ($method === 'edit') {
            $user = auth()->user();
            
            // Admins bypass all checks - use parent callAction
            if ($user && $user->hasRole('admin')) {
                return parent::callAction($method, $parameters);
            }
            
            // Check if user has view_booking or edit_booking permission
            if ($user && ($user->can('view_booking') || $user->can('edit_booking'))) {
                // Skip authorization and call the method directly
                return $this->{$method}(...array_values($parameters));
            }
            
            // If user doesn't have either permission, trigger authorization failure
            abort(403, __('messages.permission_denied'));
        }
        
        // For other methods, use the trait's default behavior
        return parent::callAction($method, $parameters);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $module_action = __('messages.list');

        $statusList = $this->statusList();

        $booking = Booking::find(request()->booking_id);

        $date = $booking->start_date_time ?? date('Y-m-d');

        return view('booking::backend.bookings.index', compact('module_action', 'statusList', 'date'));
    }

    public function statusList()
    {
        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');
        $checkout_sequence = $booking_status->where('name', 'check_in')->first()->sequence ?? 0;
        $bookingColors = Constant::getAllConstant()->where('type', 'BOOKING_STATUS_COLOR');
        $statusList = [];

        foreach ($booking_status as $key => $value) {
            if ($value->name !== 'cancelled') {
                $statusList[$value->name] = [
                    'title' => $value->value,
                    'color_hex' => $bookingColors->where('sub_type', $value->name)->first()->name,
                    'is_disabled' => $value->sequence >= $checkout_sequence,
                ];
                $nextStatus = $booking_status->where('sequence', $value->sequence + 1)->first();
                if ($nextStatus) {
                    $statusList[$value->name]['next_status'] = $nextStatus->name;
                }
            } else {
                $statusList[$value->name] = [
                    'title' => $value->value,
                    'color_hex' => $bookingColors->where('sub_type', $value->name)->first()->name,
                    'is_disabled' => true,
                ];
            }
        }

        return $statusList;
    }

    /**
     * @return Response
     */
    public function index_list(Request $request)
    {
        $date = $request->date;
        $selectedBranchId = $request->selected_session_branch_id;
        $page = $request->page ?? 1;
        $perPage = $request->per_page ?? 6;

        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $employeeId = $filterByEmployee ? $authUser->id : null;

        // My Work: ignore branch scoping
        $selectedBranchId = $isManagerMyWork ? null : $selectedBranchId;

        $data = BookingService::with('booking', 'employee', 'service')
            ->whereHas('booking', function ($q) use ($date, $selectedBranchId) {
                if (!empty($date)) {
                    $q->whereDate('start_date_time', $date);
                }
                $q->where('status', '!=', 'cancelled');
                if (!empty($selectedBranchId)) {
                    $q->where('branch_id', $selectedBranchId);
                }
            })
            ->when($employeeId, function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->get();

        $package = BookingPackages::with('booking', 'employee', 'services')
            ->whereHas('booking', function ($q) use ($date, $selectedBranchId) {
                if (!empty($date)) {
                    $q->whereDate('start_date_time', $date);
                }
                $q->where('status', '!=', 'cancelled');
                if (!empty($selectedBranchId)) {
                    $q->where('branch_id', $selectedBranchId);
                }
            })
            ->when($employeeId, function ($query) use ($employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->get();

        $service_updated = [];
        $statusList = $this->statusList();
        foreach ($data as $key => $value) {
            $duration = $value->duration_min;

            $startTime = $value->start_date_time;

            $endTime = Carbon::parse($startTime)->addMinutes($duration);

            $serviceName = $value->service->name ?? '';

            $customerName = $value->booking->user->full_name ?? 'Anonymous';

            $service_updated[$key] = [
                'id' => $value->booking_id,
                'start' => customDate($startTime, 'Y-m-d H:i'),
                'end' => customDate($endTime, 'Y-m-d H:i'),
                'resourceId' => $value->employee_id,
                'title' => $serviceName,
                'titleHTML' => view('booking::backend.bookings.calender.event', compact('serviceName', 'customerName'))->render(),
                'color' => $statusList[$value->booking->status]['color_hex'],
            ];
            $startTime = $endTime;
        }
        $package_updated = [];
        foreach ($package as $key => $value) {
            $duration = $value->services->sum('duration_min');

            $startTime = $value->booking->start_date_time;

            $endTime = Carbon::parse($startTime)->addMinutes($duration);

            $serviceName = $value->package->name ?? '';

            $customerName = $value->booking->user->full_name ?? 'Anonymous';

            $package_updated[$key] = [
                'id' => $value->booking_id,
                'start' => customDate($startTime, 'Y-m-d H:i'),
                'end' => customDate($endTime, 'Y-m-d H:i'),
                'resourceId' => $value->employee_id,
                'title' => $serviceName,
                'titleHTML' => view('booking::backend.bookings.calender.event', compact('serviceName', 'customerName'))->render(),
                'color' => $statusList[$value->booking->status]['color_hex'],
            ];
            $startTime = $endTime;
        }
        $updated_data = array_merge($service_updated, $package_updated);
        $employeesQuery = User::bookingEmployeesList();
        if ($employeeId) {
            $employeesQuery->where('users.id', $employeeId);
        } elseif (! empty($selectedBranchId)) {
            $employeesQuery->whereHas('branches', function ($q) use ($selectedBranchId) {
                $q->where('branch_id', $selectedBranchId);
            });
        }
        $employees = $employeesQuery->paginate($perPage, ['*'], 'page', $page);
        $resource = [];
        foreach ($employees as $employee) {
            $resource[] = [
                'id' => $employee->id,
                'title' => $employee->full_name,
                'titleHTML' => '<div class="d-flex gap-3 justify-content-center align-items-center py-3"><img src="' . $employee->profile_image . '" class="avatar avatar-40 rounded-pill" alt="employee" />' . $employee->full_name . '</div>',
            ];
        }

        // Get business hours for the selected branch to determine calendar time range
        $slotMinTime = '00:00:00';
        $slotMaxTime = '23:59:59';
        $scrollTime = '09:00:00';
        
        if (!empty($selectedBranchId)) {
            $businessHours = \Modules\BussinessHour\Models\BussinessHour::where('branch_id', $selectedBranchId)
                ->where('is_holiday', 0)
                ->whereNotNull('start_time')
                ->whereNotNull('end_time')
                ->get();
            
            if ($businessHours->isNotEmpty()) {
                // Find the earliest start_time and latest end_time across all working days
                $startTimes = $businessHours->pluck('start_time')->filter()->sort();
                $endTimes = $businessHours->pluck('end_time')->filter()->sort();
                
                if ($startTimes->isNotEmpty()) {
                    $slotMinTime = $startTimes->first();
                }
                if ($endTimes->isNotEmpty()) {
                    $slotMaxTime = $endTimes->last();
                }
                
                // Set scrollTime to the earliest start time or default to 09:00:00
                $scrollTime = $slotMinTime !== '00:00:00' ? $slotMinTime : '09:00:00';
            }
        }

        return response()->json([
            'data' => $updated_data,
            'employees' => $resource,
            'total_count' => $employees->total(),
            'business_hours' => [
                'slotMinTime' => $slotMinTime,
                'slotMaxTime' => $slotMaxTime,
                'scrollTime' => $scrollTime,
            ],
        ]);
    }

    public function services_index_list(Request $request)
    {
        $employee_id = $request->employee_id;
        $branch_id = $request->branch_id;
        \Log::info('services_index_list called', [
            'employee_id' => $employee_id,
            'branch_id' => $branch_id,
            'ip' => $request->ip(),
            'referer' => $request->headers->get('referer'),
            'user_id' => optional(auth()->user())->id,
        ]);
        // If an employee is provided, return only services assigned to
        // that employee and available at the branch (this is the
        // appointment dropdown behaviour you requested). Otherwise,
        // return all services for the branch.

        if (isset($employee_id)) {
            // If branch_id is provided ensure the employee belongs to that branch.
            // This prevents passing an employee id that isn't part of the branch
            // (which would otherwise return an empty or incorrect set).
            if (! empty($branch_id)) {
                $employeeInBranch = BranchEmployee::where('employee_id', $employee_id)
                    ->where('branch_id', $branch_id)
                    ->exists();

                \Log::info('services_index_list: employee branch membership', ['employee_id' => $employee_id, 'branch_id' => $branch_id, 'in_branch' => $employeeInBranch]);

                if (! $employeeInBranch) {
                    // Employee not assigned to this branch — return empty list
                    \Log::info('services_index_list: employee not in branch, returning empty', ['employee_id' => $employee_id, 'branch_id' => $branch_id]);
                    return response()->json([]);
                }
            }

            $employeeServiceIds = ServiceEmployee::where('employee_id', $employee_id)->pluck('service_id')->toArray();

            \Log::info('services_index_list: employee service ids', ['employee_id' => $employee_id, 'service_count' => count($employeeServiceIds), 'service_ids' => $employeeServiceIds]);

            $services = Service::with(['branches' => function ($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            }])
                ->whereIn('id', $employeeServiceIds)
                ->whereHas('category', function ($q) {
                    $q->active();
                })
                ->whereHas('branches', function ($q) use ($branch_id) {
                    $q->where('branch_id', $branch_id);
                })
                ->get();

            $result = $services->map(function ($service) {
                $branchData = $service->branches->first();
                $service_price = $branchData->service_price ?? $service->default_price ?? 0;

                return [
                    'service_id' => $service->id,
                    'service_name' => $service->name,
                    'service_price' => $service_price,
                    'duration_min' => $branchData->duration_min ?? $service->duration_min ?? 0,
                    'image_path' => $service->feature_image ?? null,
                    'category_id' => $service->category_id ?? null,
                    'category_name' => optional($service->category)->name ?? null,
                    'provided_by_employee' => true,

                    // legacy keys
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $service_price,
                ];
            })->values();

            \Log::info('services_index_list: returning services for employee', ['employee_id' => $employee_id, 'count' => $result->count()]);
            return response()->json($result);
        }

        // No employee specified: return all services assigned to the branch
        $branchServices = Service::with(['branches' => function ($q) use ($branch_id) {
            $q->where('branch_id', $branch_id);
        }])
            ->whereHas('category', function ($q) {
                $q->active();
            })
            ->whereHas('branches', function ($q) use ($branch_id) {
                $q->where('branch_id', $branch_id);
            })
            ->get();

        $result = $branchServices->map(function ($service) {
            $branchData = $service->branches->first();
            $service_price = $branchData->service_price ?? $service->default_price ?? 0;

            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'service_price' => $service_price,
                'duration_min' => $branchData->duration_min ?? $service->duration_min ?? 0,
                'image_path' => $service->feature_image ?? null,
                'category_id' => $service->category_id ?? null,
                'category_name' => optional($service->category)->name ?? null,
                'provided_by_employee' => false,

                // legacy keys
                'id' => $service->id,
                'name' => $service->name,
                'price' => $service_price,
            ];
        })->values();

        \Log::info('services_index_list: returning services for branch', ['branch_id' => $branch_id, 'count' => $result->count()]);

        return response()->json($result);
    }

    public function datatable_view(Request $request)
    {
        $module_action = __('messages.list');

        $filter = [
            'status' => $request->status,
        ];

        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');

        $export_import = true;
        $export_columns = [
            [
                'value' => 'date',
                'text' => 'Date',
                'translationKey' => 'export.columns.date',
            ],
            [
                'value' => 'customer',
                'text' => 'Customer Name',
                'translationKey' => 'export.columns.customer',
            ],
            [
                'value' => 'service_amount',
                'text' => 'Amount',
                'translationKey' => 'export.columns.service_amount',
            ],
            [
                'value' => 'service_duration',
                'text' => 'Duration',
                'translationKey' => 'export.columns.service_duration',
            ],
            [
                'value' => 'employee',
                'text' => 'Staff Name',
                'translationKey' => 'export.columns.employee',
            ],
            [
                'value' => 'services',
                'text' => 'Services',
                'translationKey' => 'export.columns.services',
            ],
            [
                'value' => 'status',
                'text' => 'Status',
                'translationKey' => 'export.columns.status',
            ],
            [
                'value' => 'updated_at',
                'text' => 'Updated At',
                'translationKey' => 'export.columns.updated_at',
            ],
        ];

        // Remove employee column from export options for Staff users (employees without manager role)
        $authUser = auth()->user();
        $isEmployee = $authUser && $authUser->hasRole('employee');
        $isManager = $authUser && $authUser->hasRole('manager');
        
        if ($authUser && $isEmployee && !$isManager) {
            $export_columns = array_values(array_filter($export_columns, function ($col) {
                return $col['value'] !== 'employee';
            }));
        }
        $export_url = route('backend.bookings.export');

        return view('booking::backend.bookings.index_datatable', compact('module_action', 'filter', 'booking_status', 'export_import', 'export_columns', 'export_url'));
    }

    public function index_data(Datatables $datatable, Request $request)
    {
        $module_name = $this->module_name;

        $query = Booking::with('branch', 'user', 'services', 'mainServices', 'payment', 'bookingPackages', 'bookedPackageService', 'userPackageServices');

        $filter = $request->filter;

        $isManager = auth()->user()->hasRole('manager');
        $isEmployee = auth()->user()->hasRole('employee');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        // Managers in My Work, or employees, filter by their own assignments
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $employeeId = $filterByEmployee ? auth()->id() : null;

        // Branch scoping: ignore branch if My Work is active
        $selectedBranchId = $isManagerMyWork ? null : ($request->selected_session_branch_id ?? null);

        // If employee/my-work is active, restrict to bookings assigned to that employee
        if ($filterByEmployee && $employeeId) {
            $query->where(function ($q) use ($employeeId) {
                $q->whereHas('services', function ($sub) use ($employeeId) {
                    $sub->where('employee_id', $employeeId);
                })
                    ->orWhereHas('bookingPackages', function ($sub) use ($employeeId) {
                        $sub->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('products', function ($sub) use ($employeeId) {
                        $sub->where('employee_id', $employeeId);
                    });
            });
        }

        // Scope by selected branch from session (set by middleware) unless an explicit filter is provided; ignored in My Work
        $hasBranchSelected = false;
        if (! $isManagerMyWork) {
            if (isset($filter) && isset($filter['branch_id']) && $filter['branch_id'] !== '') {
                $query->where('branch_id', $filter['branch_id']);
                $hasBranchSelected = true;
            } elseif (!empty($selectedBranchId)) {
                $query->where('branch_id', $selectedBranchId);
                $hasBranchSelected = true;
            }
        }

        if (isset($filter)) {
            if (isset($filter['column_status']) && $filter['column_status'] !== '') {
                $query->where('status', $filter['column_status']);
            }
            if (isset($filter['booking_date']) && $filter['booking_date'] !== '') {
                try {
                    $dates = $this->splitFlatpickrRange($filter['booking_date']);
                    $startDate = $dates[0] ?? null;
                    $endDate = isset($dates[1]) ? $dates[1] : ($dates[0] ?? null);
                    if ($startDate && $endDate) {
                        $query->whereDate('start_date_time', '>=', $startDate);
                        $query->whereDate('start_date_time', '<=', $endDate);
                    }
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            }
            if (isset($filter['user_id']) && $filter['user_id'] !== '') {
                $query->where('user_id', $filter['user_id']);
            }
            if (isset($filter['emploee_id']) && $filter['emploee_id'] !== '') {
                $query->whereHas('services', function ($q) use ($filter) {
                    $q->where('employee_id', $filter['emploee_id']);
                });
            }
            if (isset($filter['service_id']) && $filter['service_id'] !== '') {
                $serviceIds = is_array($filter['service_id']) ? $filter['service_id'] : [$filter['service_id']];
                $query->whereHas('services', function ($q) use ($serviceIds) {
                    $q->whereIn('service_id', $serviceIds);
                });
            }
            if (isset($filter['package_id']) && $filter['package_id'] !== '') {
                $packageIds = is_array($filter['package_id']) ? $filter['package_id'] : [$filter['package_id']];
                $query->whereHas('bookingPackages', function ($q) use ($packageIds) {
                    $q->whereIn('package_id', $packageIds);
                });
            }
            if (isset($filter['payment_status']) && $filter['payment_status'] !== '') {
                $query->whereHas('payment', function ($q) use ($filter) {
                    $q->where('payment_status', $filter['payment_status']);
                });
            }
            if (isset($filter['payment_method']) && $filter['payment_method'] !== '') {
                $query->whereHas('payment', function ($q) use ($filter) {
                    $q->where('payment_method', $filter['payment_method']);
                });
            }
        }

        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS')->where('name', '!=', 'completed');
        $booking_colors = Constant::getAllConstant()->where('type', 'BOOKING_STATUS_COLOR');
        $completed_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS')->where('name', 'completed')->first();

        $payment_status = Constant::getAllConstant()->where('type', 'PAYMENT_STATUS')->where('status', '=', '1');

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                $user = auth()->user();
                $hasActionPermission = $user->can('edit_booking') || $user->can('delete_booking');
                if (!$hasActionPermission) {
                    return '';
                }
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $row->id . '"  name="datatable_ids[]" value="' . $row->id . '" onclick="dataTableRowCheck(' . $row->id . ')">';
            })
            ->addColumn('action', function ($data) use ($module_name) {
                return view('booking::backend.bookings.datatable.action_column', compact('module_name', 'data'));
            })
            ->editColumn('status', function ($data) use ($booking_status, $booking_colors, $completed_status) {
                return view('booking::backend.bookings.datatable.select_column', compact('data', 'booking_status', 'booking_colors', 'completed_status'));
            })
            ->editColumn('payment_status', function ($data) use ($payment_status, $booking_colors) {

                return view('booking::backend.bookings.datatable.select_payment_status', compact('data', 'payment_status', 'booking_colors'));
            })
            ->editColumn('user_id', function ($data) {
                $user = optional($data->user);
                $Profile_image = $user->profile_image ?? default_user_avatar();
                $name = $user->full_name ?? default_user_name();
                $email = $user->email ?? '--';
                $id = $user->id ?? null;

                return view('booking::backend.bookings.datatable.user_id', compact('Profile_image', 'name', 'email', 'id'));
            })
            ->editColumn('employee_id', function ($data) {
                $employee = optional($data->services->first())->employee
                    ?: optional($data->bookingPackages->first())->employee;

                $Profile_image = $employee->profile_image ?? default_user_avatar();
                $name = $employee->full_name ?? default_user_name();
                $email = $employee->email ?? '--';
                $id = $employee->id ?? null;

                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
            })
            ->editColumn('service_amount', function ($data) {
                $serviceAmount = $data->services->sum('service_price');
                if ($data->bookingPackages->isNotEmpty()) {

                    foreach ($data->bookingPackages as $bookingPackage) {
                        if ($bookingPackage->is_reclaim == 0) {
                            $serviceAmount += $bookingPackage->package_price;
                        }
                    }
                }
                return '<span>' . \Currency::format($serviceAmount) . '</span>';
            })
            ->editColumn('service_duration', function ($data) {

                return '<span>' . $data->calculateServiceDuration() . ' Min</span>';
            })
            ->editColumn('services', function ($data) {
                return view('booking::backend.bookings.datatable.services', compact('data'));
            })

            ->editColumn('start_date_time', function ($data) {
                return customDate($data->start_date_time);
            })
            ->editColumn('updated_at', function ($data) {
                $diff = timeAgoInt($data->updated_at);

                if ($diff < 25) {
                    return timeAgo($data->updated_at);
                } else {
                    return customDate($data->updated_at);
                }
            })
            ->editColumn('id', function ($row) {
                return "<a href='" . route('backend.bookings.index', ['booking_id' => $row->id]) . "'>" . get_formatted_booking_id($row->id) . "</a>";
            })
            ->orderColumn('id', function($query, $order) {
                $query->orderBy('id', $order);
            })
            ->orderColumn('service_amount', function ($query, $order) {
                $query->orderBy(new Expression('
                    (SELECT COALESCE(SUM(booking_services.service_price), 0) FROM booking_services WHERE booking_services.booking_id = bookings.id) +
                    (SELECT COALESCE(SUM(booking_packages.package_price), 0) FROM booking_packages WHERE booking_packages.booking_id = bookings.id AND booking_packages.is_reclaim = 0)
                '), $order);
            })
            ->orderColumn('service_duration', function ($query, $order) {
                $query->orderBy(new Expression('
                    COALESCE((SELECT SUM(duration_min) FROM booking_services WHERE booking_id = bookings.id), 0) +
                    COALESCE((SELECT SUM(s.duration_min) FROM booking_package_services bps JOIN services s ON s.id = bps.service_id WHERE bps.booking_id = bookings.id), 0)
                '), $order);
            })
            ->orderColumn('employee_id', function ($query, $order) {
                $query->orderBy(new Expression('
                    COALESCE(
                        (SELECT u.first_name FROM booking_services bs JOIN users u ON u.id = bs.employee_id WHERE bs.booking_id = bookings.id LIMIT 1),
                        (SELECT u.first_name FROM booking_packages bp JOIN users u ON u.id = bp.employee_id WHERE bp.booking_id = bookings.id LIMIT 1)
                    )
                '), $order);
            })
            ->orderColumn('user_id', function ($query, $order) {
                $query->select('bookings.*')
                    ->leftJoin('users', 'users.id', '=', 'bookings.user_id')
                    ->orderByRaw('CONCAT(users.first_name, " ", users.last_name) ' . $order);
            })
            ->orderColumn('services', function ($query, $order) {
                 $query->orderBy(new Expression('
                    COALESCE(
                        (SELECT GROUP_CONCAT(name) FROM services s JOIN booking_services bs ON bs.service_id = s.id WHERE bs.booking_id = bookings.id),
                        (SELECT GROUP_CONCAT(name) FROM packages p JOIN booking_packages bp ON bp.package_id = p.id WHERE bp.booking_id = bookings.id)
                    )
                '), $order);
            })
            ->orderColumn('payment_status', function ($query, $order) {
                $query->select('bookings.*')
                    ->leftJoin('booking_transactions', 'booking_transactions.booking_id', '=', 'bookings.id')
                    ->orderBy('booking_transactions.payment_status', $order);
            })
            ->filterColumn('services', function ($query, $keyword) {
                $query->whereHas('mainServices', function ($q) use ($keyword) {
                    $q->where('name', 'like', '%' . $keyword . '%');
                });
            })
            ->filterColumn('employee_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('services', function ($q) use ($keyword) {
                        $q->whereHas('employee', function ($qn) use ($keyword) {
                            $qn->where('first_name', 'like', '%' . $keyword . '%');
                            $qn->orWhere('last_name', 'like', '%' . $keyword . '%');
                            $qn->orWhere('email', 'like', '%' . $keyword . '%');
                        });
                    });
                }
            })
            ->filterColumn('user_id', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->whereRaw('CONCAT(first_name, " ", last_name) LIKE ?', ['%' . $keyword . '%']);
                        $q->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->rawColumns(['check', 'id', 'action', 'status', 'services', 'service_duration', 'service_amount', 'start_date_time', 'payment_status'])
            ->make(true);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(BookingRequest $request)
    {
        // Admins can always create bookings
        // Managers and others need add_booking permission
        if (!auth()->user()->hasRole('admin') && !auth()->user()->can('add_booking')) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }
        // dd($request->all());
        $bookingData = $request->except(['services_id', 'employee_id', '_token']);

        $bookingData['status'] = 'confirmed';

        $booking = Booking::create($bookingData);

        $this->updateBookingService($request->services, $booking->id);
        $this->updateBookingPackage($request->purchase_packages, $booking->id);
        $this->storeUserPackage($booking->id);
        $message = __('messages.create_form', ['form' => __('booking.singular_title')]);

        try {
            $type = 'new_booking';
            $messageTemplate = 'New booking #[[booking_id]] has been booked.';
            $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
            $this->sendNotificationOnBookingUpdate($type, $notify_message, $booking);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        $data = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services',)->findOrFail($booking->id);

        return response()->json(['message' => $message, 'status' => true, 'data' => new BookingResource($data)], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $booking = Booking::with(['services', 'user', 'products', 'userCouponRedeem'])->find($id);

        if (is_null($booking)) {
            return response()->json(['message' => __('messages.booking_not_found')], 404);
        }

        $bookingTransaction = BookingTransaction::where('booking_id', $booking->id)->where('payment_status', 1)->first();

        $booking_product = BookingProduct::where('booking_id', $booking->id)->get();

        $sumDiscountedPrice = 0;

        if ($booking_product != '') {
            $sumDiscountedPrice = $booking_product->sum('discounted_price');
        }

        $data = [
            'booking' => new BookingResource($booking),
            'services_total_amount' => $booking->services->sum('service_price'),
            'booking_transaction' => $bookingTransaction,
            'product_amount' => $sumDiscountedPrice,
            'package_amount' => $booking->packages->sum('package_price'),
            'coupon_discount' => $booking->userCouponRedeem->discount ?? 0
        ];
        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        // Admins can always view booking details
        // For other users, allow both view_booking and edit_booking permissions to view booking details
        $user = auth()->user();
        
        if (!$user->hasRole('admin')) {
            $hasViewPermission = $user->can('view_booking');
            $hasEditPermission = $user->can('edit_booking');
            
            \Log::info('Booking edit permission check', [
                'user_id' => $user->id,
                'user_roles' => $user->getRoleNames()->toArray(),
                'has_view_booking' => $hasViewPermission,
                'has_edit_booking' => $hasEditPermission,
                'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
            ]);
            
            if (!$hasViewPermission && !$hasEditPermission) {
                return response()->json([
                    'message' => __('messages.permission_denied'),
                    'status' => false
                ], 403);
            }
        }

        $data = Booking::with([
            'services',
            'user',
            'products',
            'packages',
            'bookingPackages.services',
            'userCouponRedeem'  // Ensure this is included
        ])->findOrFail($id);

        return response()->json(['data' => new BookingResource($data), 'status' => true]);
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(BookingRequest $request, $id)
    {
        // Admins can always edit bookings
        // Managers and others need edit_booking permission
        if (!auth()->user()->hasRole('admin') && !auth()->user()->can('edit_booking')) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }

        $booking = Booking::findOrFail($id);

        $booking->update($request->all());

        $this->updateBookingService($request->services, $booking->id);
        $this->updateBookingPackage($request->purchase_packages, $booking->id);
        $message = __('booking.booking_service_update', ['form' => __('booking.singular_title')]);

        $data = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($booking->id);

        return response()->json(['message' => $message, 'status' => true, 'data' => new BookingResource($data)], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (env('IS_DEMO')) {
            return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
        }
        $booking = Booking::findOrFail($id);

        $booking->delete();

        $message = __('messages.delete_form', ['form' => __('booking.singular_title')]);

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function updateStatus($id, Request $request)
    {
        // Admins can always update booking status
        // Managers and others need edit_booking permission
        if (!auth()->user()->hasRole('admin') && !auth()->user()->can('edit_booking')) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }

        $booking = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($id);
        $status = $request->status;

        if (isset($request->action_type) && $request->action_type == 'update-status') {
            $status = $request->value;
        }

        $booking->update(['status' => $status]);

        $notify_type = null;

        switch ($status) {
            case 'check_in':
                $notify_type = 'check_in_booking';
                $messageTemplate = '#[[booking_id]] has been check-in successfully.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
            case 'checkout':
                $notify_type = 'checkout_booking';
                $messageTemplate = '#[[booking_id]] has been check-out successfully.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
            case 'completed':
                $notify_type = 'complete_booking';
                $messageTemplate = 'Booking #[[booking_id]] has been completed. Please find the attached invoice in your email.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
            case 'cancelled':
                $notify_type = 'cancel_booking';
                $messageTemplate = 'Booking #[[booking_id]] has been cancelled.';
                $notify_message = str_replace('[[booking_id]]', $id, $messageTemplate);
                break;
        }

        if (isset($notify_type)) {
            try {
                $this->sendNotificationOnBookingUpdate($notify_type, $notify_message, $booking);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }

        $message = __('booking.status_update');

        return response()->json(['data' => new BookingResource($booking), 'message' => $message, 'status' => true]);
    }

    public function updatePaymentStatus($id, Request $request)
    {
        if (isset($request->action_type) && $request->action_type == 'update-payment-status') {
            $status = $request->value;
        }

        BookingTransaction::where('booking_id', $id)->update(['payment_status' => $request->value]);

        $message = __('booking.status_update');

        return response()->json(['message' => $message, 'status' => true]);
    }

    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = __('messages.bulk_update');

        switch ($actionType) {
            case 'change-status':
                $branches = Booking::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = __('messages.bulk_booking_update');
                break;

            case 'delete':
                if (env('IS_DEMO')) {
                    return response()->json(['message' => __('messages.permission_denied'), 'status' => false], 200);
                }
                Booking::whereIn('id', $ids)->delete();
                $message = __('messages.bulk_booking_delete');
                break;

            default:
                return response()->json(['status' => false, 'message' => __('booking.booking_action_invalid')]);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    public function booking_slots(Request $request)
    {
        $day = date('l', strtotime($request->date));

        $branch_id = $request->branch_id;
        $employee_id = $request->employee_id;
        $serviceDuration = $request->service_duration ?? 0; // default to 0 if not provided

        \Log::info('booking_slots called', [
            'date' => $request->date,
            'day' => $day,
            'branch_id' => $branch_id,
            'employee_id' => $employee_id,
            'serviceDuration' => $serviceDuration,
            'ip' => $request->ip(),
            'user_id' => optional(auth()->user())->id,
        ]);

        $slots = $this->getSlots($request->date, $day, $branch_id, $serviceDuration, $employee_id);

        \Log::info('booking_slots: computed slots', ['count' => is_array($slots) ? count($slots) : null]);

        return response()->json(['status' => true, 'data' => $slots]);
    }

    public function payment_create(Request $request)
    {

        $booking_id = $request->booking_id;
        $booking = Booking::with('payment')->find($booking_id);

        $booking_services = BookingService::where('booking_id', $booking_id)->get();

        if ($request->has('userPackageserviceIds') && !empty($request->userPackageserviceIds)) {
            $userPackageserviceIds = $request->userPackageserviceIds;
            if (is_string($userPackageserviceIds)) {
                $userPackageserviceIds = explode(',', $userPackageserviceIds);
                $userPackageserviceIds = array_map('intval', $userPackageserviceIds); // Convert each ID to integer
            }
            $userPackageServices = UserPackageServices::whereIn('package_service_id', $userPackageserviceIds)
                ->with('packageService')
                ->get();
            if ($userPackageServices) {
                $coveredServiceIds = $userPackageServices->pluck('packageService.service_id')->toArray();

                $total_service_amount = $booking_services->reduce(function ($carry, $bookingService) use ($coveredServiceIds) {
                    if (!in_array($bookingService->service_id, $coveredServiceIds)) {
                        $carry += $bookingService->service_price;
                    } else {
                        $carry += 0;
                    }
                    return $carry;
                }, 0);
            }
        } else {
            $total_service_amount = $booking_services->sum('service_price');
        }

        $booking_products = BookingProduct::where('booking_id', $booking_id)->with('product')->get();

        $discounted_product_amount = getproductDiscountAmount($booking_products);
        $total_product_amount = BookingProduct::where('booking_id', $booking_id)->sum(\DB::raw('product_qty * product_price'));
        $userPackageRedeem = UserPackageRedeem::where('booking_id', $booking_id)->get();
        $discountedservice_amount = $userPackageRedeem->sum('service_price');
        // $package_amount = UserPackage::where('booking_id', $booking_id)->with('package')->get();
        // $total_package_amount = $package_amount->sum('package_price');
        $package_amount = BookingPackages::where('booking_id', $booking_id)->with('package')->get();
        $total_package_amount = $package_amount->sum('package_price');
        $product_amount = $total_product_amount - $discounted_product_amount;
        if ($discountedservice_amount) {
            $total_service_amount = $total_service_amount - $discountedservice_amount;
        }
        $currency = \Currency::getDefaultCurrency();
        $payment_methods = $booking->branch->payment_method;
        $constant = Constant::where('type', 'PAYMENT_METHODS')->whereIn('name', $payment_methods)->get();
        $payment_methods = $constant->map(function ($row) {
            return [
                'id' => $row->name,
                'text' => $row->value,
            ];
        })->toArray();

        $taxes = $booking->payment->tax_percentage ?? null;

        if ($taxes == 0) {

            $taxes = [];
        }

        if ($booking->payment == null) {
            $taxes = Tax::active()
                ->where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('module_type')
                        ->orWhere('module_type', 'services');
                })
                ->get()
                ->map(function ($tax) use ($total_service_amount) {
                    $percent = (float) $tax->value;
                    $amount = $tax->type === 'percent'
                        ? ($total_service_amount * $percent / 100)
                        : (float) $tax->value;

                    return [
                        'name'    => $tax->title,
                        'type'    => $tax->type,
                        'percent' => $percent,
                        'amount'  => $amount,
                        'tax_amount' => $amount,
                    ];
                })
                ->toArray();
        }



        $coupon = UserCouponRedeem::where('booking_id', $booking_id)->first();

        $data = [
            'booking_amounts' => [
                'amount' => $total_service_amount,
                'product_amount' => $product_amount,
                'package_amount' => $total_package_amount,
                'currency' => $currency->currency_symbol,
            ],
            'PAYMENT_METHODS' => $payment_methods,
            'tax' =>  $taxes,
            'userpackageRedeem' => $userPackageRedeem,
            'coupon' => $coupon,
        ];

        return response()->json(['status' => true, 'data' => $data]);
    }

    public function booking_payment(Request $request, Booking $booking_id)
    {
        $data = $request->all();

        $booking_id = $booking_id['id'];
        if ($request->has('packageService') && !empty($request->packageService)) {
            foreach ($request->packageService as $service) {
                $serviceId = $service['service_id'];
                $discountPrice = $service['discount_price'];
                BookingService::where('booking_id', $booking_id)
                    ->where('service_id', $serviceId)
                    ->update(['service_price' => 0]);
            }
        }


        $responseData = $this->getpayment_method($data, $booking_id);
        $this->updateUserPackageRedeem($request->packageService, $booking_id);
        $booking_product = BookingProduct::where('booking_id', $booking_id)->get();

        $booking_details = Booking::where('id', $booking_id)->with('payment')->first();
        if ($booking_product->isNotEmpty()) {
            $orderId = $this->createCart($booking_product, $booking_details);

            BookingProduct::where('booking_id', $booking_id)->update(['order_id' => $orderId]);
        }

        return response()->json(['status' => true, 'data' => $responseData]);
    }

    public function booking_payment_update(Request $request, $booking_transaction_id)
    {
        $data = $request->all();

        $responseData = $this->getrazorpaypayments($data, $booking_transaction_id);

        if (isset($responseData['booking'])) {
            $queryData = Booking::find($responseData['booking']->id);

            $messageTemplate = 'Booking #[[booking_id]] has been completed. Please find the attached invoice in your email.';
            $notify_message = str_replace('[[booking_id]]', $responseData['booking']->id, $messageTemplate);
            try {
                $this->sendNotificationOnBookingUpdate('complete_booking', $notify_message, $queryData);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }


        return response()->json(['status' => true, 'data' => $responseData]);
    }

    public function checkout(Booking $booking_id, Request $request)
    {

        // $this->updateBookingPackage($request->purchase_package, $booking_id->id);


        $this->updateBookingService($request->services, $booking_id->id);


        $this->updateBookingProduct($request->products, $booking_id->id);

        $queryData = Booking::with('services', 'user', 'products', 'packages', 'bookingPackages.services')->findOrFail($booking_id->id);

        return response()->json(['status' => true, 'data' => new BookingResource($queryData), 'message' => __('booking.booking_service_update')]);
    }

    public function stripe_payment(Request $request)
    {
        $data = $request->data;

        $checkout_session = $this->getstripepayments($data);

        if (isset($checkout_session['message'])) {
            return response()->json(['status' => false, 'data' => $checkout_session]);
        } else {
            BookingTransaction::where('id', $data['booking_transaction_id'])->update(['request_token' => $checkout_session['id']]);

            return response()->json(['status' => true, 'data_url' => $checkout_session->url, 'data' => $checkout_session]);
        }
    }

    public function payment_success($id)
    {
        $booking_transaction = BookingTransaction::where('id', $id)->first();

        $request_token = $booking_transaction['request_token'];

        $booking_id = $booking_transaction['booking_id'];

        $session_object = $this->getstripePaymnetId($request_token);

        if ($session_object['payment_intent'] !== '' && $session_object['payment_status'] == 'paid') {
            BookingTransaction::where('id', $id)->update(['external_transaction_id' => $session_object['payment_intent'], 'payment_status' => 1]);

            Booking::where('id', $booking_id)->update(['status' => 'completed']);

            $queryData = Booking::where('id', $booking_id)->first();
            try {
                $messageTemplate = 'Booking #[[booking_id]] has been completed. Please find the attached invoice in your email.';
                $notify_message = str_replace('[[booking_id]]',  $queryData->id, $messageTemplate);
                $this->sendNotificationOnBookingUpdate('complete_booking', $notify_message, $queryData);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }

        return redirect()->route('backend.bookings.index');
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function viewInvoice(Request $request)
    {
        $order = Booking::find($request->id);


        $booking = Booking::with(['services', 'user', 'products', 'userCouponRedeem', 'packages'])->where('status', 'completed')->find($request->id);

        if ($booking == null) {
            return abort(500);
        }

        if (is_null($booking)) {
            return response()->json(['message' => __('messages.booking_not_found')], 404);
        }

        $data = $this->bookingDetail($booking);

        $data = (object) [
            'booking' => new BookingResource($booking),
            'services_total_amount' => $data['serviceAmount'],
            'booking_transaction' => $data['bookingTransaction'],
            'product_amount' => $data['sumDiscountedPrice'],
            'tax_amount' => $data['tax_amount'],
            'coupon_discount' => $data['coupon_discount'],
            'grand_total' => $data['grand_total'],
            'package_amount' => $data['packageAmount'],
        ];

        return view('booking::backend.invoice', compact('data'));
    }

    public function downloadInvoice(Request $request)
    {
        // Set locale for translations - check multiple sources
        $language_code = config('app.locale'); // Default
        
        // Priority 1: Check session (most reliable for web requests)
        if (session()->has('locale')) {
            $language_code = session()->get('locale', config('app.locale'));
        }
        // Priority 2: Check authenticated user's language preference
        elseif (auth()->check() && isset(auth()->user()->language) && auth()->user()->language) {
            $language_code = auth()->user()->language;
        }
        // Priority 3: Check request header (for API)
        elseif ($request->hasHeader('Accept-Language')) {
            $language_code = $request->header('Accept-Language');
        }
        
        // Ensure locale is set before rendering
        app()->setLocale($language_code);
        
        $booking = Booking::with(['services', 'user', 'products'])->where('status', 'completed')->find($request->id);

        $booking['detail'] = $this->bookingDetail($booking);
        $filename = 'Invoice_' . $request->id . '.pdf';
        // Prepare data for notification
        $data = $this->sendNotificationOnBookingUpdate('complete_booking', 'Notification message', $booking, false);
        if ($data === false) {
            return response()->json(['status' => false, 'message' => 'Failed to prepare booking data for notification'], 500);
        }

        // Convert logo to base64 for PDF compatibility
        $logoBase64 = null;
        $logoPath = setting('logo');
        if ($logoPath) {
            try {
                // Remove leading slash if present
                $logoPath = ltrim($logoPath, '/');
                // Try public path first
                $fullLogoPath = public_path($logoPath);
                
                // If not found, try storage path
                if (!file_exists($fullLogoPath)) {
                    $fullLogoPath = storage_path('app/public/' . $logoPath);
                }
                
                // If still not found, try with asset path
                if (!file_exists($fullLogoPath) && strpos($logoPath, 'storage/') === 0) {
                    $fullLogoPath = storage_path('app/public/' . str_replace('storage/', '', $logoPath));
                }
                
                if (file_exists($fullLogoPath) && is_readable($fullLogoPath)) {
                    // Get MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $fullLogoPath);
                    finfo_close($finfo);
                    
                    $extensions = [
                        'image/jpeg' => 'jpeg',
                        'image/png' => 'png',
                        'image/gif' => 'gif',
                        'image/svg+xml' => 'svg+xml',
                        'image/webp' => 'webp',
                    ];
                    
                    $extension = $extensions[$mimeType] ?? 'png';
                    $logoData = file_get_contents($fullLogoPath);
                    $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($logoData);
                }
            } catch (\Exception $e) {
                \Log::error('Logo processing error in invoice: ' . $e->getMessage());
            }
        }
        
        // Fallback to default logo if setting logo not found
        if (!$logoBase64) {
            try {
                $defaultLogoPath = public_path('img/logo/logo.png');
                if (file_exists($defaultLogoPath) && is_readable($defaultLogoPath)) {
                    $logoData = file_get_contents($defaultLogoPath);
                    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
                }
            } catch (\Exception $e) {
                \Log::error('Default logo processing error: ' . $e->getMessage());
            }
        }

        // Render the view for the PDF
        $view = view("mail.invoice-templates." . setting('template'), [
            'data' => $data['booking'],
            'logo' => $logoBase64
        ])->render();
        
        // Configure PDF for Unicode and RTL support
        $pdf = Pdf::loadHTML($view);
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => 'DejaVu Sans', // Unicode-friendly font for Greek and Arabic
            'isPhpEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        if ($request->is('api/*')) {
            // Handle API request
            $baseDirectory = storage_path('app/public');
            $highestDirectory = collect(File::directories($baseDirectory))->map(function ($directory) {
                return basename($directory);
            })->max() ?? 0;
            $nextDirectory = intval($highestDirectory) + 1;
            while (File::exists($baseDirectory . '/' . $nextDirectory)) {
                $nextDirectory++;
            }
            $newDirectory = $baseDirectory . '/' . $nextDirectory;
            File::makeDirectory($newDirectory, 0777, true);

            $filename = 'invoice_' . $request->id . '.pdf';
            $filePath = $newDirectory . '/' . $filename;

            $pdf->save($filePath);

            $url = url('storage/' . $nextDirectory . '/' . $filename);
            if (!empty($url)) {
                return response()->json(['status' => true, 'link' => $url], 200);
            } else {
                return response()->json(['status' => false, 'message' => 'Url Not Found'], 404);
            }
        } else {
            // Handle non-API request
            // return $pdf->download($filename);
            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                "invoice.pdf",
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="invoice.pdf"',
                ]
            );
        }
    }

    // Cancel a booking (AJAX)
    public function cancel($id, Request $request)
    {
        try {
            $booking = Booking::findOrFail($id);
            $booking->status = 'cancelled';
            $booking->save();
            // Optionally, fire event/notification here
            return response()->json(['status' => true, 'message' => 'Booking cancelled successfully.']);
        } catch (\Exception $e) {
            \Log::error('Booking cancel error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to cancel booking.'], 500);
        }
    }

    // Get booking details (AJAX)
    public function details($id)
    {
        try {
            $booking = Booking::with(['services', 'user', 'products', 'packages', 'bookingPackages.services', 'userCouponRedeem'])
                ->findOrFail($id);
            return response()->json(['status' => true, 'data' => new BookingResource($booking)]);
        } catch (\Exception $e) {
            \Log::error('Booking details error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to fetch booking details.'], 500);
        }
    }
}
