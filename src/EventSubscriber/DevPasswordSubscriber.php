<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

final readonly class DevPasswordSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private string $devPassword,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 10]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ('' === $this->devPassword || '0' === $this->devPassword) {
            return;
        }

        $request = $event->getRequest();

        if (str_starts_with($request->getPathInfo(), '/dev-access')) {
            return;
        }

        if (true === $request->getSession()->get('dev_password_verified')) {
            return;
        }

        $request->getSession()->set('dev_password_intended', $request->getUri());

        $event->setResponse(new RedirectResponse($this->router->generate('dev_access_show')));
    }
}
