<?php

namespace Modules\Booking\Services\Gateways;

use App\Models\Setting;
use Stripe\StripeClient;

class StripeGateway implements PaymentGatewayInterface
{
    protected $secretKey;
    protected $publicKey;

    public function __construct()
    {
        $keys = $this->getKeys();
        $this->secretKey = $keys['stripe_secretkey'] ?? null;
        $this->publicKey = $keys['stripe_publickey'] ?? null;
    }

    public function getKeys()
    {
        $settings = Setting::where('type', 'str_payment_method')->get();
        $keys = [];
        foreach ($settings as $setting) {
            $keys[$setting->name] = $setting->val;
        }
        return $keys;
    }

    public function process($data)
    {
        if (!$this->secretKey) {
            return ['status' => false, 'message' => 'Stripe secret key not configured.'];
        }

        $baseURL = env('APP_URL');
        try {
            $stripe = new StripeClient($this->secretKey);
            $checkout_session = $stripe->checkout->sessions->create([
                'success_url' => $baseURL . '/app/bookings/payment_success/' . $data['booking_transaction_id'],
                'payment_method_types' => ['card'],
                'billing_address_collection' => 'required',
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $data['currency'],
                            'product_data' => [
                                'name' => 'Booking ID: #' . ($data['booking_id'] ?? $data['booking_transaction_id']),
                            ],
                            'unit_amount' => $data['total_amount'] * 100,
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
            ]);
            return $checkout_session;
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function verify($token)
    {
        try {
            $stripe = new StripeClient($this->secretKey);
            return $stripe->checkout->sessions->retrieve($token, []);
        } catch (\Exception $e) {
            return null;
        }
    }
}
