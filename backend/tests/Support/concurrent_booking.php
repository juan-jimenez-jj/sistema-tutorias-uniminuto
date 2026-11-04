<?php

/**
 * Proceso hijo de la prueba de concurrencia: espera a la "barrera" de tiempo común
 * y entonces intenta reservar, para que todos los procesos compitan a la vez.
 * Uso: php concurrent_booking.php <estudianteId> <franjaId> <fecha> <barreraMicrotime>
 */

declare(strict_types=1);

use Tutorias\Booking\Application\BookTutoring;
use Tutorias\Shared\Application\Actor;
use Tutorias\Shared\Domain\DomainError;
use Tutorias\Shared\Domain\Role;
use Tutorias\Tests\Support\TestApp;

require __DIR__ . '/../bootstrap.php';

[, $studentId, $slotId, $date, $barrier] = $argv;
$container = TestApp::container();
$container->get(PDO::class); // abre la conexión antes de la barrera

while (microtime(true) < (float) $barrier) {
    usleep(200);
}

try {
    $container->get(BookTutoring::class)->execute(
        new Actor((int) $studentId, Role::Estudiante, 'Concurrente', 'c@uniminuto.edu.co'),
        (int) $slotId,
        new DateTimeImmutable($date, new DateTimeZone('America/Bogota')),
        'Prueba de concurrencia',
    );
    echo 'OK';
} catch (DomainError $e) {
    echo $e->errorCode();
} catch (Throwable $e) {
    echo 'ERROR: ' . $e->getMessage();
}
