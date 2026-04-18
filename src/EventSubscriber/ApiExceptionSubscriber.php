<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\DisallowedFileTypeException;
use App\Exception\DisallowedZipContentException;
use App\Exception\FileLimitExceededException;
use App\Exception\PlanLimitException;
use App\Exception\SizeLimitExceededException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();

        if ($exception instanceof DisallowedZipContentException) {
            $event->setResponse(new JsonResponse(
                ['error' => 'zip_content_not_allowed', 'disallowed_files' => $exception->getDisallowedFiles()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            ));

            return;
        }

        $status = match ($exception::class) {
            FileLimitExceededException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            SizeLimitExceededException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            DisallowedFileTypeException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            PlanLimitException::class => Response::HTTP_UNPROCESSABLE_ENTITY,
            default => null,
        };

        if (null === $status) {
            return;
        }

        $event->setResponse(new JsonResponse(['error' => $exception->getMessage()], $status));
    }
}
