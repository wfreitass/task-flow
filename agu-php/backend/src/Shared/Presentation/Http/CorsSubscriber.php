<?php

namespace App\Shared\Presentation\Http;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Adds restrictive CORS headers for configured frontend origins on API routes. */
final class CorsSubscriber implements EventSubscriberInterface
{
    /** @param list<string> $allowedOrigins */
    public function __construct(private readonly array $allowedOrigins)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1000],
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest() || !$this->isApiRequest($request->getPathInfo()) || !$request->isMethod('OPTIONS')) {
            return;
        }

        $origin = $request->headers->get('Origin');
        if ($origin === null || !\in_array($origin, $this->allowedOrigins, true)) {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->addCorsHeaders($response, $origin);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isApiRequest($request->getPathInfo())) {
            return;
        }

        $origin = $request->headers->get('Origin');
        if ($origin === null || !\in_array($origin, $this->allowedOrigins, true)) {
            return;
        }

        $this->addCorsHeaders($event->getResponse(), $origin);
    }

    private function addCorsHeaders(Response $response, string $origin): void
    {
        if ($response->headers->get('Access-Control-Allow-Origin') === $origin) {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Max-Age', '600');
        $response->headers->set('Vary', 'Origin', false);
    }

    private function isApiRequest(string $path): bool
    {
        return $path === '/api' || \str_starts_with($path, '/api/');
    }
}
