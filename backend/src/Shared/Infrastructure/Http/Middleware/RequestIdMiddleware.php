<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Log\LoggerInterface;
use Tutorias\Shared\Infrastructure\Http\RequestContext;
use Tutorias\Shared\Infrastructure\Logging\RequestIdProcessor;

/** Asigna un X-Request-Id a cada petición y registra un access log estructurado con su latencia. */
final class RequestIdMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $incoming = $request->getHeaderLine('X-Request-Id');
        $requestId = preg_match('/^[a-zA-Z0-9-]{8,64}$/', $incoming) ? $incoming : bin2hex(random_bytes(8));
        RequestIdProcessor::set($requestId);
        $start = hrtime(true);

        $response = $handler->handle($request->withAttribute(RequestContext::REQUEST_ID, $requestId));

        $this->logger->info('http.request', [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'status' => $response->getStatusCode(),
            'duration_ms' => round((hrtime(true) - $start) / 1e6, 2),
        ]);

        return $response->withHeader('X-Request-Id', $requestId);
    }
}
