<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Earning\Models\EmployeeEarning;
use Modules\Employee\Models\BranchEmployee;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;
use Modules\Wallet\Models\WithdrawMoney;
use App\Models\User;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Commission\Models\CommissionEarning;
use Modules\Tip\Models\TipEarning;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Order;

class ReportsController extends Controller
{
    public function staffServiceReportList(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || (!$authUser->hasRole('admin') && !$authUser->hasRole('manager'))) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        // Determine date range filter
        $dateRangeFilter = null;
        if ($request->filled('date_range')) {
            $range = $request->date_range;
            if ($range === 'today') {
                $dateRangeFilter = [
                    'start' => Carbon::today()->startOfDay(),
                    'end' => Carbon::today()->endOfDay()
                ];
            } elseif ($range === 'week') {
                $dateRangeFilter = [
                    'start' => Carbon::now()->startOfWeek(),
                    'end' => Carbon::now()->endOfWeek()
                ];
            } elseif ($range === 'month') {
                $dateRangeFilter = [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth()
                ];
            }
        }

        // Base query using existing report scope
        $branchId = $request->filled('branch_id') ? (int)$request->branch_id : null;
        $query = User::staffReport($branchId);

        // Add email_verified_at to the select since staffReport scope doesn't include it
        $query->addSelect('users.email_verified_at');

        /**
         * Search filter
         */
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        /**
         * Role filter (optional)
         */
        if ($request->filled('role')) {
            $query->role($request->role);
        }

