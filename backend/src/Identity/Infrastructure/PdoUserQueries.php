<?php

declare(strict_types=1);

namespace Tutorias\Identity\Infrastructure;

use Tutorias\Identity\Application\UserQueries;

final class PdoUserQueries implements UserQueries
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function search(?string $term, ?string $role, ?string $status, int $page, int $pageSize): array
    {
        $where = ['1 = 1'];
        $params = [];
        if ($term !== null && $term !== '') {
            $where[] = '(u.nombre LIKE :t1 OR u.email LIKE :t2 OR u.id_institucional LIKE :t3)';
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $params += ['t1' => $like, 't2' => $like, 't3' => $like];
        }
        if (in_array($role, ['estudiante', 'docente', 'administrador'], true)) {
            $where[] = 'u.rol = :rol';
            $params['rol'] = $role;
        }
        if (in_array($status, ['activo', 'inactivo'], true)) {
            $where[] = 'u.estado = :estado';
            $params['estado'] = $status;
        }
        $whereSql = implode(' AND ', $where);

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios u WHERE {$whereSql}");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $pageSize = max(1, min(100, $pageSize));
        $page = max(1, $page);
        $offset = ($page - 1) * $pageSize;

        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.id_institucional, u.nombre, u.email, u.rol, u.programa_academico, u.estado, u.ultimo_acceso_en, u.creado_en,
                    (SELECT GROUP_CONCAT(m.nombre ORDER BY m.nombre SEPARATOR '||') FROM docente_materia dm JOIN materias m ON m.id = dm.materia_id WHERE dm.docente_id = u.id) AS materias,
                    (SELECT GROUP_CONCAT(dm.materia_id) FROM docente_materia dm WHERE dm.docente_id = u.id) AS materia_ids
             FROM usuarios u WHERE {$whereSql}
             ORDER BY FIELD(u.rol, 'administrador', 'docente', 'estudiante'), u.nombre
             LIMIT {$pageSize} OFFSET {$offset}"
        );
        $stmt->execute($params);

        $items = array_map(static fn (array $r) => [
            'id' => (int) $r['id'],
            'idInstitucional' => $r['id_institucional'],
            'nombre' => $r['nombre'],
            'email' => $r['email'],
            'rol' => $r['rol'],
            'programaAcademico' => $r['programa_academico'],
            'estado' => $r['estado'],
            'ultimoAcceso' => $r['ultimo_acceso_en'],
            'creadoEn' => $r['creado_en'],
            'materias' => $r['materias'] ? explode('||', (string) $r['materias']) : [],
            'materiaIds' => $r['materia_ids'] ? array_map('intval', explode(',', (string) $r['materia_ids'])) : [],
        ], $stmt->fetchAll());

        return ['items' => $items, 'total' => $total, 'page' => $page, 'pageSize' => $pageSize];
    }
}
