<?php

declare(strict_types=1);

namespace App\DTO;

use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CardTransactionRequestDto',
    title: 'Request payload',
    required: ['amount', 'currency', 'cardNumber', 'cardExpYear', 'cardExpMonth', 'cardCvv']
)]
class CardTransactionRequestDto
{
    #[Assert\Regex(
        pattern: '/^[0-9]{1,7}(\.[0-9]{2})?$/',
        message: 'The value must have 1-7 digits before the decimal point and exactly 2 digits after if a decimal point is present'
    )]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[OA\Property(type: 'string', example: '100.00')]
    public string $amount;

    #[Assert\NotBlank]
    #[Assert\Currency]
    #[OA\Property(type: 'string', example: 'USD')]
    public string $currency;

    #[Assert\NotBlank]
    #[Assert\Length(16, exactMessage: 'Invalid card number')]
    #[OA\Property(type: 'string', example: '4242424242424242')]
    public string $cardNumber;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{4}$/',
        message: 'The value must be 4 digits'
    )]
    #[OA\Property(type: 'string', example: '2028')]
    public string $cardExpYear;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{2}$/',
        message: 'The value must be 2 digits'
    )]
    #[Assert\Range(
        min: 1,
        max: 12
    )]
    #[OA\Property(type: 'string', example: '12')]
    public string $cardExpMonth;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{3}$/',
        message: 'The value must be 3 digits'
    )]
    #[OA\Property(type: 'string', example: '123')]
    public string $cardCvv;
}