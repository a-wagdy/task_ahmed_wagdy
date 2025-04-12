<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use App\DTO\PaymentGatewayInputDto;
use App\DTO\PaymentGatewayResponseDto;

interface PaymentGatewayInterface
{
    public static function getPaymentGatewayName(): string;
    public function processPayment(PaymentGatewayInputDto $dto): PaymentGatewayResponseDto;
}