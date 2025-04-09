<?php

namespace App\Controller;

use App\PaymentGateway\AciGateway;
use App\DTO\PaymentGatewayInputDto;
use App\PaymentGateway\Shift4Gateway;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class PaymentGatewayController extends AbstractController
{
    public function __construct(
        private readonly AciGateway $aciGateway,
        private readonly Shift4Gateway $shift4Gateway,
    ) {
    }

    #[
        Route('/payment/gateway/{gateway}',
        name: 'app_payment_gateway',
        requirements: ['gateway' => 'aci|shift4'],
        methods: ['POST'])
    ]
    public function index(
        string $gateway,
        #[MapRequestPayload]
        PaymentGatewayInputDto $paymentRequest
    ): JsonResponse {
        if ($this->isCardExpired((int) $paymentRequest->cardExpMonth, (int) $paymentRequest->cardExpYear)) {
            return new JsonResponse([
                'code' => 'CARD_EXPIRED',
                'message' => 'The card has expired'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $gatewayService = match ($gateway) {
                'shift4' => $this->shift4Gateway,
                'aci' => $this->aciGateway,
                default => throw $this->createNotFoundException('Gateway not found'),
            };

            $dto = $gatewayService->processPayment($paymentRequest);

            return new JsonResponse([
                'transactionId' => $dto->getTransactionId(),
                'amount' => $dto->getAmount(),
                'currency' => $dto->getCurrency(),
                'createdAt' => $dto->getCreatedAt(),
                'cardBin' => $dto->getCardBin(),
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    private function isCardExpired(int $month, int $year): bool
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        return $year < $currentYear || ($year === $currentYear && $month < $currentMonth);
    }
}
