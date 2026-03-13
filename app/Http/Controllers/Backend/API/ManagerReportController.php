<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Earning\Models\EmployeeEarning;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;
use Modules\Wallet\Models\WithdrawMoney;
use App\Models\User;
use Modules\Booking\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Order;

class ManagerReportController extends Controller
{
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
     * Staff Payout List
     * Endpoint: staff-payout?branch_id=
     */
    public function staffPayout(Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        $query = EmployeeEarning::with('employee');
        
        // For employees, filter to show only their own payout data
        if ($this->isEmployeeOnly()) {
            $query->where('employee_id', auth()->id());
        }

        if ($request->filled('branch_id')) {
            $branchId = $request->branch_id;
            $query->whereHas('employee', function ($q) use ($branchId) {
                $q->whereHas('branch', function ($b) use ($branchId) {
                    $b->where('branch_id', $branchId);
                });
            });
        }

        $earnings = $query->get();

        $data = $earnings->map(function ($earning) {
            $employee = $earning->employee;
            return [
                "Staff_image" => $employee->profile_image ?? default_user_avatar(),
                "Staff_name"  => $employee->full_name ?? default_user_name(),
                "Staff_email" => $employee->email ?? '--',
                "Date"        => customDate($earning->payment_date ?? now()),
                "commission_amount" => $earning->commission_amount ?? 0,
                "payment_type" => "Bank", // Default or extract from transaction if available
                "total_pay"    => $earning->total_amount ?? 0,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * Payout Request
     * Endpoint: payout-request
     * Method: POST
     */
    public function payoutRequest(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string', // e.g., 'bank'
            // 'bank_id' => 'required_if:payment_method,bank', // If strictly required
            'payment_gateway' => 'nullable|string',
        ]);

        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $wallet = Wallet::where('user_id', $user->id)->first();

        if (!$wallet) {
            return response()->json([
                'status' => false,
                'message' => 'Wallet not found for user.'
            ], 404);
        }

        if ($wallet->amount < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient balance to withdraw.'
            ], 400);
        }

        $data = $request->all();
        $data['user_id'] = $user->id;
        $data['datetime'] = Carbon::now();
        $data['status'] = 'pending'; // Default status
        $data['payment_type'] = $request->payment_gateway ?? $request->payment_method;

        // Create Withdrawal Request
        $withdrawal = WithdrawMoney::create($data);

        // Deduct amount from wallet
        $wallet->amount -= $request->amount;
        $wallet->save();

        // Record Wallet History
        WalletHistory::create([
            'user_id' => $user->id,
            'datetime' => now(),
            'activity_type' => 'debit',
            'activity_message' => __('messages.amount_withdrawn'),
            'activity_data' => json_encode([
                'title' => $wallet->title,
                'amount' => $wallet->amount,
                'transaction_id' => "",
                'transaction_type' => 'debit',
                'credit_debit_amount' => (float) $request->amount,
            ]),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payout request submitted successfully.',
            'data' => $withdrawal
        ]);
    }

