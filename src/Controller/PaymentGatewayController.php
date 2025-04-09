<?php

namespace App\Controller;

use App\DTO\PaymentGatewayInputDto;
use App\PaymentGateway\AciGateway;
use App\PaymentGateway\Shift4Gateway;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PaymentGatewayController extends AbstractController
{
    public function __construct(
        private readonly AciGateway $aciGateway,
        private readonly Shift4Gateway $shift4Gateway,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[
        Route('/payment/gateway/{gateway}',
        name: 'app_payment_gateway',
        requirements: ['gateway' => 'aci|shift4'],
        methods: ['POST'])
    ]
    public function index(string $gateway, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $paymentRequest = new PaymentGatewayInputDto();
        $paymentRequest->amount = $data['amount'] ?? null;
        $paymentRequest->currency = $data['currency'] ?? null;
        $paymentRequest->cardNumber = $data['cardNumber'] ?? null;
        $paymentRequest->cardExpYear = $data['cardExpYear'] ?? null;
        $paymentRequest->cardExpMonth = $data['cardExpMonth'] ?? null;
        $paymentRequest->cardCvv = $data['cardCvv'] ?? null;

        $errors = $this->validator->validate($paymentRequest);

        if (count($errors) > 0) {
            return $this->validationErrorResponse($errors);
        }

        if ($this->isCardExpired($paymentRequest->cardExpMonth, $paymentRequest->cardExpYear)) {
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

    private function validationErrorResponse($errors): JsonResponse
    {
        $errorMessages = [];
        foreach ($errors as $error) {
            $errorMessages[$error->getPropertyPath()] = $error->getMessage();
        }

        return new JsonResponse([
            'code' => 'VALIDATION_ERROR',
            'errors' => $errorMessages
        ], Response::HTTP_BAD_REQUEST);
    }

    private function isCardExpired(int $month, int $year): bool
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        return $year < $currentYear || ($year === $currentYear && $month < $currentMonth);
    }
}
