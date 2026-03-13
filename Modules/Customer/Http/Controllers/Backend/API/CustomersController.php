<?php

namespace Modules\Customer\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Booking\Models\Booking;

class CustomersController extends Controller
{
    /**
     * Get customer list with optional branch filtering
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerList(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $branchId = $request->input('branch_id');

        // Start with customers (users with 'user' role)
        $customers = User::role('user')
            ->with(['media', 'wallet'])
            ->where('status', 1);

        // Filter by branch_id - show only customers who made bookings to that branch
        if ($branchId) {
            $customers = $customers->whereHas('booking', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }

        // Optional: Filter by search term (name or email)
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $customers = $customers->where(function ($query) use ($searchTerm) {
                $query->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('mobile', 'like', "%{$searchTerm}%");
            });
        }

        // Optional: Filter by status (accepts 1/0, true/false, \"true\"/\"false\")
        if ($request->filled('status')) {
            $rawStatus = $request->status;
            // Accept numeric and string values
            if ($rawStatus === '1' || $rawStatus === 1 || $rawStatus === true || $rawStatus === 'true') {
                $customers = $customers->where('status', 1);
            } elseif ($rawStatus === '0' || $rawStatus === 0 || $rawStatus === false || $rawStatus === 'false') {
                $customers = $customers->where('status', 0);
            }
        }

        // Optional: Filter by gender
        if ($request->filled('gender')) {
            $customers = $customers->where('gender', $request->gender);
        }

        // Optional: Filter by email verification
        if ($request->has('verified')) {
            $verified = filter_var($request->verified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_null($verified)) {
                if ($verified) {
                    $customers = $customers->whereNotNull('email_verified_at');
                } else {
                    $customers = $customers->whereNull('email_verified_at');
                }
            }
        }
        if ($request->filled('customer_phone_code')) {
            $customers = $customers->where('mobile', 'like', '%' . $request->customer_phone_code . '%');
        }

        // Get booking counts for each customer
        $customers = $customers->withCount([
            'booking as total_bookings',
            'booking as completed_bookings' => function ($query) {
                $query->where('status', 'completed');
            },
            'booking as cancelled_bookings' => function ($query) {
                $query->where('status', 'cancelled');
            }
        ]);

        // If branch_id is provided, also count bookings for that specific branch
        if ($branchId) {
            $customers = $customers->withCount([
                'booking as branch_bookings' => function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                },
                'booking as branch_completed_bookings' => function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId)->where('status', 'completed');
                }
            ]);
        }

        // Order by most recent booking or created date
        $customers = $customers->orderBy('created_at', 'desc');

        // Paginate results
        $customers = $customers->paginate($perPage);

        // Format response
        $formattedCustomers = $customers->map(function ($customer) use ($branchId) {

            $walletBalance = optional($customer->wallet)->amount ?? 0;
        
            // Extract phone code (example: +91 from +911234567890)
            $phoneCode = null;
            if (!empty($customer->mobile)) {
                preg_match('/^(\+\d{1,4})/', $customer->mobile, $match);
                $phoneCode = $match[1] ?? null;
            }

            return [
                'id' => $customer->id,
                // 'first_name' => $customer->first_name,
                // 'last_name' => $customer->last_name,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
                'customer_phone_code' => $phoneCode,
                'profile_image' => $customer->profile_image,
                'status' => $customer->status == 1 ? true : false,
                // 'is_banned' => $customer->is_banned ?? 0,
                // 'email_verified_at' => $customer->email_verified_at,
                // 'wallet_balance' => $walletBalance,
                // 'total_bookings' => $customer->total_bookings ?? 0,
                // 'completed_bookings' => $customer->completed_bookings ?? 0,
                // 'cancelled_bookings' => $customer->cancelled_bookings ?? 0,
                // 'branch_bookings' => $branchId ? ($customer->branch_bookings ?? 0) : null,
                // 'branch_completed_bookings' => $branchId ? ($customer->branch_completed_bookings ?? 0) : null,
                // 'created_at' => $customer->created_at,
                // 'updated_at' => $customer->updated_at,
                'gender' => $customer->gender,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $formattedCustomers,
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
            'message' => $branchId 
                ? 'Customers list filtered by branch.' 
                : 'Customers list retrieved successfully.'
        ], 200);
    }

    /**
     * Get customer detail
     * 
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerDetail($id)
    {
        $customer = User::role('user')
            ->with(['media', 'wallet', 'booking.branch', 'booking.bookingService'])
            ->findOrFail($id);

        $walletBalance = optional($customer->wallet)->amount ?? 0;

        $totalBookings = Booking::where('user_id', $customer->id)->count();
        $completedBookings = Booking::where('user_id', $customer->id)
            ->where('status', 'completed')
            ->count();
        $cancelledBookings = Booking::where('user_id', $customer->id)
            ->where('status', 'cancelled')
            ->count();

        $recentBookings = Booking::with(['branch', 'bookingService.service', 'bookingService.employee'])
            ->where('user_id', $customer->id)
            ->orderByDesc('start_date_time')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'full_name' => $customer->full_name,
                'email' => $customer->email,
                'mobile' => $customer->mobile,
                'profile_image' => $customer->profile_image,
                'status' => $customer->status,
                'is_banned' => $customer->is_banned ?? 0,
                'email_verified_at' => $customer->email_verified_at,
                'wallet_balance' => $walletBalance,
                'total_bookings' => $totalBookings,
                'completed_bookings' => $completedBookings,
                'cancelled_bookings' => $cancelledBookings,
                'recent_bookings' => $recentBookings,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
                'gender' => $customer->gender,
            ],
            'message' => 'Customer detail retrieved successfully.'
        ], 200);
    }
}

