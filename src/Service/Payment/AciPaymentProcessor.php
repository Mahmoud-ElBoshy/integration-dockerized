<?php

namespace App\Service\Payment;

use App\Service\AciService;

class AciPaymentProcessor implements PaymentProcessorInterface
{
    private AciService $aciService;

    public function __construct(AciService $aciService)
    {
        $this->aciService = $aciService;
    }

    public function processPayment(array $paymentDetails): ?array
    {
        return $this->aciService->debit(
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
        return 'aci';
    }
}