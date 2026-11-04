<?php

declare(strict_types=1);

namespace Tutorias\Scheduling\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Scheduling\Application\AvailabilityQueries;
use Tutorias\Scheduling\Application\ManageAvailability;
use Tutorias\Scheduling\Application\SlotView;
use Tutorias\Scheduling\Domain\Modality;
use Tutorias\Scheduling\Domain\Weekday;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Infrastructure\Http\Input;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

/** Endpoints del docente para administrar sus franjas de disponibilidad. */
final class AvailabilityController
{
    public function __construct(
        private readonly ManageAvailability $manage,
        private readonly AvailabilityQueries $queries,
        private readonly Clock $clock,
    ) {
    }

    public function mine(Request $request, Response $response): Response
    {
        return Json::write($response, ['items' => $this->queries->slotsOfTeacher(RequestContext::actor($request)->id, $this->clock->now())]);
    }

    public function subjects(Request $request, Response $response): Response
    {
        return Json::write($response, ['items' => $this->queries->subjectsOfTeacher(RequestContext::actor($request)->id)]);
    }

    public function create(Request $request, Response $response): Response
    {
        [$materia, $dia, $inicio, $fin, $lugar, $modalidad, $cupo] = $this->read($request);
        $slot = $this->manage->publish(RequestContext::actor($request), $materia, $dia, $inicio, $fin, $lugar, $modalidad, $cupo);

        return Json::write($response, ['franja' => SlotView::from($slot)], 201);
    }

    /** @param array<string, string> $args */
    public function update(Request $request, Response $response, array $args): Response
    {
        [$materia, $dia, $inicio, $fin, $lugar, $modalidad, $cupo] = $this->read($request);
        $slot = $this->manage->update(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $materia, $dia, $inicio, $fin, $lugar, $modalidad, $cupo);

        return Json::write($response, ['franja' => SlotView::from($slot)]);
    }

    /** @param array<string, string> $args */
    public function setActive(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $activa = $in->bool('activa', true);
        $slot = $this->manage->setActive(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $activa);

        return Json::write($response, ['franja' => SlotView::from($slot)]);
    }

    /** @return array{int, Weekday, string, string, string, Modality, int} */
    private function read(Request $request): array
    {
        $in = Input::body($request);
        $materia = $in->requiredInt('materiaId');
        /** @var Weekday|null $dia */
        $dia = $in->enum('diaSemana', Weekday::class);
        $inicio = $in->requiredTime('horaInicio');
        $fin = $in->requiredTime('horaFin');
        $lugar = $in->requiredString('lugar', 2, 100, 'lugar');
        /** @var Modality|null $modalidad */
        $modalidad = $in->enum('modalidad', Modality::class, false);
        $cupo = $in->requiredInt('cupo', 1, 30);
        $in->validate();

        return [$materia, $dia ?? Weekday::Lunes, $inicio, $fin, $lugar, $modalidad ?? Modality::Presencial, $cupo];
    }
}
