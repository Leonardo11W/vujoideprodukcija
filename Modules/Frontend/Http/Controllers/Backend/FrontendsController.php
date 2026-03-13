<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Modules\Frontend\Models\Frontend;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\CustomField\Models\CustomField;
use Modules\CustomField\Models\CustomFieldGroup;
use Yajra\DataTables\DataTables;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use App\Models\Branch;
use Modules\Package\Models\Package;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingTransaction;
use Modules\Employee\Models\Employee;
use Modules\Service\Models\Service;
use Illuminate\Support\Facades\Cache;
use Modules\FrontendSetting\Models\FrontendSetting;
use App\Models\User;
use Modules\Tax\Models\Tax;
use Modules\Booking\Trait\BookingTrait;
use Modules\Promotion\Models\Promotion;
use Modules\Promotion\Models\Coupon;
use Modules\Promotion\Transformers\PromotionResource;
use Modules\Employee\Models\EmployeeRating;
use Modules\Blog\Models\Blog;
use Modules\Slider\Models\Slider;
use App\Models\Expert;
use Modules\Employee\Models\BranchEmployee;
use Modules\FrontendSetting\Models\WhyChoose;
use Modules\FrontendSetting\Models\VideoSection;
use Modules\BussinessHour\Models\BussinessHour;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Page\Models\Page;
use Modules\Wallet\Models\Wallet;


// use App\DataTables\BookingDataTable; // This line should be commented out or removed

class FrontendsController extends Controller
{
    // use Authorizable;
    use BookingTrait;
    public function __construct()
    {
        // Page Title
        $this->module_title = 'frontend.title';
        // module name
        $this->module_name = 'frontend';

        // module icon
        $this->module_icon = 'fa-solid fa-clipboard-list';

        view()->share([
            'module_title' => $this->module_title,
            'module_icon' => $this->module_icon,
            'module_name' => $this->module_name,
        ]);
    }

