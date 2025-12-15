<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;

class AccessDeniedRedirectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private Security $security,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $exception = $event->getThrowable();

        $isAccessDenied =
            $exception instanceof AccessDeniedHttpException ||
            $exception instanceof AccessDeniedException ||
            ($exception->getPrevious() instanceof AccessDeniedException);

        if (!$isAccessDenied) {
            return;
        }

        // Don't redirect API / JSON requests (keep 403 JSON)
        $path = (string) $request->getPathInfo();
        $accept = (string) $request->headers->get('Accept', '');
        if (str_starts_with($path, '/api') || str_contains($accept, 'application/json') || $request->isXmlHttpRequest()) {
            return;
        }

        // Prevent redirect loops
        $route = $request->attributes->get('_route');
        if (in_array($route, ['app_login', 'app_logout'], true)) {
            return;
        }

        // Always send user to login page on 403
        $url = $this->urlGenerator->generate('app_login');
        $event->setResponse(new RedirectResponse($url));
    }
}