    /**
     * Staff Service Report
     * Endpoint: staff-service?branch_id=
     */
    public function staffService(Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        $branchId = $request->branch_id ?? null;
        
        // Use the existing scope logic adapted for API
        $query = User::staffReport($branchId);
        
        // For employees, filter to show only their own staff report data
        if ($this->isEmployeeOnly()) {
            $query->where('users.id', auth()->id());
        }
        
        $staff = $query->get();

        $data = $staff->map(function ($employee) {
            return [
                "Staff_name"         => $employee->full_name,
                "Staff_email"        => $employee->email,
                "Total_booking"      => $employee->employee_booking_count ?? 0,
                "Total_amount"       => $employee->employee_booking_sum_service_price ?? 0,
                "Commission_earned"  => $employee->commission_earning_sum_commission_amount ?? 0,
                "Staff_earning"      => ($employee->commission_earning_sum_commission_amount ?? 0) + ($employee->tip_earning_sum_tip_amount ?? 0), // Commission + tips as per requirement
                "Tip_earned"         => $employee->tip_earning_sum_tip_amount ?? 0,
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * Daily Booking Report
     * Endpoint: report?branch_id=
     */
    public function dailyBookingReport(Request $request)
    {
        // Check permission to view reports
        if (!$this->hasReportPermission('view_reports')) {
            return response()->json([
                'status' => false,
                'message' => __('messages.permission_denied')
            ], 403);
        }

        $limitBranchIds = [];
        if ($request->filled('branch_id')) {
            $limitBranchIds = [$request->branch_id];
        }

        $query = Booking::with(['services', 'packages', 'payment', 'userCouponRedeem'])
            ->where('status', 'completed')
            ->whereHas('payment', function($q) {
                $q->where('payment_status', 1);
            })
            ->when(!empty($limitBranchIds), function ($query) use ($limitBranchIds) {
                $query->whereIn('bookings.branch_id', $limitBranchIds);
            });
        
        // For employees, filter to show only their own bookings
        if ($this->isEmployeeOnly()) {
            $employeeId = auth()->id();
            $query->where(function ($q) use ($employeeId) {
                $q->whereHas('services', function ($qq) use ($employeeId) {
                    $qq->where('employee_id', $employeeId);
                })
                ->orWhereHas('packages', function ($qq) use ($employeeId) {
                    $qq->where('employee_id', $employeeId);
                })
                ->orWhereHas('products', function ($qq) use ($employeeId) {
                    $qq->where('employee_id', $employeeId);
                });
            });
        }

        $bookings = $query->get()->groupBy(fn($b) => formatDateOrTime($b->start_date_time, 'date'));

        $data = [];
        foreach ($bookings as $date => $dailyBookings) {
            $totalTaxAmount = $dailyBookings->sum(fn($b) => $b->total_tax_amount);
            
            $totalServiceAmountAfterDiscount = 0;
            $totalFinalAmountAfterDiscount = 0;

            foreach ($dailyBookings as $booking) {
                $discount = optional($booking->userCouponRedeem)->discount ?? 0;
                $serviceAmount = $booking->total_service_amount;
                $serviceAfterDiscount = max(0, $serviceAmount - $discount);

                $totalServiceAmountAfterDiscount += $serviceAfterDiscount;
                $totalFinalAmountAfterDiscount += $serviceAfterDiscount + $booking->total_tax_amount + $booking->total_tip_amount;
            }

            $data[] = [
                "Booking_date"   => $date,
                "Total_booking"  => $dailyBookings->count(),
                "total_Service"  => $dailyBookings->sum(fn($b) => $b->services->count() + $b->packages->count()),
                "Service_amount" => $totalServiceAmountAfterDiscount,
                "Tax_amount"     => $totalTaxAmount,
                "Tip_amount"     => $dailyBookings->sum(fn($b) => $b->total_tip_amount),
                "final_amount"   => $totalFinalAmountAfterDiscount,
            ];
        }

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }
    /**
     * Manager Earning Report
     * Endpoint: manager-earning?branch_id=
     */
    public function managerEarning(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $branchId = $request->branch_id;
        
        // If no branch provided, try to find manager's branch
        if (!$branchId) {
             // Logic to find branch if not provided, assuming manager role structure
             // $branchId = ...
        }

        $limitBranchIds = $branchId ? [$branchId] : [];

        // 1. Total Bookings (Completed)
        $bookingsQuery = Booking::where('status', 'completed')
            ->whereHas('payment', function($q) {
                $q->where('payment_status', 1);
            })
            ->when(!empty($limitBranchIds), function ($query) use ($limitBranchIds) {
                $query->whereIn('bookings.branch_id', $limitBranchIds);
            });

        $bookings = $bookingsQuery->with(['services', 'packages', 'userCouponRedeem'])->get();
        
        $totalBookings = $bookings->count();
        
        // 2. Total Earning (Revenue)
        $totalRevenue = 0;
        foreach ($bookings as $booking) {
             $discount = optional($booking->userCouponRedeem)->discount ?? 0;
             $serviceAmount = $booking->total_service_amount;
             $serviceAfterDiscount = max(0, $serviceAmount - $discount);
             
             // Grand total includes tax and tips
             $totalRevenue += $serviceAfterDiscount + $booking->total_tax_amount + $booking->total_tip_amount;
        }

        // 3. Manager Commissions
        // Using CommissionEarning model which tracks paid/unpaid status
        $managerEarningsQuery = \Modules\Commission\Models\CommissionEarning::where('employee_id', $user->id);
            
        $managerPaidAmount = (clone $managerEarningsQuery)->where('commission_status', 'paid')->sum('commission_amount');
        $managerPayDue = (clone $managerEarningsQuery)->where('commission_status', 'unpaid')->sum('commission_amount');
        $managerTotalEarning = $managerPaidAmount + $managerPayDue;

        // 4. Admin Earning
        // Admin Earning = Total Revenue - (All Commissions)
        // Need total commissions for ALL employees in this branch for these bookings
        
        // Fetch all commissions for completed bookings in this branch
        $allCommissions = \Modules\Commission\Models\CommissionEarning::whereHas('getbooking', function($q) use ($limitBranchIds) {
                $q->where('status', 'completed');
                 if (!empty($limitBranchIds)) {
                    $q->whereIn('branch_id', $limitBranchIds);
                 }
            })->sum('commission_amount');

        $adminEarning = $totalRevenue - $allCommissions;

        $data = [
            [
                "total_booking"       => $totalBookings,
                "total_earning"       => $totalRevenue,
                "manager_pay_due"     => $managerPayDue,
                "manager_paid_amount" => $managerPaidAmount,
                "admin_eaning"        => $adminEarning, // Typo in request "admin_eaning", keeping it or fixing to "admin_earning"? Keeping as requested mostly
                "manager_total_earning" => $managerTotalEarning,
            ]
        ];

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * Manager Payout Request
     * Endpoint: manager-payout
     * Method: POST
     */
    public function managerPayout(Request $request)
    {
        // Reuse payoutRequest logic
        return $this->payoutRequest($request);
    }
}
