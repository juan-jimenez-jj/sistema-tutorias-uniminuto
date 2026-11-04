<?php

declare(strict_types=1);

namespace Tutorias\Booking\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Booking\Application\BookTutoring;
use Tutorias\Booking\Application\CancelTutoring;
use Tutorias\Booking\Application\CloseTutoring;
use Tutorias\Booking\Application\FindOffers;
use Tutorias\Booking\Application\RateTutoring;
use Tutorias\Booking\Application\TutoringQueries;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Domain\Forbidden;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\Input;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

final class BookingController
{
    public function __construct(
        private readonly BookTutoring $book,
        private readonly CancelTutoring $cancel,
        private readonly CloseTutoring $close,
        private readonly RateTutoring $rate,
        private readonly FindOffers $offers,
        private readonly TutoringQueries $queries,
        private readonly Clock $clock,
    ) {
    }

    public function offers(Request $request, Response $response): Response
    {
        $in = Input::query($request);
        $materia = $in->requiredInt('materiaId');
        $docente = $in->optionalInt('docenteId');
        $in->validate();

        return Json::write($response, $this->offers->execute(RequestContext::actor($request), $materia, $docente));
    }

    public function create(Request $request, Response $response): Response
    {
        $in = Input::body($request);
        $franja = $in->requiredInt('franjaId');
        $fecha = $in->requiredDate('fecha');
        $tema = $in->requiredString('tema', 5, 500, 'tema');
        $in->validate();

        $tutoring = $this->book->execute(RequestContext::actor($request), $franja, $fecha, $tema);

        return Json::write($response, ['tutoria' => $this->queries->detail($tutoring->id())], 201)
            ->withHeader('Location', '/api/v1/tutorias/' . $tutoring->id());
    }

    public function list(Request $request, Response $response): Response
    {
        $actor = RequestContext::actor($request);
        $q = $request->getQueryParams();
        $filters = [
            'estado' => $q['estado'] ?? null,
            'alcance' => $q['alcance'] ?? null,
            'materiaId' => isset($q['materiaId']) ? (int) $q['materiaId'] : null,
            'desde' => $q['desde'] ?? null,
            'hasta' => $q['hasta'] ?? null,
            'q' => $q['q'] ?? null,
        ];
        [$studentId, $teacherId] = match ($actor->role) {
            Role::Estudiante => [$actor->id, null],
            Role::Docente => [null, $actor->id],
            Role::Administrador => [isset($q['estudianteId']) ? (int) $q['estudianteId'] : null, isset($q['docenteId']) ? (int) $q['docenteId'] : null],
        };

        return Json::write($response, $this->queries->search($studentId, $teacherId, $filters, $this->clock->now(), (int) ($q['page'] ?? 1), (int) ($q['pageSize'] ?? 20)));
    }

    /** @param array<string, string> $args */
    public function show(Request $request, Response $response, array $args): Response
    {
        $actor = RequestContext::actor($request);
        $detail = $this->queries->detail(RequestContext::intArg($args, 'id')) ?? throw NotFound::entity('tutoría', $args['id'] ?? '');
        $this->assertCanSee($actor, $detail);

        return Json::write($response, ['tutoria' => $detail]);
    }

    /** @param array<string, string> $args */
    public function cancel(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $motivo = $in->optionalString('motivo', 500);
        $in->validate();
        $t = $this->cancel->execute(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $motivo);

        return Json::write($response, ['tutoria' => $this->queries->detail($t->id())]);
    }

    /** @param array<string, string> $args */
    public function close(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $asistio = $in->bool('asistio', true);
        $observaciones = $in->optionalString('observaciones', 1000);
        $in->validate();
        $t = $this->close->execute(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $asistio, $observaciones);

        return Json::write($response, ['tutoria' => $this->queries->detail($t->id())]);
    }

    /** @param array<string, string> $args */
    public function rate(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $score = $in->requiredInt('calificacion', 1, 5);
        $comentario = $in->optionalString('comentario', 1000);
        $in->validate();
        $t = $this->rate->execute(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $score, $comentario);

        return Json::write($response, ['tutoria' => $this->queries->detail($t->id())]);
    }

    public function stats(Request $request, Response $response): Response
    {
        $actor = RequestContext::actor($request);
        $now = $this->clock->now();
        $stats = match ($actor->role) {
            Role::Estudiante => $this->queries->studentStats($actor->id, $now),
            Role::Docente => $this->queries->teacherStats($actor->id, $now),
            Role::Administrador => throw Forbidden::because('Los administradores consultan los reportes en /admin/reportes.'),
        };

        return Json::write($response, $stats);
    }

    /** @param array<string, mixed> $detail */
    private function assertCanSee(Actor $actor, array $detail): void
    {
        $allowed = match ($actor->role) {
            Role::Administrador => true,
            Role::Estudiante => $detail['estudiante']['id'] === $actor->id,
            Role::Docente => $detail['docente']['id'] === $actor->id,
        };
        if (!$allowed) {
            // Previene IDOR (Insecure Direct Object Reference): no basta con conocer el id.
            throw NotFound::entity('tutoría', $detail['id']);
        }
    }
}
