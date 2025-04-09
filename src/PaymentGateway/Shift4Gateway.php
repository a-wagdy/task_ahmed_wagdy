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
            amount: (string) $chargeData['amount'],
            cardBin: $chargeData['card']['first6']
        );
    }

    /**
     * @param PaymentGatewayInputDto $dto
     * @return string
     * @throws PaymentProcessingException
     */
    private function generateCardToken(PaymentGatewayInputDto $dto): string
    {
        $tokenData = $this->client->createToken([
            'number' => '4242424242424242',
            'expMonth' => $dto->cardExpMonth,
            'expYear' => $dto->cardExpYear,
            'cvc' => $dto->cardCvv,
        ]);

        $token = $tokenData['id'] ?? null;

        if (!$token) {
            throw new PaymentProcessingException('Token creation failed');
        }

        return $token;
    }

    /**
     * @param PaymentGatewayInputDto $dto
     * @param string $token
     * @return array
     * @throws PaymentProcessingException
     */
    private function chargeCard(PaymentGatewayInputDto $dto, string $token): array
    {
        $chargeData = $this->client->createCharge([
            'amount' => $dto->amount * 100,
            'currency' => $dto->currency,
            'card' => $token,
        ]);

        if (isset($chargeData['error']) || $chargeData['status'] !== 'successful') {
            throw new PaymentProcessingException('Charge creation failed: ' . $chargeData['error']['message']);
        }

        return $chargeData;
    }
}