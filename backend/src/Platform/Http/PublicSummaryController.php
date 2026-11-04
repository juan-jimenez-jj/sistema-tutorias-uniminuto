<?php

declare(strict_types=1);

namespace Tutorias\Platform\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Shared\Application\Cache;
use Tutorias\Shared\Infrastructure\Http\Json;

/** Cifras públicas y agregadas para la portada (no expone datos personales). */
final class PublicSummaryController
{
    public function __construct(private readonly \PDO $pdo, private readonly Cache $cache)
    {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $data = $this->cache->remember('publico.resumen', 300, fn () => [
            'materias' => (int) $this->pdo->query("SELECT COUNT(*) FROM materias WHERE estado = 'activa'")->fetchColumn(),
            'docentes' => (int) $this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'docente' AND estado = 'activo'")->fetchColumn(),
            'tutoriasCompletadas' => (int) $this->pdo->query("SELECT COUNT(*) FROM tutorias WHERE estado = 'completada'")->fetchColumn(),
            'satisfaccion' => (float) ($this->pdo->query('SELECT ROUND(AVG(calificacion), 1) FROM tutorias WHERE calificacion IS NOT NULL')->fetchColumn() ?: 0),
        ]);

        return Json::write($response, $data)->withHeader('Cache-Control', 'public, max-age=300');
    }
}
