<?php

namespace App\Tests\Controller;

use App\PaymentGateway\AciGateway;
use App\DTO\AcquirerResponseDto;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use App\PaymentGateway\AcquirerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AcquirerGatewayControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AcquirerInterface|MockObject $acquirerGatewayMock;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->acquirerGatewayMock = $this->createMock(AcquirerInterface::class);
        self::getContainer()->set(AciGateway::class, $this->acquirerGatewayMock);
    }

    public function testSuccessfulPayment(): void
    {
        $mockResponse = new AcquirerResponseDto(
            currency: 'USD',
            transactionId: 'tx123',
            createdAt: '2024-04-12 10:00:00',
            amount: '100.00',
            cardBin: '420000'
        );

        $this->acquirerGatewayMock
            ->expects($this->once())
            ->method('authorizeAndCapture')
            ->willReturn($mockResponse)
        ;

        $this->callApiEndpoint('aci', [
            'amount' => '100.00',
            'currency' => 'USD',
            'cardNumber' => '4200000000000000',
            'cardExpYear' => date('Y', strtotime('+1 year')),
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('tx123', $data['transactionId']);
        $this->assertSame(100, $data['amount']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('420000', $data['cardBin']);
    }

    /**
     * @dataProvider invalidPayloadProvider
     */
    public function testValidationErrors(array $payload, array $expectedErrors): void
    {
        $this->callApiEndpoint('aci', $payload);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);

        foreach ($expectedErrors as $field => $errorMessage) {
            $this->assertArrayHasKey($field, $data['errors']);
            $this->assertStringContainsString($errorMessage, $data['errors'][$field]);
        }
    }

    public function invalidPayloadProvider(): \Generator
    {
        yield 'Invalid amount (too many decimals)' => [
            [
                'amount' => '123.123',
                'currency' => 'USD',
                'cardNumber' => '4200000000000000',
                'cardExpYear' => date('Y', strtotime('+1 year')),
                'cardExpMonth' => '12',
                'cardCvv' => '123',
            ],
            ['amount' => 'The value must have 1-7 digits before the decimal point and exactly 2 digits after if a decimal point is present']
        ];

        yield 'Negative amount' => [
            [
                'amount' => '-100.00',
                'currency' => 'USD',
                'cardNumber' => '4200000000000000',
                'cardExpYear' => date('Y', strtotime('+1 year')),
                'cardExpMonth' => '12',
                'cardCvv' => '123',
            ],
            ['amount' => 'This value should be positive']
        ];

        yield 'Invalid currency' => [
            [
                'amount' => '100.00',
                'currency' => 'AAA',
                'cardNumber' => '4200000000000000',
                'cardExpYear' => date('Y', strtotime('+1 year')),
                'cardExpMonth' => '12',
                'cardCvv' => '123',
            ],
            ['currency' => 'This value is not a valid currency']
        ];

        yield 'Invalid card number length' => [
            [
                'amount' => '100.00',
                'currency' => 'USD',
                'cardNumber' => '420000000000',
                'cardExpYear' => date('Y', strtotime('+1 year')),
                'cardExpMonth' => '12',
                'cardCvv' => '123',
            ],
            ['cardNumber' => 'Invalid card number']
        ];

        yield 'Invalid CVV format' => [
            [
                'amount' => '100.00',
                'currency' => 'USD',
                'cardNumber' => '4200000000000000',
                'cardExpYear' => date('Y', strtotime('+1 year')),
                'cardExpMonth' => '12',
                'cardCvv' => '12',
            ],
            ['cardCvv' => 'The value must be 3 digits']
        ];

        yield 'Invalid expiration month' => [
            [
                'amount' => '100.00',
                'currency' => 'USD',
                'cardNumber' => '4200000000000000',
                'cardExpYear' => date('Y', strtotime('+1 year')),
                'cardExpMonth' => '13',
                'cardCvv' => '123',
            ],
            ['cardExpMonth' => 'This value should be between 1 and 12']
        ];
    }

    public function testCallUnsupportedGateway(): void
    {
        $this->callApiEndpoint('unsupported', [
            'amount' => '100.00',
            'currency' => 'USD',
            'cardNumber' => '4200000000000000',
            'cardExpYear' => date('Y', strtotime('+1 year')),
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Unsupported payment gateway: unsupported', $data['errors']);
    }

    public function testExpiredCard(): void
    {
        $this->callApiEndpoint('aci', [
            'amount' => '100.00',
            'currency' => 'USD',
            'cardNumber' => '4200000000000000',
            'cardExpYear' => date('Y', strtotime('-1 year')),
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertSame('The card has expired', $data['errors']);
    }

    public function testMissingRequiredFields(): void
    {
        $this->callApiEndpoint('aci');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('amount', $data['errors']);
        $this->assertArrayHasKey('cardCvv', $data['errors']);
        $this->assertArrayHasKey('currency', $data['errors']);
        $this->assertArrayHasKey('cardNumber', $data['errors']);
        $this->assertArrayHasKey('cardExpYear', $data['errors']);
        $this->assertArrayHasKey('cardExpMonth', $data['errors']);
    }

    public function testInvalidJsonPayload(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/payment/gateway/aci',
            server: [
                'CONTENT_TYPE' => 'application/json'
            ],
            content: '{"amount": 100.00, "currency": "USD"' // Missing bracket
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('errors', $data);

        $this->assertSame('Request payload contains invalid "json" data.', $data['errors']);
    }

    private function callApiEndpoint(string $paymentGateway, array $payload = []): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/payment/gateway/' . $paymentGateway,
            server: [
                'CONTENT_TYPE' => 'application/json'
            ],
            content: json_encode($payload)
        );
    }
}