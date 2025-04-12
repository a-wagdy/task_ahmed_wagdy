<?php

declare(strict_types=1);

namespace App\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class AciClient
{
    private const API_URL = 'https://eu-test.oppwa.com/v1';

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function createPayment(array $paymentData): array
    {
        $response = $this->client->request('POST', self::API_URL . '/payments', [
            'headers' => [
                'Authorization' => 'Bearer ' . ($_ENV['ACI_AUTH_KEY'] ?? ''),
            ],
            'body' => array_merge($paymentData, [
                'entityId' => $_ENV['ACI_ENTITY_ID'] ?? '',
            ]),
        ]);

        return $response->toArray();
    }
}