    /**
     * Get section setting value by key
     *
     * @param string $key
     * @param string $type
     * @param mixed $default
     * @return array
     */
    private function getSectionSetting($key, $type = 'landing-page-setting', $default = [])
    {
        $setting = FrontendSetting::where('type', $type)->where('key', $key)->first();

        if (!$setting || !$setting->value) {
            return $default;
        }

        return is_array($setting->value) ? $setting->value : json_decode($setting->value, true);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request)
    {
        // Get section settings using common function
        $section1 = $this->getSectionSetting('section_1');
        $section2 = $this->getSectionSetting('section_2');
        $section3 = $this->getSectionSetting('section_3');
        $section4 = $this->getSectionSetting('section_4', 'landing-page-setting'); // Try with landing-page-setting type
        $section5 = $this->getSectionSetting('section_5');
        $section7 = $this->getSectionSetting('section_7');
        $section8 = $this->getSectionSetting('section_8');
        $section9 = $this->getSectionSetting('section_9');
        $section10 = $this->getSectionSetting('section_10');
        $section11 = $this->getSectionSetting('section_11');

        // Always fetch all active categories that have services for homepage filtering
        $categories = \Modules\Category\Models\Category::where('status', 1)
            ->whereHas('services', function($q) {
                $q->where('status', 1);
            })
            ->withCount(['services' => function($q) {
                $q->where('status', 1);
            }])
            ->get();

        // Get filtered categories for section 4
        $filteredCategories = collect();
        $section4Enabled = false;

        if ($section4 && isset($section4['section_4']) && $section4['section_4'] == 1) {
            $section4Enabled = true;
            $selectCategory = $section4['select_category'] ?? [];
            if (is_string($selectCategory)) {
                $selectCategory = json_decode($selectCategory, true);
            }
            $selectedIds = is_array($selectCategory) ? array_map('intval', $selectCategory) : [];

            // If specific categories are selected, filter them
            if (!empty($selectedIds)) {
                $filteredCategories = $categories->filter(function ($cat) use ($selectedIds) {
                    return in_array((int)$cat->id, $selectedIds, true);
                });
            } else {
                // If no specific categories selected, show all active categories
                $filteredCategories = $categories;
            }
        } else {
            // Section 4 is disabled - don't show categories
            $section4Enabled = false;
            $filteredCategories = collect();
        }

        // Fetch packages for section 5
        $packages = collect();
        if ($section5 && isset($section5['status']) && $section5['status'] == 1) {
            $packageIds = $section5['package_ids'] ?? [];
            if (!empty($packageIds)) {
                $packages = Package::whereIn('id', $packageIds)->where('status', 1)->get();
            }
        }

        // Fetch ratings
        $ratings = EmployeeRating::with('user')->latest()->take(5)->get();

        // Fetch blogs
        $blogs = Blog::with('author')->where('status', 1)->latest()->get();

        // Fetch sliders
        $sliders = Slider::where('status', 1)->orderBy('id', 'asc')->get();

        // Fetch branches for section 3
        $branches = collect();
        $showBranchSection = false;
        if ($section3 && isset($section3['status']) && $section3['status'] == 1) {
            $branchIds = $section3['branch_ids'] ?? [];
            if (!empty($branchIds)) {
                $branches = Branch::whereIn('id', $branchIds)->where('status', 1)->get();
                $showBranchSection = true;
            }
        }

        // Fetch products for section 8
        $products = collect();
        $section8Enabled = false;
        if ($section8 && isset($section8['status']) && $section8['status'] == 1) {
            $productIds = $section8['product_id'] ?? [];
            if (!empty($productIds) && is_array($productIds)) {
                $section8Enabled = true;
                $products = Product::whereIn('id', $productIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', $productIds) . ')')
                    ->where('status', 1)
                    ->get();
            }
        }

        $services = Service::where('status', 1)->get();

        // Fetch FAQs
        $faqs = \App\Models\Faq::where('status', 1)->get();

        // Fetch experts for section 7
        $experts = collect();
        $section7Enabled = false;
        if ($section7 && isset($section7['status']) && $section7['status'] == 1) {
            $expertIds = $section7['expert_id'] ?? [];
            if (!empty($expertIds) && is_array($expertIds)) {
                $section7Enabled = true;
                $experts = User::whereIn('id', $expertIds)
                    ->orderByRaw('FIELD(id, ' . implode(',', $expertIds) . ')')
                    ->take(5)
                    ->with('profile')
                    ->get();

                // Calculate actual ratings for section 7 experts
                foreach ($experts as $expert) {
                    $expert->avg_rating = round(
                        \Modules\Employee\Models\EmployeeRating::where('employee_id', $expert->id)->avg('rating'),
                        1
                    );
                }
            }
        }

        // Fallbacks if NO branch is selected or section_7 is not enabled
        if ((!$section7Enabled || $experts->isEmpty()) && !session()->has('selected_branch_id')) {
            $expertIds = \Modules\Employee\Models\EmployeeRating::select('employee_id')
                ->groupBy('employee_id')
                ->havingRaw('AVG(rating) >= 4')
                ->inRandomOrder()
                ->limit(6)
                ->pluck('employee_id');

            $experts = \App\Models\User::whereIn('id', $expertIds)->with('profile')->get();



            foreach ($experts as $expert) {
                $expert->avg_rating = round(
                    \Modules\Employee\Models\EmployeeRating::where('employee_id', $expert->id)->avg('rating'),
                    1
                );
            }

            // Show any 15 random products in best seller section
            $products = Product::where('status', 1)
                ->inRandomOrder()
                ->limit(15)
                ->get();
        }




        // Ensure $products always has 15 random products if empty
        if (empty($products) || (is_object($products) && $products->count() == 0)) {
            $products = Product::where('status', 1)
                ->inRandomOrder()
                ->limit(15)
                ->get();
        }

        // Add cart and wishlist status to products
        if (auth()->check()) {
            $cartItems = \Modules\Product\Models\Cart::where('user_id', auth()->id())
                ->pluck('product_id')
                ->toArray();

            $wishlistItems = \Modules\Product\Models\WishList::where('user_id', auth()->id())
                ->pluck('product_id')
                ->toArray();

            $products->each(function ($product) use ($cartItems, $wishlistItems) {
                $product->in_cart = in_array($product->id, $cartItems);
                $product->in_wishlist = in_array($product->id, $wishlistItems);
            });
        } else {
            $products->each(function ($product) {
                $product->in_cart = false;
                $product->in_wishlist = false;
            });
        }

        // Fetch Why Choose Us (dynamic section)
        $whyChoose = \Modules\FrontendSetting\Models\WhyChoose::with('features')->find(1);

        // Fetch latest video section
        $videoSection = VideoSection::latest()->first();

        // Prepare section enable flags for view
        $section1Enabled = $section1['section_1'] ?? 0;
        $section2Enabled = $section2['status'] ?? 0;
        // $section4Enabled is now set earlier in the method
        $section5Enabled = $section5['status'] ?? 0;
        $section7Enabled = $section7['status'] ?? 0;
        $section8Enabled = $section8['status'] ?? 0;
        $section9Enabled = $section9['status'] ?? 0;
        $section10Enabled = $section10['status'] ?? 0;
        $section11Enabled = $section11['status'] ?? 0;

        // Prepare additional values needed for the view
        $section1Value = $section1;
        $expert_id = $section7['expert_id'] ?? [];

        return view('frontend::index', compact(
            'categories',
            'filteredCategories',
            'products',
            'branches',
            'packages',
            'ratings',
            'blogs',
            'sliders',
            'showBranchSection',
            'services',
            'faqs',
            'experts',
            'whyChoose',
            'videoSection',
            'section1Enabled',
            'section2Enabled',
            'section4Enabled',
            'section5Enabled',
            'section7Enabled',
            'section8Enabled',
            'section9Enabled',
            'section10Enabled',
            'section11Enabled',
            'section1Value',
            'expert_id'
        ));
    }


