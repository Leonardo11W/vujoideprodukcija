<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Midtrans\Snap;
use Midtrans\Config;
use Illuminate\Support\Str;
use Modules\Service\Models\Service;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;

class BookingPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated. Please log in.'
            ], 401);
        }

        $paymentMethod = strtolower($request->input('payment_method'));
        $price = $request->input('price'); // You may want to recalculate this on the backend for security
 
        switch ($paymentMethod) {
            case 'stripe':
                return $this->handleStripe($request, $price);
            case 'cash':
                return $this->handleCash($request, $price);
            case 'razorpay':
                return $this->handleRazorpay($request, $price);
            case 'paystack':
                return $this->handlePayStack($request, $price);
            case 'paypal':

                return $this->handlePaypal($request, $price);
            case 'flutterwave':
                return $this->handleflutterwave($request, $price);
            case 'midtrans':
                return $this->handleMidtransPayment($request, $price);
            case 'wallet':
                return $this->handleWallet($request, $price);
            default:
                return back()->withErrors('Invalid payment method.');
        }
    }

    // 1. STRIPE (REAL)
    protected function handleStripe(Request $request, $price)
    {
        $stripeSecret = setting('stripe_secretkey');
        $stripepublic = setting('stripe_publickey');
        $currency = GetcurrentCurrency();
        // or get from your settings


        if (!$stripeSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe secret key not configured.'
            ], 500);
        }

        $stripe = new \Stripe\StripeClient($stripeSecret);

        // try {
        // Ensure amount is properly converted to integer cents
        $roundedPrice = round($price, 2);
        $amount = intval($roundedPrice * 100);

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => [
                        'name' => 'Booking Payment',
                    ],
                    'unit_amount' => $amount,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('booking.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => url()->previous(),
            'metadata' => [
                'employee_id' => $request->input('employee_id'),
                'branch_id' => $request->input('branch_id'),
                'date' => $request->input('date'),
                'time' => $request->input('time'),
                'services' => json_encode($request->input('services')),
                'coupon_code' => $request->input('coupon_code'),
                'tax_percentage' => json_encode($request->input('tax_percentage')),
                'tip' => $request->input('tip', 0),
                'discount_amount' => $request->input('discount_amount'),
                'discount_percentage' => $request->input('discount_percentage'),
            ],
        ]);


        return response()->json([
            'success' => true,
            'redirect' => $session->url
        ]);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Stripe error: ' . $e->getMessage()
        //     ], 500);
        // }
    }

    protected function handleMidtransPayment(Request $request, $price)
    {

        $serverKey = setting('midtrans_serverkey');
        $isProduction = false;
        $midtransUrl = $isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $amount = $price;
        $transactionId = 'BOOK-' . strtoupper(Str::random(10));

        // Metadata
        $metadata = [
            'employee_id' => $request->input('employee_id'),
            'branch_id' => $request->input('branch_id'),
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'services' => $request->input('services'),
            'coupon_code' => $request->input('coupon_code'),
            'tax_percentage' => $request->input('tax_percentage'),
            'tip' => $request->input('tip', 0),
            'discount_amount' => $request->input('discount_amount'),
            'discount_percentage' => $request->input('discount_percentage'),
        ];

        // Request payload
        $payload = [
            'transaction_details' => [
                'order_id' => $transactionId,
                'gross_amount' => $amount
            ],
            'customer_details' => [
                'email' =>  auth()->user()->email ?? 'customer@example.com',
                'first_name' =>  auth()->user()->first_name ?? 'Customer'
            ],
            'callbacks' => [
                'finish' => route('booking.midtrans.success')
            ],
            'custom_fields' => [
                'custom_field1' => json_encode($metadata)
            ]
        ];

        // Generate Snap Token
        $response = Http::withBasicAuth($serverKey, '')
            ->post($midtransUrl, $payload);

        if ($response->failed()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Midtrans API error: ' . $response->body()
            ]);
        }

        return response()->json([
            'status' => 'success',
            'snap_token' => $response['token'],
            'success_url' => route('booking.midtrans.success')
        ]);
    }





    protected function handlePayStack(Request $request)
    {
        $paystackSecretKey = setting('paystack_secretkey');
        $price = $request->input('price');
        $priceInKobo = $price * 100; // Paystack expects amount in kobo (NGN)

        $currency = GetcurrentCurrency();
        $formattedCurrency = strtoupper(strtolower($currency));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $paystackSecretKey,
        ])->post('https://api.paystack.co/transaction/initialize', [
            'email' => auth()->user()->email,
            'amount' => $priceInKobo,
            'currency' => $formattedCurrency,
            'callback_url' => route('booking.paystack.success'),

            'metadata' => [
                'employee_id' => $request->input('employee_id'),
                'branch_id' => $request->input('branch_id'),
                'date' => $request->input('date'),
                'time' => $request->input('time'),
                'services' => json_encode($request->input('services')),
                'coupon_code' => $request->input('coupon_code'),
                'tax_percentage' => json_encode($request->input('tax_percentage')),
                'tip' => $request->input('tip', 0),
                'discount_amount' => $request->input('discount_amount'),
                'discount_percentage' => $request->input('discount_percentage'),
            ],
        ]);

        $responseBody = $response->json();

        if ($responseBody['status']) {
            // Optional: log or store reference


            return response()->json([
                'success' => true,
                'redirect' => $responseBody['data']['authorization_url'],
                'reference' => $responseBody['data']['reference'], // Optional
            ]);
        } else {
            $message = $responseBody['message'] ?? 'Something went wrong, please try a different payment method.';

            return response()->json(['error' => $message], 400);
        }
    }



    protected function handleFlutterwave(Request $request)
    {
        try {
            $flutterwaveKey = setting('flutterwave_publickey');
            $price = $request->input('price');
            $currency = GetcurrentCurrency(); // e.g., "NGN"
            $formattedCurrency = strtoupper(strtolower($currency));
            $baseURL = url('/'); // << You missed this earlier
            $logo = ''; // Optional: your company logo URL

            // Generate unique transaction reference
            $tx_ref = 'FLW-' . uniqid() . '-' . time();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'public_key' => $flutterwaveKey,
                    'tx_ref' => $tx_ref,
                    'amount' => $price,
                    'currency' => $formattedCurrency,
                    'country' => 'NG',
                    'payment_options' => 'card',
                    'customer' => [
                        'email' => auth()->user()->email,
                        'name' => auth()->user()->name ?? 'Customer',
                        'phonenumber' => auth()->user()->phone ?? ''
                    ],
                    'meta' => [
                        'employee_id' => $request->input('employee_id'),
                        'branch_id' => $request->input('branch_id'),
                        'date' => $request->input('date'),
                        'time' => $request->input('time'),
                        'services' => json_encode($request->input('services')),
                        'coupon_code' => $request->input('coupon_code'),
                        'tax_percentage' => json_encode($request->input('tax_percentage')),
                        'tip' => $request->input('tip', 0),
                        'discount_amount' => $request->input('discount_amount'),
                        'discount_percentage' => $request->input('discount_percentage'),
                    ],
                    'customizations' => [
                        'title' => config('app.name', 'Booking Payment'),
                        'description' => 'Payment for Plan Booking',
                        'logo' => $logo
                    ],
                    'redirect_url' =>  route('booking.flutterwave.success'),
                ]
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'status' => 'error',
                'message' => 'Payment initialization failed: ' . $e->getMessage()
            ], 400);
        }
    }

    protected function handlePaypal(Request $request)
    {
        $clientId = setting('paypal_clientid');
        $clientSecret = setting('paypal_secretkey');

        $amount = $request->input('price'); // You should calculate from $request data
        $currency = GetcurrentCurrency();
        $formattedCurrency = strtoupper(strtolower($currency));


        // Prepare metadata
        $metadata = [
            'employee_id' => $request->input('employee_id'),
            'branch_id' => $request->input('branch_id'),
            'date' => $request->input('date'),
            'time' => $request->input('time'),
            'services' => $request->input('services'),
            'coupon_code' => $request->input('coupon_code'),
            'tax_percentage' => $request->input('tax_percentage'),
            'tip' => $request->input('tip', 0),
            'discount_amount' => $request->input('discount_amount'),
            'discount_percentage' => $request->input('discount_percentage'),
        ];

        // Encode metadata in base64 (max 127 characters)
        $encodedMetadata = base64_encode(json_encode($metadata));

        // Get PayPal access token
        $auth = base64_encode("$clientId:$clientSecret");
        $tokenRes = Http::withHeaders([
            'Authorization' => "Basic $auth",
        ])->asForm()->post('https://api-m.paypal.com/v1/oauth2/token', [
            'grant_type' => 'client_credentials'
        ]);

        $accessToken = $tokenRes['access_token'] ?? null;
        if (!$accessToken) {
            return response()->json(['error' => 'Unable to authenticate PayPal']);
        }

        // Create PayPal Order
        $orderRes = Http::withToken($accessToken)->post('https://api-m.paypal.com/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $formattedCurrency,
                    'value' => number_format($amount, 2, '.', '')
                ],
                'custom_id' => $encodedMetadata,
                'description' => 'Booking Payment'
            ]]
        ]);

        if ($orderRes->failed()) {
            return response()->json(['error' => 'Failed to create PayPal order.']);
        }

        return response()->json([
            'order_id' => $orderRes['id'],
            'client_id' => $clientId,
            'success_url' => route('bookings') // Or dynamic redirect URL
        ]);
    }


    // 2. CASH (REAL)
    protected function handleCash(Request $request, $price)
    {
        try {
            // Build services array as expected by BookingTrait (service_id, service_price, employee_id, start_date_time)
            $employeeId = $request->input('employee_id');
            $services = [];
            $date = $request->input('date');
            $time = $request->input('time');
            if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
                $date = $dateObj->format('d/m/Y');
            }
            if ($time && preg_match('/^\d{2}(:\d{2}){1,2}$/', $time)) {
                $timeObj = \Carbon\Carbon::createFromFormat(strlen($time) === 8 ? 'H:i:s' : 'H:i', $time);
                $time = $timeObj->format('H:i');
            }
            $startDateTime = null;
            if ($date && $time) {
                try {
                    $startDateTime = parseDmyDateTimeString($date, $time)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $startDateTime = null;
                }
            }
            if (is_array($request->input('services'))) {
                foreach ($request->input('services') as $service) {
                    $serviceId = is_array($service) ? ($service['id'] ?? $service[0]) : $service;
                    $serviceModel = \Modules\Service\Models\Service::find($serviceId);
                    if ($serviceModel) {
                        $services[] = [
                            'service_id' => $serviceModel->id,
                            'service_price' => $serviceModel->default_price,
                            'employee_id' => $employeeId,
                            'start_date_time' => $startDateTime,
                        ];
                    }
                }
            } else {
                $serviceIds = is_string($request->input('services')) ? explode(',', $request->input('services')) : [$request->input('services')];
                foreach ($serviceIds as $serviceId) {
                    $serviceModel = \Modules\Service\Models\Service::find($serviceId);
                    if ($serviceModel) {
                        $services[] = [
                            'service_id' => $serviceModel->id,
                            'service_price' => $serviceModel->default_price,
                            'employee_id' => $employeeId,
                            'start_date_time' => $startDateTime,
                        ];
                    }
                }
            }
            $bookingData = [
                'employee_id' => $employeeId,
                'branch_id' => $request->input('branch_id'),
                'date' => $date,
                'time' => $time,
                'services' => $services,
                'coupon_code' => $request->input('coupon_code'),
                'couponDiscountamount' => $request->input('couponDiscountamount'),
            ];

            $bookingsController = app(\Modules\Booking\Http\Controllers\Backend\API\BookingsController::class);
            $apiRequest = new \Illuminate\Http\Request($bookingData);
            $response = $bookingsController->store($apiRequest);
            if (method_exists($response, 'getData')) {
                $bookingJson = json_decode(json_encode($response->getData()), true);
            } else {
                $bookingJson = is_array($response) ? $response : [];
            }

            if (!($bookingJson['status'] ?? false)) {

                return response()->json([
                    'success' => false,
                    'message' => $bookingJson['message'] ?? 'Booking failed.'
                ], 400);
            }

            $bookingId = $bookingJson['booking_id'] ?? null;

            // 2. Save payment
            $paymentSaveData = [
                'booking_id' => $bookingId,
                'payment_method' => 'cash',
                'tax_percentage' => $request->input('tax_percentage'),
                'tip' => $request->input('tip', 0),
                'coupon_code' => $request->input('coupon_code'),
                'discount_amount' => $request->input('discount_amount'),
                'discount_percentage' => $request->input('discount_percentage'),
                'transaction_type' => 'cash',
                'payment_status' => 0, // Set to 0 (unpaid) for cash bookings
                'external_transaction_id' => 'cash_txn_' . uniqid(),
            ];
            // Use only the direct controller call for saving payment
            $paymentController = app(\Modules\Booking\Http\Controllers\Backend\API\PaymentController::class);
            $apiPaymentRequest = new \Illuminate\Http\Request($paymentSaveData);
            $paymentResponse = $paymentController->savePayment($apiPaymentRequest);
            if (method_exists($paymentResponse, 'getData')) {
                $paymentSaveJson = json_decode(json_encode($paymentResponse->getData()), true);
            } else {
                $paymentSaveJson = is_array($paymentResponse) ? $paymentResponse : [];
            }

            if (!($paymentSaveJson['status'] ?? false)) {

                return response()->json([
                    'success' => false,
                    'message' => $paymentSaveJson['message'] ?? 'Payment save failed.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Booking and payment successful!',
                'booking_id' => $bookingId,
                'payment_id' => $paymentSaveJson['payment_id'] ?? null
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }

    // RAZORPAY (NEW)
    protected function handleRazorpay(Request $request, $price)
    {
        $razorpayKey = setting('razorpay_publickey');
        $razorpaySecret = setting('razorpay_secretkey');
        $currency = GetcurrentCurrency();
        $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'SGD', 'AED'];
        $formattedCurrency = strtoupper($currency);

        try {
            if (!in_array($formattedCurrency, $supportedCurrencies)) {
                $formattedCurrency = 'INR';
            }

            // Ensure amount is properly converted to integer paise
            // First round to 2 decimal places to avoid floating point issues
            $roundedPrice = round($price, 2);
            // Then convert to paise and ensure it's an integer
            $amount = intval($roundedPrice * 100);

            // Validate amount is positive
            if ($amount <= 0) {
                return response()->json([
                    'error' => 'Invalid amount. Amount must be greater than 0.'
                ], 400);
            }

            // 1. Create Razorpay Order
            $orderData = [
                'receipt'         => 'booking_' . uniqid(),
                'amount'          => $amount,
                'currency'        => $formattedCurrency,
                'payment_capture' => 1 // auto-capture
            ];

            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
            $order = $api->order->create($orderData);

            // 2. Return config for Razorpay modal
            return response()->json([
                'key' => $razorpayKey,
                'amount' => $amount,
                'currency' => $formattedCurrency,
                'name' => config('app.name'),
                'description' => 'Booking Payment',
                'order_id' => $order['id'],
                'success_url' => route('booking.razorpay.success', [
                    'employee_id' => $request->input('employee_id'),
                    'branch_id' => $request->input('branch_id'),
                    'date' => $request->input('date'),
                    'time' => $request->input('time'),
                    'services' => json_encode($request->input('services')),
                    'coupon_code' => $request->input('coupon_code'),
                    'tax_percentage' => json_encode($request->input('tax_percentage')),
                    'tip' => $request->input('tip', 0),
                    'discount_amount' => $request->input('discount_amount'),
                    'discount_percentage' => $request->input('discount_percentage')
                ]),
                'prefill' => [
                    'name' => auth()->user()->name ?? '',
                    'email' => auth()->user()->email ?? '',
                    'contact' => auth()->user()->phone ?? ''
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Razorpay error: ' . $e->getMessage()
            ], 400);
        }
    }

    // RAZORPAY SUCCESS CALLBACK
    public function razorpaySuccess(Request $request)
    {
        $razorpayKey = setting('razorpay_publickey');
        $razorpaySecret = setting('razorpay_secretkey');
        $paymentId = $request->input('razorpay_payment_id');
        $orderId = $request->input('razorpay_order_id');

        if (empty($razorpayKey) || empty($razorpaySecret) || empty($paymentId) || empty($orderId)) {
            return redirect('/')->with('error', 'Missing payment information.');
        }

        try {
            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
            $payment = $api->payment->fetch($paymentId);

            // Capture payment if authorized (should be auto-captured, but double-check)
            if ($payment['status'] === 'authorized') {
                $payment = $payment->capture([
                    'amount' => $payment['amount'],
                    'currency' => $payment['currency'],
                ]);
            }

            if ($payment['status'] === 'captured') {
                // Build a fake request for afterPaymentSuccess
                $fakeRequest = new Request([
                    'employee_id' => $request->query('employee_id'),
                    'branch_id' => $request->query('branch_id'),
                    'date' => $request->query('date'),
                    'time' => $request->query('time'),
                    'services' => json_decode($request->query('services'), true),
                    'coupon_code' => $request->query('coupon_code'),
                    'tax_percentage' => json_decode($request->query('tax_percentage'), true),
                    'tip' => $request->query('tip', 0),
                    'discount_amount' => $request->input('discount_amount'),
                    'discount_percentage' => $request->input('discount_percentage'),

                ]);
                return $this->afterPaymentSuccess($fakeRequest, 'razorpay', $paymentId);
            }

            return redirect('/')->with('error', 'Payment verification failed. Status: ' . $payment['status']);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }


    protected function flutterwaveSuccess(Request $request)
    {
        try {
            $transactionId = $request->input('transaction_id');
            $tx_ref = $request->input('tx_ref');
            $plan_id = $request->input('plan_id'); // Optional: or rebuild from metadata

            $flutterwaveKey = setting('flutterwave_secretkey');

            $response = Http::withToken($flutterwaveKey)
                ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

            $responseData = $response->json();

            if (
                $response->successful() &&
                isset($responseData['status']) &&
                $responseData['status'] === 'success' &&
                $responseData['data']['tx_ref'] === $tx_ref
            ) {
                // Handle success (you may need to use $responseData['data']['meta'] here)
                return $this->afterPaymentSuccess(
                    new Request([
                        'employee_id' => $responseData['data']['meta']['employee_id'] ?? null,
                        'branch_id' => $responseData['data']['meta']['branch_id'] ?? null,
                        'date' => $responseData['data']['meta']['date'] ?? null,
                        'time' => $responseData['data']['meta']['time'] ?? null,
                        'services' => json_decode($responseData['data']['meta']['services'] ?? '[]', true),
                        'coupon_code' => $responseData['data']['meta']['coupon_code'] ?? null,
                        'tax_percentage' => json_decode($responseData['data']['meta']['tax_percentage'] ?? '[]', true),
                        'tip' => $responseData['data']['meta']['tip'] ?? 0,
                        'discount_amount' => $responseData['data']['meta']['discount_amount'] ?? null,
                        'discount_percentage' => $responseData['data']['meta']['discount_percentage'] ?? null,
                    ]),
                    'flutterwave',
                    $transactionId
                );
            }

            throw new \Exception('Payment verification failed');
        } catch (\Exception $e) {


            return redirect('/')->with('error', 'Payment verification failed: ' . $e->getMessage());
        }
    }


    // This method is called after payment is successful for all gateways
    protected function afterPaymentSuccess(Request $request, $gateway, $transactionId)
    {
        // Build services array as expected by BookingTrait (service_id, service_price, employee_id, start_date_time)
        $employeeId = $request->input('employee_id');
        $services = [];
        $date = $request->input('date');
        $time = $request->input('time');
        if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
            $date = $dateObj->format('d/m/Y');
        }
        if ($time && preg_match('/^\d{2}(:\d{2}){1,2}$/', $time)) {
            $timeObj = \Carbon\Carbon::createFromFormat(strlen($time) === 8 ? 'H:i:s' : 'H:i', $time);
            $time = $timeObj->format('H:i');
        }
        $startDateTime = null;
        if ($date && $time) {
            try {
                $startDateTime = parseDmyDateTimeString($date, $time)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $startDateTime = null;
            }
        }
        if (is_array($request->input('services'))) {
            foreach ($request->input('services') as $service) {
                $serviceId = is_array($service) ? ($service['id'] ?? $service[0]) : $service;
                $serviceModel = \Modules\Service\Models\Service::find($serviceId);
                if ($serviceModel) {
                    $services[] = [
                        'service_id' => $serviceModel->id,
                        'service_price' => $serviceModel->default_price,
                        'employee_id' => $employeeId,
                        'start_date_time' => $startDateTime,
                    ];
                }
            }
        } else {
            $serviceIds = is_string($request->input('services')) ? explode(',', $request->input('services')) : [$request->input('services')];
            foreach ($serviceIds as $serviceId) {
                $serviceModel = \Modules\Service\Models\Service::find($serviceId);
                if ($serviceModel) {
                    $services[] = [
                        'service_id' => $serviceModel->id,
                        'service_price' => $serviceModel->default_price,
                        'employee_id' => $employeeId,
                        'start_date_time' => $startDateTime,
                    ];
                }
            }
        }
        $bookingData = [
            'employee_id' => $employeeId,
            'branch_id' => $request->input('branch_id'),
            'date' => $date,
            'time' => $time,
            'services' => $services,
            'coupon_code' => $request->input('coupon_code'),
        ];
        // Direct controller call fallback
        $bookingsController = app(\Modules\Booking\Http\Controllers\Backend\API\BookingsController::class);
        $apiRequest = new \Illuminate\Http\Request($bookingData);
        $response = $bookingsController->store($apiRequest);
        if (method_exists($response, 'getData')) {
            $bookingJson = json_decode(json_encode($response->getData()), true);
        } else {
            $bookingJson = is_array($response) ? $response : [];
        }

        if (!($bookingJson['status'] ?? false)) {
            return back()->withErrors($bookingJson['message'] ?? 'Booking failed.');
        }

        $bookingId = $bookingJson['booking_id'] ?? null;

        // 2. Save payment
        $paymentSaveData = [
            'booking_id' => $bookingId,
            'payment_method' => $gateway,
            'tax_percentage' => $request->input('tax_percentage'),
            'tip' => $request->input('tip', 0),
            'coupon_code' => $request->input('coupon_code'),
            'discount_amount' => $request->input('discount_amount'),
            'discount_percentage' => $request->input('discount_percentage'),
            'transaction_type' => $gateway,
            'payment_status' => 1,
            'external_transaction_id' => $transactionId,
        ];
        // Use only the direct controller call for saving payment
        $paymentController = app(\Modules\Booking\Http\Controllers\Backend\API\PaymentController::class);
        $apiPaymentRequest = new \Illuminate\Http\Request($paymentSaveData);
        $paymentResponse = $paymentController->savePayment($apiPaymentRequest);
        if (method_exists($paymentResponse, 'getData')) {
            $paymentSaveJson = json_decode(json_encode($paymentResponse->getData()), true);
        } else {
            $paymentSaveJson = is_array($paymentResponse) ? $paymentResponse : [];
        }
        // dd($paymentSaveJson['message']);
        if (!($paymentSaveJson['status'] ?? false)) {
            return back()->withErrors($paymentSaveJson['message'] ?? 'Payment save failed.');
        }

        return redirect()->route('bookings')->with('success', 'Booking and payment successful!');
    }

    // STRIPE SUCCESS CALLBACK
    public function stripeSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');
        $stripeSecret = setting('stripe_secretkey');

        if (!$stripeSecret) {
            return redirect()->route('bookings')->with('error', 'Stripe secret key not configured.');
        }

        $stripe = new \Stripe\StripeClient($stripeSecret);

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('bookings')->with('error', 'Payment was not completed successfully.');
            }

            // Extract metadata for booking/payment
            $meta = $session->metadata ?? [];
            $fakeRequest = new Request([
                'employee_id' => $meta['employee_id'] ?? null,
                'branch_id' => $meta['branch_id'] ?? null,
                'date' => $meta['date'] ?? null,
                'time' => $meta['time'] ?? null,
                'services' => json_decode($meta['services'] ?? '[]', true),
                'coupon_code' => $meta['coupon_code'] ?? null,
                'tax_percentage' => json_decode($meta['tax_percentage'] ?? '[]', true),
                'tip' => $meta['tip'] ?? 0,
                'discount_amount' => $meta['discount_amount'] ?? null,
                'discount_percentage' => $meta['discount_percentage'] ?? null,
            ]);
            $this->afterPaymentSuccess($fakeRequest, 'stripe', $session->payment_intent);
            return redirect()->route('bookings')->with('success', 'Booking and payment successful!');
        } catch (\Exception $e) {
            return redirect()->route('bookings')->with('error', 'Stripe verification error: ' . $e->getMessage());
        }
    }

    public function paystackSuccess(Request $request)
    {
        $reference = $request->input('reference');
        $paystackSecret = setting('paystack_secretkey');

        if (!$paystackSecret) {
            return redirect()->route('bookings')->with('error', 'Paystack secret key not configured.');
        }

        if (!$reference) {
            return redirect()->route('bookings')->with('error', 'Missing payment reference.');
        }

        try {
            // Verify payment with Paystack API
            $response = Http::withToken($paystackSecret)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            $data = $response->json();

            if (!$response->successful() || $data['status'] !== true || $data['data']['status'] !== 'success') {
                return redirect()->route('bookings')->with('error', 'Payment verification failed.');
            }

            // Extract metadata (if sent from backend in Paystack `metadata` field)
            $meta = $data['data']['metadata'] ?? [];

            $fakeRequest = new Request([
                'employee_id' => $meta['employee_id'] ?? null,
                'branch_id' => $meta['branch_id'] ?? null,
                'date' => $meta['date'] ?? null,
                'time' => $meta['time'] ?? null,
                'services' => json_decode($meta['services'] ?? '[]', true),
                'coupon_code' => $meta['coupon_code'] ?? null,
                'tax_percentage' => json_decode($meta['tax_percentage'] ?? '[]', true),
                'tip' => $meta['tip'] ?? 0,
                'discount_amount' => $meta['discount_amount'] ?? null,
                'discount_percentage' => $meta['discount_percentage'] ?? null,
            ]);

            $this->afterPaymentSuccess($fakeRequest, 'paystack', $reference);

            return redirect()->route('bookings')->with('success', 'Booking and payment successful!');
        } catch (\Exception $e) {
            return redirect()->route('bookings')->with('error', 'Paystack verification error: ' . $e->getMessage());
        }
    }

    public function paypalSuccess(Request $request)
    {
        $orderID = $request->input('orderID');
        $clientId = setting('paypal_clientid');
        $clientSecret = setting('paypal_secretkey');

        if (!$clientId || !$clientSecret) {
            return response()->json(['success' => false, 'message' => 'PayPal not configured.']);
        }

        try {
            // Get PayPal token
            $auth = base64_encode("$clientId:$clientSecret");
            $tokenRes = Http::withHeaders([
                'Authorization' => "Basic $auth",
            ])->asForm()->post('https://api-m.paypal.com/v1/oauth2/token', [
                'grant_type' => 'client_credentials'
            ]);

            $accessToken = $tokenRes['access_token'] ?? null;
            if (!$accessToken) {
                return response()->json(['success' => false, 'message' => 'Unable to authenticate PayPal.']);
            }

            // Capture the order
            $captureRes = Http::withToken($accessToken)->post("https://api-m.paypal.com/v2/checkout/orders/{$orderID}/capture");

            if ($captureRes->failed()) {
                return response()->json(['success' => false, 'message' => 'Failed to capture PayPal payment.']);
            }

            $purchaseUnit = $captureRes['purchase_units'][0] ?? null;
            $encodedMetadata = $purchaseUnit['custom_id'] ?? null;
            $decoded = $encodedMetadata ? json_decode(base64_decode($encodedMetadata), true) : [];

            // Prepare fake request from metadata
            $fakeRequest = new Request([
                'employee_id' => $decoded['employee_id'] ?? null,
                'branch_id' => $decoded['branch_id'] ?? null,
                'date' => $decoded['date'] ?? null,
                'time' => $decoded['time'] ?? null,
                'services' => $decoded['services'] ?? [],
                'coupon_code' => $decoded['coupon_code'] ?? null,
                'tax_percentage' => $decoded['tax_percentage'] ?? [],
                'tip' => $decoded['tip'] ?? 0,
                'discount_amount' => $decoded['discount_amount'] ?? null,
                'discount_percentage' => $decoded['discount_percentage'] ?? null,
            ]);

            $this->afterPaymentSuccess($fakeRequest, 'paypal', $orderID);

            return response()->json([
                'success' => true,
                'redirect_url' => route('bookings')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'PayPal error: ' . $e->getMessage()
            ]);
        }
    }

    protected function handleWallet(Request $request, $price)
    {
        try {
            // Check if user is authenticated
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated. Please log in.'
                ], 401);
            }

            $user = auth()->user();

            // Get user's wallet
            $wallet = Wallet::where('user_id', $user->id)->first();

            if (!$wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found. Please contact support.'
                ], 404);
            }

            // Wallet balance check and amount deduction happens in PaymentController->savePayment()
            // to ensure accurate calculation with taxes, tips, and coupons

            // Build services array as expected by BookingTrait
            $employeeId = $request->input('employee_id');
            $services = [];

            $date = $request->input('date');
            $time = $request->input('time');

            if ($date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $dateObj = \Carbon\Carbon::createFromFormat('Y-m-d', $date);
                $date = $dateObj->format('d/m/Y');
            }

            if ($time && preg_match('/^\d{2}(:\d{2}){1,2}$/', $time)) {
                $timeObj = \Carbon\Carbon::createFromFormat(strlen($time) === 8 ? 'H:i:s' : 'H:i', $time);
                $time = $timeObj->format('H:i');
            }

            $startDateTime = null;
            if ($date && $time) {
                try {
                    $startDateTime = parseDmyDateTimeString($date, $time)->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $startDateTime = null;
                }
            }

            if (is_array($request->input('services'))) {
                foreach ($request->input('services') as $service) {
                    $serviceId = is_array($service) ? ($service['id'] ?? $service[0]) : $service;
                    $serviceModel = Service::find($serviceId);
                    if ($serviceModel) {
                        $services[] = [
                            'service_id' => $serviceModel->id,
                            'service_price' => $serviceModel->default_price,
                            'employee_id' => $employeeId,
                            'start_date_time' => $startDateTime,
                        ];
                    }
                }
            } else {
                $serviceIds = is_string($request->input('services')) ? explode(',', $request->input('services')) : [$request->input('services')];
                foreach ($serviceIds as $serviceId) {
                    $serviceModel = Service::find($serviceId);
                    if ($serviceModel) {
                        $services[] = [
                            'service_id' => $serviceModel->id,
                            'service_price' => $serviceModel->default_price,
                            'employee_id' => $employeeId,
                            'start_date_time' => $startDateTime,
                        ];
                    }
                }
            }

            $bookingData = [
                'employee_id' => $employeeId,
                'branch_id' => $request->input('branch_id'),
                'date' => $date,
                'time' => $time,
                'services' => $services,
                'coupon_code' => $request->input('coupon_code'),
            ];

            // Create booking
            $bookingsController = app(\Modules\Booking\Http\Controllers\Backend\API\BookingsController::class);
            $apiRequest = new \Illuminate\Http\Request($bookingData);
            $response = $bookingsController->store($apiRequest);

            if (method_exists($response, 'getData')) {
                $bookingJson = json_decode(json_encode($response->getData()), true);
            } else {
                $bookingJson = is_array($response) ? $response : [];
            }

            if (!($bookingJson['status'] ?? false)) {

                return response()->json([
                    'success' => false,
                    'message' => $bookingJson['message'] ?? 'Booking failed.'
                ], 400);
            }

            $bookingId = $bookingJson['booking_id'] ?? null;

            // Save payment record
            $paymentSaveData = [
                'booking_id' => $bookingId,
                'payment_method' => 'wallet',
                'tax_percentage' => $request->input('tax_percentage', []),
                'tip' => $request->input('tip', 0),
                'coupon_code' => $request->input('coupon_code', null),
                'discount_amount' => $request->input('discount_amount', 0),
                'discount_percentage' => $request->input('discount_percentage', 0),
                'transaction_type' => 'wallet',
                'payment_status' => 1, // Paid
                'external_transaction_id' => 'wallet_txn_' . uniqid(),
            ];

            $paymentController = app(\Modules\Booking\Http\Controllers\Backend\API\PaymentController::class);
            $apiPaymentRequest = new \Illuminate\Http\Request($paymentSaveData);
            $paymentResponse = $paymentController->savePayment($apiPaymentRequest);

            if (method_exists($paymentResponse, 'getData')) {
                $paymentSaveJson = json_decode(json_encode($paymentResponse->getData()), true);
            } else {
                $paymentSaveJson = is_array($paymentResponse) ? $paymentResponse : [];
            }

            if (!($paymentSaveJson['status'] ?? false)) {

                return response()->json([
                    'success' => false,
                    'message' => $paymentSaveJson['message'] ?? 'Payment save failed.'
                ], 400);
            }

            // Get updated wallet balance
            $updatedWallet = Wallet::where('user_id', $user->id)->first();

            return response()->json([
                'success' => true,
                'message' => 'Booking and payment successful! Amount deducted from wallet.',
                'booking_id' => $bookingId,
                'payment_id' => $paymentSaveJson['payment_id'] ?? null,
                'wallet_balance' => $updatedWallet ? $updatedWallet->amount : 0
            ]);
        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'message' => 'Wallet payment failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