        /**
         * Branch filter (already handled in staffReport scope, but we filter staff here too)
         */
        if ($request->filled('branch_id')) {
            $branchId = (int)$request->branch_id;
            $query->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        /**
         * Date range filter: Filter staff who have bookings in the date range
         */
        if ($dateRangeFilter) {
            $query->whereHas('bookings', function ($q) use ($dateRangeFilter, $branchId) {
                $q->where('status', 'completed')
                    ->whereBetween('start_date_time', [
                        $dateRangeFilter['start'],
                        $dateRangeFilter['end']
                    ]);
                // Apply branch filter to date range query as well
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });
        }

        $perPage = (int)$request->input('per_page', 10);
        $staff = $query->paginate($perPage);

        // Calculate metrics - use pre-calculated values if no date filter, otherwise recalculate
        $data = $staff->getCollection()->map(function ($item) use ($dateRangeFilter, $branchId) {
            $staffId = $item->id;

            // If no date range filter, use pre-calculated values from staffReport scope
            // Note: staffReport scope already filters by branch_id if provided, so values are branch-specific
            if (!$dateRangeFilter) {
                $commission = (float)($item->commission_earning_sum_commission_amount ?? 0);
                $tips = (float)($item->tip_earning_sum_tip_amount ?? 0);

                return [
                    'staff_id' => $staffId,
                    'staff_name' => $item->full_name,
                    'staff_email' => $item->email,
                    'staff_profile_image' => $item->profile_image,
                    'staff_is_verified' => !is_null($item->email_verified_at),
                    'staff_total_booking' => (int)($item->employee_booking_count ?? 0),
                    'staff_total_amount' => round((float)($item->employee_booking_sum_service_price ?? 0), 2),
                    'staff_commission_earned' => round($commission, 2),
                    'staff_earning' => round(($commission + $tips), 2),
                    'staff_tip_earned' => round($tips, 2),
                ];
            }

            // When date range is provided, we need to recalculate with both date and branch filters

            // Recalculate with date range filter
            $bookingQuery = Booking::where('status', 'completed')
                ->whereHas('bookingTransaction', function ($q) {
                    $q->where('payment_status', 1);
                })
                ->whereBetween('start_date_time', [
                    $dateRangeFilter['start'],
                    $dateRangeFilter['end']
                ]);

            // Always apply branch filter if provided - this ensures all metrics are branch-specific
            if ($branchId) {
                $bookingQuery->where('branch_id', $branchId);
            }

            // Get booking IDs for this staff member
            $bookingIds = BookingService::where('employee_id', $staffId)
                ->whereIn('booking_id', $bookingQuery->pluck('id'))
                ->pluck('booking_id')
                ->unique();

            // Calculate total bookings count
            $totalBooking = $bookingIds->count();

            // Calculate total amount (service price sum minus discounts)
            $totalAmount = 0;
            $totalCommission = 0;
            $totalTips = 0;

            if ($bookingIds->isNotEmpty()) {
                // Calculate service price sum
                $servicePriceSum = BookingService::where('employee_id', $staffId)
                    ->whereIn('booking_id', $bookingIds)
                    ->sum('service_price');

                // Calculate total discount proportionally for this employee
                $bookingTotalAmounts = DB::table('booking_services')
                    ->select('booking_id', DB::raw('SUM(service_price) as total_price'))
                    ->whereIn('booking_id', $bookingIds)
                    ->groupBy('booking_id')
                    ->pluck('total_price', 'booking_id');

                $proportionalDiscount = 0;
                foreach ($bookingIds as $bid) {
                    $bookingTotal = $bookingTotalAmounts[$bid] ?? 0;
                    if ($bookingTotal > 0) {
                        $employeeBookingAmount = BookingService::where('booking_id', $bid)
                            ->where('employee_id', $staffId)
                            ->sum('service_price');

                        // Get discount from transaction and coupon
                        $transactionDiscount = DB::table('booking_transactions')
                            ->where('booking_id', $bid)
                            ->selectRaw('COALESCE(
                                CASE WHEN discount_amount > 0 THEN discount_amount
                                WHEN discount_percentage > 0 THEN ? * discount_percentage / 100
                                ELSE 0 END,
                                0
                            ) as discount', [$bookingTotal])
                            ->value('discount') ?? 0;

                        $couponDiscount = DB::table('user_coupon_redeem')
                            ->where('booking_id', $bid)
                            ->value('discount') ?? 0;

                        $bookingDiscount = $transactionDiscount + $couponDiscount;
                        $proportionalDiscount += ($employeeBookingAmount / $bookingTotal) * $bookingDiscount;
                    }
                }

                $totalAmount = max(0, $servicePriceSum - $proportionalDiscount);

                // Calculate commission (only for bookings in the filtered set, which already includes branch filter)
                $totalCommission = CommissionEarning::where('employee_id', $staffId)
                    ->where('commissionable_type', Booking::class)
                    ->whereIn('commissionable_id', $bookingIds)
                    ->sum('commission_amount') ?? 0;

                // Calculate tips (only for bookings in the filtered set, which already includes branch filter)
                $totalTips = TipEarning::where('employee_id', $staffId)
                    ->where('tippable_type', Booking::class)
                    ->whereIn('tippable_id', $bookingIds)
                    ->sum('tip_amount') ?? 0;
            }

            return [
                'staff_id' => $staffId,
                'staff_name' => $item->full_name,
                'staff_email' => $item->email,
                'staff_profile_image' => $item->profile_image,
                'staff_is_verified' => !is_null($item->email_verified_at) ? true : false,
                'staff_total_booking' => (int)$totalBooking,
                'staff_total_amount' => round((float)$totalAmount, 2),
                'staff_commission_earned' => round((float)$totalCommission, 2),
                'staff_earning' => round((float)($totalCommission + $totalTips), 2),
                'staff_tip_earned' => round((float)$totalTips, 2),
            ];
        })->values();

        /**
         * Final API Response
         */
        return response()->json([
            'status' => true,
            'message' => 'Staff Service Report fetched successfully.',
            'data' => $data,
            'pagination' => [
                'current_page' => $staff->currentPage(),
                'per_page' => $staff->perPage(),
                'total' => $staff->total(),
                'last_page' => $staff->lastPage(),
            ]
        ], 200);
    }

    public function managerReportDashboard(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || (!$authUser->hasRole('admin') && !$authUser->hasRole('manager'))) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        // Get branch_id from request
        $branchId = $request->filled('branch_id') ? (int)$request->branch_id : null;

