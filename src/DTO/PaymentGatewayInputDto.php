<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentGatewayInputDto
{
    #[Assert\NotBlank]
    #[Assert\Type('float')]
    #[Assert\Positive]
    #[Assert\Regex(
        pattern: '/^\d+(\.\d{1,2})?$/',
        message: 'The amount can have at most 2 digits after the decimal point.'
    )]
    public float $amount;

    #[Assert\NotBlank]
    #[Assert\Currency]
    public string $currency;

    #[Assert\NotBlank]
    #[Assert\Length(
        min: 13,
        max: 19,
    )]
    public string $cardNumber;

    #[Assert\NotBlank]
    public string $cardExpYear;

    #[Assert\NotBlank]
    #[Assert\Length(2)]
    #[Assert\Range(
        min: 1,
        max: 12
    )]
    public string $cardExpMonth;

    #[Assert\NotBlank]
    #[Assert\Length(3)]
    public string $cardCvv;
}