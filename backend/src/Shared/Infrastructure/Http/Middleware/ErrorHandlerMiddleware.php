<?php

declare(strict_types=1);

namespace Tutorias\Shared\Infrastructure\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Tutorias\Shared\Domain\BusinessRuleViolation;
use Tutorias\Shared\Domain\Conflict;
use Tutorias\Shared\Domain\DomainError;
use Tutorias\Shared\Domain\Forbidden;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\TooManyRequests;
use Tutorias\Shared\Domain\Unauthorized;
use Tutorias\Shared\Domain\ValidationError;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

/**
 * Traduce errores del dominio a respuestas RFC 7807 (application/problem+json).
 * Los errores inesperados se registran con su traza pero nunca se exponen al cliente.
 */
final class ErrorHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactoryInterface $responses,
        private readonly LoggerInterface $logger,
        private readonly bool $exposeDetails = false,
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (DomainError $e) {
            [$status, $title] = match (true) {
                $e instanceof ValidationError => [422, 'Datos inválidos'],
                $e instanceof NotFound => [404, 'No encontrado'],
                $e instanceof Unauthorized => [401, 'No autenticado'],
                $e instanceof Forbidden => [403, 'Acceso denegado'],
                $e instanceof Conflict => [409, 'Conflicto'],
                $e instanceof TooManyRequests => [429, 'Demasiados intentos'],
                $e instanceof BusinessRuleViolation => [422, 'Regla de negocio incumplida'],
                default => [400, 'Solicitud inválida'],
            };
            $extra = $e instanceof ValidationError ? ['errors' => $e->fieldErrors()] : [];
            $response = Json::problem($this->responses->createResponse(), $status, $title, $e->errorCode(), $e->getMessage(), $extra);
            if ($e instanceof TooManyRequests) {
                $response = $response->withHeader('Retry-After', (string) ($e->context()['retry_after'] ?? 60));
            }

            return $response;
        } catch (HttpNotFoundException) {
            return Json::problem($this->responses->createResponse(), 404, 'No encontrado', 'route_not_found', 'La ruta solicitada no existe.');
        } catch (HttpMethodNotAllowedException) {
            return Json::problem($this->responses->createResponse(), 405, 'Método no permitido', 'method_not_allowed', 'El método HTTP no está permitido para esta ruta.');
        } catch (\JsonException) {
            return Json::problem($this->responses->createResponse(), 400, 'JSON inválido', 'invalid_json', 'El cuerpo de la petición no es JSON válido.');
        } catch (\Throwable $e) {
            $this->logger->error('error_no_controlado', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'path' => $request->getUri()->getPath(),
            ]);
            $detail = $this->exposeDetails ? $e->getMessage() : 'Ocurrió un error inesperado. Si persiste, reporta el código de petición.';

            return Json::problem($this->responses->createResponse(), 500, 'Error interno', 'internal_error', $detail, [
                'request_id' => $request->getAttribute(RequestContext::REQUEST_ID),
            ]);
        }
    }
}
