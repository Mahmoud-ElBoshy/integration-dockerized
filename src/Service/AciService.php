<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;


class AciService
{
    private HttpClientInterface $httpClient;
    private SerializerInterface $serializer;
    private string $authKey;
    private string $entityId;
    private string $paymentBrand;

    private $logger;

    public function __construct(HttpClientInterface $httpClient,LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        // Hardcoded auth key, entity ID, and payment brand for test mode
        $this->authKey = 'OGFjN2E0Yzc5Mzk0YmRjODAxOTM5NzM2ZjFhNzA2NDF8enlac1lYckc4QXk6bjYzI1NHNng=';
        $this->entityId = '8ac7a4c79394bdc801939736f17e063d';
        $this->paymentBrand = 'VISA'; // Or other supported brand
    }

    public function debit(float $amount, string $currency, string $cardNumber, string $expYear, string $expMonth, string $cvv,string $holder): ?array
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                'https://eu-test.oppwa.com/v1/payments',
                [
                    'headers' => [
                        'Authorization' => 'Bearer '.$this->authKey,
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'body' => [
                        'entityId' => $this->entityId,
                        'paymentType' => 'DB',
                        'paymentBrand' => $this->paymentBrand,
                        'amount' => number_format($amount, 2, '.', ''),
                        'currency' => $currency,
                        'card.number' => $cardNumber,
                        'card.expiryMonth' => $expMonth,
                        'card.expiryYear' => $expYear,
                        'card.cvv' => $cvv,
                        'card.holder' => $holder,
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            if ($statusCode === 200 && isset($content['id'])) {
                    return [
                        'transactionId' => $content['id'],
                        'dateCreated' => new \DateTimeImmutable($content['timestamp']),
                        'amount' => $amount,
                        'currency' => $currency,
                        'cardBin' => substr($cardNumber, 0, 6),
                        'system' => 'aci',
                    ];
            }

            // Log or handle errors appropriately
            return null;

        } catch (\Exception $e) {
            $this->logger->error('an error occurred with Aci service and the error message is '.$e->getMessage());
            return null;
        }
    }
}