<?php

declare(strict_types=1);

namespace App\Client;

use App\Exception\PaymentProcessingException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

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

        return $this->extractDataFromResponse($response);
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

        return $this->extractDataFromResponse($response);
    }

    private function extractDataFromResponse(ResponseInterface $response): array
    {
        $content = $response->getContent(false);

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new PaymentProcessingException('Invalid JSON received from Shift4 acquirer');
        }

        if (isset($data['error'])) {
            throw new PaymentProcessingException($data['error']['message']);
        }

        return $data;
    }
}