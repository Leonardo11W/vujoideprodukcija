<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use Carbon\Carbon;
use Currency;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Earning\Models\EmployeeEarning;
use Modules\Employee\Models\BranchEmployee;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;
use Yajra\DataTables\DataTables;

class ReportsController extends Controller
{
    public function __construct()
    {
        // Page Title
        $this->module_title = 'Reports';

        // module name
        $this->module_name = 'reports';

        // module icon
        $this->module_icon = 'fa-solid fa-chart-line';

        view()->share([
            'module_icon' => $this->module_icon,
        ]);
    }

    /**
     * Check if user has permission to access Reports module
     * 
     * @param string $permission Permission to check (view_reports, add_reports, edit_reports, delete_reports)
     * @return bool
     */
    protected function hasReportPermission($permission = 'view_reports')
    {
        $user = auth()->user();
        
        // Admin always has access
        if ($user && $user->hasRole('admin')) {
            return true;
        }
        
        // Check specific permission
        return $user && $user->can($permission);
    }

    /**
     * Check if current user is an employee (staff) and should see only their own data
     * 
     * @return bool
     */
    protected function isEmployeeOnly()
    {
        $user = auth()->user();
        return $user && $user->hasRole('employee') && !$user->hasRole('admin') && !$user->hasRole('manager');
    }

