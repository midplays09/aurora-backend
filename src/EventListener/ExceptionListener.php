<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Catches all exceptions and returns clean JSON error responses.
 * Prevents leaking stack traces or internal details to the client.
 */
#[AsEventListener(event: 'kernel.exception', priority: 0)]
class ExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $statusCode = $exception instanceof HttpExceptionInterface
            ? $exception->getStatusCode()
            : 500;

        $message = $statusCode === 500
            ? 'An internal server error occurred.'
            : $exception->getMessage();

        $response = new JsonResponse([
            'error' => $message,
            'code' => $statusCode,
        ], $statusCode);

        $response->headers->set('Content-Type', 'application/json');

        $event->setResponse($response);
    }
}