        // 1. Daily Bookings - Count of completed bookings for today
        $dailyBookingsQuery = Booking::where('status', 'completed')
            ->whereHas('bookingTransaction', function ($q) {
                $q->where('payment_status', 1);
            })
            ->whereDate('start_date_time', Carbon::today());

        if ($branchId) {
            $dailyBookingsQuery->where('branch_id', $branchId);
        }

        $dailyBookings = $dailyBookingsQuery->count();

        // 2. Order Report - Total revenue from orders (paid orders)
        $orderQuery = Order::where('payment_status', 'paid');

        if ($branchId) {
            // Orders are related to branches through bookingProducts.booking
            $orderQuery->whereHas('bookingProducts.booking', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $orderReport = (float)$orderQuery->sum('total_admin_earnings');

        // 3. Staff Payout - Total payout amount for staff in this branch
        $staffPayoutQuery = EmployeeEarning::query();

        if ($branchId) {
            $staffPayoutQuery->whereHas('employee', function ($q) use ($branchId) {
                $q->whereHas('branches', function ($b) use ($branchId) {
                    $b->where('branch_id', $branchId);
                });
            });
        }

        $staffPayout = (float)$staffPayoutQuery->sum('total_amount');

        // 4. Staff Services - Count of staff members assigned to this branch
        $staffServicesQuery = User::role(['manager', 'employee']);

        if ($branchId) {
            $staffServicesQuery->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $staffServices = $staffServicesQuery->count();

        $data = [
            'daily_bookings' => (int)$dailyBookings,
            'order_report' => round($orderReport, 2),
            'staff_payout' => round($staffPayout, 2),
            'staff_services' => (int)$staffServices,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Manager Report Dashboard fetched Successfully.',
            'data' => $data
        ], 200);
    }

    public function dailyBookingReportList(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || (!$authUser->hasRole('admin') && !$authUser->hasRole('manager'))) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        // Get branch_id from request if provided
        $branchId = $request->filled('branch_id') ? (int)$request->branch_id : null;

        // Base query for completed bookings with payment
        $query = Booking::with(['services', 'packages', 'payment', 'userCouponRedeem'])
            ->where('status', 'completed')
            ->whereHas('bookingTransaction', function ($q) {
                $q->where('payment_status', 1);
            });

        // Apply branch filter
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Apply date range filters
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('start_date_time', '>=', $startDate);
        }

        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('start_date_time', '<=', $endDate);
        }

        // Search filter (search by booking date or time)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereDate('start_date_time', 'like', "%{$search}%")
                    ->orWhereTime('start_date_time', 'like', "%{$search}%");
            });
        }

        // Get bookings and group by date
        $bookings = $query->get()->groupBy(function ($booking) {
            return Carbon::parse($booking->start_date_time)->format('Y-m-d');
        });

        $data = [];
        foreach ($bookings as $dateKey => $dailyBookings) {
            $totalTaxAmount = $dailyBookings->sum(fn($b) => $b->total_tax_amount ?? 0);

            // Calculate service amount after discount (subtotal) and final amount
            $totalServiceAmountAfterDiscount = 0;
            $totalFinalAmountAfterDiscount = 0;

            foreach ($dailyBookings as $booking) {
                $discount = optional($booking->userCouponRedeem)->discount ?? 0;
                $serviceAmount = $booking->total_service_amount ?? 0;
                $serviceAfterDiscount = max(0, $serviceAmount - $discount);

                $totalServiceAmountAfterDiscount += $serviceAfterDiscount;
                $totalFinalAmountAfterDiscount += $serviceAfterDiscount + ($booking->total_tax_amount ?? 0) + ($booking->total_tip_amount ?? 0);
            }

            // Get first booking's date time for formatting
            $firstBooking = $dailyBookings->first();
            $bookingDateTime = Carbon::parse($firstBooking->start_date_time);

            // Format as "6 May 2025 At 10:00 AM"
            $formattedDate = $bookingDateTime->format('j F Y') . ' at ' . $bookingDateTime->format('H:i');

            $data[] = [
                'date_key' => $dateKey, // Store for sorting
                'booking_date' => $formattedDate,
                'total_booking' => $dailyBookings->count(),
                'total_service' => $dailyBookings->sum(fn($b) => ($b->services->count() ?? 0) + ($b->packages->count() ?? 0)),
                'service_amount' => round((float)$totalServiceAmountAfterDiscount, 2),
                'tax_amount' => round((float)$totalTaxAmount, 2),
                'tip_amount' => round((float)$dailyBookings->sum(fn($b) => $b->total_tip_amount ?? 0), 2),
                'final_amount' => round((float)$totalFinalAmountAfterDiscount, 2),
            ];
        }

        // Sort by date descending (most recent first)
        usort($data, function ($a, $b) {
            return strcmp($b['date_key'], $a['date_key']);
        });

        // Remove date_key from final output
        $data = array_map(function ($item) {
            unset($item['date_key']);
            return $item;
        }, $data);

        return response()->json([
            'status' => true,
            'message' => 'Daily Booking Reports fetched successfully.',
            'data' => $data
        ], 200);
    }

    public function orderReportList(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || (!$authUser->hasRole('admin') && !$authUser->hasRole('manager'))) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        // Base query for orders
        $query = Order::with(['user', 'orderItems', 'orderGroup']);

        // Branch filter (if branch_id is provided)
        $branchId = $request->filled('branch_id') ? (int)$request->branch_id : null;
        if ($branchId) {
            $query->whereHas('bookingProducts.booking', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            // Map common status formats
            if (strtolower($status) === 'order placed') {
                $status = 'order_placed';
            } elseif (strtolower($status) === 'delivered') {
                $status = 'delivered';
            }
            $query->where('delivery_status', $status);
        }

        // Date range filter
        if ($request->filled('date_range')) {
            $dateRange = $request->date_range;
            if ($dateRange === 'today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($dateRange === 'week') {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($dateRange === 'month') {
                $query->whereMonth('created_at', Carbon::now()->month);
            }
        }

        // Search filter (by customer name, email, or order code)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                })
                    ->orWhereHas('orderGroup', function ($groupQuery) use ($search) {
                        $groupQuery->where('order_code', 'like', "%{$search}%");
                    });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        $ordersData = [];
        $totalReportAmount = 0;

        foreach ($orders as $order) {
            $user = $order->user;
            $orderDate = Carbon::parse($order->created_at);

            // Format date as "6, May 2025 At 10:00 AM"
            $formattedDate = $orderDate->format('j, F Y') . ' at ' . $orderDate->format('H:i');

            // Calculate total items
            $totalItems = $order->orderItems ? $order->orderItems->sum('qty') : 0;

            // Price - use total_admin_earnings or 0 if pending/unpaid
            $price = 0;
            if (strtolower($order->delivery_status) !== 'pending' && strtolower($order->payment_status) === 'paid') {
                $price = (float)($order->total_admin_earnings ?? 0);
            }

            // Add to total report amount (sum all shown prices)
            $totalReportAmount += $price;

            // Format delivery status - convert snake_case to Title Case
            $orderStatus = ucwords(str_replace('_', ' ', $order->delivery_status ?? 'pending'));

            // Format payment status
            $paymentStatus = ucfirst($order->payment_status ?? 'unpaid');

            $ordersData[] = [
                'order_status' => $orderStatus,
                'customer_name' => $user ? $user->full_name : 'Guest',
                'customer_email' => $user ? $user->email : '--',
                'customer_phone' => $user ? ($user->mobile ?? '--') : '--',
                'customer_profile_image' => $user ? $user->profile_image : '',
                'total_item' => (int)$totalItems,
                'price' => round($price, 2),
                'order_placed_date' => $formattedDate,
                'payment_status' => $paymentStatus,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Order Report List fetched successfully.',
            'data' => [
                'total_report_amount' => round($totalReportAmount, 2),
                'orders' => $ordersData
            ]
        ], 200);
    }

    public function staffPayoutList(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || (!$authUser->hasRole('admin') && !$authUser->hasRole('manager'))) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        // Determine branch context
        $branchId = $request->filled('branch_id') ? (int) $request->branch_id : null;

        // For managers, default to their primary branch if branch_id is not explicitly provided
        if (!$branchId && $authUser && $authUser->hasRole('manager')) {
            $branchId = BranchEmployee::where('employee_id', $authUser->id)
                ->where('is_primary', 1)
                ->value('branch_id');
        }

        // Base query for employee earnings (paid payouts)
        $query = EmployeeEarning::with('employee');

        // Do not show the login manager data
        if ($authUser && $authUser->hasRole('manager')) {
            $query->where('employee_id', '!=', $authUser->id);
        }

        // Branch filter (if branch_id is resolved)
        if ($branchId) {
            $query->whereHas('employee', function ($q) use ($branchId) {
                $q->whereHas('branches', function ($b) use ($branchId) {
                    $b->where('branch_id', $branchId);
                });
            });
        }

        // Search filter (by staff name or email)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Payment type filter
        if ($request->filled('payment_type')) {
            $paymentType = $request->payment_type;
            // Map common payment type formats
            $paymentTypeMap = [
                'bank transfer' => 'Bank',
                'bank' => 'Bank',
                'cash' => 'Cash',
                'check' => 'Check',
            ];
            $mappedType = $paymentTypeMap[strtolower($paymentType)] ?? $paymentType;
            $query->where('payment_type', $mappedType);
        }

        // Date range filter
        $dateRangeFilter = null;
        if ($request->filled('date_range')) {
            $range = $request->date_range;
            if ($range === 'today') {
                $dateRangeFilter = [
                    'start' => Carbon::today()->startOfDay(),
                    'end' => Carbon::today()->endOfDay()
                ];
            } elseif ($range === 'week') {
                $dateRangeFilter = [
                    'start' => Carbon::now()->startOfWeek(),
                    'end' => Carbon::now()->endOfWeek()
                ];
            } elseif ($range === 'month') {
                $dateRangeFilter = [
                    'start' => Carbon::now()->startOfMonth(),
                    'end' => Carbon::now()->endOfMonth()
                ];
            }

            if ($dateRangeFilter) {
                $query->whereBetween('payment_date', [
                    $dateRangeFilter['start'],
                    $dateRangeFilter['end']
                ]);
            }
        }

        $earnings = $query->orderBy('payment_date', 'desc')->get();

        $data = $earnings->toBase()->map(function ($earning) {
            $employee = $earning->employee;

            if (!$employee) {
                return null;
            }

            // Format payout date as "6 May 2025 At 10:00 AM"
            $payoutDate = $earning->payment_date
                ? Carbon::parse($earning->payment_date)
                : Carbon::parse($earning->created_at);
            $formattedPayoutDate = $payoutDate->format('j M Y') . ' at ' . $payoutDate->format('H:i');

            // Get staff since year from user created_at
            $staffSinceYear = $employee->created_at
                ? Carbon::parse($employee->created_at)->format('Y')
                : '--';

            // Payment type - default to "Bank" if not set
            $paymentType = $earning->payment_type ?? 'Bank';

            return [
                'staff_payout_id' => 0,
                'employee_id' => (int) ($employee->id ?? 0),
                'staff_name' => $employee->full_name ?? '--',
                'staff_email' => $employee->email ?? '--',
                'staff_profile_image' => $employee->profile_image ?? '',
                'staff_since_year' => $staffSinceYear,
                'staff_is_verified' => !is_null($employee->email_verified_at),
                'payout_date' => $formattedPayoutDate,
                'commission_amount' => round((float)($earning->commission_amount ?? 0), 2),
                'tip_amount' => round((float)($earning->tip_amount ?? 0), 2),
                'payment_type' => $paymentType,
                'total_pay' => round((float)($earning->total_amount ?? 0), 2),
                'payment_status' => !is_null($earning->payment_date) && is_null($earning->deleted_at),
                '_sort_ts' => $payoutDate->timestamp,
            ];
        })->filter()->values(); // Remove null entries and re-index

        // Also include unpaid commission and tip earnings (pending payouts) grouped by employee_id, respecting branch
        // Query unpaid commissions
        $unpaidCommissionQuery = CommissionEarning::query()
            ->where('commissionable_type', Booking::class)
            ->whereNull('payment_date')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('commission_status')
                    ->orWhereRaw('LOWER(commission_status) != ?', ['paid']);
            })
            ->whereHas('getbooking', function ($q) use ($branchId) {
                $q->where('status', 'completed');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        // Query unpaid tips
        $unpaidTipQuery = TipEarning::query()
            ->where('tippable_type', Booking::class)
            ->whereNull('payment_date')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('tip_status')
                    ->orWhereRaw('LOWER(tip_status) != ?', ['paid']);
            })
            ->whereHas('tippable', function ($q) use ($branchId) {
                $q->where('status', 'completed');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        // Do not show the login manager data
        if ($authUser && $authUser->hasRole('manager')) {
            $unpaidCommissionQuery->where('employee_id', '!=', $authUser->id);
            $unpaidTipQuery->where('employee_id', '!=', $authUser->id);
        }

        // Date range filter (include unpaid by created_at)
        if ($dateRangeFilter) {
            $unpaidCommissionQuery->whereBetween('created_at', [
                $dateRangeFilter['start'],
                $dateRangeFilter['end']
            ]);
            $unpaidTipQuery->whereBetween('created_at', [
                $dateRangeFilter['start'],
                $dateRangeFilter['end']
            ]);
        }

        // Get unpaid commissions and tips
        $unpaidCommissionRows = $unpaidCommissionQuery->get();
        $unpaidTipRows = $unpaidTipQuery->get();

        // Get all unique employee IDs from both commissions and tips
        $allEmployeeIds = $unpaidCommissionRows->pluck('employee_id')
            ->merge($unpaidTipRows->pluck('employee_id'))
            ->unique();

        // Search filter - filter employee IDs if search is provided
        if ($request->filled('search')) {
            $search = $request->search;
            $filteredEmployeeIds = User::where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->whereIn('id', $allEmployeeIds)
            ->pluck('id');
            
            $allEmployeeIds = $allEmployeeIds->intersect($filteredEmployeeIds);
        }

        // Group by employee and combine commissions and tips
        $unpaidCommissionCards = $allEmployeeIds->map(function ($employeeId) use ($unpaidCommissionRows, $unpaidTipRows, $branchId) {
            $employee = User::find($employeeId);
            if (!$employee) {
                return null;
            }

            // Filter commissions and tips for this employee
            $employeeCommissions = $unpaidCommissionRows->where('employee_id', $employeeId);
            $employeeTips = $unpaidTipRows->where('employee_id', $employeeId);

            // Calculate totals
            $commissionAmount = (float) $employeeCommissions->sum('commission_amount');
            $tipAmount = (float) $employeeTips->sum('tip_amount');
            $totalAmount = $commissionAmount + $tipAmount;

            // Skip if no unpaid earnings
            if ($totalAmount <= 0) {
                return null;
            }

            // Get latest timestamp from either commissions or tips
            $latestCommissionTs = $employeeCommissions->max(function ($r) {
                return $r->created_at ? Carbon::parse($r->created_at)->timestamp : 0;
            }) ?? 0;
            $latestTipTs = $employeeTips->max(function ($r) {
                return $r->created_at ? Carbon::parse($r->created_at)->timestamp : 0;
            }) ?? 0;
            $latestTs = max($latestCommissionTs, $latestTipTs);

            $payoutDate = $latestTs ? Carbon::createFromTimestamp($latestTs) : Carbon::now();
            $formattedPayoutDate = $payoutDate->format('j M Y') . ' at ' . $payoutDate->format('H:i');

            $staffSinceYear = $employee->created_at
                ? Carbon::parse($employee->created_at)->format('Y')
                : '--';

            return [
                'staff_payout_id' => 0,
                'employee_id' => (int) ($employee->id ?? 0),
                'staff_name' => $employee->full_name ?? '--',
                'staff_email' => $employee->email ?? '--',
                'staff_profile_image' => $employee->profile_image ?? '',
                'staff_since_year' => $staffSinceYear,
                'staff_is_verified' => !is_null($employee->email_verified_at),
                'payout_date' => $formattedPayoutDate,
                'commission_amount' => round($commissionAmount, 2),
                'tip_amount' => round($tipAmount, 2),
                'payment_type' => 'Cash',
                'total_pay' => round($totalAmount, 2),
                'payment_status' => false,
                '_sort_ts' => $payoutDate->timestamp,
            ];
        })
        ->filter()
        ->values();

        $data = $data
            ->merge($unpaidCommissionCards)
            ->sortByDesc(function ($item) {
                return (int) ($item['_sort_ts'] ?? 0);
            })
            ->values()
            ->map(function ($item, $index) {
                $item['staff_payout_id'] = (int) $index + 1;
                unset($item['_sort_ts']);
                return $item;
            });

        return response()->json([
            'status' => true,
            'message' => 'Staff Payout List fetched successfully.',
            'data' => $data
        ], 200);
    }

    /**
     * Save a staff payout entry.
     *
     * Expected payload:
     * - staff_id (int)             : Staff member ID
     * - commission_amount (float)  : Commission part of payout
     * - tip_amount (float)         : Tip part of payout
     * - total_amount (float)       : Total payout amount
     * - date_time (string)         : Datetime in "Y-m-d H:i:s" format
     */
    public function savePayout(Request $request)
    {
        $authUser = auth()->user();

        if (!$authUser || (!$authUser->hasRole('admin') && !$authUser->hasRole('manager'))) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        $validated = $request->validate([
            'staff_id' => ['required', 'integer', 'exists:users,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'commission_amount' => ['required', 'numeric'],
            'tip_amount' => ['required', 'numeric'],
            'total_amount' => ['required', 'numeric'],
            'date_time' => ['required', 'date_format:Y-m-d H:i:s'],
        ]);

        $earning = EmployeeEarning::create([
            'employee_id' => $validated['staff_id'],
            'description' => $request->input('description', 'Staff Payout Request'),
            'commission_amount' => $validated['commission_amount'],
            'tip_amount' => $validated['tip_amount'],
            'total_amount' => $validated['total_amount'],
            'payment_date' => Carbon::parse($validated['date_time']),
            'payment_type' => $request->input('payment_type', 'Cash'),
        ]);

        // Mark associated commissions and tips as paid
        $payoutDate = Carbon::parse($validated['date_time']);

        $commissionUpdate = CommissionEarning::where('employee_id', $validated['staff_id'])
            ->whereNull('payment_date')
            ->where(function ($q) {
                $q->whereNull('commission_status')
                    ->orWhere('commission_status', '!=', 'paid');
            });

        $tipUpdate = TipEarning::where('employee_id', $validated['staff_id'])
            ->whereNull('payment_date')
            ->where(function ($q) {
                $q->whereNull('tip_status')
                    ->orWhere('tip_status', '!=', 'paid');
            });

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->branch_id;
            $commissionUpdate->whereHas('getbooking', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
            $tipUpdate->whereHas('tippable', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        $commissionUpdate->update(['commission_status' => 'paid', 'payment_date' => $payoutDate]);
        $tipUpdate->update(['tip_status' => 'paid', 'payment_date' => $payoutDate]);

        return response()->json([
            'status' => true,
            'message' => 'Staff payout saved and associated earnings marked as paid successfully.',
            'data' => [
                'id' => $earning->id,
                'staff_id' => $earning->employee_id,
                'commission_amount' => round((float) ($earning->commission_amount ?? 0), 2),
                'tip_amount' => round((float) ($earning->tip_amount ?? 0), 2),
                'total_amount' => round((float) ($earning->total_amount ?? 0), 2),
                'payment_date' => optional($earning->payment_date)->format('Y-m-d H:i:s'),
                'payment_type' => $earning->payment_type,
                'description' => $earning->description,
            ]
        ], 201);
    }
}
