<?php

namespace Modules\Frontend\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Bank\Models\Bank;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WalletHistory;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Razorpay\Api\Api as RazorpayApi;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Setting;


class WalletController extends Controller
{
    public function index()
    {
        $banks = Bank::where('user_id', auth()->id())->where('status', 1)->get();
        $withdrawals = Wallet::where('user_id', auth()->id())->latest()->get();

        return view('frontend::wallet', compact('banks', 'withdrawals'));
    }

    public function withdraw(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1',
                'bank_id' => 'required|exists:banks,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => $e->getMessage(),
                    'errors' => $e->errors()
                ], 422);
            } else {
                throw $e;
            }
        }

        $wallet = \Modules\Wallet\Models\Wallet::where('user_id', auth()->id())->first();
        if (!$wallet || $wallet->amount < $request->amount) {
            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => __('frontend.insufficient_balance_withdraw'),
                ], 400);
            } else {
                return redirect()->back()->withErrors(['amount' => __('frontend.insufficient_balance_withdraw')]);
            }
        }

        // Subtract the withdrawal amount from the wallet
        $wallet->amount -= $request->amount;
        $wallet->save();

        $withdrawal = Wallet::create([
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'title' => 'Withdrawal Request',
            'status' => 1,
        ]);

        // Log withdrawal in WalletHistory
        $activity_data = [
            'title' => 'Withdrawal Request',
            'user_id' => auth()->id(),
            'amount' => $request->amount,
            'transaction_id' => $withdrawal->id,
            'credit_debit_amount' => (float) $request->amount,
            'transaction_type' => 'debit',
        ];

        $data = [
            'user_id' => auth()->id(),
            'datetime' => now(),
            'activity_type' => 'wallet_withdrawal',
            'activity_message' => 'Withdrawal Request',
            'activity_data' => json_encode($activity_data),
        ];

        \Modules\Wallet\Models\WalletHistory::create($data);

        if ($request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Withdrawal of ' . \Currency::format($request->amount) . ' submitted successfully'
            ]);
        } else {
            return redirect()->back()->with('success', 'Withdrawal of ' . \Currency::format($request->amount) . ' submitted successfully');
        }
    }

    public function topUp(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:Stripe,Razorpay,Flutterwave,Paystack',
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $method = $request->payment_method;


        switch ($method) {
            case 'Stripe':

                try {
                    $stripe_key = Setting::get('stripe_secretkey');


                    if (!$stripe_key) {

                        return response()->json([
                            'status' => false,
                            'message' => __('frontend.stripe_configuration_is_missing_please_contact_administrator')
                        ], 500);
                    }

                    $currency = GetcurrentCurrency();

                    // Validate currency
                    if (!$currency) {

                        return response()->json([
                            'status' => false,
                            'message' => __('frontend.currency_configuration_error_please_contact_administrator')
                        ], 500);
                    }

                    Stripe::setApiKey($stripe_key);

                    $session = Session::create([
                        'payment_method_types' => ['card'],
                        'line_items' => [[
                            'price_data' => [
                                'currency' => $currency,
                                'product_data' => ['name' => 'Wallet Top-Up'],
                                'unit_amount' => $amount * 100,
                            ],
                            'quantity' => 1,
                        ]],
                        'mode' => 'payment',
                        'success_url' => route('wallet.payment.success', ['amount' => $amount]),
                        'cancel_url' => route('wallet.payment.cancel'),
                    ]);


                    return response()->json([
                        'status' => true,
                        'message' => 'Redirecting to Stripe...',
                        'redirect_url' => $session->url,
                    ]);
                } catch (\Stripe\Exception\AuthenticationException $e) {

                    return response()->json([
                        'status' => false,
                        'message' => __('frontend.stripe_authentication_failed_please_check_your_api_keys')
                    ], 500);
                } catch (\Stripe\Exception\InvalidRequestException $e) {

                    return response()->json([
                        'status' => false,
                        'message' => __('frontend.invalid_payment_request_please_try_again')
                    ], 500);
                } catch (\Stripe\Exception\ApiConnectionException $e) {

                    return response()->json([
                        'status' => false,
                        'message' => __('frontend.payment_gateway_connection_error_please_try_again')
                    ], 500);
                } catch (\Stripe\Exception\ApiErrorException $e) {

                    return response()->json([
                        'status' => false,
                        'message' => __('frontend.payment_gateway_error_please_try_again')
                    ], 500);
                } catch (\Exception $e) {

                    return response()->json([
                        'status' => false,
                        'message' => __('frontend.payment_gateway_error_please_try_again')
                    ], 500);
                }

            case 'Razorpay':
                try {
                    $razorpayKey = Setting::get('razorpay_publickey');
                    $razorpaySecret = Setting::get('razorpay_secretkey');

                    if (!$razorpayKey || !$razorpaySecret) {

                        return response()->json([
                            'status' => false,
                            'message' => __('frontend.razorpay_configuration_is_missing_please_contact_administrator')
                        ], 500);
                    }

                    $currency = GetcurrentCurrency();

                    // Debug logging

                    // Validate currency
                    if (!$currency) {

                        return response()->json([
                            'status' => false,
                            'message' => __('frontend.currency_configuration_error_please_contact_administrator')
                        ], 500);
                    }

                    // Normalize currency to uppercase (fix for GetcurrentCurrency() returning lowercase)
                    $originalCurrency = $currency;
                    $currency = strtoupper(trim($currency));

                    // Razorpay currency mapping - based on official documentation
                    // Razorpay supports nearly 100 currencies including USD, EUR, GBP, etc.
                    $supportedCurrencies = [
                        'USD',
                        'INR',
                        'EUR',
                        'GBP',
                        'AED',
                        'SGD',
                        'AUD',
                        'CAD',
                        'CHF',
                        'CNY',
                        'HKD',
                        'JPY',
                        'MYR',
                        'NZD',
                        'SEK',
                        'THB',
                        'TWD',
                        'VND',
                        'RUB',
                        'ALL',
                        'AMD',
                        'ARS',
                        'AWG',
                        'BBD',
                        'BDT',
                        'BMD',
                        'BND',
                        'BOB',
                        'BSD',
                        'BWP',
                        'BZD',
                        'COP',
                        'CRC',
                        'CUP',
                        'CZK',
                        'DKK',
                        'DOP',
                        'DZD',
                        'EGP',
                        'ETB',
                        'FJD',
                        'GIP',
                        'GMD',
                        'GTQ',
                        'GYD',
                        'HNL',
                        'HRK',
                        'HTG',
                        'HUF',
                        'IDR',
                        'ILS',
                        'JMD',
                        'KES',
                        'KGS',
                        'KHR',
                        'KYD',
                        'KZT',
                        'LAK',
                        'LBP',
                        'LKR',
                        'LRD',
                        'LSL',
                        'MAD',
                        'MDL',
                        'MKD',
                        'MMK',
                        'MNT',
                        'MOP',
                        'MUR',
                        'MVR',
                        'MWK',
                        'NAD',
                        'NGN',
                        'NIO',
                        'NOK',
                        'NPR',
                        'PAB',
                        'PEN',
                        'PGK',
                        'PHP',
                        'PKR',
                        'PYG',
                        'QAR',
                        'RON',
                        'RSD',
                        'RWF',
                        'SAR',
                        'SBD',
                        'SCR',
                        'SHP',
                        'SLL',
                        'SOS',
                        'SRD',
                        'STD',
                        'SVC',
                        'SZL',
                        'TJS',
                        'TMT',
                        'TND',
                        'TOP',
                        'TTD',
                        'TZS',
                        'UAH',
                        'UGX',
                        'UYU',
                        'UZS',
                        'VEF',
                        'VUV',
                        'WST',
                        'XAF',
                        'XCD',
                        'XOF',
                        'XPF',
                        'YER',
                        'ZAR',
                        'ZMW'
                    ];

                    // Check if currency is supported
                    if (!in_array($currency, $supportedCurrencies)) {

                        return response()->json([
                            'status' => false,
                            'message' => "Currency '$currency' is not supported by Razorpay. Please contact administrator."
                        ], 400);
                    }


                    // For USD and other major currencies, amount should be in cents
                    $amountInSmallestUnit = $amount * 100;

                    // Validate amount (Razorpay requires minimum amount)
                    if ($amountInSmallestUnit < 100) {
                        return response()->json([
                            'status' => false,
                            'message' => __('frontend.minimum_amount_of_razorpay_is_1_0')
                        ], 400);
                    }

                    $api = new RazorpayApi($razorpayKey, $razorpaySecret);

                    // Create order
                    $orderData = [
                        'receipt' => 'wallet_topup_' . time(),
                        'amount' => $amountInSmallestUnit, // in smallest currency unit
                        'currency' => $currency,
                        'payment_capture' => 1 // auto capture
                    ];


                    try {
                        $razorpayOrder = $api->order->create($orderData);
                    } catch (\Razorpay\Api\Errors\Error $e) {

                        // Check if it's a currency not supported error
                        if (strpos($e->getMessage(), 'Currency is not supported') !== false) {
                            return response()->json([
                                'status' => false,
                                'message' => "Currency '$currency' is not supported by your Razorpay account. Please contact Razorpay support to enable USD for international payments."
                            ], 400);
                        }

                        return response()->json([
                            'status' => false,
                            'message' => 'Payment gateway error: ' . $e->getMessage()
                        ], 500);
                    }

                    return response()->json([
                        'status' => true,
                        'message' => 'Redirecting to Razorpay...',
                        'order_id' => $razorpayOrder['id'],
                        'amount' => $amountInSmallestUnit, // Send the amount in smallest unit to match Razorpay order
                        'key' => $razorpayKey,
                        'name' => 'Wallet Top-Up',
                        'email' => $user->email,
                        'contact' => $user->phone ?? '',
                        'formattedCurrency' => $currency,
                        'redirect_url' => route('wallet.payment.verify', ['amount' => $amount]),
                    ]);
                } catch (\Razorpay\Api\Errors\Error $e) {

                    return response()->json([
                        'status' => false,
                        'message' => 'Payment gateway error: ' . $e->getMessage()
                    ], 500);
                } catch (\Exception $e) {

                    return response()->json([
                        'status' => false,
                        'message' => __('frontend.payment_gateway_error_please_try_again')
                    ], 500);
                }

            case 'Flutterwave':
            case 'Paystack':
                return response()->json([
                    'status' => true,
                    'message' => "Redirecting to $method...",
                    'redirect_url' => route('wallet.gateway.redirect', [
                        'method' => strtolower($method),
                        'transaction_id' => $transaction->id,
                    ]),
                ]);

            default:
                return response()->json(['status' => false, 'message' => __('frontend.unsupported_payment_method')]);
        }
    }

    public function paymentSuccess(Request $request)
    {

        $user = Auth::user();
        $amount = $request->query('amount');
        $wallet = Wallet::where('user_id', $user->id)->first();

        if (empty($wallet)) {

            $wallet = Wallet::create([
                'title' => $user->first_name . ' ' . $user->last_name,
                'user_id' => $user->id,
                'amount' => 0,

            ]);
        }

        $wallet->amount += $amount;
        $wallet->save();

        $activity_message = __('messages.top_up');

        $activity_data = [
            'title' => $wallet->title,
            'user_id' => $wallet->user_id,
            'amount' => $wallet->amount,
            'transaction_id' => $request->transaction_id ?? null,
            'transaction_type' => $request->transaction_type ?? null,
            'credit_debit_amount' => (float) $request->amount,
            'transaction_type' => 'credit',
        ];

        $data = [
            'user_id' => $wallet->user_id,
            'datetime' => now(),
            'activity_type' => 'wallet_top_up',
            'activity_message' => $activity_message,
            'activity_data' => json_encode($activity_data),
        ];

        WalletHistory::create($data);

        $notification_data = [
            'type' => 'Wallet Top Up',
            'message' => __('frontend.wallet_top_up_successfully'),
            'notification_type' => 'wallet_top_up', // Matches the seeder type
            'wallet' => [
                'user_id' => $wallet->user_id,
                'user_name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                'credit_debit_amount' => (float) $request->amount,
                'transaction_id' => $request->transaction_id,
                'transaction_type' => 'credit',
                'id' => $wallet->id,
            ],
        ];

        // Send the notification
        // sendNotification($notification_data);

        return redirect()->route('wallet')->with('success', __('frontend.wallet_has_been_successfully_topped_up'));
    }

    public function paymentCancel(Request $request)
    {
        return redirect()->route('wallet')->with('error', __('frontend.payment_was_cancelled'));
    }

    public function verifyRazorpayPayment(Request $request)
    {
        $user = Auth::user();
        $wallet = Wallet::where('user_id', $user->id)->first();
        $amount = $request->amount;

        $razorpayKeySecret = Setting::get('razorpay_secretkey');

        $generatedSignature = hash_hmac(
            'sha256',
            $request->razorpay_order_id . '|' . $request->razorpay_payment_id,
            $razorpayKeySecret
        );

        if ($generatedSignature === $request->razorpay_signature) {

            if (empty($wallet)) {

                $wallet = Wallet::create([
                    'title' => $user->first_name . ' ' . $user->last_name,
                    'user_id' => $user->id,
                    'amount' => 0,
                ]);
            }

            $wallet->amount += $amount;
            $wallet->save();

            $activity_message = __('messages.top_up');

            $activity_data = [
                'title' => $wallet->title,
                'user_id' => $wallet->user_id,
                'amount' => $wallet->amount,
                'transaction_id' => $request->transaction_id ?? null,
                'credit_debit_amount' => (float) $request->amount,
                'transaction_type' => 'credit',
            ];

            $data = [
                'user_id' => $wallet->user_id,
                'datetime' => now(),
                'activity_type' => 'wallet_top_up',
                'activity_message' => $activity_message,
                'activity_data' => json_encode($activity_data),
            ];

            WalletHistory::create($data);

            $notification_data = [
                'type' => 'Wallet Top Up',
                'message' => 'Wallet Top up SuccessFully',
                'notification_type' => 'wallet_top_up', // Matches the seeder type
                'wallet' => [
                    'user_id' => $wallet->user_id,
                    'user_name' => auth()->user()->first_name . ' ' . auth()->user()->last_name,
                    'credit_debit_amount' => (float) $request->amount,
                    'transaction_id' => $request->transaction_id,
                    'transaction_type' => 'credit',
                    'id' => $wallet->id,
                ],
            ];

            return response()->json(['status' => true, 'message' => __('frontend.payment_verified')]);
        }

        return response()->json(['status' => false, 'message' => __('frontend.signature_mismatch')]);
    }

    public function testStripeConfig()
    {
        try {
            $stripe_key = Setting::get('stripe_secretkey');

            if (!$stripe_key) {
                return response()->json([
                    'status' => false,
                    'message' => __('frontend.stripe_secret_key_is_not_configured')
                ], 500);
            }

            $currency = GetcurrentCurrency();

            if (!$currency) {
                return response()->json([
                    'status' => false,
                    'message' => __('frontend.currency_is_not_configured')
                ], 500);
            }

            // Test Stripe connection
            Stripe::setApiKey($stripe_key);

            // Try to create a test session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => ['name' => 'Test Payment'],
                        'unit_amount' => 100, // $1.00
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('wallet.payment.success', ['amount' => 1]),
                'cancel_url' => route('wallet.payment.cancel'),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Stripe configuration is working correctly',
                'session_id' => $session->id,
                'currency' => $currency,
                'stripe_key_length' => strlen($stripe_key)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Stripe configuration error: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ], 500);
        }
    }

    public function testRazorpayConfig()
    {
        try {
            $razorpayKey = Setting::get('razorpay_publickey');
            $razorpaySecret = Setting::get('razorpay_secretkey');

            if (!$razorpayKey || !$razorpaySecret) {
                return response()->json([
                    'status' => false,
                    'message' => __('frontend.razorpay_configuration_is_missing')
                ], 500);
            }

            $currency = GetcurrentCurrency();

            if (!$currency) {
                return response()->json([
                    'status' => false,
                    'message' => __('frontend.currency_is_not_configured')
                ], 500);
            }

            // Normalize currency to uppercase
            $currency = strtoupper(trim($currency));

            // Check if currency is supported by Razorpay
            $supportedCurrencies = [
                'USD',
                'INR',
                'EUR',
                'GBP',
                'AED',
                'SGD',
                'AUD',
                'CAD',
                'CHF',
                'CNY',
                'HKD',
                'JPY',
                'MYR',
                'NZD',
                'SEK',
                'THB',
                'TWD',
                'VND',
                'RUB',
                'ALL',
                'AMD',
                'ARS',
                'AWG',
                'BBD',
                'BDT',
                'BMD',
                'BND',
                'BOB',
                'BSD',
                'BWP',
                'BZD',
                'COP',
                'CRC',
                'CUP',
                'CZK',
                'DKK',
                'DOP',
                'DZD',
                'EGP',
                'ETB',
                'FJD',
                'GIP',
                'GMD',
                'GTQ',
                'GYD',
                'HNL',
                'HRK',
                'HTG',
                'HUF',
                'IDR',
                'ILS',
                'JMD',
                'KES',
                'KGS',
                'KHR',
                'KYD',
                'KZT',
                'LAK',
                'LBP',
                'LKR',
                'LRD',
                'LSL',
                'MAD',
                'MDL',
                'MKD',
                'MMK',
                'MNT',
                'MOP',
                'MUR',
                'MVR',
                'MWK',
                'NAD',
                'NGN',
                'NIO',
                'NOK',
                'NPR',
                'PAB',
                'PEN',
                'PGK',
                'PHP',
                'PKR',
                'PYG',
                'QAR',
                'RON',
                'RSD',
                'RWF',
                'SAR',
                'SBD',
                'SCR',
                'SHP',
                'SLL',
                'SOS',
                'SRD',
                'STD',
                'SVC',
                'SZL',
                'TJS',
                'TMT',
                'TND',
                'TOP',
                'TTD',
                'TZS',
                'UAH',
                'UGX',
                'UYU',
                'UZS',
                'VEF',
                'VUV',
                'WST',
                'XAF',
                'XCD',
                'XOF',
                'XPF',
                'YER',
                'ZAR',
                'ZMW'
            ];

            if (!in_array($currency, $supportedCurrencies)) {
                return response()->json([
                    'status' => false,
                    'message' => "Currency '$currency' is not supported by Razorpay"
                ], 400);
            }

            // Test Razorpay connection
            $api = new RazorpayApi($razorpayKey, $razorpaySecret);

            // Try to create a test order
            $orderData = [
                'receipt' => 'test_order_' . time(),
                'amount' => 100, // 1.00 in smallest unit
                'currency' => $currency,
                'payment_capture' => 1
            ];

            $razorpayOrder = $api->order->create($orderData);

            return response()->json([
                'status' => true,
                'message' => __('frontend.razorpay_configuration_is_working_correctly'),
                'order_id' => $razorpayOrder['id'],
                'currency' => $currency,
                'razorpay_key_length' => strlen($razorpayKey),
                'razorpay_secret_length' => strlen($razorpaySecret)
            ]);
        } catch (\Razorpay\Api\Errors\Error $e) {
            return response()->json([
                'status' => false,
                'message' => 'Razorpay API error: ' . $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_description' => method_exists($e, 'getDescription') ? $e->getDescription() : $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Razorpay configuration error: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ], 500);
        }
    }

    public function testRazorpayCurrencies()
    {
        try {
            $razorpayKey = Setting::get('razorpay_publickey');
            $razorpaySecret = Setting::get('razorpay_secretkey');

            if (!$razorpayKey || !$razorpaySecret) {
                return response()->json([
                    'status' => false,
                    'message' => __('frontend.razorpay_configuration_is_missing')
                ], 500);
            }

            $api = new RazorpayApi($razorpayKey, $razorpaySecret);

            // Test different currencies
            $testCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AED'];
            $results = [];

            foreach ($testCurrencies as $currency) {
                try {
                    $orderData = [
                        'receipt' => 'test_currency_' . $currency . '_' . time(),
                        'amount' => 100, // 1.00 in smallest unit
                        'currency' => $currency,
                        'payment_capture' => 1
                    ];

                    $razorpayOrder = $api->order->create($orderData);

                    $results[$currency] = [
                        'status' => 'success',
                        'order_id' => $razorpayOrder['id']
                    ];

                    // Cancel the test order immediately
                    try {
                        $api->order->fetch($razorpayOrder['id'])->cancel();
                    } catch (\Exception $e) {
                        // Ignore cancel errors
                    }
                } catch (\Razorpay\Api\Errors\Error $e) {
                    $results[$currency] = [
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'code' => $e->getCode()
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'message' => __('frontend.currency_test_completed'),
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Currency test error: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ], 500);
        }
    }

    public function checkRazorpayAccount()
    {
        try {
            $razorpayKey = Setting::get('razorpay_publickey');
            $razorpaySecret = Setting::get('razorpay_secretkey');

            if (!$razorpayKey || !$razorpaySecret) {
                return response()->json([
                    'status' => false,
                    'message' => __('frontend.razorpay_configuration_is_missing')
                ], 500);
            }

            $api = new RazorpayApi($razorpayKey, $razorpaySecret);

            // Get account details
            try {
                $account = $api->account->fetch();

                return response()->json([
                    'status' => true,
                    'message' => 'Account details retrieved',
                    'account' => [
                        'id' => $account['id'],
                        'name' => $account['name'],
                        'email' => $account['email'],
                        'type' => $account['type'] ?? 'unknown',
                        'profile' => $account['profile'] ?? null
                    ]
                ]);
            } catch (\Razorpay\Api\Errors\Error $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Could not fetch account details: ' . $e->getMessage(),
                    'error_code' => $e->getCode()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Account check error: ' . $e->getMessage(),
                'error_type' => get_class($e)
            ], 500);
        }
    }

    public function enableUsdInstructions()
    {
        return response()->json([
            'status' => true,
            'message' => 'Instructions to enable USD in Razorpay',
            'instructions' => [
                'step1' => 'Log into your Razorpay Dashboard at dashboard.razorpay.com',
                'step2' => 'Navigate to Settings → Payment Methods',
                'step3' => 'Look for "International Payments" or "Multi-currency"',
                'step4' => 'Enable International Payments',
                'step5' => 'Add USD to your supported currencies',
                'step6' => 'Complete any required verification',
                'contact_support' => 'If you cannot find these options, contact support@razorpay.com',
                'email_template' => [
                    'subject' => 'Enable USD Currency for International Payments',
                    'body' => 'Hi, I need to enable USD currency for international payments in my Razorpay account. Currently, I can only accept INR payments, but I need to accept USD as well. Please help me enable USD currency support. Thanks!'
                ]
            ],
            'test_account_note' => 'Note: Test accounts usually only support INR. You may need to switch to a live account for USD support.',
            'check_account_url' => '/wallet/check-razorpay-account',
            'test_currencies_url' => '/wallet/test-razorpay-currencies'
        ]);
    }


    public function historyData(Request $request)
    {
        $transactions = WalletHistory::where('user_id', auth()->id())
            ->latest();

        return DataTables::of($transactions)
            ->addColumn('date_time', function ($row) {
                return \Carbon\Carbon::parse($row->created_at)->format('d/m/Y, h:i A');
            })
            ->addColumn('transaction_type', function ($row) {
                $data = json_decode($row->activity_data, true);
                $activityType = $row->activity_type;
                $activityMessage = $row->activity_message;

                // Determine transaction type based on activity_type and message
                if ($activityType === 'wallet_withdrawal') {
                    return '<span class="fw-semibold text-dark">Withdrawal Request</span>';
                } elseif ($activityType === 'debit' && str_contains($activityMessage, 'Paid for booking #')) {
                    // Show the exact message from activity_message
                    return '<span class="fw-semibold text-dark">' . $activityMessage . '</span>';
                } elseif ($activityType === 'credit') {
                    return '<span class="fw-semibold text-dark">Top Up</span>';
                } elseif ($activityType === 'debit') {
                    return '<span class="fw-semibold text-dark">Payment</span>';
                } elseif ($activityType === 'wallet_top_up') {
                    return '<span class="fw-semibold text-dark">Top Up</span>';
                } elseif ($activityType === 'booking_payment') {
                    return '<span class="fw-semibold text-dark">Booking Payment</span>';
                } elseif ($activityType === 'refund') {
                    return '<span class="fw-semibold text-dark">Refund</span>';
                } else {
                    return '<span class="fw-semibold text-dark">' . ucfirst(str_replace('_', ' ', $activityType ?? 'Transaction')) . '</span>';
                }
            })


            ->addColumn('amount', function ($row) {
                $data = json_decode($row->activity_data, true);
                $amount = $data['credit_debit_amount'] ?? 0;
                $type = $data['transaction_type'] ?? null;

                // Show amount with appropriate sign
                $sign = ($type === 'credit') ? '+' : '-';
                $colorClass = ($type === 'credit') ? 'text-success' : 'text-danger';

                return '<span class="fw-semibold ' . $colorClass . '">' . $sign . \Currency::format($amount) . '</span>';
            })
            ->addColumn('status', function ($row) {
                $data = json_decode($row->activity_data, true);
                $type = $data['transaction_type'] ?? null;
                $activityType = $row->activity_type;

                if (!$type) {
                    return '<span class="badge bg-secondary-subtle fw-semibold font-size-12 rounded">N/A</span>';
                }

                // Show Credit or Debit based on transaction type
                if ($type === 'credit') {
                    return '<span class="badge bg-success-subtle text-success fw-semibold font-size-12 rounded">Credit</span>';
                } else {
                    return '<span class="badge bg-danger-subtle text-danger fw-semibold font-size-12 rounded">Debit</span>';
                }
            })
            ->rawColumns(['transaction_type', 'amount', 'status']) // allows HTML rendering
            ->make(true);
    }
}
