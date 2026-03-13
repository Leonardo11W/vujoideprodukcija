<?php

namespace Modules\Booking\Services\Gateways;

interface PaymentGatewayInterface
{
    /**
     * Process the payment or initiate the checkout session
     */
    public function process($data);

    /**
     * Verify or retrieve a payment session/transaction
     */
    public function verify($token);
    
    /**
     * Get the gateway keys/configuration
     */
    public function getKeys();
}
