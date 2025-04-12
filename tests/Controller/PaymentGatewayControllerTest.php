<?php

namespace App\Tests\Controller;

use App\PaymentGateway\AciGateway;
use App\DTO\PaymentGatewayResponseDto;
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

        $this->client->request(
            method: 'POST',
            uri: '/payment/gateway/aci',
            server: [
                'CONTENT_TYPE' => 'application/json'
            ], 
            content:
            json_encode([
                'amount' => 100.00,
                'currency' => 'USD',
                'cardNumber' => '4200000000000000',
                'cardExpYear' => date('Y', strtotime('+1 year')),
                'cardExpMonth' => '12',
                'cardCvv' => '123',
                ])
            );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('tx123', $data['transactionId']);
        $this->assertSame('100.00', $data['amount']);
        $this->assertSame('USD', $data['currency']);
        $this->assertSame('420000', $data['cardBin']);
    }
}
