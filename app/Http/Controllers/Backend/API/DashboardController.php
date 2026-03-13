<?php

namespace App\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\BranchGallery;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Category\Models\Category;
use Modules\Category\Transformers\CategoryResource;
use Modules\Employee\Transformers\EmployeeResource;
use Modules\Product\Models\Cart;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceGallery;
use Modules\Service\Transformers\ServiceResource;
use Modules\Slider\Models\Slider;
use Modules\Slider\Transformers\SliderResource;
use Modules\Booking\Transformers\BookingListResource;
use Modules\Booking\Models\BookingTransaction;
use Modules\Commission\Models\CommissionEarning;
use Carbon\Carbon;
use Modules\Employee\Models\EmployeeRating;
class DashboardController extends Controller
{
    public function dashboardDetail(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $branchId = $request->input('branch_id'); // Assuming the branch ID is passed in the request
        $user_id = $request->input('user_id');
        $branch = Branch::find($branchId);

        if (! $branch) {
            return response()->json(['status' => false, 'message' => __('branch.branch_notfound')], 404);
        }

        $categories = Category::with('media')->where('status', 1)->whereNull('parent_id')
        ->paginate($perPage)
        ->forPage(1, 8);

            $services = Service::with(['media', 'branches' => function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            }])
            ->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->paginate($perPage);

            foreach ($services->items() as $service) {
                $service->resolveBranchSpecificData($branchId);
            }

