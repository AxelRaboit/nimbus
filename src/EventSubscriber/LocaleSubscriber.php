<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Enum\LocaleEnum;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LocaleSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $locale = $request->getSession()->get('_locale', LocaleEnum::default()->value);

        if (!LocaleEnum::isSupported($locale)) {
            $locale = LocaleEnum::default()->value;
        }

        $request->setLocale($locale);
    }
}
