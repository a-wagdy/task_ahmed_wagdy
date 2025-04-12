<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

use App\PaymentGateway\PaymentGatewayInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;

class PaymentGatewayFactory
{
    public function __construct(
        #[TaggedLocator(tag: 'app.payment_gateway', defaultIndexMethod: 'getPaymentGatewayName')]
        private readonly ServiceLocator $locator,
    ) {
    }

    public function get(string $name): PaymentGatewayInterface
    {
        $name = strtolower($name);

        if (!$this->locator->has($name)) {
            throw new InvalidArgumentException("Unsupported payment gateway: {$name}");
        }

        return $this->locator->get($name);
    }
}