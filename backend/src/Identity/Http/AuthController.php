<?php

declare(strict_types=1);

namespace Tutorias\Identity\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tutorias\Identity\Application\AuthenticateUser;
use Tutorias\Identity\Application\ChangeOwnPassword;
use Tutorias\Identity\Application\RegisterStudent;
use Tutorias\Identity\Application\UserView;
use Tutorias\Identity\Domain\UserRepository;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\NotFound;
use Tutorias\Shared\Infrastructure\Http\Input;
use Tutorias\Shared\Infrastructure\Http\Json;
use Tutorias\Shared\Infrastructure\Http\RequestContext;
use Tutorias\Shared\Infrastructure\Security\SessionCookies;
use Tutorias\Shared\Infrastructure\Security\TokenService;

final class AuthController
{
    public function __construct(
        private readonly RegisterStudent $registerStudent,
        private readonly AuthenticateUser $authenticate,
        private readonly ChangeOwnPassword $changePassword,
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
        private readonly SessionCookies $cookies,
    ) {
    }

    public function register(Request $request, Response $response): Response
    {
        $in = Input::body($request);
        $idInstitucional = $in->requiredString('idInstitucional', 4, 20, 'ID institucional');
        $nombre = $in->requiredString('nombre', 3, 120, 'nombre completo');
        $email = $in->requiredString('email', 5, 120, 'correo');
        $password = $in->password('password');
        $programa = $in->requiredString('programaAcademico', 3, 120, 'programa académico');
        $acepta = $in->bool('aceptaTratamientoDatos');
        $in->validate();

        $user = $this->registerStudent->execute($idInstitucional, $nombre, $email, $password, $programa, $acepta);

        return Json::write($response, ['usuario' => UserView::from($user)], 201);
    }

    public function login(Request $request, Response $response): Response
    {
        $in = Input::body($request);
        $email = $in->requiredString('email', 3, 120, 'correo');
        $password = $in->password('password');
        $in->validate();

        $user = $this->authenticate->execute($email, $password, RequestContext::clientIp($request));
        $actor = new Actor($user->id(), $user->role(), $user->name(), $user->email()->value);
        $token = $this->tokens->issue($actor);

        $response = $this->cookies->attach($response, $token);

        return Json::write($response, [
            'usuario' => UserView::from($user),
            // El token también se devuelve para clientes de API (Swagger UI, apps móviles).
            'token' => $token,
            'expiraEn' => $this->tokens->ttlSeconds(),
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        return $this->cookies->clear($response)->withStatus(204);
    }

    public function me(Request $request, Response $response): Response
    {
        $actor = RequestContext::actor($request);
        $user = $this->users->findById($actor->id) ?? throw NotFound::entity('usuario', $actor->id);

        return Json::write($response, ['usuario' => UserView::from($user)]);
    }

    public function changePassword(Request $request, Response $response): Response
    {
        $in = Input::body($request);
        $current = $in->password('passwordActual');
        $new = $in->password('passwordNueva');
        $in->validate();
        $this->changePassword->execute(RequestContext::actor($request), $current, $new);

        return $response->withStatus(204);
    }
}
