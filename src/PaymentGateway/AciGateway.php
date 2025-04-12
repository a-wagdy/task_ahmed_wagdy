<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use DateTime;
use App\Client\AciClient;
use App\DTO\PaymentGatewayInputDto;
use App\DTO\PaymentGatewayResponseDto;
use App\Exception\PaymentProcessingException;

class AciGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly AciClient $client,
    ) {
    }

    public static function getPaymentGatewayName(): string
    {
        return 'aci';
    }

    public function processPayment(PaymentGatewayInputDto $dto): PaymentGatewayResponseDto
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

        if (isset($response['error'])) {
            throw new PaymentProcessingException($response['error']);
        }

        $dateCreated = DateTime::createFromFormat('Y-m-d H:i:s.uO', $response['timestamp']);

        return new PaymentGatewayResponseDto(
            currency: $response['currency'],
            transactionId: $response['id'],
            createdAt: $dateCreated->format('Y-m-d H:i:s'),
            amount: $response['amount'],
            cardBin: $response['card']['bin']
        );
    }
}