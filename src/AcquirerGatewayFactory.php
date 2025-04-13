<?php

declare(strict_types=1);

namespace App;

use InvalidArgumentException;

use App\PaymentGateway\AcquirerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;

class AcquirerGatewayFactory
{
    public function __construct(
        #[TaggedLocator(tag: 'app.acquirer_gateway', defaultIndexMethod: 'getAcquirerName')]
        private readonly ServiceLocator $locator,
    ) {
    }

    public function get(string $name): AcquirerInterface
    {
        $name = strtolower($name);

        if (!$this->locator->has($name)) {
            throw new InvalidArgumentException("Unsupported payment gateway: {$name}");
        }

        return $this->locator->get($name);
    }
}