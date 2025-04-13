<?php

namespace App\Service\Payment;

interface PaymentProcessorInterface
{
    public function processPayment(array $paymentDetails): ?array;

    public function getName(): string;
}