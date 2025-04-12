<?php

declare(strict_types=1);

namespace App\Command;

use App\PaymentGatewayFactory;
use App\DTO\PaymentGatewayInputDto;
use App\PaymentGateway\PaymentGatewayService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:process-payment',
    description: 'Process a payment through a specified payment gateway',
)]
class ProcessPaymentCommand extends Command
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly PaymentGatewayService $paymentGatewayService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'gateway',
                InputArgument::REQUIRED,
                'The payment gateway to use (e.g., aci, shift4)'
            )
            ->addOption(
                'amount',
                null,
                InputOption::VALUE_REQUIRED,
                'Payment amount (e.g., 100.00)'
            )
            ->addOption(
                'currency',
                null,
                InputOption::VALUE_REQUIRED,
                'Currency code (e.g., USD, EUR)'
            )
            ->addOption(
                'cardNumber',
                null,
                InputOption::VALUE_REQUIRED,
                'Card number (16 digits)'
            )
            ->addOption(
                'cardExpMonth',
                null,
                InputOption::VALUE_REQUIRED,
                'Card expiration month (MM)'
            )
            ->addOption(
                'cardExpYear',
                null,
                InputOption::VALUE_REQUIRED,
                'Card expiration year (YYYY)'
            )
            ->addOption(
                'cardCvv',
                null,
                InputOption::VALUE_REQUIRED,
                'Card CVV (3 digits)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $gatewayName = strtolower($input->getArgument('gateway'));

        $dto = new PaymentGatewayInputDto();
        $dto->amount = (float) $input->getOption('amount');
        $dto->currency = $input->getOption('currency');
        $dto->cardNumber = $input->getOption('cardNumber');
        $dto->cardExpMonth = $input->getOption('cardExpMonth');
        $dto->cardExpYear = $input->getOption('cardExpYear');
        $dto->cardCvv = $input->getOption('cardCvv');

        $violations = $this->validator->validate($dto);
        if ($violations->count() > 0) {
            foreach ($violations as $violation) {
                $io->error(sprintf('%s: %s', $violation->getPropertyPath(), $violation->getMessage()));
            }
            return Command::FAILURE;
        }

        // Check card expiration
        if ($this->paymentGatewayService->isCardExpired(
            (int) $dto->cardExpMonth,
            (int) $dto->cardExpYear)
        ) {
            $io->error('The card has expired.');
            return Command::FAILURE;
        }

        try {
            $gateway = $this->gatewayFactory->get($gatewayName);
            $response = $gateway->processPayment($dto);
        } catch (\Throwable $e) {
            $io->error(sprintf('Error: %s', $e->getMessage()));
            return Command::FAILURE;
        }

        // Display success
        $io->success('Payment processed successfully');
        $io->table(
            ['Field', 'Value'],
            [
                ['Transaction ID', $response->getTransactionId()],
                ['Amount', $response->getAmount()],
                ['Currency', $response->getCurrency()],
                ['Created At', $response->getCreatedAt()],
                ['Card BIN', $response->getCardBin()],
            ]
        );

        return Command::SUCCESS;
    }
}