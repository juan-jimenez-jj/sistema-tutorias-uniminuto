<?php

declare(strict_types=1);

namespace Tutorias\Identity\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Identity\Application\ManageUsers;
use Tutorias\Identity\Application\UserQueries;
use Tutorias\Identity\Application\UserView;
use Tutorias\Shared\Domain\Role;
use Tutorias\Shared\Infrastructure\Http\Input;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;

final class UserAdminController
{
    public function __construct(private readonly ManageUsers $manage, private readonly UserQueries $queries)
    {
    }

    public function list(Request $request, Response $response): Response
    {
        $q = $request->getQueryParams();

        return Json::write($response, $this->queries->search(
            isset($q['q']) ? (string) $q['q'] : null,
            isset($q['rol']) ? (string) $q['rol'] : null,
            isset($q['estado']) ? (string) $q['estado'] : null,
            (int) ($q['page'] ?? 1),
            (int) ($q['pageSize'] ?? 20),
        ));
    }

    public function create(Request $request, Response $response): Response
    {
        $in = Input::body($request);
        $idInstitucional = $in->requiredString('idInstitucional', 4, 20, 'ID institucional');
        $nombre = $in->requiredString('nombre', 3, 120, 'nombre');
        $email = $in->requiredString('email', 5, 120, 'correo');
        $password = $in->password('password');
        /** @var Role|null $rol */
        $rol = $in->enum('rol', Role::class);
        $programa = $in->optionalString('programaAcademico', 120);
        $in->validate();

        $user = $this->manage->create(RequestContext::actor($request), $idInstitucional, $nombre, $email, $password, $rol ?? Role::Estudiante, $programa);

        return Json::write($response, ['usuario' => UserView::from($user)], 201);
    }

    /** @param array<string, string> $args */
    public function update(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $idInstitucional = $in->requiredString('idInstitucional', 4, 20, 'ID institucional');
        $nombre = $in->requiredString('nombre', 3, 120, 'nombre');
        $email = $in->requiredString('email', 5, 120, 'correo');
        /** @var Role|null $rol */
        $rol = $in->enum('rol', Role::class);
        $programa = $in->optionalString('programaAcademico', 120);
        $in->validate();

        $user = $this->manage->update(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $idInstitucional, $nombre, $email, $rol ?? Role::Estudiante, $programa);

        return Json::write($response, ['usuario' => UserView::from($user)]);
    }

    /** @param array<string, string> $args */
    public function setStatus(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $estado = $in->requiredString('estado', 6, 8);
        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            $in->addError('estado', 'El estado debe ser "activo" o "inactivo".');
        }
        $in->validate();
        $user = $this->manage->setActive(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $estado === 'activo');

        return Json::write($response, ['usuario' => UserView::from($user)]);
    }

    /** @param array<string, string> $args */
    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        $in = Input::body($request);
        $password = $in->password('password');
        $in->validate();
        $this->manage->resetPassword(RequestContext::actor($request), RequestContext::intArg($args, 'id'), $password);

        return $response->withStatus(204);
    }
}
