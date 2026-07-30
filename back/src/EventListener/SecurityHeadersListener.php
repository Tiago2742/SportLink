<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE)]
class SecurityHeadersListener
{
    public function __construct(
        #[Autowire(env: 'APP_ENV')]
        private string $appEnv,
    ) {}

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Content-Security-Policy', "frame-ancestors 'none'");
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS uniquement en prod (HTTPS requis — dangereux en dev HTTP)
        if ($this->appEnv === 'prod') {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
    }
}
