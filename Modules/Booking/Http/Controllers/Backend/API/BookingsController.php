<?php

namespace Modules\Booking\Http\Controllers\Backend\API;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Currency\Models\Currency;
use Modules\Promotion\Models\Coupon;
use Modules\Promotion\Models\UserCouponRedeem;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceBranches;
use Modules\Service\Models\ServiceEmployee;
use Modules\Tax\Models\Tax;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Trait\BookingTrait;
use Modules\Booking\Trait\PaymentTrait;
use Modules\Booking\Transformers\BookingDetailResource;
use Modules\Booking\Transformers\BookingPackageDetailResource;
use Modules\Booking\Transformers\BookingListResource;
use Modules\Booking\Transformers\BookingResource;
use Modules\Constant\Models\Constant;
use Modules\Package\Models\BookingPackages;
use Modules\Package\Models\Package;
use Modules\Package\Models\PackageService;
use Modules\Package\Models\UserPackage;
use Modules\Package\Models\UserPackageServices;
use Modules\Promotion\Models\Promotion;
use Modules\Package\Models\BookingPackageService;
//use Modules\Booking\Trait\BookingTrait;

class BookingsController extends Controller
{
    use BookingTrait;
    use PaymentTrait;
    public function __construct()
    {
        // Page Title
        $this->module_title = 'Bookings';
    }

