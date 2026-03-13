<?php

namespace Modules\Booking\Services\Gateways;

use App\Models\Setting;
use Razorpay\Api\Api;
use Modules\Booking\Models\BookingTransaction;
use Modules\Booking\Models\Booking;
use Modules\Booking\Transformers\BookingResource;

class RazorpayGateway implements PaymentGatewayInterface
{
    protected $keyId;
    protected $secret;

    public function __construct()
    {
        $keys = $this->getKeys();
        $this->keyId = $keys['razorpay_publickey'] ?? null;
        $this->secret = $keys['razorpay_secretkey'] ?? null;
    }

    public function getKeys()
    {
        $settings = Setting::where('type', 'razor_payment_method')->get();
        $keys = [];
        foreach ($settings as $setting) {
            $keys[$setting->name] = $setting->val;
        }
        return $keys;
    }

    public function process($data)
    {
        // For Razorpay, the "process" is often capturing the payment after frontend success
        // or just returning keys for the frontend to open the checkout.
        if (isset($data['response']['razorpay_payment_id'])) {
            return $this->capture($data);
        }

        return [
            'status' => true,
            'public_key' => $this->keyId,
        ];
    }

    public function capture($data)
    {
        try {
            $currency = $data['response']['currency'];
            $floatTotalAmount = floatval($data['response']['total_amount']);
            $totalamountInPaise = $floatTotalAmount * 100;
            
            $api = new Api($this->keyId, $this->secret);
            $paymentId = $data['response']['razorpay_payment_id'];
            
            $api->payment->fetch($paymentId)->capture([
                'amount' => $totalamountInPaise, 
                'currency' => $currency
            ]);

            return ['status' => true, 'payment_id' => $paymentId];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function verify($token)
    {
        // Not implemented in original code as a separate verify step
        return null;
    }
}
