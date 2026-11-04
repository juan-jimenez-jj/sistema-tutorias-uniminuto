<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface as Response;

final class Json
{
    public static function write(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write((string) json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        ));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /** @param array<string, mixed> $extra */
    public static function problem(Response $response, int $status, string $title, string $code, string $detail, array $extra = []): Response
    {
        $response->getBody()->write((string) json_encode(
            ['type' => 'about:blank', 'title' => $title, 'status' => $status, 'code' => $code, 'detail' => $detail] + $extra,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));

        return $response->withStatus($status)->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
    }
}
