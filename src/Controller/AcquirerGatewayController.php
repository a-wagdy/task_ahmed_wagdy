<?php

namespace App\Controller;

use OpenApi\Attributes as OA;
use App\AcquirerGatewayFactory;
use App\DTO\AcquirerResponseDto;
use App\DTO\CardTransactionRequestDto;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\PaymentGateway\CardUtilsService;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class AcquirerGatewayController extends AbstractController
{
    public function __construct(
        private readonly CardUtilsService $cardUtilsService,
        private readonly AcquirerGatewayFactory $gatewayFactory,
    ) {
    }

    #[OA\Tag(name: 'Payment')]
    #[Route('/payment/gateway/{acquirer}', name: 'app_payment_gateway', methods: ['POST'])]
    #[OA\Post(
        path: '/payment/gateway/{acquirer}',
        summary: 'Process a payment using Shift4 or ACI',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CardTransactionRequestDto::class))
        ),
        parameters: [
            new OA\Parameter(
                name: 'acquirer',
                description: 'Acquirer name (e.g., shift4, aci)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '',
                content: new OA\JsonContent(ref: new Model(type: AcquirerResponseDto::class))
            ),
            new OA\Response(
                response: 422,
                description: '',
            ),
        ]
    )]
    public function index(
        string $acquirer,
        #[MapRequestPayload]
        CardTransactionRequestDto $paymentRequest
    ): JsonResponse {
        try {
            $paymentGateway = $this->gatewayFactory->get($acquirer);

            if ($this->cardUtilsService->isCardExpired(
                (int) $paymentRequest->cardExpMonth,
                (int) $paymentRequest->cardExpYear)
            ) {
                return new JsonResponse([
                    'errors' => 'The card has expired'
                ], Response::HTTP_BAD_REQUEST);
            }

            $dto = $paymentGateway->authorizeAndCapture($paymentRequest);

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
