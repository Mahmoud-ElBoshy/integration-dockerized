<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;


class Shift4Service
{
    private HttpClientInterface $httpClient;
    private SerializerInterface $serializer;
    private string $authKey;
    private $logger;

    public function __construct(HttpClientInterface $httpClient,LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        // Hardcoded auth key for test mode
        $this->authKey = 'sk_test_4gW8biNXPzJuCbfuRLMz5ZGF:';
    }

    public function charge(float $amount, string $currency, string $cardNumber, string $expYear, string $expMonth, string $cvv,string $holder): ?array
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                'https://api.shift4.com/charges',
                [
                    'auth_basic' => [$this->authKey, ''],
                    'headers' => [
                        'Accept' => '*/*',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'amount' => (int) ($amount * 100),
                        'currency' => $currency,
                        'card' => [
                            'number' => $cardNumber,
                            'expYear' => $expYear,
                            'expMonth' => $expMonth,
                            'cvc' => $cvv,
                            'cardholderName' => $holder
                        ],
                    ],
                ]
            );

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            if ($statusCode >= 200 && $statusCode < 300 && isset($content['id'])) {
                return [
                    'transactionId' => $content['id'],
                    'dateCreated' => new \DateTimeImmutable(), // Shift4 doesn't directly provide creation date in this response
                    'amount' => $amount,
                    'currency' => $currency,
                    'cardBin' => substr($cardNumber, 0, 6),
                    'system' => 'shift4',
                ];
            }

            // Log or handle errors appropriately
            return null;

        } catch (\Exception $e) {
            $this->logger->error('an error occurred with Shift4 service service and the error message is '.$e->getMessage());
            return null;
        }
    }
}