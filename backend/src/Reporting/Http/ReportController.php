<?php

declare(strict_types=1);

namespace Tutorias\Reporting\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Reporting\Application\GenerateReports;
use Tutorias\Reporting\Application\ReportQueries;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Infrastructure\Http\Input;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

final class ReportController
{
    public function __construct(
        private readonly GenerateReports $reports,
        private readonly ReportQueries $queries,
        private readonly Clock $clock,
    ) {
    }

    public function dashboard(Request $request, Response $response): Response
    {
        [$from, $to] = $this->range($request);

        return Json::write($response, $this->reports->dashboard(RequestContext::actor($request), $from, $to));
    }

    public function exportCsv(Request $request, Response $response): Response
    {
        [$from, $to] = $this->range($request);
        $rows = $this->reports->export(RequestContext::actor($request), $from, $to);

        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel abra bien las tildes
        fputcsv($out, ['Código', 'Fecha', 'Inicio', 'Fin', 'Cód. materia', 'Materia', 'Docente', 'ID estudiante', 'Estudiante', 'Programa', 'Modalidad', 'Lugar', 'Estado', 'Cancelada por', 'Calificación', 'Tema'], ';', '"', '');
        foreach ($rows as $row) {
            // Neutraliza inyección de fórmulas en Excel (CSV injection, OWASP).
            $safe = array_map(static fn ($v) => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : $v, array_values($row));
            fputcsv($out, $safe, ';', '"', '');
        }
        rewind($out);
        $response->getBody()->write((string) stream_get_contents($out));
        fclose($out);
        $name = sprintf('tutorias_%s_%s.csv', $from->format('Ymd'), $to->format('Ymd'));

        return $response
            ->withHeader('Content-Type', 'text/csv; charset=utf-8')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $name . '"');
    }

    public function audit(Request $request, Response $response): Response
    {
        RequestContext::actor($request);
        $q = $request->getQueryParams();

        return Json::write($response, $this->queries->auditTrail(
            isset($q['accion']) ? (string) $q['accion'] : null,
            (int) ($q['page'] ?? 1),
            (int) ($q['pageSize'] ?? 30),
        ));
    }

    /** @return array{\DateTimeImmutable, \DateTimeImmutable} */
    private function range(Request $request): array
    {
        $in = Input::query($request);
        $now = $this->clock->now()->setTime(0, 0);
        $from = $in->optionalDate('desde') ?? $now->modify('first day of -5 months');
        $to = $in->optionalDate('hasta') ?? $now->modify('+30 days');
        $in->validate();

        return [$from, $to];
    }
}
