<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use DateTime;
use App\Client\AciClient;
use App\DTO\AcquirerResponseDto;
use App\DTO\CardTransactionRequestDto;

class AciGateway implements AcquirerInterface
{
    public function __construct(
        private readonly AciClient $client,
    ) {
    }

    public static function getAcquirerName(): string
    {
        return 'aci';
    }

    public function authorizeAndCapture(CardTransactionRequestDto $dto): AcquirerResponseDto
    {
        $paymentData = [
            'amount' => $dto->amount,
            'currency' => $dto->currency,
            'card.expiryMonth' => $dto->cardExpMonth,
            'card.expiryYear' => $dto->cardExpYear,
            'card.cvv' => $dto->cardCvv,
            'card.number' => '4200000000000000', // hardcoded as stated by the task
            'card.holder' => 'John Doe',
            'paymentType' => 'PA',
            'paymentBrand' => 'VISA',
        ];

        $response = $this->client->createPayment($paymentData);

        $dateCreated = DateTime::createFromFormat('Y-m-d H:i:s.uO', $response['timestamp']);

        return new AcquirerResponseDto(
            currency: $response['currency'],
            transactionId: $response['id'],
            createdAt: $dateCreated->format('Y-m-d H:i:s'),
            amount: (float) $response['amount'],
            cardBin: $response['card']['bin']
        );
    }
}