            $employees = User::with(['media', 'employeeprofile', 'services.service.category'])->withCount(['branches', 'services'])
                ->whereHas('branches', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->orderByDesc('services_count')
                ->paginate($perPage)
                ->forPage(1, 6);

        $slider = SliderResource::collection(Slider::where('status', 1)->paginate($perPage));

        $cartCount = 0;
        if ($user_id) {
            $cartCount = Cart::where('user_id', $user_id)->count();
        }

        $popular_services = BookingService::with('service.branches')
        ->select('service_id', DB::raw('COUNT(*) as total_booked'))
        ->whereHas('booking', function ($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
        ->groupBy('service_id')
        ->orderByDesc('total_booked')
        ->paginate($perPage);

        $popular_services_data = $popular_services->getCollection()->map(function ($bookingService) use ($branchId) {
            $service = $bookingService->service;
            if ($service) {
                $service->resolveBranchSpecificData($branchId);
            }
            return $service;
        })->filter();
        $booking = Booking::where('user_id', $user_id)->where('start_date_time', '>', now())->where('status', '=', 'pending')
            ->with('booking_service', 'bookingTransaction', 'bookingPackages.bookedPackageService');

        $booking = $booking->orderBy('updated_at', 'desc')->paginate($perPage);
        $responseData = [
            'category' => CategoryResource::collection($categories)->toArray(request()),
            'service' => ServiceResource::collection($services)->toArray(request()),
            'top_experts' => EmployeeResource::collection($employees)->toArray(request()),
            'slider' => $slider,
            'cart_count' => $cartCount,
            'popular_services' => ServiceResource::collection($popular_services_data)->toArray(request()),
            'upcoming_booking' => BookingListResource::collection($booking),
        ];

        return response()->json([
            'status' => true,
            'data' => $responseData,
            'message' => __('messages.dashboard_detail'),
        ], 200);
    }

    public function managerDashboardDetail(Request $request)
    {
        $branchId = $request->input('branch_id');
        $user_id = $request->input('user_id');

        $branch = Branch::find($branchId);
        if (! $branch) {
            return response()->json([
                'status' => false,
                'message' => __('branch.branch_notfound')
            ], 404);
        }

        // Active staff count (employees linked to branch, excluding managers)
        $activeStaff = User::whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })
            ->where('status', 1)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'manager');
            })
            ->count();

        // Helper to calculate net amount for a booking (service + package - discount + tax + tip)
        $calculateNetAmount = function (Booking $booking) {
            $serviceAmount = $booking->services->sum('service_price');
            $packageAmount = $booking->packages->sum('package_price');
            $grossAmount = $serviceAmount + $packageAmount;

            $transaction = $booking->payment;
            $discountAmount = $transaction->discount_amount ?? 0;
            if ($transaction && ($transaction->discount_percentage > 0)) {
                $discountAmount = ($grossAmount * $transaction->discount_percentage) / 100;
            }

            $tipAmount = $transaction->tip_amount ?? 0;
            $taxAmount = 0;
            if ($transaction && ! empty($transaction->tax_percentage)) {
                $taxData = is_array($transaction->tax_percentage)
                    ? $transaction->tax_percentage
                    : json_decode($transaction->tax_percentage, true);
                if ($taxData) {
                    $taxBreakdown = getBookingTaxamount($grossAmount, $discountAmount, $taxData);
                    $taxAmount = $taxBreakdown['total_tax_amount'] ?? 0;
                }
            }

            return max($grossAmount - $discountAmount, 0) + $taxAmount + $tipAmount;
        };

        // Total revenue: Calculate from booking services and packages with paid transactions
        $bookingsForRevenue = Booking::with(['services', 'packages', 'payment'])
            ->where('branch_id', $branchId)
            ->whereHas('payment', function ($q) {
                $q->where('payment_status', 1);
            })
            ->get();

        $totalRevenue = $bookingsForRevenue->sum(function ($booking) use ($calculateNetAmount) {
            return $calculateNetAmount($booking);
        });

        // Manager earning: Get commission earnings for the manager from this branch
        $managerId = $user_id ?? $branch->manager_id;
        $myEarning = 0;
        if ($managerId) {
            $myEarning = CommissionEarning::where('employee_id', $managerId)
                ->whereHas('getbooking', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId)
                      ->where('status', 'completed');
                })
                ->sum('commission_amount') ?? 0;
        }

        $totalAppointmentCount = Booking::where('branch_id', $branchId)->count();

        $totalServiceCount = Service::whereHas('branches', function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        })->count();

        // Monthly appointment revenue (total amount per month)
        $appointmentStats = $bookingsForRevenue
            ->groupBy(function ($booking) {
                $dateValue = $booking->start_date_time ?? $booking->created_at;
                return Carbon::parse($dateValue)->format('Y-m');
            })
            ->map(function ($monthlyBookings) use ($calculateNetAmount) {
                $dateValue = $monthlyBookings->first()->start_date_time ?? $monthlyBookings->first()->created_at;
                $monthName = Carbon::parse($dateValue)->format('M');
                $monthTotal = $monthlyBookings->sum(function ($booking) use ($calculateNetAmount) {
                    return $calculateNetAmount($booking);
                });

                return [
                    'x' => $monthName, // Jan, Feb, Mar...
                    'y' => $monthTotal
                ];
            })
            ->values();
    
        $totalStaffReviewsCount = EmployeeRating::whereHas('employee', function ($q) use ($branchId) {
            $q->whereHas('branches', function ($b) use ($branchId) {
                $b->where('branch_id', $branchId);
            });
        })->count();
        return response()->json([
            'status' => true,
            'data' => [
                'active_staff' => $activeStaff ?? 0,
                'my_earning' => $myEarning ?? 0,
                'total_revenue' => $totalRevenue ?? 0,
                'total_appointment_count' => $totalAppointmentCount ?? 0,
                'total_service_count' => $totalServiceCount ?? 0,
                'staff_reviews_count ' => $totalStaffReviewsCount ?? 0,
                'appointment_stats' => [
                    'appointment_data' => $appointmentStats
                ]
            ],
            'message' => __('messages.dashboard_detail')
        ], 200);
    }

    

    public function searchList(Request $request)
    {
        $query = $request->input('query');
        $results = [];

        // Search in Branches
        $branches = Branch::where('name', 'like', "%{$query}%")->get();
        $results['branches'] = $branches;

        // Search in Employees // Need To Add Role Base
        $employees = User::role('employee')->where(function ($queryBuilder) use ($query) {
            $queryBuilder->where('first_name', 'like', "%{$query}%")
                ->orWhere('last_name', 'like', "%{$query}%");
        })->get();
        $results['employees'] = $employees;

        // Search in Categories
        $categories = Category::where('name', 'like', "%{$query}%")->get();
        $results['categories'] = $categories;

        $subcategories = Category::where('name', 'like', "%{$query}%")
            ->orWhere('parent_id', 'like', "%{$query}%")
            ->get();
        $results['subcategory'] = $subcategories;

        // Search in Bookings
        $bookings = Booking::where('note', 'like', "%{$query}%")->get();
        $results['bookings'] = $bookings;

        // Search in Services
        $services = Service::where('name', 'like', "%{$query}%")->get();
        $results['services'] = $services;

        return response()->json($results);
    }

    public function globalGallery(Request $request)
    {
        $galleryId = $request->input('gallery_id');

        // Retrieve branch gallery
        $branchGallery = BranchGallery::find($galleryId);
        if ($branchGallery) {
            $branch = Branch::find($branchGallery->branch_id);

            if ($branch) {
                return response()->json([
                    'status' => true,
                    'data' => [
                        'gallery' => $branchGallery,
                        'branch' => $branch,
                    ],
                    'message' => __('branch.branch_gal_retrived'),
                ], 200);
            }
        }

        // Retrieve service gallery
        $serviceGallery = ServiceGallery::find($galleryId);
        if ($serviceGallery) {
            $service = Service::find($serviceGallery->service_id);

            if ($service) {
                return response()->json([
                    'status' => true,
                    'data' => [
                        'gallery' => $serviceGallery,
                        'service' => $service,
                    ],
                    'message' => __('service.service_gal_retrived'),
                ], 200);
            }
        }

        // Gallery not found
        return response()->json([
            'status' => false,
            'message' => __('messages.gallery_notfound'),
        ], 404);
    }

    /**
     * Booking Dashboard with filters
     * Endpoint: booking-dashboard?branch_id={branchId}&user_id={userId}&date={dd-mm-yyyy}&page=1&per_page=5
     */
    public function bookingDashboard(Request $request)
    {
        $branchId = $request->input('branch_id');
        $userId = $request->input('user_id');
        $date = $request->input('date'); // Format: dd-mm-yyyy
        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 5);

        // Validate required parameters
        if (!$branchId) {
            return response()->json([
                'status' => false,
                'message' => 'Branch ID is required.'
            ], 400);
        }

        // Build query for bookings
        $bookingsQuery = Booking::with(['booking_service.service', 'services'])
            ->where('branch_id', $branchId);

        // Filter by user_id if provided
        if ($userId) {
            $bookingsQuery->where('user_id', $userId);
        }

        // Filter by date if provided
        if ($date) {
            try {
                // Parse date from dd-mm-yyyy format to Y-m-d for whereDate
                $parsedDate = Carbon::createFromFormat('d-m-Y', $date);
                $formattedDate = $parsedDate->format('Y-m-d');
                
                $bookingsQuery->whereDate('start_date_time', $formattedDate);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid date format. Expected format: dd-mm-yyyy. Error: ' . $e->getMessage()
                ], 400);
            }
        }

        // Get paginated bookings
        $bookings = $bookingsQuery->orderBy('start_date_time', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Transform booking list data
        $bookingListData = $bookings->getCollection()->map(function ($booking) {
            // Get first service name from booking services
            $serviceName = '';
            
            // Try booking_service relationship first
            if ($booking->booking_service && $booking->booking_service->isNotEmpty()) {
                $firstService = $booking->booking_service->first();
                if ($firstService && $firstService->service) {
                    $serviceName = $firstService->service->name ?? '';
                }
            }
            
            // Fallback to services relationship if booking_service didn't work
            if (empty($serviceName) && $booking->services && $booking->services->isNotEmpty()) {
                $firstService = $booking->services->first();
                // The services relationship already has service_name in the select
                $serviceName = $firstService->service_name ?? '';
            }

            // Format booking time for display
            $bookingTime = '';
            if ($booking->start_date_time) {
                $bookingTime = Carbon::parse($booking->start_date_time)
                    ->format('h:i A'); 
            }

            return [
                'booking_id' => $booking->id,
                'service_name' => $serviceName,
                'booking_status' => $booking->status ?? 'pending',
                'booking_time' => $bookingTime,
            ];
        })->values()->toArray();

        // Get booking status counts for ALL bookings on this date at this branch
        // (Status counts should not be filtered by user_id - they represent overall branch status)
        $statusCountsQuery = Booking::where('branch_id', $branchId);
        
        // Don't filter by user_id for status counts - show all bookings for the branch/date
        // Only filter by date if provided
        if ($date) {
            try {
                $parsedDate = Carbon::createFromFormat('d-m-Y', $date);
                $formattedDate = $parsedDate->format('Y-m-d');
                $statusCountsQuery->whereDate('start_date_time', $formattedDate);
            } catch (\Exception $e) {
                // Date already validated above, so this shouldn't happen
            }
        }
        
        $statusCounts = $statusCountsQuery->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                // Normalize status to lowercase for consistent checking
                return [strtolower($item->status) => $item->count];
            })
            ->toArray();

        // Build booking status data array
        // Note: config uses 'checkout' but schema expects 'check_out'
        // All statuses are normalized to lowercase for comparison
        $bookingStatusData = [
            [
                'pending' => isset($statusCounts['pending']) && $statusCounts['pending'] > 0,
                'confirmed' => (isset($statusCounts['confirmed']) && $statusCounts['confirmed'] > 0) || 
                              (isset($statusCounts['confirm']) && $statusCounts['confirm'] > 0),
                'check_in' => (isset($statusCounts['check_in']) && $statusCounts['check_in'] > 0) || 
                            (isset($statusCounts['checkin']) && $statusCounts['checkin'] > 0),
                'check_out' => (isset($statusCounts['checkout']) && $statusCounts['checkout'] > 0) || 
                              (isset($statusCounts['check_out']) && $statusCounts['check_out'] > 0),
                'cancelled' => isset($statusCounts['cancelled']) && $statusCounts['cancelled'] > 0,
                'completed' => isset($statusCounts['completed']) && $statusCounts['completed'] > 0,
            ]
        ];

        /**
         * Current month booking status by day
         * Returns an array entry per day of the current month with status flags.
         */
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $monthlyStatusRows = Booking::where('branch_id', $branchId)
            ->whereBetween('start_date_time', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->select(
                DB::raw('DATE(start_date_time) as date'),
                'status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date', 'status')
            ->get();

        // Build a map: Y-m-d => [status => count]
        $monthlyStatusMap = [];
        foreach ($monthlyStatusRows as $row) {
            $dateKey = $row->date;
            $statusKey = strtolower($row->status);
            $monthlyStatusMap[$dateKey][$statusKey] = $row->count;
        }

        // Helper to build the boolean status array for a given date
        $buildStatusFlags = function ($statusMapForDate) {
            $statusMapForDate = $statusMapForDate ?? [];
            return [
                'pending' => isset($statusMapForDate['pending']) && $statusMapForDate['pending'] > 0,
                'confirmed' => (isset($statusMapForDate['confirmed']) && $statusMapForDate['confirmed'] > 0) ||
                               (isset($statusMapForDate['confirm']) && $statusMapForDate['confirm'] > 0),
                'check_in' => (isset($statusMapForDate['check_in']) && $statusMapForDate['check_in'] > 0) ||
                              (isset($statusMapForDate['checkin']) && $statusMapForDate['checkin'] > 0),
                'check_out' => (isset($statusMapForDate['checkout']) && $statusMapForDate['checkout'] > 0) ||
                               (isset($statusMapForDate['check_out']) && $statusMapForDate['check_out'] > 0),
                'cancelled' => isset($statusMapForDate['cancelled']) && $statusMapForDate['cancelled'] > 0,
                'completed' => isset($statusMapForDate['completed']) && $statusMapForDate['completed'] > 0,
            ];
        };

        $currentMonthBookingData = [];

        foreach ($monthlyStatusMap as $dateKey => $statusMapForDate) {
            $currentMonthBookingData[] = [
                'date' => Carbon::parse($dateKey)->format('d-m-Y'),
                'booking_status_data' => [
                    $buildStatusFlags($statusMapForDate)
                ],
            ];
        }
        

        return response()->json([
            'status' => true,
            'message' => 'Booking dashboard data retrieved successfully.',
            'data' => [
                'booking_list_data' => $bookingListData,
                'booking_status_data' => $bookingStatusData,
                'current_month_booking_data' => $currentMonthBookingData,
            ],
            'page' => $page,
            'per_page' => $perPage,
        ], 200);
    }
}