    /**
     * Ensure view_product_orders_report permission exists in database
     * 
     * @return void
     */
    protected function ensureOrderReportPermissionExists()
    {
        try {
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'view_product_orders_report', 'guard_name' => 'web'],
                ['is_fixed' => false]
            );
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Exception $e) {
            \Log::warning('Failed to ensure order report permission exists', [
                'permission' => 'view_product_orders_report',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function daily_booking_report(Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $module_title = __('report.title_daily_report');

        $module_name = 'daily-booking-report';
        $export_import = true;
        $export_columns = [
            [
                'value' => 'date',
                'text' => 'Date',
                'translationKey' => 'export.columns.date',
            ],
            [
                'value' => 'total_booking',
                'text' => 'No. Booking',
                'translationKey' => 'export.columns.no_booking',
            ],
            [
                'value' => 'total_service',
                'text' => 'No. Services',
                'translationKey' => 'export.columns.no_services',
            ],
            [
                'value' => 'total_service_amount',
                'text' => 'Service Amount',
                'translationKey' => 'export.columns.service_amount',
            ],
            [
                'value' => 'total_tax_amount',
                'text' => 'Tax Amount',
                'translationKey' => 'export.columns.tax_amount',
            ],
            [
                'value' => 'total_tip_amount',
                'text' => 'Tips Amount',
                'translationKey' => 'export.columns.tips_amount',
            ],
            [
                'value' => 'total_amount',
                'text' => 'Final Amount',
                'translationKey' => 'export.columns.final_amount',
            ],
        ];
        $export_url = route('backend.reports.daily-booking-report-review');

        return view('backend.reports.daily-booking-report', compact('module_title', 'module_name', 'export_import', 'export_columns', 'export_url'));
    }

    public function order_report(Request $request)
    {
        // Ensure permission exists
        $this->ensureOrderReportPermissionExists();
        
        // Check permission to view order reports
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('view_product_orders_report')) {
            return redirect()->route('backend.home');
        }

        $module_title = 'order_report.title';

        $module_name = '.order-report';
        $export_import = true;
        $export_columns = [
            [
                'value' => 'order_code',
                'text' => 'Order Code',
            ],
            [
                'value' => 'customer_name',
                'text' => 'Customer Name',
            ],
            [
                'value' => 'placed_on',
                'text' => 'placed On',
            ],
            [
                'value' => 'items',
                'text' => 'Items',
            ],
            [
                'value' => 'total_admin_earnings',
                'text' => 'Total Amount',
            ],
            [
                'value' => 'payment',
                'text' => 'Payment',
            ],
            [
                'value' => 'status',
                'text' => 'Status',
            ]

        ];
        $export_url = route('backend.reports.order_booking_report_review');

        $totalAdminEarnings = Order::where('payment_status', 'paid')->sum('total_admin_earnings');

        return view('backend.reports.order-report', compact('module_title', 'module_name', 'export_import', 'export_columns', 'export_url', 'totalAdminEarnings'));
    }

    public function order_report_index_data(Datatables $datatable, Request $request)
    {
        // Ensure permission exists
        $this->ensureOrderReportPermissionExists();
        
        // Check permission to view order reports
        $user = auth()->user();
        if (!$user->hasRole('admin') && !$user->can('view_product_orders_report')) {
            abort(403, __('messages.permission_denied'));
        }

        $orders = Order::with('orderGroup');
        
        // For employees, filter to show only orders related to their services
        if ($this->isEmployeeOnly()) {
            $authUser = auth()->user();
            $orders->whereHas('orderItems', function ($q) use ($authUser) {
                $q->whereHas('bookingService', function ($bs) use ($authUser) {
                    $bs->where('employee_id', $authUser->id);
                });
            });
        }

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['code'])) {
                $orders = $orders->where(function ($q) use ($filter) {
                    $orderGroup = OrderGroup::where('order_code', $filter['code'])->pluck('id');
                    $q->orWhereIn('order_group_id', $orderGroup);
                });
            }

            if (isset($filter['delivery_status'])) {
                $orders = $orders->where('delivery_status', $filter['delivery_status']);
            }

            if (isset($filter['payment_status'])) {
                $orders = $orders->where('payment_status', $filter['payment_status']);
            }
            if (isset($filter['order_date'][0])) {
                $startDate = $filter['order_date'][0];
                $endDate = $filter['order_date'][1] ?? null;

                if (isset($endDate)) {
                    $orders->whereDate('created_at', '>=', date('Y-m-d', strtotime($startDate)));
                    $orders->whereDate('created_at', '<=', date('Y-m-d', strtotime($endDate)));
                } else {
                    $orders->whereDate('created_at', date('Y-m-d', strtotime($startDate)));
                }
            }
        }

        $orders = $orders->where(function ($q) {
            $orderGroup = OrderGroup::pluck('id');
            $q->orWhereIn('order_group_id', $orderGroup);
        });

        return $datatable->eloquent($orders)
            ->addIndexColumn()
            ->editColumn('order_code', function ($data) {
                return optional($data->orderGroup)->formatted_order_code ?? '-';
            })
            ->editColumn('customer_name', function ($data) {
                $Profile_image = optional($data->user)->profile_image ?? default_user_avatar();
                $name = optional($data->user)->full_name ?? default_user_name();
                $email = optional($data->user)->email ?? '--';
                $id = optional($data->user)->id ?? null;
                return view('booking::backend.bookings.datatable.user_id', compact('Profile_image', 'name', 'email', 'id'));
                // return view('product::backend.order.columns.customer_column', compact('data'));
            })
            ->addColumn('phone', function ($data) {
                return optional($data->user)->mobile ?? '-';
            })
            ->editColumn('placed_on', function ($data) {
                return customDate($data->created_at);
            })
            ->editColumn('items', function ($data) {
                return $data->orderItems()->count();
            })
            ->editColumn('payment', function ($data) {
                return view('product::backend.order.columns.payment_column', compact('data'));
            })
            ->editColumn('status', function ($data) {
                return view('product::backend.order.columns.status_column', compact('data'));
            })
            ->editColumn('total_admin_earnings', function ($data) {
                return Currency::format($data->total_admin_earnings ?? 0);
            })
            ->filterColumn('customer_name', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('first_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('last_name', 'like', '%' . $keyword . '%');
                        $q->orWhere('email', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->filterColumn('phone', function ($query, $keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('user', function ($q) use ($keyword) {
                        $q->where('mobile', 'like', '%' . $keyword . '%');
                    });
                }
            })
            ->editColumn('updated_at', function ($data) {
                $diff = Carbon::now()->diffInHours($data->updated_at);
                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->orderColumns(['id'], '-:column $1')
            ->rawColumns(['phone'])
            ->toJson();
    }

    public function daily_booking_report_index_data(Datatables $datatable, Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');
        
        // For employees, ensure they only see their own data
        if ($this->isEmployeeOnly()) {
            $isEmployee = true;
        }

        // My Work always overrides branch selection for managers
        $filter = $request->filter;
        $explicitBranchId = $filter['branch_id'] ?? null;
        $selectedBranchId = $explicitBranchId ?? ($request->selected_session_branch_id ?? null);
        $isManagerMyWork = $isManager && session('my_work_mode', false);

        // Managers in My Work (My Work) or employees see only their own bookings
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $employeeId = $filterByEmployee ? $authUser->id : null;

        // Branch scoping: ignore branch when My Work is active; otherwise honor selected branch
        $limitBranchIds = [];
        $hasBranchSelected = false;
        if (! $isManagerMyWork && $selectedBranchId) {
            $limitBranchIds = [$selectedBranchId];
            $hasBranchSelected = true;
        }

        // Managers: if no branch is explicitly selected, limit to their assigned branches
        if ($isManager && ! $isManagerMyWork && ! $hasBranchSelected) {
            $limitBranchIds = $authUser->branches->pluck('id')->toArray();
        }
        
        $query = Booking::with(['services', 'packages', 'payment', 'userCouponRedeem'])
            ->where('status', 'completed')
            ->whereHas('payment', function($q) {
                $q->where('payment_status', 1);
            })
            ->when(! empty($limitBranchIds), function ($query) use ($limitBranchIds) {
                $query->whereIn('bookings.branch_id', $limitBranchIds);
            })
            ->when($filterByEmployee && $employeeId, function ($q) use ($employeeId) {
                $q->where(function ($sub) use ($employeeId) {
                    $sub->whereHas('services', function ($qq) use ($employeeId) {
                        $qq->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('packages', function ($qq) use ($employeeId) {
                        $qq->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('products', function ($qq) use ($employeeId) {
                        $qq->where('employee_id', $employeeId);
                    });
                });
            });

        // if (auth()->user()->hasRole('admin')) {
        //     $query->whereHas('branch', fn($q) => $q->where('created_by', auth()->id()));
        // }

        if ($request->filled('filter.booking_date')) {
            $dates = explode(' to ', $request->filter['booking_date']);
            $start = date('Y-m-d 00:00:00', strtotime($dates[0]));
            $end = count($dates) > 1
                ? date('Y-m-d 23:59:59', strtotime($dates[1]))
                : date('Y-m-d 23:59:59', strtotime($dates[0]));
            $query->whereBetween('bookings.start_date_time', [$start, $end]);
        }



        $bookings = $query->get()->groupBy(fn($b) => formatDateOrTime($b->start_date_time, 'date'));


        $data = collect();
        foreach ($bookings as $date => $dailyBookings) {

            $totalTaxAmount = $dailyBookings->sum(fn($b) => $b->total_tax_amount);

            // Calculate service amount after discount (subtotal) and final amount
            $totalServiceAmountAfterDiscount = 0;
            $totalFinalAmountAfterDiscount = 0;
            foreach ($dailyBookings as $booking) {
                $discount = optional($booking->userCouponRedeem)->discount ?? 0;
                $serviceAmount = $booking->total_service_amount;
                $serviceAfterDiscount = max(0, $serviceAmount - $discount);

                $totalServiceAmountAfterDiscount += $serviceAfterDiscount;
                $totalFinalAmountAfterDiscount += $serviceAfterDiscount + $booking->total_tax_amount + $booking->total_tip_amount;
            }

            \Log::info('ReportsController::daily_booking_report_index_data - Daily totals', [
                'date' => $date,
                'total_booking' => $dailyBookings->count(),
                'total_service_amount' => $totalServiceAmountAfterDiscount,
                'total_tax_amount' => $totalTaxAmount,
                'total_tip_amount' => $dailyBookings->sum(fn($b) => $b->total_tip_amount),
                'grand_total_amount' => $totalFinalAmountAfterDiscount,
            ]);

            $data->push((object)[
                'start_date_time'      => $date,
                'total_booking'        => $dailyBookings->count(),
                'total_service'        => $dailyBookings->sum(fn($b) => $b->services->count() + $b->packages->count()),
                'total_service_amount' => $totalServiceAmountAfterDiscount,
                'total_tax_amount'     => $totalTaxAmount,
                'total_tip_amount'     => $dailyBookings->sum(fn($b) => $b->total_tip_amount),
                'grand_total_amount'   => $totalFinalAmountAfterDiscount,
            ]);
        }

        return Datatables::of($data)
            ->editColumn('start_date_time', fn($row) => formatDateOrTime($row->start_date_time))
            ->editColumn('total_service_amount', fn($row) => Currency::format($row->total_service_amount))
            ->editColumn('total_tip_amount', fn($row) => Currency::format($row->total_tip_amount))
            ->editColumn('total_tax_amount', fn($row) => Currency::format($row->total_tax_amount))
            ->editColumn('total_amount', fn($row) => Currency::format($row->grand_total_amount))
            ->addIndexColumn()
            ->toJson();
            
    }


    public function overall_booking_report(Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $module_title = __('report.title_overall_report');

        $module_name = 'overall-booking-report';
        $export_import = true;
        $export_columns = [
            [
                'value' => 'date',
                'text' => 'Date',
                'translationKey' => 'export.columns.date',
            ],
            [
                'value' => 'inv_id',
                'text' => 'Inv ID',
                'translationKey' => 'export.columns.inv_id',
            ],
            [
                'value' => 'employee',
                'text' => 'Staff',
                'translationKey' => 'export.columns.staff',
            ],
            [
                'value' => 'total_service',
                'text' => 'Total Service',
                'translationKey' => 'export.columns.total_service',
            ],
            [
                'value' => 'total_service_amount',
                'text' => 'Total Service Amount',
                'translationKey' => 'export.columns.total_service_amount',
            ],
            [
                'value' => 'total_tax_amount',
                'text' => 'Taxes',
                'translationKey' => 'export.columns.taxes',
            ],
            [
                'value' => 'total_tip_amount',
                'text' => 'Tips',
                'translationKey' => 'export.columns.tips',
            ],
            [
                'value' => 'total_amount',
                'text' => 'Final Amount',
                'translationKey' => 'export.columns.final_amount',
            ],
        ];
        $export_url = route('backend.reports.overall-booking-report-review');

        return view('backend.reports.overall-booking-report', compact('module_title', 'module_name', 'export_import', 'export_columns', 'export_url'));
    }

    public function overall_booking_report_index_data(Datatables $datatable, Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $query = Booking::overallReport()->with('userCouponRedeem');
        $authUser = auth()->user();

        $isManager = $authUser && $authUser->hasRole('manager');
        $isEmployee = $authUser && $authUser->hasRole('employee');
        
        // For employees, ensure they only see their own data
        if ($this->isEmployeeOnly()) {
            $isEmployee = true;
        }

        // My Work always overrides branch selection for managers
        $filter = $request->filter;
        $selectedBranchId = $filter['branch_id'] ?? ($request->selected_session_branch_id ?? null);

        $isManagerMyWork = $isManager && session('my_work_mode', false);

        // Managers in My Work or employees see only their own bookings
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $employeeId = $filterByEmployee ? $authUser->id : null;

        // Branch scoping: ignore when My Work is active; otherwise use selected branch
        $hasBranchSelected = false;
        if (! $isManagerMyWork && $selectedBranchId) {
            $query->where('bookings.branch_id', $selectedBranchId);
            $hasBranchSelected = true;
        }

        if ($request->has('booing_id')) {
            $query->where('bookings.id', $request->booing_id);
        }

        if ($request->has('date_range')) {
            $dateRange = explode(' to ', $request->date_range);
            if (isset($dateRange[1])) {
                $startDate = $dateRange[0] ?? date('Y-m-d');
                $endDate = $dateRange[1] ?? date('Y-m-d');
                $query->whereDate('start_date_time', '>=', $startDate)
                    ->whereDate('start_date_time', '<=', $endDate);
            }
        }

        $filter = $request->filter;
        if (isset($filter['booking_date'])) {
            $bookingDates = explode(' to ', $filter['booking_date']);

            if (count($bookingDates) >= 2) {
                $startDate = date('Y-m-d 00:00:00', strtotime($bookingDates[0]));
                $endDate = date('Y-m-d 23:59:59', strtotime($bookingDates[1]));

                $query->where('bookings.start_date_time', '>=', $startDate)
                    ->where('bookings.start_date_time', '<=', $endDate);
            } elseif (count($bookingDates) === 1) {
                $singleDate = date('Y-m-d', strtotime($bookingDates[0]));
                $startDate = $singleDate . ' 00:00:00';
                $endDate = $singleDate . ' 23:59:59';
                $query->whereBetween('bookings.start_date_time', [$startDate, $endDate]);
            }
        }

        if (isset($filter['employee_id'])) {
            $query->whereHas('services', function ($q) use ($filter) {
                $q->where('employee_id', $filter['employee_id']);
            });
        }

        if ($filterByEmployee && $employeeId) {
            $query->where(function ($q) use ($employeeId) {
                $q->whereHas('services', function ($qq) use ($employeeId) {
                    $qq->where('employee_id', $employeeId);
                })
                ->orWhereHas('bookingPackages', function ($qq) use ($employeeId) {
                    $qq->where('employee_id', $employeeId);
                })
                ->orWhereHas('products', function ($qq) use ($employeeId) {
                    $qq->where('employee_id', $employeeId);
                });
            });
        }


        return $datatable->eloquent($query)
            ->editColumn('start_date_time', function ($data) {
                return customDate($data->start_date_time, 'Y-m-d');
            })
            ->editColumn('id', function ($data) {
                return setting('booking_invoice_prifix') . $data->id;
            })
            ->editColumn('employee_id', function ($data) {
                // return $data->services->first()->employee?->full_name ?? '-';
                $employee = optional($data->services->first())->employee;
                $Profile_image = $employee->profile_image ?? default_user_avatar();
                $name = $employee->full_name ?? default_user_name();
                $email = $employee->email ?? '--';
                $id = $employee->id ?? null;

                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
            })
            ->editColumn('total_service', function ($data) {
                return $data->total_service;
            })
            ->editColumn('total_service_amount', function ($data) {
                // Calculate service amount after discount (subtotal)
                $serviceAmount = $data->total_service_amount ?? 0;
                $discount = optional($data->userCouponRedeem)->discount ?? 0;
                $serviceAmountAfterDiscount = max(0, $serviceAmount - $discount);
                return Currency::format($serviceAmountAfterDiscount);
            })
            ->editColumn('total_tax_amount', function ($data) {
                return Currency::format($data->total_tax_amount ?? 0);
            })
            ->editColumn('total_tip_amount', function ($data) {
                return Currency::format($data->total_tip_amount);
            })
            ->editColumn('total_amount', function ($data) {
                $serviceAmount = $data->total_service_amount ?? 0;
                $discount = optional($data->userCouponRedeem)->discount ?? 0;
                $serviceAfterDiscount = max(0, $serviceAmount - $discount);

                $taxAmount = $data->total_tax_amount ?? 0;
                $tipAmount = $data->total_tip_amount ?? 0;

                return Currency::format($serviceAfterDiscount + $taxAmount + $tipAmount);
            })
            ->orderColumn('employee_id', function ($query, $order) {
                $query->orderBy(new Expression('(SELECT employee_id FROM booking_services WHERE booking_id = bookings.id LIMIT 1)'), $order);
            }, 1)
            ->addIndexColumn()
            ->rawColumns([])
            ->toJson();
    }


    public function payout_report(Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $module_title = __('report.title_staff_report');

        $module_name = 'payout-report-review';
        $export_import = true;
        $export_columns = [
            [
                'value' => 'date',
                'text' => 'Payment Date',
                'translationKey' => 'export.columns.payment_date',
            ],
            [
                'value' => 'employee',
                'text' => 'Staff',
                'translationKey' => 'export.columns.staff',
            ],
            [
                'value' => 'commission_amount',
                'text' => 'Commission Amount',
                'translationKey' => 'export.columns.commission_amount',
            ],
            [
                'value' => 'tip_amount',
                'text' => 'Tips Amount',
                'translationKey' => 'export.columns.tips_amount',
            ],
            [
                'value' => 'payment_type',
                'text' => 'Payment Type',
                'translationKey' => 'export.columns.payment_type',
            ],
            [
                'value' => 'total_pay',
                'text' => 'Total Pay',
                'translationKey' => 'export.columns.total_pay',
            ],
        ];
        $export_url = route('backend.reports.payout-report-review');

        return view('backend.reports.payout-report', compact('module_title', 'module_name', 'export_import', 'export_columns', 'export_url'));
    }

    public function payout_report_index_data(Datatables $datatable, Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $query = EmployeeEarning::with('employee');

        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        
        // For employees, filter to show only their own payout data
        if ($this->isEmployeeOnly()) {
            $query->where('employee_id', $authUser->id);
        }

        $filter = $request->filter;

        if ($isManagerMyWork) {
            $query->where('employee_id', $authUser->id);
        } else {
            // Branch filtering: explicit branch_id overrides session-selected branch
            $selectedBranchId = $request->selected_session_branch_id;
            $activeBranchId = null;

            if (isset($filter['branch_id']) && $filter['branch_id'] !== '') {
                $activeBranchId = $filter['branch_id'];
            } elseif (!empty($selectedBranchId)) {
                $activeBranchId = $selectedBranchId;
            }

            if ($activeBranchId) {
                $query->whereHas('employee', function ($q) use ($activeBranchId) {
                    $q->whereHas('branch', function ($b) use ($activeBranchId) {
                        $b->where('branch_id', $activeBranchId);
                    });
                });

                // If Manager is viewing a specific branch (not My Work), exclude themselves
                if ($isManager) {
                    $query->where('employee_id', '!=', $authUser->id);
                }
            }
        }

        if (isset($filter['booking_date'])) {
            $bookingDates = explode(' to ', $filter['booking_date']);

            if (count($bookingDates) >= 2) {
                $startDate = date('Y-m-d 00:00:00', strtotime($bookingDates[0]));
                $endDate = date('Y-m-d 23:59:59', strtotime($bookingDates[1]));

                $query->where('payment_date', '>=', $startDate)
                    ->where('payment_date', '<=', $endDate);
            } elseif (count($bookingDates) === 1) {
                $singleDate = date('Y-m-d', strtotime($bookingDates[0]));
                $startDate = $singleDate . ' 00:00:00';
                $endDate = $singleDate . ' 23:59:59';
                $query->whereBetween('payment_date', [$startDate, $endDate]);
            }
        }

        if (isset($filter['employee_id'])) {
            $query->whereHas('employee', function ($q) use ($filter) {
                $q->where('employee_id', $filter['employee_id']);
            });
        }

        return $datatable->eloquent($query)
            ->editColumn('payment_date', function ($data) {
                return customDate($data->payment_date ?? '-');
            })
            ->editColumn('first_name', function ($data) {
                $Profile_image = optional($data->employee)->profile_image ?? default_user_avatar();
                $name = optional($data->employee)->full_name ?? default_user_name();
                $email = optional($data->employee)->email ?? '--';
                $id = optional($data->employee)->id ?? null;
                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
            })
            ->editColumn('commission_amount', function ($data) {
                return Currency::format($data->commission_amount ?? 0);
            })
            ->editColumn('tip_amount', function ($data) {
                return Currency::format($data->tip_amount ?? 0);
            })
            ->editColumn('total_pay', function ($data) {
                return Currency::format($data->total_amount ?? 0);
            })
            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            // ->orderColumn('first_name', function ($query, $order) {
            //     $query->orderBy(new Expression('(SELECT id FROM users WHERE id = employee_id LIMIT 1)'), $order);
            // }, 1)
            ->orderColumn('first_name', function ($query, $direction) {
                $query->leftJoin('users', 'users.id', '=', 'employee_id')
                    ->orderBy('users.first_name', $direction)
                    ->orderBy('users.last_name', $direction);
            })

            ->orderColumn('total_pay', function ($query, $order) {
                $query->orderBy(new Expression('(SELECT total_amount FROM users WHERE id = employee_id LIMIT 1)'), $order);
            }, 1)

            ->addIndexColumn()
            ->rawColumns([])
            ->orderColumns(['id'], '-:column $1')
            ->toJson();
    }

    public function staff_report(Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $module_title = __('report.title_staff_service_report');

        $module_name = 'staff-report-review';
        $export_import = true;
        $export_columns = [
            [
                'value' => 'employee',
                'text' => 'Staff',
                'translationKey' => 'export.columns.staff',
            ],
            [
                'value' => 'total_services',
                'text' => 'Total Services',
                'translationKey' => 'export.columns.total_services',
            ],
            [
                'value' => 'total_service_amount',
                'text' => 'Total Amount',
                'translationKey' => 'export.columns.total_amount',
            ],
            [
                'value' => 'total_commission_earn',
                'text' => 'Commission Earn',
                'translationKey' => 'export.columns.commission_earn',
            ],
            [
                'value' => 'total_tip_earn',
                'text' => 'Tips Earn',
                'translationKey' => 'export.columns.tips_earn',
            ],
            [
                'value' => 'total_earning',
                'text' => 'Total Earning',
                'translationKey' => 'export.columns.total_earning',
            ],
        ];
        $export_url = route('backend.reports.staff-report-review');

        return view('backend.reports.staff-report', compact('module_title', 'module_name', 'export_import', 'export_columns', 'export_url'));
    }

    public function staff_report_index_data(Datatables $datatable, Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }
        
        $filter = $request->filter;
        $explicitBranchId = isset($filter['branch_id']) && $filter['branch_id'] !== '' ? (int) $filter['branch_id'] : null;
        $activeBranchId = $explicitBranchId ?? ($request->selected_session_branch_id ? (int) $request->selected_session_branch_id : null);

        $authUser = auth()->user();
        $isManagerOnly = $authUser && $authUser->hasRole('manager') && ! $authUser->hasRole('admin');
        $isManagerMyWork = $isManagerOnly && session('my_work_mode', false);
        $managerBranchIds = [];
        
        // For employees, filter to show only their own staff report data
        if ($this->isEmployeeOnly()) {
            $query = User::staffReport($activeBranchId)->where('users.id', $authUser->id);
            return $datatable->eloquent($query)
                ->editColumn('first_name', function ($data) {
                    $Profile_image = optional($data)->profile_image ?? default_user_avatar();
                    $name = optional($data)->full_name ?? default_user_name();
                    $email = optional($data)->email ?? '--';
                    $id = optional($data)->id ?? null;
                    return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
                })
                ->orderColumn('first_name', function ($query, $order) {
                    $query->orderBy('users.first_name', $order)
                        ->orderBy('users.last_name', $order);
                }, 1)
                ->editColumn('total_services', function ($data) {
                    return $data->employee_booking_count ?? 0;
                })
                ->editColumn('total_service_amount', function ($data) {
                    return Currency::format($data->employee_booking_sum_service_price ?? 0);
                })
                ->editColumn('total_commission_earn', function ($data) {
                    return Currency::format($data->commission_earning_sum_commission_amount ?? 0);
                })
                ->editColumn('total_tip_earn', function ($data) {
                    return Currency::format($data->tip_earning_sum_tip_amount ?? 0);
                })
                ->editColumn('total_earning', function ($data) {
                    return Currency::format($data->commission_earning_sum_commission_amount + $data->tip_earning_sum_tip_amount);
                })
                ->editColumn('updated_at', function ($data) {
                    $module_name = $this->module_name;
                    $diff = Carbon::now()->diffInHours($data->updated_at);
                    if ($diff < 25) {
                        return $data->updated_at->diffForHumans();
                    } else {
                        return $data->updated_at->isoFormat('llll');
                    }
                })
                ->addIndexColumn()
                ->rawColumns([])
                ->orderColumns(['id'], '-:column $1')
                ->toJson();
        }

        if ($isManagerOnly) {
            $managerBranchIds = Branch::where('manager_id', $authUser->id)->pluck('id')->toArray();
            if (empty($managerBranchIds)) {
                $managerBranchIds = BranchEmployee::where('employee_id', $authUser->id)->pluck('branch_id')->toArray();
            }
            $managerBranchIds = array_values(array_unique($managerBranchIds));

            if ($isManagerMyWork) {
                // In My Work mode, ignore branch scoping
                $activeBranchId = null;
            } else {
                if (!empty($managerBranchIds)) {
                    if ($activeBranchId && ! in_array($activeBranchId, $managerBranchIds)) {
                        $activeBranchId = $managerBranchIds[0];
                    } elseif (! $activeBranchId) {
                        $activeBranchId = $managerBranchIds[0];
                    }
                } else {
                    // No branches assigned; ensure we don't unintentionally expose other managers
                    $activeBranchId = null;
                }
            }
        }

        $query = User::staffReport($activeBranchId);

        // Manager My Work: only show their own data
        if ($isManagerMyWork) {
            $query->where('users.id', $authUser->id);
        } elseif ($activeBranchId) {
            $query->whereHas('branches', function ($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId);
            });
        }

        // if ($isManagerOnly) {
        //     if (!empty($managerBranchIds)) {
        //         $query->where(function ($q) use ($managerBranchIds, $authUser) {
        //             $q->whereHas('branches', function ($branchQuery) use ($managerBranchIds) {
        //                 $branchQuery->whereIn('branch_id', $managerBranchIds);
        //             })->orWhere('users.id', $authUser->id);
        //         });
        //     } else {
        //         $query->where('users.id', $authUser->id);
        //     }

        //     $query->where(function ($q) use ($authUser) {
        //         $q->where('users.is_manager', '!=', 1)
        //             ->orWhereNull('users.is_manager')
        //             ->orWhere('users.id', $authUser->id);
        //     });
        // }

        if (isset($filter['employee_id'])) {
            $query->where('id', $filter['employee_id']);
        }

        return $datatable->eloquent($query)
            // ->editColumn('first_name', function ($data) {
            //     return $data->full_name;
            // })
            ->editColumn('first_name', function ($data) {
                $Profile_image = optional($data)->profile_image ?? default_user_avatar();
                $name = optional($data)->full_name ?? default_user_name();
                $email = optional($data)->email ?? '--';
                $id = optional($data)->id ?? null;
                return view('booking::backend.bookings.datatable.employee_id', compact('Profile_image', 'name', 'email', 'id'));
            })
            ->orderColumn('first_name', function ($query, $order) {
                $query->orderBy('users.first_name', $order) // Ordering by first name
                    ->orderBy('users.last_name', $order); // Ordering by first name
            }, 1)
            ->editColumn('total_services', function ($data) {
                return $data->employee_booking_count ?? 0;
            })
            ->editColumn('total_service_amount', function ($data) {
                return Currency::format($data->employee_booking_sum_service_price ?? 0);
            })
            ->editColumn('total_commission_earn', function ($data) {
                return Currency::format($data->commission_earning_sum_commission_amount ?? 0);
            })
            ->editColumn('total_tip_earn', function ($data) {
                return Currency::format($data->tip_earning_sum_tip_amount ?? 0);
            })
            ->editColumn('total_earning', function ($data) {
                return Currency::format($data->commission_earning_sum_commission_amount + $data->tip_earning_sum_tip_amount);
            })
            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);

                if ($diff < 25) {
                    return $data->updated_at->diffForHumans();
                } else {
                    return $data->updated_at->isoFormat('llll');
                }
            })
            ->orderColumn('total_services', function ($data, $order) {
                $data->selectRaw('(SELECT COUNT(service_id) FROM booking_services WHERE employee_id = users.id) as total_services')
                    ->orderBy('total_services', $order);
            })

            ->orderColumn('total_service_amount', function ($data, $order) {
                $data->selectRaw('(SELECT SUM(service_price) FROM booking_services WHERE employee_id = users.id) as total_service_amount')
                    ->orderBy('total_service_amount', $order);
            })

            ->orderColumn('total_service_amount', function ($data, $order) {
                $data->selectRaw('(SELECT SUM(service_price) FROM booking_services WHERE employee_id = users.id) as total_service_amount')
                    ->orderBy('total_service_amount', $order);
            })

            ->orderColumn('total_commission_earn', function ($data, $order) {
                $data->selectRaw('(SELECT SUM(commission_amount) FROM commission_earnings WHERE employee_id = users.id) as total_commission_earn')
                    ->orderBy('total_commission_earn', $order);
            })

            ->orderColumn('total_tip_earn', function ($data, $order) {
                $data->selectRaw('(SELECT SUM(tip_amount) FROM tip_earnings WHERE employee_id = users.id) as total_tip_earn')
                    ->orderBy('total_tip_earn', $order);
            })

            ->orderColumn('total_earning', function ($data, $order) {
                $data->selectRaw('(SELECT COALESCE(SUM(commission_amount), 0) FROM commission_earnings WHERE employee_id = users.id) + (SELECT COALESCE(SUM(tip_amount), 0) FROM tip_earnings WHERE employee_id = users.id) as total_earning')
                    ->orderBy('total_earning', $order);
            })

            ->addIndexColumn()
            ->rawColumns([])
            ->orderColumns(['id'], '-:column $1')
            ->toJson();
    }

    public function daily_booking_report_review(Request $request)
    {
        // Check permission to view reports (export is part of viewing)
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $this->exportClass = '\App\Exports\DailyReportsExport';

        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        
        // For employees, filter to show only their own data in export
        if ($this->isEmployeeOnly()) {
            return $this->export($request, null, $authUser->id);
        }

        // Manager + my work mode: export only manager's own data
        if ($isManagerMyWork) {
            return $this->export($request, null, $authUser->id);
        }

        // Branch scoping: honor selected branch from filter or session
        $filter = $request->filter ?? [];
        $activeBranchId = isset($filter['branch_id']) && $filter['branch_id'] !== '' ? (int) $filter['branch_id'] : null;
        if ($activeBranchId === null) {
            $selectedBranchId = $request->selected_session_branch_id ?? $request->session()->get('selected_branch');
            $activeBranchId = $selectedBranchId ? (int) $selectedBranchId : null;
        }

        // Managers: If no specific branch selected, pass array of their branch IDs
        if ($isManager && ! $isManagerMyWork && $activeBranchId === null) {
            $branchIds = $authUser->branches->pluck('id')->toArray();
            return $this->export($request, $branchIds, null);
        }

        return $this->export($request, $activeBranchId, null);
    }

    public function overall_booking_report_review(Request $request)
    {
        // Check permission to view reports (export is part of viewing)
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $this->exportClass = '\App\Exports\OverallReportsExport';

        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isManagerMyWork = $isManager && session('my_work_mode', false);

        // For employees, filter to show only their own data in export
        if ($this->isEmployeeOnly()) {
            return $this->export($request, null, $authUser->id, null);
        }

        // Manager + my work mode: export only manager's own data (manager data)
        if ($isManagerMyWork) {
            return $this->export($request, null, $authUser->id, null);
        }

        // Branch selected: export all data (including manager as staff)
        $filter = $request->filter ?? [];
        $activeBranchId = isset($filter['branch_id']) && $filter['branch_id'] !== '' ? (int) $filter['branch_id'] : null;
        if ($activeBranchId === null) {
            $selectedBranchId = $request->selected_session_branch_id ?? $request->session()->get('selected_branch');
            $activeBranchId = $selectedBranchId ? (int) $selectedBranchId : null;
        }

        return $this->export($request, $activeBranchId, null, null);
    }

    public function payout_report_review(Request $request)
    {
        // Check permission to view reports (export is part of viewing)
        if (!$this->hasReportPermission('view_reports')) {
            abort(403, __('messages.permission_denied'));
        }

        $this->exportClass = '\App\Exports\StaffPayoutReportExport';

        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isManagerMyWork = $isManager && session('my_work_mode', false);
        
        // For employees, filter to show only their own data in export
        if ($this->isEmployeeOnly()) {
            // Pass employee ID to export class to filter only their data
            return $this->export($request, null, $authUser->id);
        }

        // Get branch ID (similar to payout_report_index_data)
        $filter = $request->filter ?? [];
        $activeBranchId = null;
        $excludeManagerId = null;

        if ($isManagerMyWork) {
            // In My Work mode, show only manager's own data
            return $this->export($request, null, $authUser->id);
        } else {
            // Branch filtering: explicit branch_id overrides session-selected branch
            $selectedBranchId = $request->selected_session_branch_id ?? request()->session()->get('selected_branch');
            
            if (isset($filter['branch_id']) && $filter['branch_id'] !== '') {
                $activeBranchId = $filter['branch_id'];
            } elseif (!empty($selectedBranchId)) {
                $activeBranchId = $selectedBranchId;
            }
        }

        return $this->export($request, $activeBranchId, null, $excludeManagerId);
    }

    public function staff_report_review(Request $request)
    {
        $this->exportClass = '\App\Exports\StaffServiceReportExport';

        $authUser = auth()->user();
        $isManager = $authUser && $authUser->hasRole('manager');
        $isManagerMyWork = $isManager && session('my_work_mode', false);

        // For employees, filter to show only their own data in export
        if ($this->isEmployeeOnly()) {
            // Pass employee ID to export class to filter only their data
            return $this->export($request, null, $authUser->id);
        }

        if ($isManagerMyWork) {
            // In My Work mode, only show manager's own data
            return $this->export($request, null, $authUser->id);
        }

        // Get branch_id for manager filtering
        $filter = $request->filter ?? [];
        $explicitBranchId = isset($filter['branch_id']) && $filter['branch_id'] !== '' ? (int) $filter['branch_id'] : null;
        $selectedBranchId = $request->selected_session_branch_id ?? $request->session()->get('selected_branch');
        $activeBranchId = $explicitBranchId ?? ($selectedBranchId ? (int) $selectedBranchId : null);

        // If Manager is viewing a specific branch (not My Work), we don't exclude them from Staff Report 
        // (unlike Daily Report where they might want only staff activity)
        // Usually Staff Report should show all staff in that branch.

        return $this->export($request, $activeBranchId);
    }
    public function order_booking_report_review(Request $request)
    {
        $this->exportClass = '\App\Exports\OrderReportsExport';

        return $this->export($request);
    }
}
