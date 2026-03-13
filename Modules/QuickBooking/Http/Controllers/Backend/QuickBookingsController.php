<?php

namespace Modules\QuickBooking\Http\Controllers\Backend;

use App\Events\Backend\UserCreated;
use App\Http\Controllers\Controller;
use App\Models\Address;
// Traits
use App\Models\Branch;
use Modules\BussinessHour\Models\BussinessHour;
use Modules\Holiday\Models\Holiday;
// Listing Models
use App\Models\User;
use App\Notifications\UserAccountCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Booking\Models\Booking;
// Events
use Modules\Booking\Trait\BookingTrait;
use Modules\Booking\Trait\PaymentTrait;
use Modules\Service\Transformers\ServiceResource;
use Modules\Tax\Models\Tax;
use Carbon\Carbon;
use Modules\Service\Models\Service;
use Modules\Commission\Models\CommissionEarning;
use Modules\Tip\Models\TipEarning;

class QuickBookingsController extends Controller
{
    use BookingTrait, PaymentTrait;

    public function index()
    {
        if (! setting('is_quick_booking')) {
            return abort(404);
        }

        return view('quickbooking::backend.quickbookings.index');
    }

    // API Methods for listing api
    public function branch_list()
    {
        $list = Branch::active()->with('address')->select('id', 'name', 'branch_for', 'contact_number', 'contact_email')->get();

        return $this->sendResponse($list, __('booking.booking_branch'));
    }

    public function slot_time_list(Request $request)
    {
        $day = date('l', strtotime($request->date));

        $data = $this->requestData($request);
        $businessHours = BussinessHour::where('branch_id', $data['branch_id'])->get();
        $service = Service::where('id', $data['service_id'])->first();
        $serviceDuration = $service->duration_min;

        $slots = $this->getSlots($data['date'], $day, $data['branch_id'], $serviceDuration, $data['employee_id']);

        return $this->sendResponse($slots, $businessHours, __('booking.booking_timeslot'));
    }


    public function slot_date_list(Request $request)
    {
        $data = $this->requestData($request);

        $businessHours = BussinessHour::where('branch_id', $data['branch_id'])->get();
        $holidays = Holiday::where('branch_id', $data['branch_id'])->get();
        $holidayDates = $holidays->map(function ($holiday) {
            return Carbon::parse($holiday->date)->format('Y-m-d');
        });

        return response()->json([
            'data' => $businessHours,
            'holidays' => $holidayDates,
        ]);
    }

    public function services_list(Request $request)
    {
        $branch_id = $request->branch_id;
        $data = $this->requestData($request);

        $item = Branch::find($data['branch_id']);
        // Load branch services with branches relationship for resolution
        $branchServices = $item->services()->with('branches')->where('status', 1)->get();

        // If employee_id is provided, gather the employee's service IDs so we can mark services
        $employeeServiceIds = [];
        if (!empty($data['employee_id'])) {
            $employee = User::find($data['employee_id']);
            if ($employee) {
                $employeeServiceIds = $employee->services()->pluck('service_id')->toArray();
            }
        }

        // Map branch services and mark whether the employee provides each service (if employee_id provided)
        $items = $branchServices->map(function ($service) use ($branch_id, $employeeServiceIds) {
            $imageUrl = $service->feature_image ?? asset('img/frontend/hair-wash-service.png');

            // Resolve branch-specific price and duration using centralized logic
            $service->resolveBranchSpecificData($branch_id);
            
            $servicePrice = $service->default_price;
            $serviceDuration = $service->duration_min;

            // Return fields matching frontend expectations
            return [
                'service_id' => $service->id,
                'service_name' => $service->name,
                'service_price' => $servicePrice,
                'provided_by_employee' => in_array($service->id, $employeeServiceIds),
                'duration_min' => $serviceDuration,
                'image_path' => $imageUrl,
                'category_id' => $service->category_id,
                'category_name' => $service->category->name ?? '',
                // keep legacy keys as well for other consumers
                'id' => $service->id,
                'name' => $service->name,
                'price' => $servicePrice,
            ];
        });

        return $this->sendResponse($items, __('booking.booking_sevice'));
    }

