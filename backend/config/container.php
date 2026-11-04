<?php

/**
 * RAÍZ DE COMPOSICIÓN
 * -------------------
 * Aquí (y solo aquí) se decide qué adaptador concreto implementa cada puerto.
 * Cambiar MySQL por otro motor, el SMTP por un servicio de correo en la nube o la caché
 * en disco por Redis implica modificar este archivo, no los casos de uso ni el dominio.
 */

declare(strict_types=1);

use Psr\Log\LoggerInterface;
use Tutorias\Booking\Application\OfferQueries;
use Tutorias\Booking\Application\TutoringQueries;
use Tutorias\Booking\Domain\BookingPolicy;
use Tutorias\Booking\Domain\TutoringRepository;
use Tutorias\Booking\Infrastructure\PdoOfferQueries;
use Tutorias\Booking\Infrastructure\PdoTutoringQueries;
use Tutorias\Booking\Infrastructure\PdoTutoringRepository;
use Tutorias\Catalog\Application\CatalogQueries;
use Tutorias\Catalog\Domain\SubjectRepository;
use Tutorias\Catalog\Infrastructure\PdoCatalogQueries;
use Tutorias\Catalog\Infrastructure\PdoSubjectRepository;
use Tutorias\Identity\Application\UserQueries;
use Tutorias\Identity\Domain\LoginThrottle;
use Tutorias\Identity\Domain\PasswordHasher;
use Tutorias\Identity\Domain\UserRepository;
use Tutorias\Identity\Infrastructure\BcryptPasswordHasher;
use Tutorias\Identity\Infrastructure\PdoLoginThrottle;
use Tutorias\Identity\Infrastructure\PdoSessionValidator;
use Tutorias\Identity\Infrastructure\PdoUserQueries;
use Tutorias\Identity\Infrastructure\PdoUserRepository;
use Tutorias\Kernel;
use Tutorias\Notifications\Application\ContactDirectory;
use Tutorias\Notifications\Application\Mailer;
use Tutorias\Notifications\Application\NotificationStore;
use Tutorias\Notifications\Application\TutoringNotifier;
use Tutorias\Notifications\Infrastructure\LogMailer;
use Tutorias\Notifications\Infrastructure\PdoContactDirectory;
use Tutorias\Notifications\Infrastructure\PdoNotificationStore;
use Tutorias\Notifications\Infrastructure\SmtpMailer;
use Tutorias\Notifications\Infrastructure\TutoringEventsHandler;
use Tutorias\Platform\Http\HealthController;
use Tutorias\Reporting\Application\ReportQueries;
use Tutorias\Reporting\Infrastructure\PdoReportQueries;
use Tutorias\Scheduling\Application\AvailabilityQueries;
use Tutorias\Scheduling\Application\BookingsOfSlot;
use Tutorias\Scheduling\Application\TeachingAssignments;
use Tutorias\Scheduling\Domain\SlotRepository;
use Tutorias\Scheduling\Infrastructure\PdoAvailabilityQueries;
use Tutorias\Scheduling\Infrastructure\PdoBookingsOfSlot;
use Tutorias\Scheduling\Infrastructure\PdoSlotRepository;
use Tutorias\Scheduling\Infrastructure\PdoTeachingAssignments;
use Tutorias\Shared\Application\AuditLog;
use Tutorias\Shared\Application\Cache;
use Tutorias\Shared\Application\EventPublisher;
use Tutorias\Shared\Application\SessionValidator;
use Tutorias\Shared\Application\TransactionManager;
use Tutorias\Shared\Domain\Clock;
use Tutorias\Shared\Infrastructure\Cache\FileCache;
use Tutorias\Shared\Infrastructure\Cache\NullCache;
use Tutorias\Shared\Infrastructure\Config;
use Tutorias\Shared\Infrastructure\Container;
use Tutorias\Shared\Infrastructure\Logging\LoggerFactory;
use Tutorias\Shared\Infrastructure\Outbox\OutboxEventPublisher;
use Tutorias\Shared\Infrastructure\Outbox\OutboxProcessor;
use Tutorias\Shared\Infrastructure\Persistence\ConnectionFactory;
use Tutorias\Shared\Infrastructure\Persistence\PdoAuditLog;
use Tutorias\Shared\Infrastructure\Persistence\PdoTransactionManager;
use Tutorias\Shared\Infrastructure\Security\SessionCookies;
use Tutorias\Shared\Infrastructure\Security\TokenService;
use Tutorias\Shared\Infrastructure\SystemClock;

