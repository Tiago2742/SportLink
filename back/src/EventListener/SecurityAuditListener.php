<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

#[AsEventListener(event: LoginFailureEvent::class, method: 'onLoginFailure')]
#[AsEventListener(event: KernelEvents::EXCEPTION, method: 'onKernelException')]
class SecurityAuditListener
{
    public function __construct(
        #[Autowire(service: 'monolog.logger.security')]
        private LoggerInterface $logger,
    ) {}

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request    = $event->getRequest();
        $exception  = $event->getException();
        $passport   = $event->getPassport();
        $identifier = $passport?->getBadge(UserBadge::class)?->getUserIdentifier() ?? '';

        $context = [
            'ip'         => $request->getClientIp(),
            'email_hash' => $identifier !== '' ? hash('sha256', strtolower($identifier)) : null,
        ];

        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $this->logger->warning('rate_limiting_triggered', $context);
        } else {
            $this->logger->warning('login_failed', $context);
        }
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        if (!$event->getThrowable() instanceof AccessDeniedException) {
            return;
        }
        $request = $event->getRequest();
        $this->logger->warning('access_denied', [
            'ip'     => $request->getClientIp(),
            'path'   => $request->getPathInfo(),
            'method' => $request->getMethod(),
        ]);
    }
}
