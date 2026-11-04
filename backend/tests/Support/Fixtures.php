<?php

declare(strict_types=1);

namespace Tutorias\Tests\Support;

/**
 * Datos mínimos y conocidos para las pruebas. "Ahora" = lunes 1 de marzo de 2027, 08:00.
 *   Franja A: Docente Ana, Cálculo Diferencial (1), lunes 10:00–11:00, cupo 2
 *   Franja B: Docente Beto, Cálculo Integral (2), lunes 10:30–11:30, cupo 1 (se cruza con A)
 *   Franja C: Docente Ana, Cálculo Diferencial (1), miércoles 14:00–15:00, cupo 1
 */
final class Fixtures
{
    public const PASSWORD = 'Clave2027segura';
    public const ADMIN = 1;
    public const TEACHER_ANA = 2;
    public const TEACHER_BETO = 3;
    public const STUDENT_LAURA = 4;
    public const STUDENT_MATEO = 5;
    public const STUDENT_SARA = 6;
    public const SLOT_A = 1;
    public const SLOT_B = 2;
    public const SLOT_C = 3;

    public static function load(\PDO $pdo): void
    {
        $hash = password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]);
        $users = [
            [self::ADMIN, 'ADM001', 'Coordinación', 'admin@uniminuto.edu.co', 'administrador'],
            [self::TEACHER_ANA, 'DOC001', 'Ana Docente', 'ana@uniminuto.edu.co', 'docente'],
            [self::TEACHER_BETO, 'DOC002', 'Beto Docente', 'beto@uniminuto.edu.co', 'docente'],
            [self::STUDENT_LAURA, 'EST001', 'Laura Estudiante', 'laura@uniminuto.edu.co', 'estudiante'],
            [self::STUDENT_MATEO, 'EST002', 'Mateo Estudiante', 'mateo@uniminuto.edu.co', 'estudiante'],
            [self::STUDENT_SARA, 'EST003', 'Sara Estudiante', 'sara@uniminuto.edu.co', 'estudiante'],
        ];
        $stmt = $pdo->prepare("INSERT INTO usuarios (id, id_institucional, nombre, email, password_hash, rol, programa_academico, estado) VALUES (?, ?, ?, ?, ?, ?, 'Ingeniería de Sistemas', 'activo')");
        foreach ($users as [$id, $inst, $name, $email, $role]) {
            $stmt->execute([$id, $inst, $name, $email, $hash, $role]);
        }
        $pdo->exec('INSERT INTO docente_materia (docente_id, materia_id) VALUES (2, 1), (3, 2)');
        $pdo->exec("INSERT INTO franjas_disponibilidad (id, docente_id, materia_id, dia_semana, hora_inicio, hora_fin, lugar, modalidad, cupo) VALUES
            (1, 2, 1, 1, '10:00', '11:00', 'Salón B402', 'presencial', 2),
            (2, 3, 2, 1, '10:30', '11:30', 'Salón B305', 'presencial', 1),
            (3, 2, 1, 3, '14:00', '15:00', 'Microsoft Teams', 'virtual', 1)");
    }
}
