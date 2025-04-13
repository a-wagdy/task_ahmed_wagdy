<?php

declare(strict_types=1);

namespace App\PaymentGateway;

use App\DTO\CardTransactionRequestDto;
use App\DTO\AcquirerResponseDto;

interface AcquirerInterface
{
    public static function getAcquirerName(): string;
    public function authorizeAndCapture(CardTransactionRequestDto $dto): AcquirerResponseDto;
}