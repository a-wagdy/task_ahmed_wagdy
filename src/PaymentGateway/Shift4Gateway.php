<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use DateTime;
use App\DTO\PaymentGatewayInputDto;
use App\DTO\PaymentGatewayResponseDto;
use App\Exception\PaymentProcessingException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Shift4Gateway implements PaymentGatewayInterface
{
    private const API_URL = 'https://api.shift4.com';
    private const AUTH_KEY = 'sk_test_QhvkbCgZGSjMymoKuckXQfRq';

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function processPayment(PaymentGatewayInputDto $dto): PaymentGatewayResponseDto
    {
        $response = $this->client->request('POST', self::API_URL . '/tokens', [
            'auth_basic' => [self::AUTH_KEY, ''],
            'body' => [
                'number' => '4242424242424242',
                'expMonth' => $dto->cardExpMonth,
                'expYear' => $dto->cardExpYear,
                'cvc' => $dto->cardCvv,
            ],
        ]);

        $tokenData = $response->toArray();
        $token = $tokenData['id'] ?? null;

        if (is_null($token)) {
            throw new PaymentProcessingException('Token creation failed');
        }

        $response = $this->client->request('POST', self::API_URL . '/charges', [
            'auth_basic' => [self::AUTH_KEY, ''],
            'body' => [
                'amount' => $dto->amount * 100,
                'currency' => $dto->currency,
                'card' => $token,
            ],
        ]);

        $chargeData = $response->toArray();

        if (isset($chargeData['error']) || $chargeData['status'] !== 'successful') {
            throw new PaymentProcessingException('Charge creation failed: ' . $chargeData['error']['message']);
        }

        $date = (new DateTime())->setTimestamp($chargeData['created']);

        return new PaymentGatewayResponseDto(
            currency: $chargeData['currency'],
            transactionId: $chargeData['id'],
            createdAt: $date->format('Y-m-d H:i:s'),
            amount: $chargeData['amount'],
            cardBin: $chargeData['card']['first6']
        );
    }
}