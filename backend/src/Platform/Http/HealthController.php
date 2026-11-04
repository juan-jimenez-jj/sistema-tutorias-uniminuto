<?php

declare(strict_types=1);

namespace Tutorias\Platform\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Shared\Infrastructure\Http\Json;

/**
 * Health checks para el orquestador (Docker / Kubernetes) y el monitoreo:
 *  - /health        liveness: el proceso responde.
 *  - /health/ready  readiness: la base de datos responde y la outbox no está atascada.
 *  - /metrics       métricas en formato Prometheus.
 */
final class HealthController
{
    public function __construct(private readonly \PDO $pdo, private readonly string $version)
    {
    }

    public function live(Request $request, Response $response): Response
    {
        return Json::write($response, ['status' => 'ok', 'version' => $this->version]);
    }

    public function ready(Request $request, Response $response): Response
    {
        $checks = [];
        $healthy = true;
        $start = hrtime(true);
        try {
            $this->pdo->query('SELECT 1')->fetchColumn();
            $checks['database'] = ['status' => 'ok', 'latency_ms' => round((hrtime(true) - $start) / 1e6, 2)];
        } catch (\Throwable $e) {
            $healthy = false;
            $checks['database'] = ['status' => 'down', 'error' => 'No hay conexión con la base de datos'];
        }
        if ($healthy) {
            $stuck = (int) $this->pdo->query("SELECT COUNT(*) FROM outbox_eventos WHERE estado = 'pendiente' AND disponible_desde < NOW() - INTERVAL 10 MINUTE")->fetchColumn();
            $failed = (int) $this->pdo->query("SELECT COUNT(*) FROM outbox_eventos WHERE estado = 'fallido'")->fetchColumn();
            $checks['outbox'] = ['status' => $stuck > 50 ? 'degraded' : 'ok', 'atrasados' => $stuck, 'fallidos' => $failed];
        }

        return Json::write($response, ['status' => $healthy ? 'ok' : 'down', 'version' => $this->version, 'checks' => $checks], $healthy ? 200 : 503);
    }

    public function metrics(Request $request, Response $response): Response
    {
        $lines = [];
        $lines[] = '# HELP tutorias_total Tutorías por estado.';
        $lines[] = '# TYPE tutorias_total gauge';
        foreach ($this->pdo->query('SELECT estado, COUNT(*) AS c FROM tutorias GROUP BY estado')->fetchAll() as $r) {
            $lines[] = sprintf('tutorias_total{estado="%s"} %d', $r['estado'], $r['c']);
        }
        $lines[] = '# HELP outbox_eventos Eventos de la outbox por estado.';
        $lines[] = '# TYPE outbox_eventos gauge';
        foreach ($this->pdo->query('SELECT estado, COUNT(*) AS c FROM outbox_eventos GROUP BY estado')->fetchAll() as $r) {
            $lines[] = sprintf('outbox_eventos{estado="%s"} %d', $r['estado'], $r['c']);
        }
        $lines[] = '# HELP usuarios_activos Usuarios activos por rol.';
        $lines[] = '# TYPE usuarios_activos gauge';
        foreach ($this->pdo->query("SELECT rol, COUNT(*) AS c FROM usuarios WHERE estado = 'activo' GROUP BY rol")->fetchAll() as $r) {
            $lines[] = sprintf('usuarios_activos{rol="%s"} %d', $r['rol'], $r['c']);
        }
        $lines[] = '# HELP login_fallidos_15m Intentos de login fallidos en los últimos 15 minutos.';
        $lines[] = '# TYPE login_fallidos_15m gauge';
        $lines[] = 'login_fallidos_15m ' . (int) $this->pdo->query('SELECT COUNT(*) FROM intentos_login WHERE exitoso = 0 AND creado_en > NOW() - INTERVAL 15 MINUTE')->fetchColumn();

        $response->getBody()->write(implode("\n", $lines) . "\n");

        return $response->withHeader('Content-Type', 'text/plain; version=0.0.4');
    }
}
