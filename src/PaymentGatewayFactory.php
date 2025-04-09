<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

use App\PaymentGateway\AciGateway;
use App\PaymentGateway\Shift4Gateway;
use App\PaymentGateway\PaymentGatewayInterface;

class PaymentGatewayFactory
{
    public function __construct(
        private readonly AciGateway $aciGateway,
        private readonly Shift4Gateway $shift4Gateway,
    ) {
    }

    public function get(string $name): PaymentGatewayInterface
    {
        return match (strtolower($name)) {
            'shift4' => $this->shift4Gateway,
            'aci' => $this->aciGateway,
            default => throw new InvalidArgumentException("Unsupported payment gateway {$name}"),
        };
    }
}