    public function Bookings(Request $request)
    {
        $user = auth()->user();
        $now = now();

        $bookings = Booking::with(['branch', 'bookingTransaction', 'booking_service'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $upcomingBookingsCount = $bookings->where('status', 'pending')->filter(function ($booking) use ($now) {
            return Carbon::parse($booking->start_date_time)->isAfter($now);
        })->count();

        $completedBookingsCount = $bookings->filter(function ($booking) {
            $hasTransaction = $booking->bookingTransaction !== null;
            $latestTransaction = $hasTransaction ? $booking->bookingTransaction->sortByDesc('id')->first() : null;
            $paymentStatus = $latestTransaction ? $latestTransaction->payment_status : null;
            return $hasTransaction && ($paymentStatus == 'completed' || $paymentStatus == 1);
        })->count();

        $allBookingsCount = $bookings->count();

        return view('frontend::bookings', compact('bookings', 'allBookingsCount', 'upcomingBookingsCount', 'completedBookingsCount'));
    }

    public function cancel($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            // Check if user owns this booking
            if ($booking->user_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => __("frontend.unauthorized")], 403);
            }

            // Check if booking can be cancelled
            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                return response()->json(['success' => false, 'message' => __("frontend.booking_can_not_be_cancelled_in_its_current_status")], 400);
            }

            $booking->status = 'cancelled';
            $booking->save();

