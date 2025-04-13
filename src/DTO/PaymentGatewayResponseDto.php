<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PaymentGatewayResponseDto',
    required: ['transactionId', 'amount', 'currency', 'createdAt', 'cardBin']
)]
class PaymentGatewayResponseDto
{
    public function __construct(
        #[OA\Property(type: 'string', example: 'USD')]
        private readonly string $currency,
        #[OA\Property(type: 'string', example: 'txn_123456')]
        private readonly string $transactionId,
        #[OA\Property(type: 'string', example: '2024-04-13 15:30:45')]
        private readonly string $createdAt,
        #[OA\Property(type: 'float', example: 100.0)]
        private readonly float $amount,
        #[OA\Property(type: 'string', example: '420000')]
        private readonly string $cardBin
    ) {
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCardBin(): string
    {
        return $this->cardBin;
    }
}