return static function (Container $c, Config $config): void {
    // --- Núcleo compartido -------------------------------------------------
    $c->instance(Config::class, $config);
    $c->set(PDO::class, static fn () => ConnectionFactory::create($config));
    $c->set(LoggerInterface::class, static fn () => LoggerFactory::create($config));
    $c->set(Clock::class, static fn () => new SystemClock($config->string('APP_TIMEZONE', 'America/Bogota')));
    $c->set(TransactionManager::class, static fn (Container $c) => new PdoTransactionManager($c->get(PDO::class)));
    $c->set(EventPublisher::class, static fn (Container $c) => new OutboxEventPublisher($c->get(PDO::class)));
    $c->set(AuditLog::class, static fn (Container $c) => $c->get(PdoAuditLog::class));
    $c->set(Cache::class, static fn () => $config->bool('CACHE_ENABLED', true)
        ? new FileCache($config->string('CACHE_DIR', sys_get_temp_dir() . '/tutorias-cache'))
        : new NullCache());

    // --- Seguridad ----------------------------------------------------------
    $c->set(TokenService::class, static fn (Container $c) => new TokenService(
        $config->string('JWT_SECRET'),
        $c->get(Clock::class),
        $config->int('JWT_TTL_SECONDS', 28800),
    ));
    $c->set(SessionCookies::class, static fn () => new SessionCookies($config->bool('COOKIE_SECURE', true), $config->int('JWT_TTL_SECONDS', 28800)));

    // --- Identidad ------------------------------------------------------------
    $c->set(UserRepository::class, static fn (Container $c) => new PdoUserRepository($c->get(PDO::class)));
    $c->set(SessionValidator::class, static fn (Container $c) => new PdoSessionValidator($c->get(PDO::class)));
    $c->set(UserQueries::class, static fn (Container $c) => new PdoUserQueries($c->get(PDO::class)));
    $c->set(PasswordHasher::class, static fn () => new BcryptPasswordHasher($config->int('BCRYPT_COST', 12)));
    $c->set(LoginThrottle::class, static fn (Container $c) => new PdoLoginThrottle(
        $c->get(PDO::class),
        $c->get(Clock::class),
        $config->int('LOGIN_MAX_ATTEMPTS', 5),
        $config->int('LOGIN_WINDOW_SECONDS', 900),
    ));

    // --- Catálogo -------------------------------------------------------------
    $c->set(SubjectRepository::class, static fn (Container $c) => new PdoSubjectRepository($c->get(PDO::class)));
    $c->set(CatalogQueries::class, static fn (Container $c) => new PdoCatalogQueries($c->get(PDO::class)));

    // --- Disponibilidad -------------------------------------------------------
    $c->set(SlotRepository::class, static fn (Container $c) => new PdoSlotRepository($c->get(PDO::class)));
    $c->set(BookingsOfSlot::class, static fn (Container $c) => new PdoBookingsOfSlot($c->get(PDO::class)));
    $c->set(TeachingAssignments::class, static fn (Container $c) => new PdoTeachingAssignments($c->get(PDO::class)));
    $c->set(AvailabilityQueries::class, static fn (Container $c) => new PdoAvailabilityQueries($c->get(PDO::class)));

    // --- Reservas -------------------------------------------------------------
    $c->set(BookingPolicy::class, static fn () => new BookingPolicy(
        minAdvanceMinutes: $config->int('BOOKING_MIN_ADVANCE_MINUTES', 60),
        maxDaysAhead: $config->int('BOOKING_MAX_DAYS_AHEAD', 28),
        cancellationWindowHours: $config->int('BOOKING_CANCELLATION_HOURS', 3),
        maxActivePerStudent: $config->int('BOOKING_MAX_ACTIVE_PER_STUDENT', 5),
    ));
    $c->set(TutoringRepository::class, static fn (Container $c) => new PdoTutoringRepository($c->get(PDO::class)));
    $c->set(OfferQueries::class, static fn (Container $c) => new PdoOfferQueries($c->get(PDO::class)));
    $c->set(TutoringQueries::class, static fn (Container $c) => new PdoTutoringQueries($c->get(PDO::class)));

    // --- Notificaciones -------------------------------------------------------
    $c->set(Mailer::class, static fn (Container $c) => $config->string('MAIL_DRIVER', 'log') === 'smtp'
        ? new SmtpMailer(
            $config->string('MAIL_HOST', 'mailpit'),
            $config->int('MAIL_PORT', 1025),
            $config->string('MAIL_USERNAME'),
            $config->string('MAIL_PASSWORD'),
            $config->string('MAIL_ENCRYPTION'),
            $config->string('MAIL_FROM_ADDRESS', 'tutorias@uniminuto.edu.co'),
            $config->string('MAIL_FROM_NAME', 'Tutorías UNIMINUTO'),
        )
        : new LogMailer($c->get(LoggerInterface::class)));
    $c->set(NotificationStore::class, static fn (Container $c) => new PdoNotificationStore($c->get(PDO::class), $c->get(Clock::class)));
    $c->set(ContactDirectory::class, static fn (Container $c) => new PdoContactDirectory($c->get(PDO::class)));
    $c->set(TutoringNotifier::class, static fn (Container $c) => new TutoringNotifier(
        $c->get(ContactDirectory::class),
        $c->get(NotificationStore::class),
        $c->get(Mailer::class),
        $config->string('APP_URL', 'http://localhost:8080'),
    ));
    $c->set(OutboxProcessor::class, static fn (Container $c) => new OutboxProcessor(
        $c->get(PDO::class),
        $c->get(Clock::class),
        $c->get(LoggerInterface::class),
        [$c->get(TutoringEventsHandler::class)],
        $config->int('OUTBOX_MAX_ATTEMPTS', 5),
    ));

    // --- Reportes y plataforma ---------------------------------------------
    $c->set(ReportQueries::class, static fn (Container $c) => new PdoReportQueries($c->get(PDO::class)));
    $c->set(HealthController::class, static fn (Container $c) => new HealthController($c->get(PDO::class), Kernel::VERSION));
};
