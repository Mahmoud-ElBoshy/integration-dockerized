<?php

namespace App\Command;

use App\Dto\PaymentResponse;
use App\Service\Payment\PaymentContext;
use App\Service\Payment\PaymentProcessorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[AsCommand(
    name: 'gateway:pay',
    description: 'Processes a payment through either Shift4 or ACI.',
)]
class PaymentCommand extends Command
{
    private PaymentContext $paymentContext;
    private iterable $paymentProcessors; // Inject tagged services
    private ValidatorInterface $validator;

    public function __construct(PaymentContext $paymentContext, iterable $paymentProcessors, ValidatorInterface $validator)
    {
        parent::__construct();
        $this->paymentContext = $paymentContext;
        $this->paymentProcessors = $paymentProcessors;
        $this->validator = $validator;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('system', InputArgument::REQUIRED, 'The external system to use (aci or shift4)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $system = $input->getArgument('system');

        if (!in_array($system, ['aci', 'shift4'])) {
            $output->writeln('<error>Invalid system parameter. Use "aci" or "shift4".</error>');
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');

        $questions = [
            'amount' => new Question('Enter amount: '),
            'currency' => new Question('Enter currency: '),
            'cardNumber' => new Question('Enter card number: '),
            'expYear' => new Question('Enter card expiry year (YYYY): '),
            'expMonth' => new Question('Enter card expiry month (MM): '),
            'cvv' => new Question('Enter card CVV: '),
            'holder' => new Question('Enter card holder name: '),
        ];

        $params = [];
        foreach ($questions as $key => $question) {
            $answer = $helper->ask($input, $output, $question);
            $params[$key] = $answer;

            $constraints = match ($key) {
                'amount' => [new Assert\NotBlank(), new Assert\Positive(), new Assert\Type('numeric')],
                'currency' => [new Assert\NotBlank(), new Assert\Length(['min' => 3, 'max' => 3])],
                'cardNumber' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{13,19}$/')],
                'expYear' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{4}$/')],
                'expMonth' => [new Assert\NotBlank(), new Assert\Regex('/^(0[1-9]|1[0-2])$/')],
                'cvv' => [new Assert\NotBlank(), new Assert\Regex('/^[0-9]{3,4}$/')],
                'holder' => [new Assert\NotBlank(), new Assert\type('string')],
                default => [],
            };

            $violations = $this->validator->validate($answer, $constraints);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[$key][] = $violation->getMessage();
                }
            }
        }

        if (!empty($errors)) {
            $output->writeln('<error>Validation errors:</error>');
            foreach ($errors as $field => $messages) {
                foreach ($messages as $message) {
                    $output->writeln("<error>  - {$field}: {$message}</error>");
                }
            }
            return Command::FAILURE;
        }


        $amount = (float) $params['amount'];
        $paymentDetails = $params; // All input params are payment details

        $selectedProcessor = null;
        foreach ($this->paymentProcessors as $processor) {
            if ($processor->getName() === $system) {
                $selectedProcessor = $processor;
                break;
            }
        }

        if (!$selectedProcessor) {
            $output->writeln('<error>Invalid payment system.</error>');
            return Command::FAILURE;
        }

        $this->paymentContext->setPaymentProcessor($selectedProcessor);
        $paymentInfo = $this->paymentContext->processPayment($paymentDetails);

        if ($paymentInfo) {
            $paymentResponse = new PaymentResponse(
                $paymentInfo['transactionId'],
                $paymentInfo['dateCreated'],
                $paymentInfo['amount'],
                $paymentInfo['currency'],
                $paymentInfo['cardBin'],
                $this->paymentContext->getPaymentProcessorName()
            );
            $output->writeln('<info>Payment successful:</info>');
            $output->writeln('  Transaction ID: ' . $paymentResponse->transactionId);
            $output->writeln('  Date Created: ' . $paymentResponse->dateCreated->format('Y-m-d H:i:s'));
            $output->writeln('  Amount: ' . $paymentResponse->amount);
            $output->writeln('  Currency: ' . $paymentResponse->currency);
            $output->writeln('  Card BIN: ' . $paymentResponse->cardBin);
            $output->writeln('  System: ' . $paymentResponse->system);
            return Command::SUCCESS;
        } else {
            $output->writeln('<error>Payment processing failed with ' . $this->paymentContext->getPaymentProcessorName() . '</error>');
            return Command::FAILURE;
        }
    }
}