<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Notifications\Application\NotificationStore;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

final class NotificationController
{
    public function __construct(private readonly NotificationStore $store)
    {
    }

    public function list(Request $request, Response $response): Response
    {
        $limit = (int) ($request->getQueryParams()['limit'] ?? 20);

        return Json::write($response, $this->store->forUser(RequestContext::actor($request)->id, $limit));
    }

    /** @param array<string, string> $args */
    public function markRead(Request $request, Response $response, array $args): Response
    {
        $this->store->markRead(RequestContext::actor($request)->id, RequestContext::intArg($args, 'id'));

        return $response->withStatus(204);
    }

    public function markAllRead(Request $request, Response $response): Response
    {
        $this->store->markAllRead(RequestContext::actor($request)->id);

        return $response->withStatus(204);
    }
}
