<?php

namespace Modules\Booking\Services;

use Modules\Booking\Services\Gateways\CashGateway;
use Modules\Booking\Services\Gateways\RazorpayGateway;
use Modules\Booking\Services\Gateways\StripeGateway;
use Modules\Booking\Services\Gateways\PaymentGatewayInterface;

class PaymentService
{
    /**
     * Get the appropriate gateway based on method
     */
    public function getGateway(string $method): ?PaymentGatewayInterface
    {
        return match ($method) {
            'stripe' => new StripeGateway(),
            'razorpay' => new RazorpayGateway(),
            'cash' => new CashGateway(),
            default => null,
        };
    }

    /**
     * Process payment
     */
    public function processPayment(string $method, array $data)
    {
        $gateway = $this->getGateway($method);
        if (!$gateway) {
            return ['status' => false, 'message' => 'Unsupported payment method: ' . $method];
        }

        return $gateway->process($data);
    }
}
