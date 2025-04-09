<?php

declare(strict_types=1);

namespace App\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AciClient
{
    private const API_URL = 'https://eu-test.oppwa.com/v1';
    private const AUTH_KEY = 'OGFjN2E0Yzc5Mzk0YmRjODAxOTM5NzM2ZjFhNzA2NDF8enlac1lYckc4QXk6bjYzI1NHNng=';
    private const ENTITY_ID = '8ac7a4c79394bdc801939736f17e063d';

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function createPayment(array $paymentData): array
    {
        $response = $this->client->request('POST', self::API_URL . '/payments', [
            'headers' => [
                'Authorization' => 'Bearer ' . self::AUTH_KEY,
            ],
            'body' => array_merge($paymentData, [
                'entityId' => self::ENTITY_ID,
            ]),
        ]);

        return $response->toArray();
    }
}