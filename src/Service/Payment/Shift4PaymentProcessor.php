<?php

namespace App\Service\Payment;

use App\Service\Shift4Service;

class Shift4PaymentProcessor implements PaymentProcessorInterface
{
    private Shift4Service $shift4Service;

    public function __construct(Shift4Service $shift4Service)
    {
        $this->shift4Service = $shift4Service;
    }

    public function processPayment(array $paymentDetails): ?array
    {
        return $this->shift4Service->charge(
            $paymentDetails['amount'] ?? '',
            $paymentDetails['currency'] ?? '',
            $paymentDetails['cardNumber'] ?? '',
            $paymentDetails['expYear'] ?? '',
            $paymentDetails['expMonth'] ?? '',
            $paymentDetails['cvv'] ?? '',
            $paymentDetails['holder'] ?? ''
        );
    }

    public function getName(): string
    {
        return 'shift4';
    }
}