<?php
class Tutoria {
    private $conn;
    private $table_name = "tutorias";

    public $id;
    public $estudiante_id;
    public $docente_id;
    public $materia_id;
    public $disponibilidad_id;
    public $fecha_tutoria;
    public $fecha_solicitud;
    public $tema_tratar;
    public $estado;
    public $calificacion;
    public $comentarios_estudiante;
    public $comentarios_docente;
    public $fecha_cancelacion;
    public $motivo_cancelacion;
    public $fecha_completacion;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (estudiante_id, docente_id, materia_id, disponibilidad_id, fecha_tutoria, tema_tratar, estado, fecha_solicitud)
                 VALUES 
                 (:estudiante_id, :docente_id, :materia_id, :disponibilidad_id, :fecha_tutoria, :tema_tratar, :estado, NOW())";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->estudiante_id = htmlspecialchars(strip_tags($this->estudiante_id));
        $this->docente_id = htmlspecialchars(strip_tags($this->docente_id));
        $this->materia_id = htmlspecialchars(strip_tags($this->materia_id));
        $this->disponibilidad_id = htmlspecialchars(strip_tags($this->disponibilidad_id));
        $this->fecha_tutoria = htmlspecialchars(strip_tags($this->fecha_tutoria));
        $this->tema_tratar = htmlspecialchars(strip_tags($this->tema_tratar));
        $this->estado = htmlspecialchars(strip_tags($this->estado));

        // Vincular parámetros
        $stmt->bindParam(":estudiante_id", $this->estudiante_id);
        $stmt->bindParam(":docente_id", $this->docente_id);
        $stmt->bindParam(":materia_id", $this->materia_id);
        $stmt->bindParam(":disponibilidad_id", $this->disponibilidad_id);
        $stmt->bindParam(":fecha_tutoria", $this->fecha_tutoria);
        $stmt->bindParam(":tema_tratar", $this->tema_tratar);
        $stmt->bindParam(":estado", $this->estado);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function obtenerPorEstudiante($estudiante_id) {
        $query = "SELECT t.*, 
                         u.nombre as docente_nombre, 
                         m.nombre as materia_nombre,
                         m.codigo as materia_codigo,
                         dd.dia_semana, dd.hora_inicio, dd.hora_fin, dd.lugar
                  FROM " . $this->table_name . " t
                  JOIN usuarios u ON t.docente_id = u.id
                  JOIN materias m ON t.materia_id = m.id
                  JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
                  WHERE t.estudiante_id = :estudiante_id
                  ORDER BY t.fecha_tutoria DESC, dd.hora_inicio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":estudiante_id", $estudiante_id);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerPorDocente($docente_id) {
        $query = "SELECT t.*, 
                         u.nombre as estudiante_nombre, 
                         u.id_institucional,
                         m.nombre as materia_nombre,
                         dd.dia_semana, dd.hora_inicio, dd.hora_fin, dd.lugar
                  FROM " . $this->table_name . " t
                  JOIN usuarios u ON t.estudiante_id = u.id
                  JOIN materias m ON t.materia_id = m.id
                  JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
                  WHERE t.docente_id = :docente_id
                  ORDER BY t.fecha_tutoria DESC, dd.hora_inicio DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":docente_id", $docente_id);
        $stmt->execute();

