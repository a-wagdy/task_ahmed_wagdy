<?php

namespace App\Tests\Controller;

use App\PaymentGateway\AciGateway;
use App\DTO\PaymentGatewayResponseDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PaymentGatewayControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private AciGateway $aciGatewayMock;

    protected static function getKernelClass(): string
    {
        return \App\Kernel::class;
    }

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->aciGatewayMock = $this->createMock(AciGateway::class);
        self::getContainer()->set(AciGateway::class, $this->aciGatewayMock);
    }

    public function testSuccessfulPayment(): void
    {
        $mockResponse = new PaymentGatewayResponseDto(
            currency: 'USD',
            transactionId: 'tx123',
            createdAt: '2024-04-12 10:00:00',
            amount: '100.00',
            cardBin: '420000'
        );

        $this->aciGatewayMock
            ->expects($this->once())
            ->method('processPayment')
            ->willReturn($mockResponse)
        ;

        $this->callApiEndpoint('aci', [
            'amount' => 100.00,
            'currency' => 'USD',
            'cardNumber' => '4200000000000000',
            'cardExpYear' => date('Y', strtotime('+1 year')),
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('tx123', $data['transactionId']);
        $this->assertSame('100.00', $data['amount']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('420000', $data['cardBin']);
    }

    public function testValidationErrorForInvalidAmount(): void
    {
        $this->callApiEndpoint('shift4', [
            'amount' => 123.123,
            'currency' => 'USD',
            'cardNumber' => '4200000000000000',
            'cardExpYear' => date('Y', strtotime('+1 year')),
            'cardExpMonth' => '12',
            'cardCvv' => '123',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $data);
        $this->assertArrayHasKey('amount', $data['errors']);
    }

    public function testCallUnsupportedGateway(): void
    {
        $this->callApiEndpoint('unsupported', [
            'amount' => 100.00,
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
            'amount' => 100.00,
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
