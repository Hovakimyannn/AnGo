<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Security;

final class AdminExceptionLoggerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ?Security $security = null,
    ) {}

    public static function getSubscribedEvents(): array
    {
        // Run late; we only want to log once the exception is decided.
        return [
            KernelEvents::EXCEPTION => ['onKernelException', -255],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $path = (string) $request->getPathInfo();
        if (!str_starts_with($path, '/admin')) {
            return;
        }

        $e = $event->getThrowable();

        $user = $this->security?->getUser();
        $userId = null;
        if ($user && method_exists($user, 'getUserIdentifier')) {
            $userId = (string) $user->getUserIdentifier();
        }

        $route = (string) ($request->attributes->get('_route') ?? '');
        $rid = (string) ($request->headers->get('x-railway-request-id') ?? '');

        error_log(sprintf(
            '[AdminException] method=%s path=%s route=%s user=%s railway_request_id=%s exception=%s message=%s',
            $request->getMethod(),
            $path,
            $route,
            $userId ?: '-',
            $rid ?: '-',
            $e::class,
            $e->getMessage()
        ));

        // Stack trace (shows up in Railway logs) — helpful to quickly pinpoint the exact file/line.
        error_log((string) $e);
    }
}


