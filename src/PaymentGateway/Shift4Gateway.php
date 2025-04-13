<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use DateTime;
use App\Client\Shift4Client;
use App\DTO\AcquirerResponseDto;
use App\DTO\CardTransactionRequestDto;

class Shift4Gateway implements AcquirerInterface
{
    public function __construct(
        private readonly Shift4Client $client,
    ) {
    }

    public static function getAcquirerName(): string
    {
        return 'shift4';
    }

    public function authorizeAndCapture(CardTransactionRequestDto $dto): AcquirerResponseDto
    {
        $token = $this->authorizeCard($dto);
        $chargeData = $this->capturePayment($dto, $token);

        $dateCreated = (new DateTime())->setTimestamp($chargeData['created']);

        return new AcquirerResponseDto(
            currency: $chargeData['currency'],
            transactionId: $chargeData['id'],
            createdAt: $dateCreated->format('Y-m-d H:i:s'),
            amount: $chargeData['amount'] > 0 ? $chargeData['amount'] / 100 : $chargeData['amount'],
            cardBin: $chargeData['card']['first6']
        );
    }

    private function authorizeCard(CardTransactionRequestDto $dto): string
    {
        $tokenData = $this->client->createToken([
            'number' => '4242424242424242', // hardcoded as stated by the task
            'expMonth' => $dto->cardExpMonth,
            'expYear' => $dto->cardExpYear,
            'cvc' => $dto->cardCvv,
        ]);

        return $tokenData['id'];
    }

    private function capturePayment(CardTransactionRequestDto $dto, string $token): array
    {
        $amount = (int) $dto->amount;

        return $this->client->createCharge([
            'amount' => $amount * 100,
            'currency' => $dto->currency,
            'card' => $token,
        ]);
    }
}