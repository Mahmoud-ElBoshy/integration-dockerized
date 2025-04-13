<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentResponse
{
    #[Assert\NotBlank]
    public string $transactionId;

    #[Assert\NotBlank]
    public \DateTimeImmutable $dateCreated;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public float $amount;

    #[Assert\NotBlank]
    public string $currency;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 6)]
    public string $cardBin;

    #[Assert\NotBlank]
    public string $system;

    public function __construct(string $transactionId, \DateTimeImmutable $dateCreated, float $amount, string $currency, string $cardBin, string $system)
    {
        $this->transactionId = $transactionId;
        $this->dateCreated = $dateCreated;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->cardBin = $cardBin;
        $this->system = $system;
    }

    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId,
            'dateCreated' => $this->dateCreated->format('Y-m-d H:i:s'),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'cardBin' => $this->cardBin,
            'system' => $this->system,
        ];
    }
}