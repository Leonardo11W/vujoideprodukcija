<?php

namespace App\Traits;

use App\Models\Setting;
use Modules\Constant\Models\Constant;

trait PaymentMethodTrait
{
    /**
     * Get filtered payment methods based on database settings
     *
     * @return array
     */
    protected function getFilteredPaymentMethods()
    {
        // Get existing constant options (cash, upi, stripe, razorpay seeded by default)
        $constantMethods = collect(Constant::getTypeDataObject('PAYMENT_METHODS'))
            ->keyBy('id');

        // Read settings values
        $settings = Setting::all()->pluck('val', 'name');

        // Map of supported gateways => setting key and label
        $supported = [
            'cash'        => ['key' => null,                         'label' => 'Cash'],
            'upi'         => ['key' => null,                         'label' => 'UPI'],
            'razorpay'    => ['key' => 'razor_payment_method',       'label' => 'Razorpay'],
            'stripe'      => ['key' => 'str_payment_method',         'label' => 'Stripe'],
            'paystack'    => ['key' => 'paystack_payment_method',    'label' => 'Paystack'],
            'paypal'      => ['key' => 'paypal_payment_method',      'label' => 'Paypal'],
            'flutterwave' => ['key' => 'flutterwave_payment_method', 'label' => 'Flutterwave'],
            'cinet'       => ['key' => 'cinet_payment_method',       'label' => 'Cinet'],
            'sadad'       => ['key' => 'sadad_payment_method',       'label' => 'Sadad'],
            'airtelmoney' => ['key' => 'airtelmoney_payment_method', 'label' => 'Airtel Money'],
            'phonepay'    => ['key' => 'phonepay_payment_method',    'label' => 'PhonePe'],
            'midtrans'    => ['key' => 'midtrans_payment_method',    'label' => 'Midtrans'],
        ];

        $result = [];

        foreach ($supported as $method => $data) {
            // Always include non-gateway basic methods
            if (is_null($data['key'])) {
                $result[] = [
                    'id' => $method,
                    'text' => $constantMethods[$method]['text'] ?? $data['label'],
                ];
                continue;
            }

            // Include only if enabled in settings (value truthy == 1)
            $enabled = (string)($settings[$data['key']] ?? '0') === '1';
            if ($enabled) {
                $result[] = [
                    'id' => $method,
                    'text' => $constantMethods[$method]['text'] ?? $data['label'],
                ];
            }
        }

        return $result;
    }
    
    /**
     * Get the setting key for a payment method
     *
     * @param string $methodName
     * @return string|null
     */
    protected function getPaymentMethodSettingKey($methodName)
    {
        $settingKeys = [
            'razorpay' => 'razor_payment_method',
            'stripe' => 'str_payment_method',
            'paystack' => 'paystack_payment_method',
            'paypal' => 'paypal_payment_method',
            'flutterwave' => 'flutterwave_payment_method',
            'cinet' => 'cinet_payment_method',
            'sadad' => 'sadad_payment_method',
            'airtelmoney' => 'airtelmoney_payment_method',
            'phonepay' => 'phonepay_payment_method',
            'midtrans' => 'midtrans_payment_method',
        ];
        
        return $settingKeys[$methodName] ?? null;
    }
}
