<?php

namespace App\Controller;

use App\Dto\PaymentResponse;
use App\Service\Payment\PaymentContext;
use App\Service\Payment\PaymentProcessorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/gateway')]
class PaymentController extends AbstractController
{
    private PaymentContext $paymentContext;
    private iterable $paymentProcessors;
    private ValidatorInterface $validator;

    public function __construct(PaymentContext $paymentContext, iterable $paymentProcessors,ValidatorInterface $validator)
    {
        $this->paymentContext = $paymentContext;
        $this->paymentProcessors = $paymentProcessors;
        $this->validator = $validator;
    }

    #[Route('/pay/{system}', name: 'pay', methods: ['POST'])]
    public function processPaymentApi(string $system, Request $request): JsonResponse
    {
        $amount = $request->request->get('amount');
        $currency = $request->request->get('currency');
        $cardNumber = $request->request->get('cardNumber');
        $expYear = $request->request->get('expYear');
        $expMonth = $request->request->get('expMonth');
        $cvv = $request->request->get('cvv');
        $holder = $request->request->get('holder');

        $violations = $this->validator->validate([
            'amount' => $amount,
            'currency' => $currency,
            'cardNumber' => $cardNumber,
            'expYear' => $expYear,
            'expMonth' => $expMonth,
            'cvv' => $cvv,
            'holder' => $holder,
        ], new Assert\Collection([
            'amount' => [new Assert\NotBlank(), new Assert\Positive(), new Assert\Type('numeric')],
            'currency' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 3])],
            'cardNumber' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{13,19}$/')],
            'expYear' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{4}$/')],
            'expMonth' => [new Assert\NotBlank(), new Assert\Regex('/^(0[1-9]|1[0-2])$/')],
            'cvv' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{3,4}$/')],
            'holder' => [new Assert\NotBlank(), new Assert\type('string')]
        ]));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()][] = $violation->getMessage();
            }
            return new JsonResponse(['errors' => $errors], 400);
        }

        $paymentDetails = [
            'amount' => $amount,
            'currency' => $currency,
            'cardNumber' => $cardNumber,
            'expYear' => $expYear,
            'expMonth' => $expMonth,
            'cvv' => $cvv,
            'holder' => $holder
        ];

        $selectedProcessor = null;
        foreach ($this->paymentProcessors as $processor) {
            if ($processor instanceof PaymentProcessorInterface) {
                if ($processor->getName() === $system) {
                    $selectedProcessor = $processor;
                    break;
                }
            }
            else{
                return new JsonResponse(['error' => 'Invalid payment system'], 400);
            }
        }

        if (!$selectedProcessor)
            return new JsonResponse(['error' => 'Invalid payment system'], 400);

        $this->paymentContext->setPaymentProcessor($selectedProcessor);
        $paymentInfo = $this->paymentContext->processPayment($paymentDetails);

        if ($paymentInfo) {
            $paymentResponse = new PaymentResponse(
                $paymentInfo['transactionId'],
                $paymentInfo['dateCreated'],
                $paymentInfo['amount'],
                $paymentInfo['currency'],
                $paymentInfo['cardBin'],
                $paymentInfo['system']
            );
            return new JsonResponse($paymentResponse->toArray(), 200);
        } else {
            return new JsonResponse(['error' => 'Payment processing failed'], 500);
        }
    }
}