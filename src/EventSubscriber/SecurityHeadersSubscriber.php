<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds security headers to every response.
 */
final readonly class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $env,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', implode('; ', $this->buildCsp()));
    }

    private const string VITE_DEV_SERVER = 'http://127.0.0.1:5173';

    /** @return string[] */
    private function buildCsp(): array
    {
        $isDev = 'dev' === $this->env;
        $vite = self::VITE_DEV_SERVER;

        return [
            "default-src 'self'",
            $isDev
                ? "script-src 'self' 'unsafe-inline' ".$vite
                : "script-src 'self' 'unsafe-inline'",
            $isDev
                ? sprintf("style-src 'self' 'unsafe-inline' %s https://fonts.bunny.net", $vite)
                : "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            $isDev
                ? sprintf("font-src 'self' %s https://fonts.bunny.net", $vite)
                : "font-src 'self' https://fonts.bunny.net",
            $isDev
                ? sprintf("connect-src 'self' %s ws://127.0.0.1:5173", $vite)
                : "connect-src 'self'",
            "img-src 'self' data: blob:",
            "object-src 'none'",
            "frame-src 'self'",
            "worker-src 'self' blob:",
        ];
    }
}
