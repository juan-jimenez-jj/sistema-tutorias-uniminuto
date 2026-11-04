<?php

declare(strict_types=1);

namespace Tutorias\Catalog\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Catalog\Application\ManageCatalog;
use Tutorias\Shared\Infrastructure\Http\Input;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

final class CatalogController
{
    public function __construct(private readonly ManageCatalog $catalog)
    {
    }

    public function subjects(Request $request, Response $response): Response
    {
        $all = ($request->getQueryParams()['incluirInactivas'] ?? '') === '1'
            && RequestContext::actor($request)->role->value === 'administrador';

        return Json::write($response, ['items' => $this->catalog->listSubjects($all)]);
    }

    /** @param array<string, string> $args */
    public function teachers(Request $request, Response $response, array $args): Response
    {
        return Json::write($response, ['items' => $this->catalog->teachersForSubject(RequestContext::intArg($args, 'id'))]);
    }

    public function create(Request $request, Response $response): Response
    {
        $in = Input::body($request);
        $codigo = $in->requiredString('codigo', 6, 6, 'código');
        $nombre = $in->requiredString('nombre', 3, 120, 'nombre');
        $descripcion = $in->optionalString('descripcion', 500);
        $area = $in->requiredString('area', 3, 30, 'área');
        $in->validate();
        $subject = $this->catalog->createSubject(RequestContext::actor($request), $codigo, $nombre, $descripcion, $area);

        return Json::write($response, ['materia' => $subject->toArray()], 201);
    }

    /** @param array<string, string> $args */
    public function update(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $codigo = $in->requiredString('codigo', 6, 6, 'código');
        $nombre = $in->requiredString('nombre', 3, 120, 'nombre');
        $descripcion = $in->optionalString('descripcion', 500);
        $area = $in->requiredString('area', 3, 30, 'área');
        $activa = ($in->optionalString('estado') ?? 'activa') === 'activa';
        $in->validate();
        $subject = $this->catalog->updateSubject(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $codigo, $nombre, $descripcion, $area, $activa);

        return Json::write($response, ['materia' => $subject->toArray()]);
    }

    /** @param array<string, string> $args */
    public function assignToTeacher(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $ids = $in->intList('materiaIds');
        $in->validate();
        $this->catalog->assignSubjectsToTeacher(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $ids);

        return $response->withStatus(204);
    }
}
