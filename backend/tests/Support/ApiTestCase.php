<?php

declare(strict_types=1);

namespace Tutorias\Tests\Support;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Tutorias\Kernel;
use Tutorias\Shared\Infrastructure\Container;

/**
 * Pruebas de integración: atraviesan HTTP → middleware → casos de uso → MySQL real,
 * sin levantar un servidor (la petición PSR-7 se despacha en memoria).
 */
abstract class ApiTestCase extends TestCase
{
    protected Container $container;
    protected FrozenClock $clock;
    protected \PDO $pdo;
    /** @var App<\Psr\Container\ContainerInterface|null> */
    protected App $app;

    protected function setUp(): void
    {
        $this->clock = new FrozenClock('2027-03-01 08:00:00');
        $this->container = TestApp::container($this->clock);
        $this->pdo = $this->container->get(\PDO::class);
        TestApp::resetDatabase($this->pdo);
        Fixtures::load($this->pdo);
        $this->app = Kernel::app($this->container);
    }

    /** @param array<string, mixed>|null $body @param array<string, string> $headers */
    protected function request(string $method, string $path, ?array $body = null, ?string $token = null, array $headers = []): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest($method, $path, ['REMOTE_ADDR' => '10.0.0.1']);
        if ($body !== null) {
            $request = $request->withHeader('Content-Type', 'application/json')
                ->withBody((new StreamFactory())->createStream(json_encode($body, JSON_THROW_ON_ERROR)));
        }
        if ($token !== null) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $token);
        }
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        if (isset($headers['Cookie'])) {
            $cookies = [];
            foreach (explode(';', $headers['Cookie']) as $pair) {
                [$k, $v] = array_map('trim', explode('=', $pair, 2)) + [1 => ''];
                $cookies[$k] = urldecode($v);
            }
            $request = $request->withCookieParams($cookies);
        }

        return $this->app->handle($request);
    }

    protected function login(string $email, string $password = Fixtures::PASSWORD): string
    {
        $response = $this->request('POST', '/api/v1/auth/login', ['email' => $email, 'password' => $password]);
        self::assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        return $this->json($response)['token'];
    }

    /** @return array<string, mixed> */
    protected function json(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function assertStatus(int $expected, ResponseInterface $response): void
    {
        self::assertSame($expected, $response->getStatusCode(), 'Respuesta: ' . $response->getBody());
    }

    /** @return array<string, mixed> */
    protected function book(string $token, int $slot, string $date, string $topic = 'Derivadas por definición'): array
    {
        $response = $this->request('POST', '/api/v1/tutorias', ['franjaId' => $slot, 'fecha' => $date, 'tema' => $topic], $token);
        $this->assertStatus(201, $response);

        return $this->json($response)['tutoria'];
    }
}