        return $stmt;
    }

    public function obtenerPorId($id) {
        $query = "SELECT t.*, 
                         u_est.nombre as estudiante_nombre, 
                         u_est.id_institucional as estudiante_id_institucional,
                         u_doc.nombre as docente_nombre,
                         m.nombre as materia_nombre,
                         m.codigo as materia_codigo,
                         dd.dia_semana, dd.hora_inicio, dd.hora_fin, dd.lugar
                  FROM " . $this->table_name . " t
                  JOIN usuarios u_est ON t.estudiante_id = u_est.id
                  JOIN usuarios u_doc ON t.docente_id = u_doc.id
                  JOIN materias m ON t.materia_id = m.id
                  JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
                  WHERE t.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->estudiante_id = $row['estudiante_id'];
            $this->docente_id = $row['docente_id'];
            $this->materia_id = $row['materia_id'];
            $this->disponibilidad_id = $row['disponibilidad_id'];
            $this->fecha_tutoria = $row['fecha_tutoria'];
            $this->fecha_solicitud = $row['fecha_solicitud'];
            $this->tema_tratar = $row['tema_tratar'];
            $this->estado = $row['estado'];
            $this->calificacion = $row['calificacion'];
            $this->comentarios_estudiante = $row['comentarios_estudiante'];
            $this->comentarios_docente = $row['comentarios_docente'];
            $this->fecha_cancelacion = $row['fecha_cancelacion'];
            $this->motivo_cancelacion = $row['motivo_cancelacion'];
            $this->fecha_completacion = $row['fecha_completacion'];
            return true;
        }
        return false;
    }

    public function actualizar() {
        $query = "UPDATE " . $this->table_name . " 
                 SET estado = :estado,
                     calificacion = :calificacion,
                     comentarios_estudiante = :comentarios_estudiante,
                     comentarios_docente = :comentarios_docente,
                     fecha_cancelacion = :fecha_cancelacion,
                     motivo_cancelacion = :motivo_cancelacion,
                     fecha_completacion = :fecha_completacion
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Limpiar datos
        $this->estado = htmlspecialchars(strip_tags($this->estado));
        $this->calificacion = htmlspecialchars(strip_tags($this->calificacion));
        $this->comentarios_estudiante = htmlspecialchars(strip_tags($this->comentarios_estudiante));
        $this->comentarios_docente = htmlspecialchars(strip_tags($this->comentarios_docente));
        $this->fecha_cancelacion = htmlspecialchars(strip_tags($this->fecha_cancelacion));
        $this->motivo_cancelacion = htmlspecialchars(strip_tags($this->motivo_cancelacion));
        $this->fecha_completacion = htmlspecialchars(strip_tags($this->fecha_completacion));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Vincular parámetros
        $stmt->bindParam(":estado", $this->estado);
        $stmt->bindParam(":calificacion", $this->calificacion);
        $stmt->bindParam(":comentarios_estudiante", $this->comentarios_estudiante);
        $stmt->bindParam(":comentarios_docente", $this->comentarios_docente);
        $stmt->bindParam(":fecha_cancelacion", $this->fecha_cancelacion);
        $stmt->bindParam(":motivo_cancelacion", $this->motivo_cancelacion);
        $stmt->bindParam(":fecha_completacion", $this->fecha_completacion);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function cancelar($motivo = '') {
        $query = "UPDATE " . $this->table_name . " 
                 SET estado = 'cancelada',
                     fecha_cancelacion = NOW(),
                     motivo_cancelacion = :motivo
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":motivo", $motivo);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function completar($comentarios_docente = '') {
        $query = "UPDATE " . $this->table_name . " 
                 SET estado = 'completada',
                     fecha_completacion = NOW(),
                     comentarios_docente = :comentarios_docente
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":comentarios_docente", $comentarios_docente);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function calificar($calificacion, $comentarios_estudiante = '') {
        $query = "UPDATE " . $this->table_name . " 
                 SET calificacion = :calificacion,
                     comentarios_estudiante = :comentarios_estudiante
                 WHERE id = :id AND estado = 'completada'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":calificacion", $calificacion);
        $stmt->bindParam(":comentarios_estudiante", $comentarios_estudiante);
        $stmt->bindParam(":id", $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
    
    public function obtenerHistorialEstudiante($estudiante_id) {
    $query = "SELECT t.*, 
                     u.nombre as docente_nombre, 
                     m.nombre as materia_nombre,
                     m.codigo as materia_codigo,
                     dd.dia_semana, dd.hora_inicio, dd.hora_fin, dd.lugar
              FROM " . $this->table_name . " t
              JOIN usuarios u ON t.docente_id = u.id
              JOIN materias m ON t.materia_id = m.id
              JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
              WHERE t.estudiante_id = :estudiante_id
              ORDER BY t.fecha_tutoria DESC, dd.hora_inicio DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":estudiante_id", $estudiante_id);
    $stmt->execute();

    return $stmt;
}

public function obtenerEstadisticasEstudiante($estudiante_id) {
    $query = "SELECT 
                COUNT(*) as total_tutorias,
                SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                SUM(CASE WHEN estado = 'programada' THEN 1 ELSE 0 END) as programadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                AVG(CASE WHEN estado = 'completada' THEN calificacion ELSE NULL END) as promedio_calificaciones,
                COUNT(CASE WHEN estado = 'completada' AND calificacion IS NOT NULL THEN 1 ELSE NULL END) as tutorias_calificadas
              FROM " . $this->table_name . " 
              WHERE estudiante_id = :estudiante_id";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":estudiante_id", $estudiante_id);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function obtenerTutoriasPorMateria($estudiante_id) {
    $query = "SELECT m.nombre as materia_nombre, m.codigo,
                     COUNT(*) as total_tutorias,
                     SUM(CASE WHEN t.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
                     AVG(CASE WHEN t.estado = 'completada' THEN t.calificacion ELSE NULL END) as promedio
              FROM " . $this->table_name . " t
              JOIN materias m ON t.materia_id = m.id
              WHERE t.estudiante_id = :estudiante_id
              GROUP BY m.id, m.nombre, m.codigo
              ORDER BY total_tutorias DESC";

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(":estudiante_id", $estudiante_id);
    $stmt->execute();

    return $stmt;
}

}
?>