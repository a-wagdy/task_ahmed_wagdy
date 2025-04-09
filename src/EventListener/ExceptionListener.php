<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class ExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();
        $statusCode = $exception->getStatusCode();

        if ($request->getContentTypeFormat() === 'json' && $exception instanceof HttpExceptionInterface) {
            $throwable = $exception->getPrevious();
            if ($throwable instanceof ValidationFailedException) {
                $response = $this->handleValidationException($throwable, $statusCode);
            } else {
                $response = new JsonResponse([
                    'errors' => $exception->getMessage(),
                ], $statusCode);
            }

            $event->setResponse($response);
        }
    }

    /**
     * @param ValidationFailedException $validationErrors
     * @param $statusCode
     * @return JsonResponse
     */
    private function handleValidationException(ValidationFailedException $validationErrors, $statusCode): JsonResponse
    {
        $errors = [];
        foreach ($validationErrors->getViolations() as $error) {
            $errors[$error->getPropertyPath()] = $error->getMessage();
        }

        return new JsonResponse([
            'errors' => $errors,
        ], $statusCode);
    }
}