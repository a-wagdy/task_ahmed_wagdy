<?php

namespace App\Controller;

use App\PaymentGatewayFactory;
use App\DTO\PaymentGatewayInputDto;
use App\PaymentGateway\PaymentGatewayService;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class PaymentGatewayController extends AbstractController
{
    public function __construct(
        private readonly PaymentGatewayFactory $gatewayFactory,
        private readonly PaymentGatewayService $paymentGatewayService,
    ) {
    }

    #[
        Route('/payment/gateway/{gateway}',
        name: 'app_payment_gateway',
        methods: ['POST'])
    ]
    public function index(
        string $gateway,
        #[MapRequestPayload]
        PaymentGatewayInputDto $paymentRequest
    ): JsonResponse {
        try {
            $paymentGateway = $this->gatewayFactory->get($gateway);

            if ($this->paymentGatewayService->isCardExpired(
                (int) $paymentRequest->cardExpMonth,
                (int) $paymentRequest->cardExpYear)
            ) {
                return new JsonResponse([
                    'errors' => 'The card has expired'
                ], Response::HTTP_BAD_REQUEST);
            }

            $dto = $paymentGateway->processPayment($paymentRequest);

            return new JsonResponse([
                'transactionId' => $dto->getTransactionId(),
                'amount' => $dto->getAmount(),
                'currency' => $dto->getCurrency(),
                'createdAt' => $dto->getCreatedAt(),
                'cardBin' => $dto->getCardBin(),
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse(['errors' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
