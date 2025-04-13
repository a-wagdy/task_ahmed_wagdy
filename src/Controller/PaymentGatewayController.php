<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\PaymentGatewayFactory;
use App\DTO\PaymentGatewayInputDto;
use App\DTO\PaymentGatewayResponseDto;
use Nelmio\ApiDocBundle\Attribute\Model;
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

    #[OA\Tag(name: 'Payment')]
    #[Route('/payment/gateway/{gateway}', name: 'app_payment_gateway', methods: ['POST'])]
    #[OA\Post(
        path: '/payment/gateway/{gateway}',
        summary: 'Process a payment using Shift4 or ACI',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: PaymentGatewayInputDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'gateway',
                description: 'Payment gateway name (e.g., shift4, aci)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '',
                content: new OA\JsonContent(ref: new Model(type: PaymentGatewayResponseDto::class))
            ),
            new OA\Response(
                response: 422,
                description: '',
            ),
        ]
    )]
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
