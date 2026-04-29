<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Package\Models\BookingPackages;
use Modules\Booking\Models\BookingTransaction;
use Modules\Product\Models\Order;
use Modules\Product\Models\OrderGroup;
use Modules\Employee\Models\BranchEmployee;
use Modules\Commission\Models\CommissionEarning;
use Modules\Tip\Models\TipEarning;
use App\Models\Branch;
use Modules\Wallet\Models\WalletHistory;
use Modules\Earning\Models\EmployeeEarning;

class BackendController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        \Log::info('Dashboard access check', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_roles' => $user->roles->pluck('name')->toArray(),
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'has_view_dashboard_direct' => $user->hasPermissionTo('view_dashboard'),
            'can_view_dashboard' => $user->can('view_dashboard'),
            'request_url' => $request->fullUrl()
        ]);

        // Check if user has dashboard permission
        $hasDashboardPermission = $user->can('view_dashboard');

        // Enforce strict permission check: If the user has any of the defined roles, 
        // that role MUST have the permission. (Intersection logic as requested).
        if (!$user->hasRole('admin')) {
            $strictRoles = ['manager', 'employee', 'expert', 'user'];
            foreach ($strictRoles as $roleName) {
                if ($user->hasRole($roleName)) {
                    $roleModel = \Spatie\Permission\Models\Role::where('name', $roleName)->first();
                    if ($roleModel && !$roleModel->hasPermissionTo('view_dashboard')) {
                        $hasDashboardPermission = false;
                        \Log::info("Strict Permission Check: Role '{$roleName}' does not have view_dashboard permission. Access denied.");
                        break;
                    }
                }
            }
        }

        if (!$hasDashboardPermission) {
            \Log::info('User does not have view_dashboard permission - checking for alternative routes');

            // Get the first accessible route using PermissionHelper
            $accessibleRoute = \App\Helpers\PermissionHelper::getFirstAccessibleRoute();

            if ($accessibleRoute) {
                \Log::info('Redirecting to accessible route', ['route' => $accessibleRoute]);
                return redirect()->route($accessibleRoute);
            } else {
                \Log::info('No accessible routes found - aborting 403');
                abort(403, 'You do not have permission to access any dashboard or module.');
            }
        }

        \Log::info('User has view_dashboard permission - showing dashboard');
        $global_booking = false;
        $today = Carbon::today();
        $action = $request->action ?? 'reset';
        if (isset($request->date_range) && $action !== 'reset') {
            $parts = $this->splitFlatpickrRange($request->date_range);
            try {
                if (count($parts) >= 2) {
                    $startDate = Carbon::parse($parts[0])->toDateString();
                    $endDate = Carbon::parse($parts[1])->toDateString();
                } elseif (count($parts) === 1) {
                    $startDate = Carbon::parse($parts[0])->toDateString();
                    $endDate = $startDate;
                } else {
                    throw new \InvalidArgumentException('empty date range');
                }
            } catch (\Throwable $e) {
                $startDate = Carbon::now()->subDays(10)->toDateString();
                $endDate = Carbon::now()->toDateString();
            }
        } else {
            $startDate = Carbon::now()->subDays(10)->toDateString();
            $endDate = Carbon::now()->toDateString();
        }

        $date_range = $startDate . ' to ' . $endDate;
        $startDateTime = Carbon::parse($startDate)->startOfDay();
        $endDateTime = Carbon::parse($endDate)->endOfDay();

        // Check if logged-in user is an employee (staff)
        $isEmployee = auth()->user()->hasRole('employee');
        $isManager = auth()->user()->hasRole('manager');

        // "My Work" means: manager sees only their own bookings/earnings regardless of branch
        $myWorkMode = session('my_work_mode', false);
        $isManagerMyWork = $isManager && $myWorkMode;

        // Filter by employee if: staff is logged in OR manager is in My Work mode
        $filterByEmployee = ($isEmployee && ! $isManager) || $isManagerMyWork;
        $employeeId = $filterByEmployee ? auth()->id() : null;
        $managerEmployeeIds = [];
        $managerBranches = collect();

        $data = [
            'total_appointments' => 0,
            'total_commission' => 0,
            'total_revenue' => 0,
            'total_new_customers' => 0,
            'total_returning_customers' => 0,
            'total_customers_served' => 0,
            'total_available_services' => 0,
            'upcomming_appointments' => [],
            'top_services' => [],
            'revenue_chart' => [],
            'total_orders' => 0,
            'product_sales' => 0,
            'total_staff' => 0,
            'total_services' => 0,
            'employee_commission' => \Currency::format(0),
            'manager_commission' => \Currency::format(0),
            'payout_amount' => \Currency::format(0),
            'cancelled_bookings_count' => 0,
            'avg_revenue_per_booking' => \Currency::format(0),
        ];

        $selectedBranchId = request()->selected_session_branch_id ?? session('selected_branch');
        $selectedBranchId = $isManagerMyWork ? null : $selectedBranchId;
        $applyBranchScope = ! $isManagerMyWork;

        if ($isEmployee && ! $isManager) {
            $selectedBranchId = null;
            $applyBranchScope = false;
        }

        $bookingQuery = Booking::where('status', 'completed')
            ->whereBetween('start_date_time', [$startDateTime, $endDateTime]);

        if ($selectedBranchId) {
            $bookingQuery->where('branch_id', $selectedBranchId);
        } elseif ($applyBranchScope) {
            $bookingQuery->branch();
        }

        // Filter by employee if staff is logged in OR manager is in My Work mode
        if ($filterByEmployee && $employeeId) {
            $bookingQuery->where(function ($query) use ($employeeId) {
                $query->whereHas('bookingService', function ($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                })
                    ->orWhereHas('bookingPackages', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('products', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    });
            });
        }

        $cancelledBookingsQuery = Booking::where('status', 'cancelled')
            ->whereBetween('start_date_time', [$startDateTime, $endDateTime]);

        if ($selectedBranchId) {
            $cancelledBookingsQuery->where('branch_id', $selectedBranchId);
        } elseif ($applyBranchScope) {
            $cancelledBookingsQuery->branch();
        }

        if ($filterByEmployee && $employeeId) {
            $cancelledBookingsQuery->where(function ($query) use ($employeeId) {
                $query->whereHas('bookingService', function ($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                })
                    ->orWhereHas('bookingPackages', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('products', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    });
            });
        }

        $data['cancelled_bookings_count'] = $cancelledBookingsQuery->count();

        // My Work: shows only manager's own bookings across all branches

        $completedBookingsBySchedule = $bookingQuery->pluck('id');

        $completedBookingsByTransactionQuery = BookingTransaction::whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereHas('booking', function ($query) use ($filterByEmployee, $employeeId, $selectedBranchId, $applyBranchScope) {
                $query->where('status', 'completed');

                // Apply branch filtering; My Work skips branch scoping
                if ($selectedBranchId) {
                    $query->where('branch_id', $selectedBranchId);
                } elseif ($applyBranchScope) {
                    $query->branch();
                }

                if ($filterByEmployee && $employeeId) {
                    $query->where(function ($q) use ($employeeId) {
                        $q->whereHas('bookingService', function ($subQ) use ($employeeId) {
                            $subQ->where('employee_id', $employeeId);
                        })
                            ->orWhereHas('bookingPackages', function ($subQ) use ($employeeId) {
                                $subQ->where('employee_id', $employeeId);
                            })
                            ->orWhereHas('products', function ($subQ) use ($employeeId) {
                                $subQ->where('employee_id', $employeeId);
                            });
                    });
                }
            });

        $completedBookingsByTransaction = $completedBookingsByTransactionQuery->pluck('booking_id');

        $completedBookingIds = $completedBookingsBySchedule
            ->merge($completedBookingsByTransaction)
            ->unique();

        // $data['payout_amount'] = WalletHistory::where('user_id', auth()->id())
        //     ->get()
        //     ->sum(function ($entry) {
        //         $payload = json_decode($entry->activity_data, true) ?: [];
        //         $amount = $payload['credit_debit_amount'] ?? $payload['amount'] ?? $entry->credit_debit_amount ?? 0;
        //         $isCredit = ($payload['transaction_type'] ?? $entry->transaction_type ?? '') === 'credit';
        //         return $isCredit ? (float) $amount : 0;
        //     });
        // $data['payout_amount'] = \Currency::format($data['payout_amount']);

        // Payout amount should be the sum of total_amount (commission + tips) from EmployeeEarning
        // This matches what's shown in the payout report
        $data['payout_amount'] = EmployeeEarning::where('employee_id', auth()->id())
            ->sum('total_amount');
        $data['payout_amount'] = \Currency::format($data['payout_amount']);


        $data['total_appointments'] = $completedBookingIds->count();

        // Count unique customers served by staff member from completed bookings
        if ($filterByEmployee && $employeeId && $completedBookingIds->isNotEmpty()) {
            $customersServedQuery = Booking::whereIn('id', $completedBookingIds->all())
                ->whereHas('user', function ($q) {
                    $q->whereNull('deleted_at');
                })
                ->select('user_id')
                ->distinct();

            $data['total_customers_served'] = $customersServedQuery->count();
        } else {
            $data['total_customers_served'] = 0;
        }

        $commissionBookings = $completedBookingIds->isNotEmpty()
            ? Booking::with('commission')->whereIn('id', $completedBookingIds->all())->get()
            : collect();

        $data['total_commission'] = \Currency::format($commissionBookings->sum(function ($booking) {
            return $booking->commission->commission_amount ?? 0;
        }));

        $topServiceBookingIds = $completedBookingIds->isNotEmpty() ? $completedBookingIds->all() : [];
        $totalServices = BookingService::query();

        if (! empty($topServiceBookingIds)) {
            $totalServices->whereIn('booking_id', $topServiceBookingIds);
        } else {
            $totalServices->whereRaw('0 = 1');
        }

        if ($filterByEmployee && $employeeId) {
            $totalServices->where('employee_id', $employeeId);
        }

        $data['total_services'] = $totalServices->count();

        if ($isEmployee && $employeeId) {
            $availableServicesQuery = \Modules\Service\Models\ServiceEmployee::where('employee_id', $employeeId)
                ->whereHas('service', function ($q) {
                    $q->whereNull('deleted_at');
                });

            $data['total_available_services'] = $availableServicesQuery->count();
        }

        $bookingIdsForRevenue = $completedBookingsByTransaction->unique()->values()->toArray();

        $bookingTransactions = ! empty($bookingIdsForRevenue)
            ? BookingTransaction::whereBetween('created_at', [$startDateTime, $endDateTime])
            ->whereIn('booking_id', $bookingIdsForRevenue)
            ->get()
            : collect();

        $groupedTransactions = $bookingTransactions->sortBy('created_at')->groupBy('booking_id');
        $transactionsGroupedByDate = $bookingTransactions->sortBy('created_at')->groupBy(function ($transaction) {
            return Carbon::parse($transaction->created_at)->toDateString();
        });
        $bookingsForRevenueQuery = ! empty($bookingIdsForRevenue)
            ? Booking::with(['services', 'bookingPackages', 'userCouponRedeem'])
            ->whereIn('id', $bookingIdsForRevenue)
            : Booking::whereRaw('0 = 1');

        // Filter by employee if staff is logged in OR manager is in My Work mode
        if ($filterByEmployee && $employeeId) {
            $bookingsForRevenueQuery->where(function ($query) use ($employeeId) {
                $query->whereHas('bookingService', function ($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                })
                    ->orWhereHas('bookingPackages', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('products', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    });
            });
        }

        // My Work uses the employee filter above to show only manager's own bookings

        $bookingsForRevenue = ! empty($bookingIdsForRevenue) ? $bookingsForRevenueQuery->get() : collect();

        $bookingNetTotals = [];
        $totalDiscountAmount = 0;
        $totalRevenueAmount = 0;

        foreach ($bookingsForRevenue as $booking) {
            // Filter services by employee if staff is logged in OR manager is in My Work mode
            $services = $booking->services;
            if ($filterByEmployee && $employeeId) {
                $services = $services->where('employee_id', $employeeId);
            }
            $serviceAmount = $services->sum('service_price');

            // Filter packages by employee if staff is logged in OR manager is in My Work mode
            $packages = $booking->bookingPackages
                ->filter(fn($package) => (int) ($package->is_reclaim ?? 0) === 0);
            if ($filterByEmployee && $employeeId) {
                $packages = $packages->where('employee_id', $employeeId);
            }
            $packageAmount = $packages->sum('package_price');

            $grossAmount = $serviceAmount + $packageAmount;
            $transactions = $groupedTransactions->get($booking->id, collect());

            $transactionDiscount = $transactions->sum(function ($txn) use ($grossAmount) {
                if ($txn->discount_amount > 0) {
                    return $txn->discount_amount;
                }

                if ($txn->discount_percentage > 0) {
                    return ($grossAmount * $txn->discount_percentage) / 100;
                }

                return 0;
            });

            $transactionTip = $transactions->sum('tip_amount');

            $taxPayloadArray = null;
            $latestTransaction = $transactions->last();
            if ($latestTransaction && ! empty($latestTransaction->tax_percentage)) {
                $taxPayload = $latestTransaction->tax_percentage;
                $taxPayloadArray = is_array($taxPayload) ? $taxPayload : json_decode($taxPayload, true);
            }

            $couponDiscount = optional($booking->userCouponRedeem)->discount ?? 0;
            $discountAmount = $transactionDiscount > 0 ? $transactionDiscount : $couponDiscount;

            $taxAmount = 0;
            if (! empty($taxPayloadArray)) {
                $taxBreakdown = getBookingTaxamount($grossAmount, $discountAmount, $taxPayloadArray);
                $taxAmount = $taxBreakdown['total_tax_amount'] ?? 0;
            }

            $netAmount = max($grossAmount - $discountAmount, 0) + $taxAmount + $transactionTip;

            $bookingNetTotals[$booking->id] = $netAmount;
            $totalRevenueAmount += $netAmount;
            $totalDiscountAmount += $discountAmount;
        }

        $data['total_revenue'] = \Currency::format($totalRevenueAmount);
        $data['total_discount_amount'] = \Currency::format($totalDiscountAmount);

        $data['avg_revenue_per_booking'] = \Currency::format(
            $data['total_appointments'] > 0 ? $totalRevenueAmount / $data['total_appointments'] : 0
        );

        // Calculate total staff earning (commission + tips)
        $totalCommission = 0;
        $totalTips = 0;

        if ($selectedBranchId) {
            $employeeCommissionQuery = CommissionEarning::whereHas('getbooking', function ($query) use ($selectedBranchId, $startDate, $endDate) {
                $query->where('branch_id', $selectedBranchId)
                    ->where('status', 'completed')
                    ->where('start_date_time', '>=', $startDate)
                    ->whereDate('start_date_time', '<=', $endDate);
            });
            $totalCommission = $employeeCommissionQuery->sum('commission_amount');

            $employeeTipQuery = TipEarning::where('tip_status', 'unpaid')->whereHas('tippable', function ($query) use ($selectedBranchId, $startDate, $endDate) {
                $query->where('branch_id', $selectedBranchId)
                    ->where('status', 'completed')
                    ->where('start_date_time', '>=', $startDate)
                    ->whereDate('start_date_time', '<=', $endDate);
            });
            $totalTips = $employeeTipQuery->sum('tip_amount');
        } elseif ($isManagerMyWork) {
            $employeeCommissionQuery = CommissionEarning::where('employee_id', auth()->id())
                ->where('commission_status', 'unpaid')
                ->whereHas('getbooking', function ($query) {
                    $query->where('status', 'completed');
                });
            $totalCommission = $employeeCommissionQuery->sum('commission_amount');

            $employeeTipQuery = TipEarning::where('employee_id', auth()->id())
                ->where('tip_status', 'unpaid')
                ->where('tippable_type', Booking::class)
                ->whereHas('tippable', function ($query) {
                    $query->where('status', 'completed');
                });
            $totalTips = $employeeTipQuery->sum('tip_amount');
        } else {
            $employeeCommissionQuery = CommissionEarning::where('employee_id', auth()->id())
                ->where('commission_status', 'unpaid');
            $totalCommission = $employeeCommissionQuery->sum('commission_amount');

            $employeeTipQuery = TipEarning::where('employee_id', auth()->id())
                ->where('tip_status', 'unpaid')
                ->where('tippable_type', Booking::class);
            $totalTips = $employeeTipQuery->sum('tip_amount');
        }

        // Calculate total staff earning:
        // 1. Sum of all paid earnings from EmployeeEarning (after payout)
        // 2. Plus unpaid commission and tips (not yet paid out)
        $paidEarnings = EmployeeEarning::where('employee_id', auth()->id())
            ->sum('total_amount');

        // Calculate total staff earning (paid earnings + unpaid commission + unpaid tips)
        $totalStaffEarning = $paidEarnings + $totalCommission + $totalTips;

        // If Manager is in "My Work" mode, show Total Earnings (Paid + Unpaid).
        // Otherwise (Branch View), show Unpaid Commission (Liability).
        $data['employee_commission'] = ($isManagerMyWork)
            ? \Currency::format($totalStaffEarning)
            : \Currency::format($totalCommission);
        $data['my_earnings'] = \Currency::format($totalStaffEarning);

        $data['total_new_customers'] = User::where('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'user');
            })
            ->count();

        $returningCustomersQuery = Booking::query()
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 3');

        $selectedBranchId = $isManagerMyWork ? null : (request()->selected_session_branch_id ?? session('selected_branch'));
        if ($selectedBranchId) {
            $returningCustomersQuery->where('branch_id', $selectedBranchId);
        } elseif ($applyBranchScope) {
            $returningCustomersQuery->branch();
        }

        $data['total_returning_customers'] = $returningCustomersQuery->count();

        $datetime = Carbon::now()->setTimezone(setting('default_time_zone') ?? 'UTC');

        $upcomingAppointmentsQuery = Booking::with('branch', 'user', 'services')
            ->where('start_date_time', '>=', $datetime)->orderBy('start_date_time')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->whereHas('branch', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->whereNotIn('status', ['completed', 'cancelled','check_in']); // Exclude both statuses

        $selectedBranchId = $isManagerMyWork ? null : (request()->selected_session_branch_id ?? session('selected_branch'));
        if ($selectedBranchId) {
            $upcomingAppointmentsQuery->where('branch_id', $selectedBranchId);
        } elseif ($applyBranchScope) {
            $upcomingAppointmentsQuery->branch();
        }

        if ($filterByEmployee && $employeeId) {
            $upcomingAppointmentsQuery->where(function ($query) use ($employeeId) {
                $query->whereHas('bookingService', function ($q) use ($employeeId) {
                    $q->where('employee_id', $employeeId);
                })
                    ->orWhereHas('bookingPackages', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    })
                    ->orWhereHas('products', function ($q) use ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    });
            });
        }

        $data['upcomming_appointments'] = $upcomingAppointmentsQuery->take(10)->get();

        $topServicesQuery = $totalServices->with('service')
            ->whereHas('service')
            ->select(
                'service_id',
                \DB::raw('COUNT(*) as total_service_count'),
                \DB::raw('SUM(service_price) as total_service_price')
            )
            ->groupBy('service_id')
            ->orderByDesc('total_service_price')
            ->limit(5);

        $data['top_services'] = $topServicesQuery->get();

        $serviceTotalsQuery = ! empty($bookingIdsForRevenue)
            ? BookingService::select('booking_id', \DB::raw('SUM(service_price) as total_service_price'))
            ->whereIn('booking_id', $bookingIdsForRevenue)
            : BookingService::whereRaw('0 = 1');

        if ($filterByEmployee && $employeeId) {
            $serviceTotalsQuery->where('employee_id', $employeeId);
        }

        $serviceTotals = ! empty($bookingIdsForRevenue)
            ? $serviceTotalsQuery->groupBy('booking_id')
            ->pluck('total_service_price', 'booking_id')
            ->toArray()
            : [];

        $packageTotalsQuery = ! empty($bookingIdsForRevenue)
            ? BookingPackages::select('booking_id', \DB::raw('SUM(package_price) as total_package_price'))
            ->whereIn('booking_id', $bookingIdsForRevenue)
            : BookingPackages::whereRaw('0 = 1');

        if ($filterByEmployee && $employeeId) {
            $packageTotalsQuery->where('employee_id', $employeeId);
        }

        $packageTotals = ! empty($bookingIdsForRevenue)
            ? $packageTotalsQuery->groupBy('booking_id')
            ->pluck('total_package_price', 'booking_id')
            ->toArray()
            : [];

        $chartBookingRevenue = [];

        if (! empty($bookingIdsForRevenue)) {
            $chartBookingRevenue = $transactionsGroupedByDate
                ->map(function ($transactions, $date) use ($bookingNetTotals) {
                    $bookingIds = $transactions->pluck('booking_id')->unique();

                    $totalPrice = $bookingIds->sum(fn($bookingId) => $bookingNetTotals[$bookingId] ?? 0);

                    return [
                        'booking_date' => $date,
                        'total_booking' => $bookingIds->count(),
                        'total_price' => $totalPrice,
                    ];
                })
                ->values();
        }

        $data['revenue_chart']['xaxis'] = collect($chartBookingRevenue)->pluck('booking_date')->toArray();
        $data['revenue_chart']['total_bookings'] = collect($chartBookingRevenue)->pluck('total_booking')->toArray();
        $data['revenue_chart']['total_price'] = collect($chartBookingRevenue)->pluck('total_price')->toArray();

        $ordersBaseQuery = Order::query()
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDateTime, $endDateTime]);

        if ($selectedBranchId) {
            $ordersBaseQuery->where(function ($q) use ($selectedBranchId) {
                $q->whereHas('bookingProducts.booking', function ($subQ) use ($selectedBranchId) {
                    $subQ->where('branch_id', $selectedBranchId);
                })->orWhereDoesntHave('bookingProducts');
            });
        } elseif ($applyBranchScope) {
            $ordersBaseQuery->where(function ($q) {
                $q->whereHas('bookingProducts.booking', function ($subQ) {
                    $subQ->branch();
                })->orWhereDoesntHave('bookingProducts');
            });
        }

        $data['total_orders'] = (clone $ordersBaseQuery)->count();

        $data['product_sales'] = \Currency::format(
            (clone $ordersBaseQuery)->sum('total_admin_earnings')
        );

        if ($request->selected_session_branch_id) {
            $data['total_staff'] = BranchEmployee::where('branch_id', $request->selected_session_branch_id)
                ->whereHas('employee', function ($query) {
                    $query->where('is_manager', 0)->active();
                })
                ->count();
        }

        // $data['employee_commission'] already filtered by branch above when applicable
        // if (auth()->user()->hasRole('manager')) {
        //     $managerCommission = CommissionEarning::where('employee_id', auth()->id())
        //         ->where('commissionable_type', Booking::class)
        //         ->whereHas('getbooking', function ($query) use ($startDate, $endDate, $request) {
        //             $query->whereDate('start_date_time', '>=', $startDate)
        //                 ->whereDate('start_date_time', '<=', $endDate);
        //             if ($request->selected_session_branch_id) {
        //                 $query->where('branch_id', $request->selected_session_branch_id);
        //             }
        //         })
        //         ->sum('commission_amount');

        //     $data['manager_commission'] = \Currency::format($managerCommission);
        // }

        if (auth()->user()->hasRole('manager')) {
            $view = 'backend.manager.dashboard';
        } elseif (auth()->user()->hasRole('employee')) {
            $view = 'backend.employee.dashboard';
        } else {
            $view = 'backend.index';
        }

        return view($view, compact('data', 'date_range', 'global_booking'));
    }


    public function setCurrentBranch($branch_id)
    {
        request()->session()->forget('selected_branch');
        // Clear "My Work" mode when a branch is selected, so branch selection takes precedence
        request()->session()->forget('my_work_mode');

        request()->session()->put('selected_branch', $branch_id);

        return redirect()->back()->with('success', 'Current Branch Has Been Changes')->withInput();
    }

    public function resetBranch()
    {
        request()->session()->forget('selected_branch');

        return redirect()->back()->with('success', 'Show All Branch Content')->withInput();
    }

    public function setMyWork()
    {
        // Activate "My Work" mode - this will override branch selection
        request()->session()->put('my_work_mode', true);

        return redirect()->back()->with('success', 'My Work Mode Activated')->withInput();
    }

    public function resetMyWork()
    {
        request()->session()->forget('my_work_mode');

        return redirect()->back()->with('success', 'My Work Mode Deactivated')->withInput();
    }

    public function setUserSetting(Request $request)
    {
        auth()->user()->update(['user_setting' => $request->settings]);

        return response()->json(['status' => true]);
    }

    public function UsersInquiries()
    {
        return view('backend.users-inquiries.index');
    }

    /**
     * Get the first accessible route name when user doesn't have dashboard permission
     * Returns route name string or null if no accessible routes found
     */
    protected function getFirstAccessibleRoute()
    {
        $user = auth()->user();

        // Define routes to check in order of priority - these are common routes that users might have access to
        $routesToCheck = [
            'backend.bookings.index',         // Calendar Bookings
            'backend.bookings.datatable_view', // Bookings
            'backend.services.index',         // Services
            'backend.employees.index',        // Staff
            'backend.customers.index',        // Customers
            'backend.employees.review',       // Reviews
            'backend.reports.daily-booking-report', // Daily Bookings Report
            'backend.settings',               // Settings
        ];

        \Log::info('Checking for accessible routes', [
            'user_id' => $user->id,
            'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray()
        ]);

        // Check each route for permission
        foreach ($routesToCheck as $routeName) {
            try {
                // Check if route exists
                $route = \Route::getRoutes()->getByName($routeName);
                if ($route) {
                    // Get permission from menu config
                    $permission = $this->getPermissionForRoute($routeName);
                    $hasPermission = empty($permission) || $user->can($permission);

                    \Log::info('Checking route access', [
                        'route' => $routeName,
                        'permission_required' => $permission,
                        'has_permission' => $hasPermission,
                        'user_can' => $permission ? $user->can($permission) : 'no_permission_required',
                        'route_exists' => true
                    ]);

                    // If no permission is required for this route, or user has the permission, return route name
                    if ($hasPermission) {
                        \Log::info('Found accessible route', ['route' => $routeName]);
                        return $routeName;
                    }
                } else {
                    \Log::info('Route does not exist', ['route' => $routeName, 'route_exists' => false]);
                }
            } catch (\Exception $e) {
                \Log::info('Exception checking route', ['route' => $routeName, 'error' => $e->getMessage()]);
                continue;
            }
        }

        \Log::info('No accessible routes found');
        // No accessible routes found
        return null;
    }

    /**
     * Get permission required for a specific route from menu config
     */
    protected function getPermissionForRoute($routeName)
    {
        $menuConfig = config('menubuilder.ARRAY_MENU', []);

        // Flatten the menu config to find the route
        $flattenMenus = function($menus) use (&$flattenMenus, $routeName) {
            $flat = [];
            foreach ($menus as $menu) {
                if (isset($menu['route']) && $menu['route'] === $routeName) {
                    $flat[] = $menu;
                }
                if (isset($menu['children']) && is_array($menu['children'])) {
                    $flat = array_merge($flat, $flattenMenus($menu['children']));
                }
            }
            return $flat;
        };

        $matchingMenus = $flattenMenus($menuConfig);

        if (!empty($matchingMenus)) {
            $menu = $matchingMenus[0];
            if (isset($menu['permission']) && is_array($menu['permission']) && !empty($menu['permission'])) {
                // Return the first permission (usually there's only one)
                return $menu['permission'][0];
            }
        }

        return null;
    }
}