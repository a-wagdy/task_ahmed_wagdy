<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use DateTime;
use App\Client\Shift4Client;
use App\DTO\PaymentGatewayInputDto;
use App\DTO\PaymentGatewayResponseDto;
use App\Exception\PaymentProcessingException;

class Shift4Gateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly Shift4Client $client,
    ) {
    }

    public static function getPaymentGatewayName(): string
    {
        return 'shift4';
    }

    /**
     * @throws PaymentProcessingException
     */
    public function processPayment(PaymentGatewayInputDto $dto): PaymentGatewayResponseDto
    {
        $token = $this->generateCardToken($dto);
        $chargeData = $this->chargeCard($dto, $token);

        $dateCreated = (new DateTime())->setTimestamp($chargeData['created']);

        return new PaymentGatewayResponseDto(
            currency: $chargeData['currency'],
            transactionId: $chargeData['id'],
            createdAt: $dateCreated->format('Y-m-d H:i:s'),
            amount: $chargeData['amount'] > 0 ? $chargeData['amount'] / 100 : $chargeData['amount'],
            cardBin: $chargeData['card']['first6']
        );
    }

    private function generateCardToken(PaymentGatewayInputDto $dto): string
    {
        $tokenData = $this->client->createToken([
            'number' => '4242424242424242', // hardcoded as stated by the task
            'expMonth' => $dto->cardExpMonth,
            'expYear' => $dto->cardExpYear,
            'cvc' => $dto->cardCvv,
        ]);

        return $tokenData['id'];
    }

    private function chargeCard(PaymentGatewayInputDto $dto, string $token): array
    {
        $amount = (int) $dto->amount;

        return $this->client->createCharge([
            'amount' => $amount * 100,
            'currency' => $dto->currency,
            'card' => $token,
        ]);
    }
}