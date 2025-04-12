<?php

declare(strict_types=1);

namespace App\Client;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class Shift4Client
{
    private const API_URL = 'https://api.shift4.com';

    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function createToken(array $cardData): array
    {
        $response = $this->client->request('POST', self::API_URL . '/tokens', [
            'auth_basic' => [
                $_ENV['SHIFT4_AUTH_KEY'] ?? '',
                ''
            ],
            'body' => $cardData,
        ]);

        return $response->toArray();
    }

    public function createCharge(array $chargeData): array
    {
        $response = $this->client->request('POST', self::API_URL . '/charges', [
            'auth_basic' => [
                $_ENV['SHIFT4_AUTH_KEY'] ?? '',
                ''
            ],
            'body' => $chargeData,
        ]);

        return $response->toArray();
    }

}