    public function employee_list(Request $request)
    {
        $data = $this->requestData($request);

        $list = User::whereHas('services', function ($query) use ($data) {
            $query->where('service_id', $data['service_id']);
        })
            ->whereHas('branches', function ($query) use ($data) {
                $query->where('branch_id', $data['branch_id']);
            })
            ->get();

        return $this->sendResponse($list, __('booking.booking_employee'));
    }

    // Create Method for Booking API
    public function create_booking(Request $request)
    {

        try {
            $userRequest = $request->user;
            $user = User::where('email', $userRequest['email'])->first();

            if (! isset($user)) {
                $userRequest['password'] = Hash::make('12345678');
                $user = User::create($userRequest);
                // Sync Roles
                $roles = ['user'];
                $user->syncRoles($roles);

                \Artisan::call('cache:clear');

                event(new UserCreated($user));

                $data = [
                    'password' => '12345678',
                ];

                try {
                    $user->notify(new UserAccountCreated($data));
                } catch (\Exception $e) {
                    \Log::error($e->getMessage());
                }
            }

            $bookingData = $request->booking;
            $bookingData['user_id'] = $user->id;
            $bookingData['created_by'] = $user->id;
            $bookingData['updated_by'] = $user->id;
            $booking = Booking::create($bookingData);

            $this->updateBookingService($bookingData['services'], $booking->id);

            // Fetch active taxes for services
            $taxes = Tax::active()
                ->where('status', 1)
                ->where(function ($query) {
                    $query->whereNull('module_type')
                        ->orWhere('module_type', 'services');
                })
                ->get()
                ->map(function ($tax) {
                    return [
                        'name' => $tax->title,
                        'type' => $tax->type,
                        'percent' => $tax->type == 'percent' ? $tax->value : 0,
                        'tax_amount' => $tax->type != 'percent' ? $tax->value : 0,
                    ];
                })
                ->toArray();

            // Get tip amount from request (if sent from frontend)
            $tip_amount = $request->input('tip_amount', $bookingData['tip_amount'] ?? 0);

            // Create booking_transactions record for cash payment with proper tax data
            $booking_transaction = \Modules\Booking\Models\BookingTransaction::create([
                'booking_id' => $booking->id,
                'external_transaction_id' => 'cash_txn_' . uniqid(),
                'transaction_type' => 'cash',
                'discount_percentage' => 0,
                'discount_amount' => 0,
                'tip_amount' => $tip_amount,
                'tax_percentage' => $taxes,
                'payment_status' => 0, // unpaid
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create commission and tip earnings for staff
            $earning_data = $this->commissionData($booking_transaction);

            if (isset($earning_data['commission_data'])) {
                $commission_data = $earning_data['commission_data'];
                $commission_data['employee_id'] = $earning_data['employee_id'];

                $booking->commission()->save(new CommissionEarning($earning_data['commission_data']));
            }

            // Save tip earning if tip amount exists and employee is assigned
            if (isset($earning_data['employee_id']) && $earning_data['employee_id']) {
                if ($tip_amount > 0) {
                    $booking->tip()->save(new TipEarning([
                        'employee_id' => $earning_data['employee_id'],
                        'tip_amount' => $tip_amount,
                        'tip_status' => 'unpaid',
                        'payment_date' => null,
                    ]));
                }
            }

            $booking['user'] = $booking->user;
            $booking['services'] = $booking->services;
            $booking['branch'] = $booking->branch;

            $branchAddress = Address::where('addressable_id', $booking['branch']->id)
                ->where('addressable_type', get_class($booking['branch']))
                ->with('country_data')
                ->first();


            $booking['branch_address'] = $branchAddress;

            $booking['tax'] = $taxes;

            // Send response before notification
            $response = $this->sendResponse($booking, __('booking.booking_create'));

            // Try notification, but do not fail booking if it fails
            try {
                $notify_type = 'quick_booking';
                $messageTemplate = 'New booking #[[booking_id]] has been booked.';
                $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
                $this->sendNotificationOnBookingUpdate($notify_type, $notify_message, $booking);
            } catch (\Exception $e) {
                \Log::error($e->getMessage());
            }

            return $response;
        } catch (\Exception $ex) {

            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $ex->getMessage(),
            ], 500);
        }
    }

    public function requestData($request)
    {
        return [
            'branch_id' => $request->branch_id,
            'service_id' => $request->service_id,
            'date' => $request->date,
            'employee_id' => $request->employee_id,
            'start_date_time' => $request->start_date_time,
        ];
    }
}
