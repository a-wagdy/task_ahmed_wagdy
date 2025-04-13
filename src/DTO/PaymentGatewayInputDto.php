<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentGatewayInputDto
{
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Positive]
    #[Assert\Regex(
        pattern: '/^[0-9]{1,7}(\.[0-9]{2})?$/',
        message: 'The value must have 1-7 digits before the decimal point and exactly 2 digits after if a decimal point is present'
    )]
    public string $amount;

    #[Assert\NotBlank]
    #[Assert\Currency]
    public string $currency;

    #[Assert\NotBlank]
    #[Assert\Length(16)]
    public string $cardNumber;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{4}$/',
        message: 'The value must be 4 digits'
    )]
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
    public string $cardExpMonth;

    #[Assert\NotBlank]
    #[Assert\Regex(
        pattern: '/^\d{3}$/',
        message: 'The value must be 3 digits'
    )]
    public string $cardCvv;
}