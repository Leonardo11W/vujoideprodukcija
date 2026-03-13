<?php

namespace Modules\Frontend\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\Payment;
use Stripe\StripeClient;
use Modules\Frontend\Http\Controllers\Backend\ProductController;
use Modules\Product\Http\Controllers\Backend\API\OrdersController;
use Modules\Product\Http\Requests\OrderRequest;


class PaymentController extends Controller
{
    // Show payment method selection and summary
    public function checkout(Request $request)
    {
        $price = $request->input('price');
        $methods = ['stripe', 'paypal', 'razorpay', 'cash'];
        return view('payment.checkout', compact('price', 'methods'));
    }

    // Handle payment initiation
    public function ProductPaymentProccess(Request $request)
    {
        $method = strtolower($request->input('payment_method'));
        switch ($method) {
            case 'stripe':
                return $this->stripeCheckout($request);
            case 'paypal':
                return $this->paypalCheckout($request);
            case 'razorpay':
                return $this->razorpayCheckout($request);
            case 'cash':
                return $this->cashCheckout($request);
            default:
                return back()->withErrors('Invalid payment method.');
        }
    }

    // Stripe checkout
    public function stripeCheckout(Request $request)
    {
        $stripeSecret = setting('stripe_secretkey');
        $stripepublic = setting('stripe_publickey');
        $currency = GetcurrentCurrency();

        $productController = new ProductController();
        $summary = $productController->cartSummary($request);

        $data = $summary->getData(true);
        $totalWithDelivery = $data['total_with_delivery'] ?? 0;

        if (!$stripeSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe secret key not configured.'
            ], 500);
        }

        $amount = $totalWithDelivery;

        $stripe = new \Stripe\StripeClient($stripeSecret);

        try {
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Product Payment',
                        ],
                        'unit_amount' => intval($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('product.payment.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel', ['gateway' => 'stripe']),
                'metadata' => [
                    'shipping_address_id'      => $request->input('shipping_address_id'),
                    'billing_address_id'       => $request->input('billing_address_id'),
                    'chosen_logistic_zone_id'  => $request->input('chosen_logistic_zone_id'),
                    'payment_method'           => $request->input('payment_method'),
                    'shipping_delivery_type'   => $request->input('shipping_delivery_type'),
                    'payment_status'           => $request->input('payment_status'),
                    'amount'                   => $amount,
                ],
            ]);

            return response()->json(['success' => true, 'redirect' => $session->url]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function productStripeSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');
        $stripeSecret = setting('stripe_secretkey');

        if (!$stripeSecret) {
            return redirect()->route('payment.checkout')->with('error', 'Stripe secret key not configured.');
        }

        $stripe = new \Stripe\StripeClient($stripeSecret);

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                return redirect()->route('payment.checkout')->with('error', 'Payment was not completed successfully.');
            }

            $meta = $session->metadata ?? [];

            $request_data = new Request([
                'employee_id' => $meta['employee_id'] ?? null,
                'shipping_address_id'      => $meta['shipping_address_id'] ?? null,
                'billing_address_id'       => $meta['billing_address_id'] ?? null,
                'chosen_logistic_zone_id'  => $meta['chosen_logistic_zone_id'] ?? null,
                'payment_method'           => $meta['payment_method'] ?? null,
                'shipping_delivery_type'   => $meta['shipping_delivery_type'] ?? null,
                'payment_status'           => 'paid',
                'amount'                   => $meta['amount'] ?? null,

            ]);
            $response = $this->afterPaymentSuccess($request_data, 'stripe', $session->payment_intent);

            $responseData = json_decode($response->getContent(), true);

            if ($response->getStatusCode() === 200 && isset($responseData['status']) && $responseData['status'] === true) {
                return redirect()->route('myorder')->with('success', $responseData['message'] ?? 'Booking and payment successful!');
            }
            return redirect()->back()->with('error', $responseData['message'] ?? 'Something went wrong with your order. Please try again.');
        } catch (\Exception $e) {
            return redirect()->route('payment.checkout')->with('error', 'Stripe verification error: ' . $e->getMessage());
        }
    }

    // After payment is successful, create booking and payment records
    protected function afterPaymentSuccess(Request $request, $gateway, $transactionId)
    {
        $ordersController = new OrdersController();

        $orderRequest = new OrderRequest($request->all());

        $orderData = $ordersController->store($orderRequest);

        return $orderData;
    }


    public function razorpayCheckout(Request $request)
    {

        $razorpayKey = setting('razorpay_publickey');
        $razorpaySecret = setting('razorpay_secretkey');
        $currency = GetcurrentCurrency();
        $supportedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'SGD', 'AED'];
        $formattedCurrency = strtoupper($currency);

        $productController = new ProductController();
        $summary = $productController->cartSummary($request);

        $data = $summary->getData(true);
        $totalWithDelivery = $data['total_with_delivery'] ?? 0;

        try {
            if (!in_array($formattedCurrency, $supportedCurrencies)) {
                $formattedCurrency = 'INR';
            }

            $formattedCurrency = 'INR';

            $roundedPrice = round($totalWithDelivery, 2);

            $amount = intval($roundedPrice * 100);

            if ($amount <= 0) {
                return response()->json([
                    'error' => 'Invalid amount. Amount must be greater than 0.'
                ], 400);
            }

            $orderData = [
                'receipt'         => 'order_' . uniqid(),
                'amount'          => $amount,
                'currency'        => $formattedCurrency,
                'payment_capture' => 1
            ];

            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
            $order = $api->order->create($orderData);

            return response()->json([
                'key' => $razorpayKey,
                'amount' => $amount,
                'currency' => $formattedCurrency,
                'name' => config('app.name'),
                'description' => 'Order Payment',
                'order_id' => $order['id'],
                'success_url' => route('product.razorpay.success', [
                    'shipping_address_id'      => $request->input('shipping_address_id'),
                    'billing_address_id'       => $request->input('billing_address_id'),
                    'chosen_logistic_zone_id'  => $request->input('chosen_logistic_zone_id'),
                    'payment_method'           => $request->input('payment_method'),
                    'shipping_delivery_type'   => $request->input('shipping_delivery_type'),
                    'payment_status'           => $request->input('payment_status'),
                    'amount'                   => $amount,
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

            if ($payment['status'] === 'authorized') {
                $payment = $payment->capture([
                    'amount' => $payment['amount'],
                    'currency' => $payment['currency'],
                ]);
            }

            if ($payment['status'] === 'captured') {

                $request_data = new Request([
                    'shipping_address_id'      => $request->input('shipping_address_id'),
                    'billing_address_id'       => $request->input('billing_address_id'),
                    'chosen_logistic_zone_id'  => $request->input('chosen_logistic_zone_id'),
                    'payment_method'           => $request->input('payment_method'),
                    'shipping_delivery_type'   => $request->input('shipping_delivery_type'),
                    'payment_status'           => $request->input('payment_status'),
                    'amount'                   => $payment['amount'],
                ]);
                return $this->afterPaymentSuccess($request_data, 'razorpay', $paymentId);
            }

            return redirect('/')->with('error', 'Payment verification failed. Status: ' . $payment['status']);
        } catch (\Exception $e) {
            return redirect('/')->with('error', 'Payment processing error: ' . $e->getMessage());
        }
    }



    // PayPal checkout
    public function paypalCheckout(Request $request)
    {
        $price = $request->input('price');
        $paypalUrl = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_xclick&business=' . urlencode(config('services.paypal.email')) . '&amount=' . $price . '&currency_code=USD&item_name=Booking+Payment&return=' . urlencode(route('payment.success', ['gateway' => 'paypal'])) . '&cancel_return=' . urlencode(route('payment.cancel', ['gateway' => 'paypal'])) . '&notify_url=' . urlencode(route('payment.success', ['gateway' => 'paypal'])) . '&no_shipping=1';
        return redirect($paypalUrl);
    }

    // Cash checkout
    public function cashCheckout(Request $request)
    {
        try {
            // If user info is provided, find or create user (as in QuickBooking)
            $userRequest = $request->user;
            $user = null;
            if ($userRequest && isset($userRequest['email'])) {
                $user = \App\Models\User::where('email', $userRequest['email'])->first();
                if (!isset($user)) {
                    $userRequest['password'] = \Hash::make('12345678');
                    $user = \App\Models\User::create($userRequest);
                    $roles = ['user'];
                    $user->syncRoles($roles);
                    \Artisan::call('cache:clear');
                    event(new \App\Events\Backend\UserCreated($user));
                    try {
                        $user->notify(new \App\Notifications\UserAccountCreated(['password' => '12345678']));
                    } catch (\Exception $e) {
                    }
                }
            }
            // Build booking data
            $bookingData = $request->booking ?? $request->all();
            if ($user) {
                $bookingData['user_id'] = $user->id;
                $bookingData['created_by'] = $user->id;
                $bookingData['updated_by'] = $user->id;
            }
            $booking = \Modules\Booking\Models\Booking::create($bookingData);
            // If you have a method to update services, call it here (as in QuickBooking)
            if (method_exists($this, 'updateBookingService') && isset($bookingData['services'])) {
                $this->updateBookingService($bookingData['services'], $booking->id);
            }
            // Optionally send notification (as in QuickBooking)
            try {
                $notify_type = 'cancel_booking';
                $messageTemplate = 'New booking #[[booking_id]] has been booked.';
                $notify_message = str_replace('[[booking_id]]', $booking->id, $messageTemplate);
                if (method_exists($this, 'sendNotificationOnBookingUpdate')) {
                    $this->sendNotificationOnBookingUpdate($notify_type, $notify_message, $booking);
                }
            } catch (\Exception $e) {
            }
            // Create payment record
            \Modules\Booking\Models\Payment::create([
                'booking_id' => $booking->id,
                'payment_method' => 'cash',
                'amount' => $bookingData['price'] ?? $bookingData['amount'] ?? 0,
                'status' => 1,
                'transaction_id' => 'cash_txn_id',
            ]);
            // Always return JSON for AJAX/fetch
            if ($request->ajax() || $request->wantsJson() || $request->isJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Booking and payment successful!',
                    'booking_id' => $booking->id,
                    'data' => $booking
                ]);
            }
            // For normal form, redirect
            return redirect()->route('index')->with('success', 'Booking and payment successful!');
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            // CSRF error
            return response()->json(['success' => false, 'message' => 'Session expired. Please refresh and try again.'], 419);
        } catch (\Exception $e) {

            if ($request->ajax() || $request->wantsJson() || $request->isJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Booking/payment failed: ' . $e->getMessage()], 500);
            }
            return back()->withErrors('Booking/payment failed: ' . $e->getMessage());
        }
    }

    // Success callback
    public function success(Request $request, $gateway)
    {
        if ($gateway === 'stripe') {
            $sessionId = $request->input('session_id');
            $stripeSecret = config('services.stripe.secret');
            $stripe = new StripeClient($stripeSecret);
            try {
                $session = $stripe->checkout->sessions->retrieve($sessionId);
                $meta = $session->metadata ?? [];
                $fakeRequest = new Request([
                    'employee_id' => $meta['employee_id'] ?? null,
                    'branch_id' => $meta['branch_id'] ?? null,
                    'date' => $meta['date'] ?? null,
                    'time' => $meta['time'] ?? null,
                    'services' => json_decode($meta['services'] ?? '[]', true),
                    'price' => $meta['price'] ?? null,
                ]);
                return $this->afterPaymentSuccess($fakeRequest, 'stripe', $session->payment_intent);
            } catch (\Exception $e) {
                return back()->withErrors('Stripe verification error: ' . $e->getMessage());
            }
        }
        // Razorpay and PayPal can be handled similarly (implement as needed)
        return view('payment.success', compact('gateway'));
    }

    // Cancel/failure callback
    public function cancel(Request $request, $gateway)
    {
        return view('payment.failure', compact('gateway'));
    }


    public function productStripeCheckout(Request $request)
    {
        $stripeSecret = setting('stripe_secretkey');
        $stripepublic = setting('stripe_publickey');
        $currency = GetcurrentCurrency();

        $productController = new ProductController();
        $summary = $productController->cartSummary($request);

        $data = $summary->getData(true);

        $totalWithDelivery = $data['total_with_delivery'] ?? 0;

        if (!$stripeSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe secret key not configured.'
            ], 500);
        }
        $amount = $totalWithDelivery;

        $stripe = new \Stripe\StripeClient($stripeSecret);

        try {
            $session = $stripe->checkout->sessions->create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => 'Product Payment',
                        ],
                        'unit_amount' => intval($amount * 100),
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('product.payment.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel', ['gateway' => 'stripe']),
            ]);

            return response()->json(['success' => true, 'redirect' => $session->url]);
        } catch (\Exception $e) {

            return response()->json(['success' => false, 'message' => 'Stripe error: ' . $e->getMessage()], 500);
        }
    }

    public function productRazorpayCheckout(Request $request)
    {

        $razorpayKey = \DB::table('settings')->where('name', 'razorpay_publickey')->value('val');
        $razorpaySecret = \DB::table('settings')->where('name', 'razorpay_secretkey')->value('val');
        $currency = 'INR';
        $amount = $request->input('amount');

        if (!$razorpayKey || !$razorpaySecret) {

            return response()->json(['success' => false, 'message' => 'Razorpay key/secret not configured.'], 500);
        }
        if (!is_numeric($amount) || $amount < 1) {

            return response()->json(['success' => false, 'message' => 'Order amount must be a valid number and at least ₹1. Received: ' . $amount], 400);
        }
        try {
            $api = new \Razorpay\Api\Api($razorpayKey, $razorpaySecret);
            $order = $api->order->create([
                'receipt' => 'product_order_' . uniqid(),
                'amount' => round($amount * 100),
                'currency' => $currency,
                'payment_capture' => 1
            ]);

            if (
                $request->ajax() ||
                $request->wantsJson() ||
                $request->isJson() ||
                $request->expectsJson() ||
                strpos($request->header('Accept'), 'application/json') !== false
            ) {
                return response()->json([
                    'success' => true,
                    'order' => $order,
                    'razorpayKey' => $razorpayKey,
                    'amount' => $amount,
                    'currency' => $currency,
                    'prefill' => [
                        'name' => auth()->user()->name ?? '',
                        'email' => auth()->user()->email ?? '',
                        'contact' => auth()->user()->phone ?? ''
                    ]
                ]);
            }
            // Otherwise, return the view
            return view('payment.razorpay', [
                'order' => $order,
                'razorpayKey' => $razorpayKey,
                'amount' => $amount,
                'currency' => $currency,
            ]);
        } catch (\Exception $e) {

            if (
                $request->ajax() ||
                $request->wantsJson() ||
                $request->isJson() ||
                $request->expectsJson() ||
                strpos($request->header('Accept'), 'application/json') !== false
            ) {
                return response()->json(['success' => false, 'message' => 'Razorpay error: ' . $e->getMessage()], 500);
            }
            return back()->withErrors('Razorpay error: ' . $e->getMessage());
        }
    }

    public function productSuccess(Request $request)
    {
        $sessionId = $request->input('session_id');
        $stripeSecret = \DB::table('settings')->where('name', 'stripe_secrectkey')->value('val');
        if (empty($stripeSecret)) {
            $stripeSecret = \DB::table('settings')->where('name', 'stripe_secretkey')->value('val');
        }
        if (empty($stripeSecret) || !is_string($stripeSecret)) {
            return back()->withErrors('Stripe secret key is not configured.');
        }
        $stripe = new \Stripe\StripeClient($stripeSecret);
        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId);
            $meta = $session->metadata ?? [];
            // You can store order/payment info here as needed
            // Example: mark product order as paid, etc.
            // $meta['product_id'], $meta['user_id'], etc. if you set them in metadata
            return redirect()->route('myorder')->with('success', 'Product payment successful!');
        } catch (\Exception $e) {
            return back()->withErrors('Stripe verification error: ' . $e->getMessage());
        }
    }




    public function productRazorpaySuccess(Request $request)
    {


        $razorpayPaymentId = $request->input('razorpay_payment_id');
        $razorpayOrderId = $request->input('razorpay_order_id');

        if (empty($razorpayPaymentId) || empty($razorpayOrderId)) {

            return back()->withErrors('Payment verification failed: Missing payment information.');
        }

        $user = auth()->user();
        if (!$user) {

            return back()->withErrors('User not authenticated.');
        }

        $now = now();
        $cartItems = \Modules\Product\Models\Cart::where('user_id', $user->id)->get();



        if ($cartItems->isEmpty()) {

            return back()->withErrors('Cart is empty. Please add items to cart before checkout.');
        }

        \DB::beginTransaction();

        try {
            // Create order
            $order = \Modules\Product\Models\Order::create([
                'order_group_id' => 0,
                'user_id' => $user->id,
                'guest_user_id' => null,
                'location_id' => null,
                'delivery_status' => 'pending',
                'payment_status' => 'paid',
                'applied_coupon_code' => null,
                'coupon_discount_amount' => 0,
                'admin_earning_percentage' => 0,
                'total_admin_earnings' => 0,
                'logistic_id' => null,
                'logistic_name' => null,
                'pickup_or_delivery' => 'delivery',
                'pickup_hub_id' => null,
                'shipping_cost' => 0,
                'tips_amount' => 0,
                'reward_points' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);


            // Create order items
            $orderItemsCreated = 0;
            foreach ($cartItems as $item) {
                $product = \Modules\Product\Models\Product::find($item->product_id);
                $variation = \Modules\Product\Models\ProductVariation::where('product_id', $item->product_id)->first();

                if ($product && $variation) {
                    // Calculate unit price with discount
                    $unitPrice = $product->max_price;
                    if ($product->discount_type === 'percent' && $product->discount_value > 0) {
                        $unitPrice = $unitPrice - ($unitPrice * $product->discount_value / 100);
                    } elseif ($product->discount_type === 'fixed' && $product->discount_value > 0) {
                        $unitPrice = $unitPrice - $product->discount_value;
                    }

                    $orderItem = \Modules\Product\Models\OrderItem::create([
                        'order_id' => $order->id,
                        'product_variation_id' => $variation->id,
                        'qty' => $item->qty,
                        'location_id' => null,
                        'unit_price' => $unitPrice,
                        'total_tax' => 0,
                        'total_price' => $unitPrice * $item->qty,
                        'reward_points' => 0,
                        'is_refunded' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $orderItemsCreated++;
                }
            }



            // Clear the cart
            $cartDeleted = \Modules\Product\Models\Cart::where('user_id', $user->id)->delete();



            return redirect()->route('myorder')->with('success', 'Product payment successful!');
        } catch (\Exception $e) {


            return back()->withErrors('Payment succeeded but server did not record it. Please contact support. Error: ' . $e->getMessage());
        }
    }

    // Product cash checkout method
    public function productCashCheckout(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return back()->withErrors('User not authenticated.');
            }

            $now = now();
            $cartItems = \Modules\Product\Models\Cart::where('user_id', $user->id)->get();
            if ($cartItems->isEmpty()) {
                return back()->withErrors('Cart is empty.');
            }



            // Calculate order totals
            $subtotal = 0;
            $discount = 0;
            $deliveryCharge = 0;
            foreach ($cartItems as $item) {
                $product = $item->product;
                $price = $product->max_price;
                if ($product->discount_type === 'percent' && $product->discount_value > 0) {
                    $itemDiscount = ($price * $product->discount_value / 100) * $item->qty;
                    $discount += $itemDiscount;
                    $price = $price - ($price * $product->discount_value / 100);
                } elseif ($product->discount_type === 'fixed' && $product->discount_value > 0) {
                    $itemDiscount = $product->discount_value * $item->qty;
                    $discount += $itemDiscount;
                    $price = $price - $product->discount_value;
                }
                $subtotal += $price * $item->qty;
            }

            // Get addressId from session or request
            $addressId = session('checkout_address_id') ?? $request->address_id ?? null;
            $orderGroup = null;
            if ($addressId) {
                $orderGroup = \Modules\Product\Models\OrderGroup::create([
                    'user_id' => $user->id,
                    'shipping_address_id' => $addressId,
                    'billing_address_id' => $addressId,
                    'sub_total_amount' => $subtotal,
                    'total_shipping_cost' => $deliveryCharge,
                    'grand_total_amount' => $subtotal + $deliveryCharge,
                ]);
            }

            // Create order
            $order = \Modules\Product\Models\Order::create([
                'order_group_id' => $orderGroup ? $orderGroup->id : 0,
                'user_id' => $user->id,
                'guest_user_id' => null,
                'location_id' => null,
                'delivery_status' => 'pending',
                'payment_status' => 'paid',
                'applied_coupon_code' => null,
                'coupon_discount_amount' => $discount,
                'admin_earning_percentage' => 0,
                'total_admin_earnings' => 0,
                'logistic_id' => null,
                'logistic_name' => null,
                'pickup_or_delivery' => 'delivery',
                'pickup_hub_id' => null,
                'shipping_cost' => $deliveryCharge,
                'tips_amount' => 0,
                'reward_points' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                $product = \Modules\Product\Models\Product::find($item->product_id);
                $variation = \Modules\Product\Models\ProductVariation::where('product_id', $item->product_id)->first();

                if ($product && $variation) {
                    // Calculate unit price with discount
                    $unitPrice = $product->max_price;
                    if ($product->discount_type === 'percent' && $product->discount_value > 0) {
                        $unitPrice = $unitPrice - ($unitPrice * $product->discount_value / 100);
                    } elseif ($product->discount_type === 'fixed' && $product->discount_value > 0) {
                        $unitPrice = $unitPrice - $product->discount_value;
                    }

                    \Modules\Product\Models\OrderItem::create([
                        'order_id' => $order->id,
                        'product_variation_id' => $variation->id,
                        'qty' => $item->qty,
                        'location_id' => null,
                        'unit_price' => $unitPrice,
                        'total_tax' => 0,
                        'total_price' => $unitPrice * $item->qty,
                        'reward_points' => 0,
                        'is_refunded' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Clear the cart
            \Modules\Product\Models\Cart::where('user_id', $user->id)->delete();

            \DB::commit();

            if ($request->ajax() || $request->wantsJson() || $request->isJson() || $request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product order successful!',
                    'order_id' => $order->id,
                    'data' => $order
                ]);
            }

            return redirect()->route('myorder')->with('success', 'Product order successful!');
        } catch (\Exception $e) {
            \DB::rollBack();

            if ($request->ajax() || $request->wantsJson() || $request->isJson() || $request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Order failed: ' . $e->getMessage()], 500);
            }

            return back()->withErrors('Order failed: ' . $e->getMessage());
        }
    }
}
