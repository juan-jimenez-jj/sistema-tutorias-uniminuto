<?php

declare(strict_types=1);

namespace Tutorias\Notifications\Application;

/**
 * Decide QUÉ se comunica y A QUIÉN ante cada evento del ciclo de vida de una tutoría.
 * Es independiente del transporte: el correo y la notificación interna son adaptadores.
 */
final class TutoringNotifier
{
    private const DAYS = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];
    private const MONTHS = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    public function __construct(
        private readonly ContactDirectory $contacts,
        private readonly NotificationStore $store,
        private readonly Mailer $mailer,
        private readonly string $appUrl,
    ) {
    }

    /** @param array<string, mixed> $p */
    public function handle(string $eventType, string $eventId, array $p): void
    {
        $student = $this->contacts->find((int) $p['estudianteId']);
        $teacher = $this->contacts->find((int) $p['docenteId']);
        if ($student === null || $teacher === null) {
            return; // usuario eliminado: nada que notificar
        }
        $subject = (string) ($p['materiaNombre'] ?? $this->contacts->subjectName((int) $p['materiaId']));
        $when = self::humanDate((string) $p['fecha']) . ', de ' . self::hour12((string) $p['horaInicio']) . ' a ' . self::hour12((string) $p['horaFin']);
        $code = (string) $p['codigo'];

        switch ($eventType) {
            case 'tutoria.reservada':
                $this->notify(
                    $student,
                    $eventId,
                    'reserva_confirmada',
                    "Tutoría confirmada · {$subject}",
                    "Tu tutoría {$code} con {$teacher->name} quedó programada para el {$when} en {$p['lugar']}.",
                    '/estudiante/tutorias'
                );
                $this->notify(
                    $teacher,
                    $eventId,
                    'nueva_reserva',
                    "Nueva reserva · {$subject}",
                    "{$student->name} reservó la tutoría {$code} para el {$when} (tema: {$p['tema']}).",
                    '/docente/agenda'
                );
                break;
            case 'tutoria.cancelada':
                $byStudent = ($p['canceladaPor'] ?? '') === 'estudiante';
                $target = $byStudent ? $teacher : $student;
                $link = $byStudent ? '/docente/agenda' : '/estudiante/tutorias';
                $who = $byStudent ? $student->name : ($p['canceladaPor'] === 'docente' ? $teacher->name : 'Coordinación académica');
                $this->notify(
                    $target,
                    $eventId,
                    'tutoria_cancelada',
                    "Tutoría cancelada · {$subject}",
                    "{$who} canceló la tutoría {$code} del {$when} (motivo: {$p['motivo']}).",
                    $link
                );
                if (($p['canceladaPor'] ?? '') === 'administrador') {
                    $this->notify(
                        $teacher,
                        $eventId,
                        'tutoria_cancelada',
                        "Tutoría cancelada · {$subject}",
                        "Coordinación canceló la tutoría {$code} del {$when} (motivo: {$p['motivo']}).",
                        '/docente/agenda'
                    );
                }
                break;
            case 'tutoria.completada':
                $this->notify(
                    $student,
                    $eventId,
                    'calificar_tutoria',
                    "¿Cómo te fue en tu tutoría de {$subject}?",
                    "{$teacher->name} registró la tutoría {$code} como completada. Califícala para ayudarnos a mejorar.",
                    '/estudiante/historial'
                );
                break;
            case 'tutoria.inasistencia':
                $this->notify(
                    $student,
                    $eventId,
                    'inasistencia',
                    "Inasistencia registrada · {$subject}",
                    "{$teacher->name} registró que no asististe a la tutoría {$code} del {$when}; si fue un error, comunícate con el docente.",
                    '/estudiante/historial'
                );
                break;
        }
    }

    private function notify(Contact $to, string $eventId, string $type, string $title, string $message, string $link): void
    {
        $this->store->add($to->id, $type, $title, $message, $link, $eventId);
        $url = rtrim($this->appUrl, '/') . $link;
        $this->mailer->send($to->email, $to->name, $title, EmailTemplate::render($to->name, $title, $message, $url), "{$message}\n\n{$url}");
    }

    public static function hour12(string $hhmm): string
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return sprintf('%d:%02d %s', ($h + 11) % 12 + 1, $m, $h >= 12 ? 'p. m.' : 'a. m.');
    }

    public static function humanDate(string $ymd): string
    {
        $d = new \DateTimeImmutable($ymd);

        return self::DAYS[(int) $d->format('N')] . ' ' . (int) $d->format('j') . ' de ' . self::MONTHS[(int) $d->format('n')];
    }
}