    public function store(Request $request)
    {
        // Admins can always create bookings
        // Managers and others need add_booking permission
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('user') && !auth()->user()->can('add_booking')) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }
        $data = $request->all();
        if (!empty($request->date) && !empty($request->date)) {
            $data['start_date_time'] = Carbon::createFromFormat('d/m/Y h:i A', $data['date'] . ' ' . $data['time']);
        }

        $data['user_id'] = !empty($request->user_id) ? $request->user_id : auth()->user()->id;
        $userId = $data['user_id'];
        $is_reclaim = false;
        $alreadyPurchased = false;
        if (!empty($request->packages) && (!$request->has('is_reclaim') || !$request->is_reclaim)) {

            foreach ($request->packages as $key => $value) {
                $existingPackage = UserPackage::where('package_id', $value['id'])
                    ->where('user_id', $userId)
                    ->exists();
                if ($existingPackage) {
                    $alreadyPurchased = true;
                    return response()->json(['message' => 'Package already purchased.', 'status' => false], 200);
                }
            }
        }

        // Coupon validation BEFORE booking creation
        if (!empty($data['coupon_code'])) {
            $coupon = UserCouponRedeem::where('coupon_code', $data['coupon_code'])->first();
            $coupon_data = Coupon::where('coupon_code', $data['coupon_code'])->first();

            $totalPrice = 0;
            if (!empty($data['services'])) {
                $totalPrice = array_sum(array_column($data['services'], 'service_price'));
            } elseif (!empty($data['packages'])) {
                $totalPrice = array_sum(array_column($data['packages'], 'package_price'));
            }
            if (!isset($data['couponDiscountamount'])) {
                $data['couponDiscountamount'] = 0;
            }
            if ($data['couponDiscountamount'] > $totalPrice) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Discount exceeds the total price',
                    'status' => false
                ], 200);
            }

            // Unified coupon validation logic
            $isExpired = $coupon_data && $coupon_data->is_expired == 1;
            $couponId = $coupon_data ? $coupon_data->id : ($coupon ? $coupon->coupon_id : null);
            $redemptionsCount = $couponId ? UserCouponRedeem::where('coupon_id', $couponId)->count() : 0;
            $useLimitReached = $coupon_data && $coupon_data->use_limit && $redemptionsCount >= $coupon_data->use_limit;

            if ($isExpired) {
                $message = 'Coupon has expired.';
                return response()->json(['message' => $message, 'status' => false], 200);
            }
            if ($useLimitReached) {
                $message = 'Your coupon limit has been reached.';
                return response()->json(['message' => $message, 'status' => false], 200);
            }
        }

        // Only create booking after coupon validation passes
        $booking = Booking::create($data);

        if (!empty($data['coupon_code'])) {
            $coupon = UserCouponRedeem::where('coupon_code', $data['coupon_code'])->first();
            $coupon_data = Coupon::where('coupon_code', $data['coupon_code'])->first();

            $totalPrice = 0;

            // Calculate the total price based on services or packages
            if (!empty($data['services'])) {
                $totalPrice = array_sum(array_column($data['services'], 'service_price'));
            } elseif (!empty($data['packages'])) {
                $totalPrice = array_sum(array_column($data['packages'], 'package_price'));
            }
            // Apply the discount validation
            if (!isset($data['couponDiscountamount'])) {
                $data['couponDiscountamount'] = 0;
            }

            if ($data['couponDiscountamount'] > $totalPrice) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Discount exceeds the total price',
                    'status' => false
                ], 200);
            }

            if (!$coupon) {
                if ($coupon_data->is_expired == 1) {
                    $message = 'Coupon has expired.';
                    return response()->json(['message' => $message, 'status' => false], 200);
                } else {
                    $redeemCoupon = [
                        'coupon_code' => $data['coupon_code'],
                        'discount' => $data['couponDiscountamount'],
                        'user_id' => $data['user_id'],
                        'coupon_id' => $coupon_data->id,
                        'booking_id' => $booking->id,
                    ];

                    $user_coupon = UserCouponRedeem::create($redeemCoupon);

                    $couponRedemptionsCount = UserCouponRedeem::where('coupon_id', $user_coupon->coupon_id)->count();
                    if ($coupon_data->use_limit && $couponRedemptionsCount >= $coupon_data->use_limit) {
                        Coupon::where('coupon_code', $data['coupon_code'])->update(['is_expired' => 1]);
                        if ($coupon = Coupon::where('coupon_code', $data['coupon_code'])->first()) {
                            Promotion::where('id', $coupon->promotion_id)->update(['status' => 0]);
                        }
                    }
                }
            } else {
                if ($coupon_data->is_expired == 1) {
                    $message = 'Coupon has expired.';
                    return response()->json(['message' => $message, 'status' => false], 200);
                } else {
                    $couponRedemptionsCount = UserCouponRedeem::where('coupon_id', $coupon->coupon_id)->count();
                    if ($coupon_data->use_limit && $couponRedemptionsCount >= $coupon_data->use_limit) {
                        $message = 'Your coupon limit has been reached.';
                        return response()->json(['message' => $message, 'status' => false], 200);
                    } else {
                        $redeemCoupon = [
                            'coupon_code' => $data['coupon_code'],
                            'discount' => $data['couponDiscountamount'],
                            'user_id' => $data['user_id'],
                            'coupon_id' => $coupon_data->id,
                            'booking_id' => $booking->id,
                        ];

                        UserCouponRedeem::create($redeemCoupon);
                        $total_coupon = UserCouponRedeem::where('coupon_code', $data['coupon_code'])->count();
                        if ($total_coupon == $coupon_data->use_limit) {
                            Coupon::where('coupon_code', $data['coupon_code'])->update(['is_expired' => 1]);
                            if ($coupon = Coupon::where('coupon_code', $data['coupon_code'])->first()) {
                                Promotion::where('id', $coupon->promotion_id)->update(['status' => 0]);
                            }
                        }
                    }
                }
            }
        }
        //if package reclaim


        if ($request->has('is_reclaim') && isset($request->packages) && $request->is_reclaim == true) {
            $is_reclaim = true;

            $this->updateAPIBookingPackage($request->packages, $booking->id, $request->employee_id, $userId, $is_reclaim);
            foreach ($request->packages as $key => $value) {
                $UserPackages = UserPackage::with('bookings')
                    ->where('package_id', $value['id'])
                    ->where('user_id', $userId)
                    ->get();

                $bookingPackage = BookingPackages::where('booking_id', $booking->id)->first();

                if ($UserPackages->isNotEmpty()) {
                    foreach ($UserPackages as $UserPackage) {
                        foreach ($value['services'] as $service) {
                            $userPackageService = UserPackageServices::where('user_package_id', $UserPackage->id)
                                ->whereHas('packageService', function ($query) use ($service) {
                                    $query->where('service_id', $service['service_id']);
                                })->first();

                            if ($userPackageService) {
                                if ($userPackageService->qty >= 1) {
                                    $bookingPackageService = BookingPackageService::Create([
                                        'booking_id' => $booking->id,
                                        'package_id' => $value['id'],
                                        'user_id' => $userId,
                                        'package_service_id' => $userPackageService->package_service_id,
                                        'booking_package_id' => $bookingPackage->id,
                                        'service_name' => $userPackageService->service_name,
                                        'qty' => $userPackageService->qty - 1,
                                        'service_id' => $service['service_id'],
                                    ]);
                                    $userPackageService->qty -= 1;
                                    $userPackageService->save();
                                }

                                if ($userPackageService->qty == 0) {
                                    $userPackageService->delete();
                                }
                            }
                        }

                        $remainingServices = UserPackageServices::where('user_package_id', $UserPackage->id)->count();
                        if ($remainingServices == 0) {
                            $UserPackage->delete();
                        } else {
                            $UserPackage->type = 'reclaimed';
                            $UserPackage->save();
                        }
                    }
                }
            }
        } else  if ($alreadyPurchased == false) {
            $this->updateAPIBookingPackage($request->packages, $booking->id, $request->employee_id, $userId, $is_reclaim);
            $this->storeApiUserPackage($booking->id);
        }

        //service
        if (!empty($request->services)) {
            $this->updateBookingService($request->services, $booking->id);
        }

        $message = 'New ' . Str::singular($this->module_title) . ' Added';
        try {
            $type = 'new_booking';
            $messageTemplate = 'New booking #[[booking_id]] has been booked.';
            $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
            $this->sendNotificationOnBookingUpdate($type, $notify_message, $booking);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        return response()->json(['message' => $message, 'status' => true, 'booking_id' => $booking->id], 200);
    }

    /**
     * Save booking for manager
     * Endpoint: save-booking-manager
     */
    public function saveBookingManager(Request $request)
    {
        $user = auth()->user();

        // Only managers and admins can use this endpoint
        if (!$user || (!$user->hasRole('manager') && !$user->hasRole('admin'))) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }

        // Validate required fields
        if (!$request->has('branch_id') || empty($request->branch_id)) {
            return response()->json([
                'message' => 'branch_id is required',
                'status' => false
            ], 400);
        }

        if (!$request->has('customer_id') || empty($request->customer_id)) {
            return response()->json([
                'message' => 'customer_id is required',
                'status' => false
            ], 400);
        }

        if (!$request->has('date') || empty($request->date)) {
            return response()->json([
                'message' => 'date is required',
                'status' => false
            ], 400);
        }

        if (!$request->has('time') || empty($request->time)) {
            return response()->json([
                'message' => 'time is required',
                'status' => false
            ], 400);
        }

        if (!$request->has('staff_id') || empty($request->staff_id)) {
            return response()->json([
                'message' => 'staff_id is required',
                'status' => false
            ], 400);
        }

        if (!$request->has('services') || empty($request->services)) {
            return response()->json([
                'message' => 'services are required',
                'status' => false
            ], 400);
        }

        // Prepare booking data
        $data = [
            'branch_id' => (int) $request->branch_id,
            'user_id' => (int) $request->customer_id,
            'status' => $request->status ?? 'pending',
            'note' => $request->note ?? null,
        ];

        // Convert date and time to start_date_time
        try {
            $data['start_date_time'] = Carbon::createFromFormat('d/m/Y h:i A', $request->date . ' ' . $request->time);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid date or time format. Expected date format: dd/mm/yyyy and time format: hh:mm AM/PM',
                'status' => false
            ], 400);
        }

        $userId = $data['user_id'];
        $staffId = (int) $request->staff_id;

        // Prepare services array with employee_id from staff_id
        $services = [];
        foreach ($request->services as $service) {
            $services[] = [
                'service_id' => (int) $service['service_id'],
                'employee_id' => $staffId,
                'start_date_time' => $service['start_date_time'] ?? $data['start_date_time']->format('Y-m-d H:i:s'),
                'service_price' => (float) ($service['service_price'] ?? 0),
                'duration_min' => (int) ($service['duration_min'] ?? 30),
            ];
        }

        // Coupon validation BEFORE booking creation
        if (!empty($request->coupon_code)) {
            $coupon = UserCouponRedeem::where('coupon_code', $request->coupon_code)->first();
            $coupon_data = Coupon::where('coupon_code', $request->coupon_code)->first();

            $totalPrice = array_sum(array_column($services, 'service_price'));

            $couponDiscountAmount = (float) ($request->couponDiscountamount ?? 0);

            if ($couponDiscountAmount > $totalPrice) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Discount exceeds the total price',
                    'status' => false
                ], 400);
            }

            // Unified coupon validation logic
            $isExpired = $coupon_data && $coupon_data->is_expired == 1;
            $couponId = $coupon_data ? $coupon_data->id : ($coupon ? $coupon->coupon_id : null);
            $redemptionsCount = $couponId ? UserCouponRedeem::where('coupon_id', $couponId)->count() : 0;
            $useLimitReached = $coupon_data && $coupon_data->use_limit && $redemptionsCount >= $coupon_data->use_limit;

            if ($isExpired) {
                $message = 'Coupon has expired.';
                return response()->json(['message' => $message, 'status' => false], 400);
            }
            if ($useLimitReached) {
                $message = 'Your coupon limit has been reached.';
                return response()->json(['message' => $message, 'status' => false], 400);
            }
        }

        // Create booking
        $booking = Booking::create($data);

        // Handle coupon redemption
        if (!empty($request->coupon_code)) {
            $coupon = UserCouponRedeem::where('coupon_code', $request->coupon_code)->first();
            $coupon_data = Coupon::where('coupon_code', $request->coupon_code)->first();

            $totalPrice = array_sum(array_column($services, 'service_price'));
            $couponDiscountAmount = (float) ($request->couponDiscountamount ?? 0);

            if ($couponDiscountAmount > $totalPrice) {
                $booking->delete(); // Rollback booking if discount invalid
                return response()->json([
                    'valid' => false,
                    'message' => 'Discount exceeds the total price',
                    'status' => false
                ], 400);
            }

            if (!$coupon) {
                if ($coupon_data && $coupon_data->is_expired == 1) {
                    $message = 'Coupon has expired.';
                    $booking->delete(); // Rollback booking
                    return response()->json(['message' => $message, 'status' => false], 400);
                } else {
                    $redeemCoupon = [
                        'coupon_code' => $request->coupon_code,
                        'discount' => $couponDiscountAmount,
                        'user_id' => $userId,
                        'coupon_id' => $coupon_data->id,
                        'booking_id' => $booking->id,
                    ];

                    $user_coupon = UserCouponRedeem::create($redeemCoupon);

                    $couponRedemptionsCount = UserCouponRedeem::where('coupon_id', $user_coupon->coupon_id)->count();
                    if ($coupon_data->use_limit && $couponRedemptionsCount >= $coupon_data->use_limit) {
                        Coupon::where('coupon_code', $request->coupon_code)->update(['is_expired' => 1]);
                        if ($coupon = Coupon::where('coupon_code', $request->coupon_code)->first()) {
                            Promotion::where('id', $coupon->promotion_id)->update(['status' => 0]);
                        }
                    }
                }
            } else {
                if ($coupon_data && $coupon_data->is_expired == 1) {
                    $message = 'Coupon has expired.';
                    $booking->delete(); // Rollback booking
                    return response()->json(['message' => $message, 'status' => false], 400);
                } else {
                    $couponRedemptionsCount = UserCouponRedeem::where('coupon_id', $coupon->coupon_id)->count();
                    if ($coupon_data->use_limit && $couponRedemptionsCount >= $coupon_data->use_limit) {
                        $message = 'Your coupon limit has been reached.';
                        $booking->delete(); // Rollback booking
                        return response()->json(['message' => $message, 'status' => false], 400);
                    } else {
                        $redeemCoupon = [
                            'coupon_code' => $request->coupon_code,
                            'discount' => $couponDiscountAmount,
                            'user_id' => $userId,
                            'coupon_id' => $coupon_data->id,
                            'booking_id' => $booking->id,
                        ];

                        UserCouponRedeem::create($redeemCoupon);
                        $total_coupon = UserCouponRedeem::where('coupon_code', $request->coupon_code)->count();
                        if ($total_coupon == $coupon_data->use_limit) {
                            Coupon::where('coupon_code', $request->coupon_code)->update(['is_expired' => 1]);
                            if ($coupon = Coupon::where('coupon_code', $request->coupon_code)->first()) {
                                Promotion::where('id', $coupon->promotion_id)->update(['status' => 0]);
                            }
                        }
                    }
                }
            }
        }

        // Create booking services
        if (!empty($services)) {
            $this->updateBookingService($services, $booking->id);
        }

        $message = 'New ' . Str::singular($this->module_title) . ' Added';

        try {
            $type = 'new_booking';
            $messageTemplate = 'New booking #[[booking_id]] has been booked.';
            $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
            $this->sendNotificationOnBookingUpdate($type, $notify_message, $booking);
        } catch (\Exception $e) {
            \Log::error($e->getMessage());
        }

        return response()->json([
            'message' => $message,
            'status' => true,
            'booking_id' => $booking->id
        ], 200);
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        if ($user->hasRole('user')) {
            $bookingId = $request->id;
            $booking = Booking::find($bookingId);
            if (!$booking || $booking->user_id !== $user->id) {
                return response()->json([
                    'message' => __('messages.permission_denied'),
                    'status' => false
                ], 403);
            }
            // Users can only change status to 'cancelled' or 'pending' (rescheduling)
            if ($request->has('status') && !in_array($request->status, ['cancelled', 'pending'])) {
                return response()->json([
                    'message' => __('messages.permission_denied'),
                    'status' => false
                ], 403);
            }
            // Prevent updates if booking is already final
            if (in_array($booking->status, ['checkout', 'completed'])) {
                return response()->json([
                    'message' => "Cannot update a booking with status: {$booking->status}",
                    'status' => false
                ], 422);
            }
        } elseif (!$user->hasRole('admin') && !$user->can('edit_booking')) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }

        $booking = Booking::findOrFail($request->id);

        if ($request->has('status') && $request->status == 'cancelled') {
            if (!in_array($booking->status, ['check_in', 'checkout', 'completed'])) {
                $booking->update(['status' => 'cancelled']);
            } else {
                return response()->json(['message' => "Cannot cancel a booking with status: {$booking->status}"], 422);
            }
        }
 else {

            $booking->update($request->all());

            if (!empty($request->packages)) {

                $this->updateAPIBookingPackage($request->packages, $booking->id, $request->employee_id);
            }
            if (!empty($request->services)) {
                $this->updateBookingService($request->services, $booking->id);
            }
        }

        $message = __('booking.booking_update');

        return response()->json(['message' => $message, 'status' => true], 200);
    }

    public function updateStatus(Request $request)
    {
        $user = auth()->user();
        $id = $request->id;
        $booking = Booking::find($id);

        if ($user->hasRole('user')) {
            if (!$booking || $booking->user_id !== $user->id) {
                return response()->json([
                    'message' => __('messages.permission_denied'),
                    'status' => false
                ], 403);
            }
            $requestedStatus = $request->status;
            if (isset($request->action_type) && $request->action_type == 'update-status') {
                $requestedStatus = $request->value;
            }
            if ($requestedStatus && !in_array($requestedStatus, ['cancelled', 'pending'])) {
                return response()->json([
                    'message' => __('messages.permission_denied'),
                    'status' => false
                ], 403);
            }
            // Prevent updates if booking is already final
            if (in_array($booking->status, ['checkout', 'completed'])) {
                return response()->json([
                    'message' => "Cannot update a booking with status: {$booking->status}",
                    'status' => false
                ], 422);
            }
        } elseif (!$user->hasRole('admin') && !$user->can('edit_booking')) {
            return response()->json([
                'message' => __('messages.permission_denied'),
                'status' => false
            ], 403);
        }

        if (!$booking) {
            return response()->json(['message' => __('booking.booking_not_found'), 'status' => false], 404);
        }
        $booking->load('services', 'user', 'products');
        $status = $request->status;

        if (isset($request->action_type) && $request->action_type == 'update-status') {
            $status = $request->value;
        }

        $booking->update(['status' => $status]);

        $notify_type = null;

        switch ($status) {
            case 'check_in':
                $notify_type = 'check_in_booking';
                break;
            case 'checkout':
                $notify_type = 'checkout_booking';
                break;
            case 'completed':
                $notify_type = 'complete_booking';
                break;
            case 'cancelled':
                $notify_type = 'cancel_booking';
                break;
        }

        if (isset($notify_type)) {
            try {
                $this->sendNotificationOnBookingUpdate($notify_type, '', $booking);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }
        }

        $message = __('booking.status_update');

        return response()->json(['data' => new BookingResource($booking), 'message' => $message, 'status' => true]);
    }

    public function bookingList(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $booking = Booking::with(
            'booking_service',
            'bookingTransaction',
            'bookingPackages.bookedPackageService'
        );

        if ($user->hasRole('user')) {
            $booking->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $branchIds = Branch::where('manager_id', $user->id)->pluck('id');
            $booking->whereIn('branch_id', $branchIds);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized role'
            ], 403);
        }

        // Filter by date if provided (format: dd-mm-yyyy)
        if ($request->has('date') && !empty($request->date)) {
            try {
                $parsedDate = Carbon::createFromFormat('d-m-Y', $request->date);
                $formattedDate = $parsedDate->format('Y-m-d');
                $booking->whereDate('start_date_time', $formattedDate);
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid date format. Expected format: dd-mm-yyyy. Error: ' . $e->getMessage()
                ], 400);
            }
        }

        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $booking->where('branch_id', $request->branch_id);
        }

        // Filter by staff/employee_id
        if ($request->has('staff_id') && !empty($request->staff_id)) {
            $booking->whereHas('booking_service', function ($query) use ($request) {
                $query->where('employee_id', $request->staff_id);
            });
        }

        // Filter by customer/user_id
        if ($request->has('customer_id') && !empty($request->customer_id)) {
            $booking->where('user_id', $request->customer_id);
        }

        // Filter by service_id
        if ($request->has('service_id') && !empty($request->service_id)) {
            $booking->whereHas('booking_service', function ($query) use ($request) {
                $query->where('service_id', $request->service_id);
            });
        }

        // Filter by status
        if ($request->has('status') && !empty($request->status)) {
            $status = $request->status;
            if ($status === 'upcoming') {
                $booking->where('start_date_time', '>', Carbon::now());
                $booking->whereNotIn('status', ['cancelled', 'rejected', 'completed', 'checkout']);
            } elseif ($status === 'past') {
                $booking->where('start_date_time', '<=', Carbon::now());
            } else {
                 if (is_array($status)) {
                    $booking->whereIn('status', $status);
                 } else {
                    $booking->where('status', $status);
                 }
            }
        }

        // Filter by user_id (alias for customer_id)
        if ($request->has('user_id') && !empty($request->user_id)) {
            $booking->where('user_id', $request->user_id);
        }

        $per_page = $request->input('per_page', 10);
        if ($request->has('per_page') && !empty($request->per_page)) {
            if (is_numeric($request->per_page)) {
                $per_page = $request->per_page;
            }
            if ($request->per_page === 'all') {
                $per_page = $booking->count();
            }
        }
        $orderBy = 'desc';
        if ($request->has('order_by') && !empty($request->order_by)) {
            $orderBy = $request->order_by;
        }
        // Apply search conditions for booking ID, employee name, and service name
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $booking->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('services', function ($subquery) use ($search) {
                        $subquery->whereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery->where(function ($nameQuery) use ($search) {
                                $nameQuery->where('first_name', 'LIKE', "%$search%")
                                    ->orWhere('last_name', 'LIKE', "%$search%");
                            });
                        });
                    })
                    ->orWhereHas('services', function ($subquery) use ($search) {
                        $subquery->whereHas('service', function ($employeeQuery) use ($search) {
                            $employeeQuery->where('name', 'LIKE', "%$search%");
                        });
                    })
                    ->orWhereHas('services.service.category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'LIKE', "%$search%");
                    });
            });
        }

        $booking = $booking->orderBy('updated_at', $orderBy)->paginate($per_page);

        $items = BookingListResource::collection($booking);

        // Build query for status counts (similar to dashboard-booking API)
        $statusCountsQuery = Booking::query();

        // Apply same role-based filters
        if ($user->hasRole('user')) {
            $statusCountsQuery->where('user_id', $user->id);
        } elseif ($user->hasRole('manager')) {
            $branchIds = Branch::where('manager_id', $user->id)->pluck('id');
            $statusCountsQuery->whereIn('branch_id', $branchIds);
        }

        // Apply same date filter if provided
        if ($request->has('date') && !empty($request->date)) {
            try {
                $parsedDate = Carbon::createFromFormat('d-m-Y', $request->date);
                $formattedDate = $parsedDate->format('Y-m-d');
                $statusCountsQuery->whereDate('start_date_time', $formattedDate);
            } catch (\Exception $e) {
                // Date already validated above, so this shouldn't happen
            }
        }

        // Apply same branch_id filter if provided
        if ($request->has('branch_id') && !empty($request->branch_id)) {
            $statusCountsQuery->where('branch_id', $request->branch_id);
        }

        // Apply same staff_id filter if provided
        if ($request->has('staff_id') && !empty($request->staff_id)) {
            $statusCountsQuery->whereHas('booking_service', function ($query) use ($request) {
                $query->where('employee_id', $request->staff_id);
            });
        }

        // Apply same customer_id filter if provided
        if ($request->has('customer_id') && !empty($request->customer_id)) {
            $statusCountsQuery->where('user_id', $request->customer_id);
        }

        // Apply same service_id filter if provided
        if ($request->has('service_id') && !empty($request->service_id)) {
            $statusCountsQuery->whereHas('booking_service', function ($query) use ($request) {
                $query->where('service_id', $request->service_id);
            });
        }

        // Get status counts
        $statusCounts = $statusCountsQuery->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                // Normalize status to lowercase for consistent checking
                return [strtolower($item->status) => $item->count];
            })
            ->toArray();


        return response()->json([
            'status' => true,
            'data' => $items,
            'message' => __('booking.booking_list'),
        ], 200);
    }

    public function bookingDetail(Request $request)
    {
        $id = $request->id;

        $booking_data = Booking::with(['branch', 'user', 'booking_service', 'payment', 'products', 'bookingPackages', 'media'])->where('id', $id)->first();

        if ($booking_data == null) {
            $message = __('booking.booking_not_found');

            return response()->json([
                'status' => false,
                'message' => __('booking.booking_not_found'),
            ], 200);
        }



        $booking_detail = new BookingDetailResource($booking_data);


        return response()->json([
            'status' => true,
            'data' => $booking_detail,
            'message' => __('booking.booking_detail'),
        ], 200);
    }

    public function searchBookings(Request $request)
    {
        $keyword = $request->input('keyword');

        $bookings = Booking::where('note', 'like', "%{$keyword}%")
            ->with('branch', 'user')
            ->get();

        return response()->json([
            'status' => true,
            'data' => BookingResource::collection($bookings),
            'message' => __('booking.search_booking'),
        ], 200);
    }

    public function statusList()
    {
        $booking_status = Constant::getAllConstant()->where('type', 'BOOKING_STATUS');
        $checkout_sequence = $booking_status->where('name', 'check_in')->first()->sequence ?? 0;
        $bookingColors = Constant::getAllConstant()->where('type', 'BOOKING_STATUS_COLOR');
        $statusList = [];
        $finalstatusList = [];

        foreach ($booking_status as $key => $value) {
            if ($value->name !== 'cancelled') {
                $statusList = [
                    'status' => $value->name,
                    'title' => $value->value,
                    'color_hex' => $bookingColors->where('sub_type', $value->name)->first()->name,
                    'is_disabled' => $value->sequence >= $checkout_sequence,
                ];
                array_push($finalstatusList, $statusList);
                $nextStatus = $booking_status->where('sequence', $value->sequence + 1)->first();
                if ($nextStatus) {
                    $statusList[$value->name]['next_status'] = $nextStatus->name;
                }
            } else {
                $statusList = [
                    'status' => $value->name,
                    'title' => $value->value,
                    'color_hex' => $bookingColors->where('sub_type', $value->name)->first()->name,
                    'is_disabled' => true,
                ];
                array_push($finalstatusList, $statusList);
            }
        }

        return response()->json([
            'status' => true,
            'data' => $finalstatusList,
            'message' => __('booking.booking_status_list'),
        ], 200);
    }

    public function bookingUpdate(Request $request)
    {
        $data = $request->all();
        $id = $request->id;

        if (!empty($request->date)) {
            $data['start_date_time'] = $request->date;
        }
        $bookingdata = Booking::find($id);

        $bookingdata->update($data);

        $booking = Booking::findOrFail($id);

        $booking->update($data);

        $bookingService = BookingService::where('booking_id', $booking->id)->get();

        if (!empty($request->packages)) {

            $this->updateAPIBookingPackage($request->packages, $booking->id, $request->employee_id);
        }
        $this->updateBookingService($bookingService, $booking->id);

        if (!empty($request->packages)) {
            $this->updateBookingPackage($request->packages, $booking->id);
        }

        return response()->json([
            'status' => true,
            'message' => __('booking.booking_update'),
        ], 200);
    }

    /**
     * Get booking creation data (customers, services, staff)
     * Endpoint: booking-creation-data?branch_id=4
     */
    public function bookingCreationData(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $branchId = $request->input('branch_id');
        $staffId = $request->input('staff_id');

        // Get customer list (users with 'user' role)
        $customers = User::role('user')
            ->where('status', 1)
            ->select('id', 'first_name', 'last_name', 'avatar')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $customerListData = $customers->map(function ($customer) {
            $fullName = $customer->first_name . ' ' . $customer->last_name;
            return [
                'customer_id' => $customer->id,
                'customer_name' => $fullName,
                'customer_image' => $customer->profile_image ?? $customer->avatar ?? default_user_avatar(),
            ];
        })->values()->toArray();

        // Get service list
        $services = Service::where('status', 1)
            ->select('id', 'name', 'default_price');

        // Filter services by branch_id if provided
        if ($branchId) {
            $services = $services->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }

        // Filter services by staff_id if provided - show only services assigned to that staff
        if ($staffId) {
            $serviceIds = ServiceEmployee::where('employee_id', $staffId)
                ->pluck('service_id')
                ->toArray();

            if (!empty($serviceIds)) {
                $services = $services->whereIn('id', $serviceIds);
            } else {
                // If staff has no assigned services, return empty list
                $services = $services->whereRaw('1 = 0');
            }
        }

        $services = $services->orderBy('name')
            ->get();

        $serviceList = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'service_price' => $service->default_price,
                'image' => $service->feature_image,
            ];
        })->values()->toArray();

        // Get staff list (users with 'employee' role)
        $staff = User::role('employee')
            ->where('status', 1)
            ->where('is_manager', 0)
            ->select('id', 'first_name', 'last_name', 'avatar');

        // Filter staff by branch_id if provided
        if ($branchId) {
            $staff = $staff->whereHas('branches', function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            });
        }

        $staff = $staff->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $staffList = $staff->map(function ($employee) {
            $fullName = $employee->first_name . ' ' . $employee->last_name;
            return [
                'staff_id' => $employee->id,
                'staff_name' => $fullName,
                'staff_image' => $employee->profile_image
                ?? default_user_avatar(),
            ];
        })->values()->toArray();

        return response()->json([
            'status' => true,
            'message' => 'Booking creation data retrieved successfully',
            'data' => [
                'customer_list_data' => $customerListData,
                'service_list' => $serviceList,
                'staff_list' => $staffList,
            ],
        ], 200);
    }

    /**
     * Get available booking slots for a staff member on a specific date
     * Endpoint: get-booking-slot?staff_id=1&date=2026-01-12&branch_id=4
     */
    public function getBookingSlot(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Validate required parameters
        if (!$request->has('staff_id') || empty($request->staff_id)) {
            return response()->json([
                'status' => false,
                'message' => 'staff_id is required'
            ], 400);
        }

        if (!$request->has('date') || empty($request->date)) {
            return response()->json([
                'status' => false,
                'message' => 'date is required'
            ], 400);
        }

        if (!$request->has('branch_id') || empty($request->branch_id)) {
            return response()->json([
                'status' => false,
                'message' => 'branch_id is required'
            ], 400);
        }

        $employeeId = (int) $request->staff_id;
        $date = $request->date;
        $branchId = (int) $request->branch_id;
        $serviceDuration = (int) ($request->service_duration ?? 0);

        // Get day name from date (e.g., "Monday", "Tuesday", etc.)
        $day = date('l', strtotime($date));

        // Get available slots using the existing getSlots method from BookingTrait
        $slots = $this->getSlots($date, $day, $branchId, $serviceDuration, $employeeId);

        // Filter out past slots for today
        $today = date('Y-m-d');
        if ($date === $today) {
            $now = time();
            $slots = array_filter($slots, function ($slot) use ($now) {
                if (empty($slot['value']) || $slot['disabled']) {
                    return false;
                }
                $slotTimestamp = strtotime($slot['value']);
                return $slotTimestamp > $now;
            });
            // Re-index array after filtering
            $slots = array_values($slots);
        }

        // Remove "No Slot Available" entry if there are actual slots
        if (count($slots) > 1 && isset($slots[0]['label']) && $slots[0]['label'] === 'No Slot Available') {
            array_shift($slots);
        }

        // If no slots available, return a message
        if (empty($slots)) {
            return response()->json([
                'status' => true,
                'message' => 'No available slots for the selected date and staff',
                'data' => []
            ], 200);
        }

        return response()->json([
            'status' => true,
            'message' => 'Available slots retrieved successfully',
            'data' => $slots
        ], 200);
    }

    /**
     * Calculate booking price with services, coupon, and taxes
     * Endpoint: calculate-booking-price
     */
    public function calculateBookingPrice(Request $request, $booking_id = null)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $bookingId = $booking_id ?? $request->input('booking_id');
        $branchId = null;
        $serviceIds = [];

        if (!empty($bookingId)) {
            $booking = Booking::with(['booking_service', 'userCouponRedeem'])->find($bookingId);

            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            $branchId = (int) ($booking->branch_id ?? 0);
            $serviceIds = $booking->booking_service
                ? $booking->booking_service->pluck('service_id')->filter()->values()->toArray()
                : [];
        } else {
            if (!$request->has('branch_id') || empty($request->branch_id)) {
                return response()->json([
                    'status' => false,
                    'message' => 'branch_id is required'
                ], 400);
            }

            if (!$request->has('service_ids') || empty($request->service_ids)) {
                return response()->json([
                    'status' => false,
                    'message' => 'service_ids is required'
                ], 400);
            }

            $branchId = (int) $request->branch_id;
            $serviceIds = is_array($request->service_ids) ? $request->service_ids : [$request->service_ids];
        }

        if (empty($branchId)) {
            return response()->json([
                'status' => false,
                'message' => 'branch_id is required'
            ], 400);
        }

        if (empty($serviceIds)) {
            return response()->json([
                'status' => false,
                'message' => 'service_ids is required'
            ], 400);
        }

        $couponCode = $request->coupon_code
            ?? (!empty($bookingId) ? (optional(optional($booking)->userCouponRedeem)->coupon_code ?? null) : null);

        // Get services with branch-specific pricing
        $services = Service::whereIn('id', $serviceIds)
            ->where('status', 1)
            ->get();

        if ($services->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No valid services found'
            ], 400);
        }

        // Calculate service prices
        $serviceList = [];
        $originalTotal = 0;
        $discountedTotal = 0;

        foreach ($services as $service) {
            // Get branch-specific price or fallback to default price
            $branchService = ServiceBranches::where('service_id', $service->id)
                ->where('branch_id', $branchId)
                ->first();

            // Use branch-specific price if available and greater than 0, otherwise use default
            $originalPrice = ($branchService && isset($branchService->service_price) && $branchService->service_price > 0)
                ? $branchService->service_price
                : ($service->default_price ?? 0);

            $serviceList[] = [
                'id' => $service->id,
                'name' => $service->name,
                'original_price' => round($originalPrice, 2),
                'discounted_price' => round($originalPrice, 2), // No service-level discount for now
                'quantity' => 1
            ];

            $originalTotal += $originalPrice;
            $discountedTotal += $originalPrice;
        }

        // Build service display name
        $serviceNames = collect($serviceList)->pluck('name')->toArray();
        $displayName = count($serviceNames) > 1
            ? implode(' + ', $serviceNames)
            : ($serviceNames[0] ?? '');

        // Apply coupon discount if provided
        $couponDiscount = 0;
        $couponData = [
            'applied' => false,
            'code' => null,
            'discount_type' => null,
            'discount_percentage' => 0,
            'discount_amount' => 0.00,
            'description' => null
        ];

        if ($couponCode) {
            $coupon = Coupon::where('coupon_code', $couponCode)
                ->where('is_expired', '!=', 1)
                ->where('end_date_time', '>=', now())
                ->whereHas('promotion', function ($query) {
                    $query->where('status', '!=', 0);
                })
                ->first();

            if ($coupon) {
                // Check use limit
                $redemptionsCount = UserCouponRedeem::where('coupon_id', $coupon->id)->count();
                $useLimitReached = $coupon->use_limit && $redemptionsCount >= $coupon->use_limit;

                if (!$useLimitReached) {
                    // Calculate discount - check for both 'percentage' and 'percent' for compatibility
                    $discountType = strtolower($coupon->discount_type ?? '');

                    if ($discountType == 'percentage' || $discountType == 'percent') {
                        $discountPercentage = (float) ($coupon->discount_percentage ?? 0);
                        $couponDiscount = ($discountedTotal * $discountPercentage) / 100;
                    } elseif ($discountType == 'fixed') {
                        $couponDiscount = (float) ($coupon->discount_amount ?? 0);
                    } else {
                        $couponDiscount = 0;
                    }

                    // Ensure discount doesn't exceed total
                    if ($couponDiscount > $discountedTotal) {
                        $couponDiscount = $discountedTotal;
                    }

                    // Ensure discount is not negative
                    if ($couponDiscount < 0) {
                        $couponDiscount = 0;
                    }

                    $discountTypeLower = strtolower($coupon->discount_type ?? '');
                    $discountPercentageValue = (float) ($coupon->discount_percentage ?? 0);

                    $couponData = [
                        'applied' => true,
                        'code' => $coupon->coupon_code,
                        'discount_type' => $discountTypeLower,
                        'discount_percentage' => $discountPercentageValue,
                        'discount_amount' => round($couponDiscount, 2),
                        'description' => ($discountTypeLower == 'percentage' || $discountTypeLower == 'percent')
                            ? "Coupon Discount ({$discountPercentageValue}%)"
                            : "Coupon Discount"
                    ];
                }
            }
        }

        // Calculate subtotal (after coupon discount)
        $subtotal = $discountedTotal - $couponDiscount;

        // Get taxes
        $taxes = Tax::where('status', 1)
            ->where(function ($query) {
                $query->whereNull('module_type')
                    ->orWhere('module_type', 'services');
            })
            ->get();

        $taxBreakdown = [];
        $totalTax = 0;

        foreach ($taxes as $tax) {
            $taxAmount = 0;
            if ($tax->type == 'percent') {
                $taxAmount = ($subtotal * $tax->value) / 100;
            } elseif ($tax->type == 'fixed') {
                $taxAmount = $tax->value;
            }

            $taxBreakdown[] = [
                'id' => $tax->id,
                'name' => $tax->title,
                'type' => $tax->type,
                'percentage' => $tax->type == 'percent' ? $tax->value : 0,
                'amount' => round($taxAmount, 2),
                'description' => $tax->type == 'percent'
                    ? "{$tax->title} ({$tax->value}%)"
                    : $tax->title
            ];

            $totalTax += $taxAmount;
        }

        // Calculate total
        $total = $subtotal + $totalTax;

        // Get currency information
        $currency = Currency::where('is_primary', 1)->first();
        $currencyCode = $currency ? $currency->currency_code : 'USD';
        $currencySymbol = $currency ? $currency->currency_symbol : '$';

        return response()->json([
            'status' => true,
            'message' => 'Booking price calculated successfully',
            'data' => [
                'booking_id' => $bookingId ? (int) $bookingId : null,
                'services' => $serviceList,
                'service_total' => [
                    'original_total' => round($originalTotal, 2),
                    'discounted_total' => round($discountedTotal, 2),
                    'display_name' => $displayName
                ],
                'coupon' => $couponData,
                'subtotal' => round($subtotal, 2),
                'tax' => [
                    'total' => round($totalTax, 2),
                    'breakdown' => $taxBreakdown
                ],
                'total' => round($total, 2),
            ]
        ], 200);
    }
}
