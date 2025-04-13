<?php

declare(strict_types=1);

namespace App\Client;

use App\Exception\PaymentProcessingException;
use Symfony\Contracts\HttpClient\ResponseInterface;
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

        return $this->extractDataFromResponse($response);
    }

    private function extractDataFromResponse(ResponseInterface $response): array
    {
        $content = $response->getContent(false);

        $data = json_decode($content, true);

        if (isset($data['result']['parameterErrors'])) {
            throw new PaymentProcessingException($data['result']['parameterErrors'][0]['name'] . ': ' . $data['result']['parameterErrors'][0]['message']);
        }

        return $data;
    }
}