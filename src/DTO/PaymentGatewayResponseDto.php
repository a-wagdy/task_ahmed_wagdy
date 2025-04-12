<?php

declare(strict_types=1);

namespace App\DTO;

class PaymentGatewayResponseDto
{
    public function __construct(
        private readonly string $currency,
        private readonly string $transactionId,
        private readonly string $createdAt,
        private readonly float $amount,
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