            return response()->json(['success' => true, 'message' => __("frontend.booking_cancelled_successfully")]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => __("frontend.an_error_occurred_while_cancelling_booking")], 500);
        }
    }

    public function details($id)
    {
        try {

            $booking = Booking::with(['bookingService'])->with(['booking_service.employee', 'branch'])->find($id);
            if (!$booking) {
                return response()->json(['success' => false, 'message' => __("frontend.booking_not_found")], 404);
            }

            // Get the first booking service to extract employee_id
            $firstService = $booking->booking_service->first();
            $employee_id = $firstService ? $firstService->employee_id : null;

            $response = [
                'date' => Carbon::parse($booking->start_date_time)->format('Y-m-d'),
                'time' => Carbon::parse($booking->start_date_time)->format('H:i'),
                'branch_id' => $booking->branch_id,
                'employee_id' => $employee_id,
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => __("frontend.an_error_occured_while_fetching_booking_details")], 500);
        }
    }

    public function reschedule(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);

            if ($booking->user_id !== auth()->id()) {
                return response()->json(['success' => false, 'message' => __("frontend.unauthorized")], 403);
            }

            if (!in_array($booking->status, ['pending', 'confirmed'])) {
                return response()->json(['success' => false, 'message' => __("frontend.booking_can_not_be_rescheduled_in_its_current_status")], 400);
            }

            $date = $request->input('date');
            $time = $request->input('time');

            if (!$date || !$time) {
                return response()->json(['success' => false, 'message' => __("frontend.date_and_time_are_required")], 400);
            }

            $newDateTime = $date . ' ' . $time;


            if (Carbon::parse($newDateTime)->isPast()) {
                return response()->json(['success' => false, 'message' => 'Cannot reschedule to a past date/time'], 400);
            }

            //   if(Carbon::parse($newDateTime)==$booking->start_date_time){

            //     return response()->json(['success' => false, 'message' => 'This Date and time already selected'], 400);
            //   }

            $booking->start_date_time = $newDateTime;
            $booking->save();

            BookingService::where('booking_id', $booking->id)
                ->update(['start_date_time' => $newDateTime]);

            return response()->json(['success' => true, 'message' => __("frontend.booking_rescheduled_successfully")]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => 'An error occurred while rescheduling the booking'], 500);
        }
    }
    // ADDED method for DataTables
    public function bookingsData(Request $request)
    {
        $user = auth()->user();

        $query = Booking::where('user_id', $user->id)
            ->whereHas('bookingService')
            ->with(['bookingService.employee', 'bookingTransaction', 'branch', 'products']);

        $type = $request->input('type', 'all');
        $searchValue = $request->input('search.value');

        if ($searchValue) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhereHas('branch', function ($sq) use ($searchValue) {
                        $sq->where('name', 'like', "%{$searchValue}%");
                    })
                    ->orWhereHas('bookingService', function ($sq) use ($searchValue) {
                        $sq->whereHas('service', function ($serviceQuery) use ($searchValue) {
                            $serviceQuery->where('name', 'like', "%{$searchValue}%");
                        });
                    });
            });
        }

        switch ($type) {
            case 'upcoming':
                $query->where('start_date_time', '>', now())->where('status', '=', 'pending');
                break;
            case 'completed':
                $query->where('status', 'completed')
                    ->whereHas('bookingTransaction', function ($q) {
                        $q->where(function ($sq) {
                            $sq->where('payment_status', 'Paid')
                                ->orWhere('payment_status', 1)
                                ->orWhere('payment_status', 'paid');
                        });
                    });
                break;
            case 'all':
            default:
                // No additional filters needed for 'all'
                break;
        }

        $query->orderByDesc('created_at');

        // Get total counts for all, upcoming, and completed bookings
        $allBookingsCount = Booking::where('user_id', $user->id)
            ->whereHas('bookingService')
            ->count();
        $upcomingBookingsCount = Booking::where('user_id', $user->id)->where('start_date_time', '>', now())->where('status', '=', 'pending')->count();
        $completedBookingsQuery = Booking::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('bookingTransaction', function ($q) {
                $q->where(function ($sq) {
                    $sq->where('payment_status', 'Paid')
                        ->orWhere('payment_status', 1)
                        ->orWhere('payment_status', 'paid');
                });
            });
        $completedBookingsCount = $completedBookingsQuery->count();

        return DataTables::of($query)
            ->addColumn('card', function ($booking) {
                // Debugging to check if booking and its relations are loaded

                if ($booking->bookingService) {
                }
                if ($booking->bookingTransaction) {
                }
                if ($booking->branch) {
                }
                return view('frontend::components.card.booking_card', compact('booking'))->render();
            })
            ->addColumn('details', function ($booking) {
                $details = [];
                if ($booking->bookingService) {
                    foreach ($booking->bookingService as $service) {
                        if ($service->service) {
                            $details[] = $service->service->name;
                        }
                        if ($service->employee) {
                            $details[] = $service->employee->full_name;
                        }
                    }
                }
                if ($booking->branch) {
                    $details[] = $booking->branch->name;
                }
                return implode(' ', $details);
            })
            ->rawColumns(['card'])
            ->with(['allBookingsCount' => $allBookingsCount, 'upcomingBookingsCount' => $upcomingBookingsCount, 'completedBookingsCount' => $completedBookingsCount])
            ->make(true);
    }

    public function BookingDetail(Request $request)
    {
        return view('frontend::booking-details');
    }

    public function bookingDetailPage($id)
    {
        $booking = \Modules\Booking\Models\Booking::with([
            'branch.address',
            'booking_service.service.category',
            'booking_service.employee',
            'bookingTransaction',
            'payment',
            'user',
            'products',
            'packages',
            'bookingPackages.services',
            'userCouponRedeem'
        ])->findOrFail($id);


        if ($booking == null) {
            abort(404, 'Booking not found.');
        }

        if ($booking->user_id != auth()->id()) {

            abort(404, 'You are not authorized to view this booking.');
        }

        // Payment summary: same source of truth as mobile app (subtotal - discount + tax = total)
        $branchId = $booking->branch_id ?? null;
        $serviceSubtotal = 0;
        foreach ($booking->booking_service ?? [] as $service) {
            $servicePrice = (float) ($service->service_price ?? 0);
            if ($branchId && !empty($service->service_id)) {
                $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->service_id)
                    ->where('branch_id', $branchId)
                    ->first();
                if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                    $servicePrice = (float) $branchService->service_price;
                }
            }
            $serviceSubtotal += $servicePrice;
        }
        $productSubtotal = $booking->products ? (float) $booking->products->sum('discounted_price') : 0;
        $packageSubtotal = $booking->packages ? (float) $booking->packages->sum('package_price') : 0;
        $subtotal = $serviceSubtotal + $productSubtotal + $packageSubtotal;

        $latestTransaction = $booking->bookingTransaction && $booking->bookingTransaction->isNotEmpty()
            ? $booking->bookingTransaction->sortByDesc('id')->first()
            : null;
        $discountFromTransaction = $latestTransaction ? (float) ($latestTransaction->discount_amount ?? 0) : 0;
        $discountFromCouponRedeem = (float) (optional($booking->userCouponRedeem)->discount ?? 0);
        $coupon_discount = $discountFromTransaction > 0 ? $discountFromTransaction : $discountFromCouponRedeem;

        $taxData = $booking->payment->tax_percentage ?? null;
        if (is_string($taxData)) {
            $taxData = json_decode($taxData, true) ?: null;
        }
        $taxResult = getBookingTaxamount($subtotal, $coupon_discount, $taxData);
        $tax_sum = (float) ($taxResult['total_tax_amount'] ?? 0);
        $total = $subtotal - $coupon_discount + $tax_sum;

        $booking->coupon_discount = $coupon_discount;
        $paymentSummary = [
            'subtotal' => $subtotal,
            'coupon_discount' => $coupon_discount,
            'tax_sum' => $tax_sum,
            'total' => $total,
            'coupon_percent' => $latestTransaction ? (float) ($latestTransaction->discount_percentage ?? 0) : 0,
        ];

        $employee_id = $booking->booking_service->first()->employee_id ?? null;
        $employeeReview = null;
        if ($employee_id && auth()->check()) {
            $employeeReview = \Modules\Employee\Models\EmployeeRating::where('employee_id', $employee_id)
                ->where('user_id', auth()->id())
                ->orderByDesc('created_at')
                ->first();
        }

        return view('frontend::booking-details', compact('booking', 'employeeReview', 'paymentSummary'));
    }

    public function chooseExpert(Request $request)
    {
        $pageTitle = __('frontend.choose_expert');
        $progress = session('booking_progress', []);
        $fields = [
            'branch_id' => $request->input('selected_branch_id'),
            'services' => $request->input('selected_services'),
            'expert_id' => $request->input('selected_expert'),
            'date' => $request->input('selected_date'),
            'time' => $request->input('selected_time'),
            'payment_method' => $request->input('payment_method'),
        ];
        foreach ($fields as $key => $value) {
            if ($value !== null) {
                $progress[$key] = $value;
            }
        }
        session(['booking_progress' => $progress]);

        if (!auth()->check()) {
            session(['booking_redirect_url' => url()->current()]);
        }

        if ($progress) {

            if (isset($progress['branch_id'])) {
                session(['selected_branch_id' => $progress['branch_id']]);
            }
            if (isset($progress['services'])) {
                $servicesStr = is_array($progress['services']) ? implode(',', $progress['services']) : $progress['services'];
                $request->merge(['selected_services' => $servicesStr]);
            }
            if (isset($progress['expert_id'])) {
                $request->merge(['selected_expert' => $progress['expert_id']]);
            }
            if (isset($progress['date'])) {
                $request->merge(['selected_date' => $progress['date']]);
            }
            if (isset($progress['time'])) {
                $request->merge(['selected_time' => $progress['time']]);
            }
            if (isset($progress['payment_method'])) {
                $request->merge(['payment_method' => $progress['payment_method']]);
            }
            if (isset($progress['coupon_code'])) {
                $request->merge(['coupon_code' => $progress['coupon_code']]);
            }
        }

        $branchEmployeeIds = null;
        $branchId = null;
        if (session()->has('selected_branch_id')) {
            $branchId = session('selected_branch_id');
            $branchEmployeeIds = \Modules\Employee\Models\BranchEmployee::where('branch_id', $branchId)->pluck('employee_id')->toArray();
        }


        // Get selected services
        $servieces = $request->input('selected_services');
        $selectedServices = array_filter(explode(',', $servieces));

        // Get employee IDs for all selected services (intersection)
        $serviceEmployeeIds = null;
        if (!empty($selectedServices)) {
            $serviceEmployeeIds = null;
            foreach ($selectedServices as $serviceId) {
                $ids = \Modules\Service\Models\ServiceEmployee::where('service_id', $serviceId)->pluck('employee_id')->toArray();
                if ($serviceEmployeeIds === null) {
                    $serviceEmployeeIds = $ids;
                } else {
                    $serviceEmployeeIds = array_intersect($serviceEmployeeIds, $ids);
                }
            }
        }

        // Employees who are in both branch and all selected services
        $finalEmployeeIds = $branchEmployeeIds && $serviceEmployeeIds ? array_intersect($branchEmployeeIds, $serviceEmployeeIds) : [];


        $employeesList = [];
        if (!empty($finalEmployeeIds)) {
            $employeesList = \App\Models\User::role('employee')
                ->with(['media', 'branches', 'services'])
                ->where('status', 1)
                ->whereNotNull('email_verified_at')
                ->whereIn('id', $finalEmployeeIds)
                ->get();
            $fallbackMode = false;
        } else {
            $employeesList = collect();
            $fallbackMode = false;
        }


        // Get services details
        $services = \Modules\Service\Models\Service::with('category')->whereIn('id', $selectedServices)->get();


        $tax = \Modules\Tax\Models\Tax::where(function ($query) {
            $query->where('module_type', 'services')
                ->orWhereNull('module_type');
        })->where('status', 1)->get();
        if ($tax->isEmpty()) {
            $tax = collect();
        }

        // Calculate subtotal using branch-specific prices
        $branchId = session('selected_branch_id');
        $subtotal = 0;
        foreach ($services as $service) {
            $servicePrice = $service->default_price;
            if ($branchId) {
                $branchService = \Modules\Service\Models\ServiceBranches::where('service_id', $service->id)
                    ->where('branch_id', $branchId)
                    ->first();
                // Use branch-specific price if available and greater than 0
                if ($branchService && isset($branchService->service_price) && $branchService->service_price > 0) {
                    $servicePrice = $branchService->service_price;
                }
            }
            $subtotal += $servicePrice;
        }
        
        // Calculate total tax
        $totalTaxAmount = 0;
        foreach ($tax as $taxItem) {
            if ($taxItem->type == 'fixed') {
                $taxAmount = $taxItem->value;
            } else {
                $taxAmount = ($subtotal * $taxItem->value) / 100;
            }
            $totalTaxAmount += $taxAmount;
        }

        $appliedCoupon = null;
        if ($request->has('coupon_code')) {
            $couponCode = $request->input('coupon_code');
            $appliedCoupon = \Modules\Promotion\Models\Coupon::where('coupon_code', $couponCode)
                ->where('is_expired', 0)
                ->where('start_date_time', '<=', now())
                ->where('end_date_time', '>=', now())
                ->first();
        }

        $currentStep = isset($progress['step']) ? $progress['step'] : null;
        $wallet = Wallet::where('user_id', auth()->id())->first();
        if ($wallet) {
            $walletPayment = true;
            $walletBalance = $wallet->amount;
        } else {
            $walletPayment = false;
            $walletBalance = 0;
        }
        return view('frontend::booking-choose-expert', compact('pageTitle', 'employeesList', 'services', 'tax', 'totalTaxAmount', 'appliedCoupon', 'fallbackMode', 'progress', 'currentStep', 'walletBalance', 'walletPayment'));
    }

    public function BlogDetails(Request $request)
    {
        return view('frontend::blog-details');
    }

    public function Address(Request $request)
    {
        return view('frontend::address');
    }

    public function Profile(Request $request)
    {
        return view('frontend::profile');
    }

    public function Contact(Request $request)
    {
        $contact = Page::where('slug', 'contact-us')->first();
        $contactTitle = $contact->name ?? 'Contact Us';
        $contactContent = $contact->description ?? '';

        $branch_id = session('selected_branch_id');
        if ($branch_id) {
            $branch = Branch::with('bussinesshours')->where('id', $branch_id)->first();
        } else {
            $branch = Branch::with('bussinesshours')->where('status', 1)->first();
        }

        return view('frontend::contact', compact('contactTitle', 'contactContent', 'branch'));
    }

    public function Faq(Request $request)
    {
        $data = [
            'bread_crumb' => [], // Set your breadcrumb data here if needed
            'faqs' => \App\Models\Faq::where('status', 1)->get(),
        ];
        return view('frontend::faq', compact('data'));
    }

    public function Search(Request $request)
    {
        return view('frontend::search');
    }

    public function Bookingflow(Request $request)
    {
        $categories = \Modules\Category\Models\Category::where('status', 1)
            ->whereNull('parent_id')
            ->whereHas('services', function($q) {
                $q->where('status', 1);
            })
            ->withCount(['services' => function($q) {
                $q->where('status', 1);
            }])
            ->get();
        $allServicesCount = \Modules\Service\Models\Service::where('status', 1)->count();

        return view('frontend::bookingflow', compact('categories', 'allServicesCount'));
    }

    public function Membership(Request $request)
    {
        return view('frontend::membership');
    }

    public function About(Request $request)
    {
        $about = null;
        if (class_exists('Modules\\Page\\Models\\Page')) {
            $about = \Modules\Page\Models\Page::where('slug', 'about-us')->first();
        }
        $aboutTitle = $about->name ?? 'About Us';
        $aboutContent = $about->description ?? 'Your about us content goes here.';
        return view('frontend::about', compact('aboutTitle', 'aboutContent'));
    }

    public function Blog(Request $request)
    {
        return view('frontend::blog');
    }

    public function index_list(Request $request)
    {
        $term = trim($request->q);

        if (empty($term)) {
            return response()->json([]);
        }

        $query_data = Frontend::where('name', 'LIKE', "%$term%")->orWhere('slug', 'LIKE', "%$term%")->limit(7)->get();

        $data = [];

        foreach ($query_data as $row) {
            $data[] = [
                'id' => $row->id,
                'text' => $row->name . ' (Slug: ' . $row->slug . ')',
            ];
        }
        return response()->json($data);
    }

    public function index_data()
    {
        $query = Frontend::query();

        return Datatables::of($query)
            ->addColumn('check', function ($data) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-' . $data->id . '"  name="datatable_ids[]" value="' . $data->id . '" onclick="dataTableRowCheck(' . $data->id . ')">';
            })
            ->addColumn('action', function ($data) {
                return view('frontend::backend.frontends.action_column', compact('data'));
            })
            ->editColumn('status', function ($data) {
                return $data->getStatusLabelAttribute();
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
            ->rawColumns(['action', 'status', 'check'])
            ->orderColumns(['id'], '-:column $1')
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = Frontend::create($request->all());

        $message = __("frontend.new_frontend_added");

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function edit($id)
    {
        $data = Frontend::findOrFail($id);

        return response()->json(['data' => $data, 'status' => true]);
    }

    public function update(Request $request, $id)
    {
        $data = Frontend::findOrFail($id);

        $data->update($request->all());

        $message = __("frontend.fronted_updated_successfully");

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function destroy($id)
    {
        $data = Frontend::findOrFail($id);

        $data->delete();

        $message = __("frontend.frontend_deleted_successfully");

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function MembershipCheckout()
    {
        return view('frontend::membership_checkout');
    }

    public function getAvailableSlots(Request $request)
    {
        $day = date('l', strtotime($request->date));
        $branch_id = $request->branch_id;
        $employee_id = $request->employee_id;
        $serviceDuration = $request->service_duration ?? 0; // default to 0 if not provided

        $slots = $this->getSlots($request->date, $day, $branch_id, $serviceDuration, $employee_id);

        return response()->json([
            'status' => 'success',
            'data' => $slots
        ]);
    }

    public function couponlist()
    {
        try {
            $today_date = date('Y-m-d');

            $coupons = Promotion::with('coupon')
                ->where('status', 1)
                ->whereHas('coupon', function ($query) use ($today_date) {
                    $query->where('is_expired', 0)
                        ->where('end_date_time', '>=', $today_date)
                        ->where('start_date_time', '<=', $today_date);
                })
                ->get();

            return response()->json([
                'status' => true,
                'data' => PromotionResource::collection($coupons),
                'message' => __('promotion.coupons_list'),
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Error loading coupons: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function getDetails(Request $request)
    {
        $code = $request->input('code');
        $coupon = Coupon::where('coupon_code', $code)->first();

        if (!$coupon) {
            return response()->json(['status' => 'error', 'message' => __('frontend.invalid_coupon')]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'discount_type' => $coupon->discount_type,
                'discount_amount' => $coupon->discount_amount,
                'discount_percentage' => $coupon->discount_percentage
            ]
        ]);
    }

    public function getExpertsByBranch(Request $request)
    {
        $branchId = $request->input('branch_id');
        // $serviceId = $request->input('service_id'); // No longer needed

        if (!$branchId) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.branch_id_is_required'),
                'experts' => []
            ]);
        }

        $employeeIds = \Modules\Employee\Models\BranchEmployee::where('branch_id', $branchId)
            ->pluck('employee_id');

        $section7 = FrontendSetting::where('type', 'landing-page-setting')->where('key', 'section_7')->first();

        $section7Enabled = false;
        $expertIds = [];
        if ($section7 && $section7->value) {
            $config = is_array($section7->value) ? $section7->value : json_decode($section7->value, true);

            if (isset($config['status']) && $config['status'] == 1 && !empty($config['expert_id']) && is_array($config['expert_id'])) {
                $section7Enabled = true;
                $expertIds = $config['expert_id'];
            }
        }

        $expertsQuery = User::role('employee')
            ->whereIn('id', $employeeIds)->whereIn('id', $expertIds)
            ->where('status', 1)
            ->with(['media', 'profile', 'services.service.category']);



        // Service filter removed: always show all branch experts

        $experts = $expertsQuery->get()->map(function ($expert) {
            $averageRating = \Modules\Employee\Models\EmployeeRating::where('employee_id', $expert->id)
                ->avg('rating');
            
        return [
            'id' => $expert->id,
            'name' => $expert->full_name,
            'image_path' => $expert->profile_image,
            'rating' => $averageRating ? round($averageRating, 1) : 0,
            'speciality' => $expert->specialty,
            'email' => $expert->email,
            'mobile' => $expert->mobile
        ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Experts retrieved successfully',
            'experts' => $experts
        ]);
    }

    public function setBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        session(['selected_branch_id' => $request->branch_id]);

        return response()->json([
            'success' => true,
            'message' => __('frontend.branch_selected_successfully'),
            'branch_id' => $request->branch_id,
            'redirect' => route('index')
        ]);
    }

    public function globalSearch(Request $request)
    {
        $query = $request->input('query');
        $categories = [];
        $packages = [];
        $services = [];
        $products = [];
        $experts = [];
        $allServicesCount = \Modules\Service\Models\Service::where('status', 1)->count();


        if ($query) {
            $categories = \Modules\Category\Models\Category::where('status', 1)->where('parent_id', null)
                ->where('name', 'like', "%{$query}%")
                ->get();

            // $packages = \Modules\Package\Models\Package::where('name', 'like', "%{$query}%")->get();

            $services = \Modules\Service\Models\Service::where('name', 'like', "%{$query}%")
                ->with('category')
                ->get();

            $products = \Modules\Product\Models\Product::where('name', 'like', "%{$query}%")
                ->with(['categories', 'media'])
                ->get();

            $experts = \App\Models\User::role('employee')
                ->where('status', 1)
                ->where(function ($q) use ($query) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$query}%"])
                        ->orWhere('email', 'like', "%{$query}%");
                })
                ->get();
        }



        $categories_data = Category::where('status', 1)
            ->whereNull('parent_id')
            ->withCount(['services' => function($q) {
                $q->where('status', 1);
            }])
            ->orderBy('services_count', 'desc')
            ->take(5)
            ->get();

        // For AJAX requests, return the search results as HTML
        if ($request->ajax() || $request->wantsJson()) {

            $view = view('frontend::partials.search-results', compact(
                'query',
                'categories',
                'packages',
                'services',
                'products',
                'experts'
            ));

            $html = $view->render();

            return response()->json([
                'success' => true,
                'html' => $html,
                'query' => $query
            ]);
        }

        // For regular requests, return the full view
        return view('frontend::search', compact(
            'query',
            'categories',
            'packages',
            'services',
            'products',
            'experts',
            'allServicesCount',
            'categories_data'
        ));
    }

    public function storeBookingProgress(Request $request)
    {
        // Store booking progress data with all details
        $bookingData = $request->all();

        // Ensure we store all necessary booking information
        $bookingData['timestamp'] = now()->timestamp; // Add timestamp for tracking

        session(['booking_progress' => $bookingData]);

        // Store the current URL for redirect after login
        session(['booking_redirect_url' => url()->current()]);

        session()->forget('selected_service');
        session()->forget('selected_category');


        return response()->json(['success' => true]);
    }

    public function terms(Request $request)
    {
        $terms = null;
        if (class_exists('Modules\\Page\\Models\\Page')) {
            $terms = \Modules\Page\Models\Page::where('slug', 'terms-conditions')->first();
        }
        $termsTitle = $terms->name ?? 'Terms & Conditions';
        $termsContent = $terms->description ?? 'Your terms and conditions content goes here.';
        return view('frontend::terms', compact('termsTitle', 'termsContent'));
    }

    public function privacy()
    {
        $privacy = null;
        if (class_exists('Modules\\Page\\Models\\Page')) {
            $privacy = \Modules\Page\Models\Page::where('slug', 'privacy-policy')->first();
        }
        $privacyTitle = $privacy->name ?? 'Privacy Policy';
        $privacyContent = $privacy->description ?? 'Your privacy policy content goes here.';
        return view('frontend::privacy', compact('privacyTitle', 'privacyContent'));
    }

    public function support(Request $request)
    {
        $support = null;
        if (class_exists('Modules\\Page\\Models\\Page')) {
            $support = \Modules\Page\Models\Page::where('slug', 'help-support')->first();
        }
        $supportTitle = $support->name ?? 'Help & Support';
        $supportContent = $support->description ?? 'Your help and support content goes here.';
        return view('frontend::support', compact('supportTitle', 'supportContent'));
    }

    public function dataDeletion(Request $request)
    {
        $dataDeletion = null;
        if (class_exists('Modules\\Page\\Models\\Page')) {
            $dataDeletion = \Modules\Page\Models\Page::where('slug', 'data-deletion-request')->first();
        }
        $dataDeletionTitle = $dataDeletion->name ?? 'Data Deletion Request';
        $dataDeletionContent = $dataDeletion->description ?? 'Your data deletion request content goes here.';
        return view('frontend::data_deletion', compact('dataDeletionTitle', 'dataDeletionContent'));
    }

    public function downloadBookingInvoice($booking_id)
    {
        try {
            $booking = \Modules\Booking\Models\Booking::with([
                'branch.address',
                'booking_service.service',
                'booking_service.employee',
                'bookingTransaction',
                'user',
                'products',
                'packages',
                'bookingPackages.services',
                'userCouponRedeem'
            ])->findOrFail($booking_id);

            $taxes = \Modules\Tax\Models\Tax::where('status', 1)->get();

            // ------------------------------
            // Get logo path from settings or use default
            // ------------------------------
            $logo = null;
            $logoSetting = setting('logo');

            if ($logoSetting) {
                try {
                    // Extract path from setting (works with URLs and relative paths)
                    $logoPath = parse_url($logoSetting, PHP_URL_PATH) ?? $logoSetting;

                    // Remove leading slash and possible "frezka/" subfolder
                    $logoPath = ltrim($logoPath, '/');
                    $logoPath = preg_replace('/^frezka\//', '', $logoPath);

                    // Build absolute public path
                    $logoPath = public_path($logoPath);

                    if (file_exists($logoPath) && is_readable($logoPath)) {
                        // Get MIME type
                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                        $mimeType = finfo_file($finfo, $logoPath);
                        finfo_close($finfo);

                        $extensions = [
                            'image/jpeg'    => 'jpeg',
                            'image/png'     => 'png',
                            'image/gif'     => 'gif',
                            'image/svg+xml' => 'svg+xml',
                            'image/webp'    => 'webp',
                        ];

                        $extension = $extensions[$mimeType] ?? 'jpeg';

                        $logoData = file_get_contents($logoPath);
                        $logo = 'data:image/' . $extension . ';base64,' . base64_encode($logoData);
                    }
                } catch (\Exception $e) {
                }
            }

            // If no logo from settings or failed to load, try default logo
            if (!$logo) {
                try {
                    $defaultLogoPath = public_path('img/logo/logo.png');
                    if (file_exists($defaultLogoPath) && is_readable($defaultLogoPath)) {
                        $logoData = file_get_contents($defaultLogoPath);
                        $logo = 'data:image/png;base64,' . base64_encode($logoData);
                    }
                } catch (\Exception $e) {
                }
            }

            // ------------------------------
            // Generate PDF
            // ------------------------------
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('frontend::invoice_booking', [
                'booking' => $booking,
                'taxes'   => $taxes,
                'logo'    => $logo,
            ]);

            $pdf->setPaper('a4')
                ->setOptions([
                    'isRemoteEnabled'     => false,
                    'isHtml5ParserEnabled' => true,
                    'defaultFont'         => 'DejaVu Sans',
                ]);

            return response()->streamDownload(
                function () use ($pdf) {
                    echo $pdf->output();
                },
                "invoice-booking-{$booking->id}.pdf",
                [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename=\"invoice-booking-{$booking->id}.pdf\"',
                ]
            );
        } catch (\Exception $e) {

            return back()->with('error', __('messages.something_went_wrong'));
        }
    }
}
