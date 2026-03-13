<?php

namespace Modules\Booking\Services\Gateways;

class CashGateway implements PaymentGatewayInterface
{
    public function getKeys()
    {
        return [];
    }

    public function process($data)
    {
        // Cash payment is always successful by default in terms of gateway
        return ['status' => true];
    }

    public function verify($token)
    {
        return ['status' => true];
    }
}
