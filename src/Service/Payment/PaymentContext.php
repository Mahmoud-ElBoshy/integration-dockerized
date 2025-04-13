<?php

namespace App\Service\Payment;

class PaymentContext
{
    private PaymentProcessorInterface $paymentProcessor;

    public function setPaymentProcessor(PaymentProcessorInterface $paymentProcessor): void
    {
        $this->paymentProcessor = $paymentProcessor;
    }

    public function processPayment(array $paymentDetails): ?array
    {
        if (!$this->paymentProcessor) {
            throw new \RuntimeException('Payment processor not set.');
        }
        return $this->paymentProcessor->processPayment($paymentDetails);
    }

    public function getPaymentProcessorName(): ?string
    {
        return $this->paymentProcessor ? $this->paymentProcessor->getName() : null;
    }
}