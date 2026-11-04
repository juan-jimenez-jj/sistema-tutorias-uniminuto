<?php
class Docente {
    private $conn;
    private $table_name = "usuarios";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodos() {
        $query = "SELECT id, nombre, email, programa_academico 
                  FROM " . $this->table_name . " 
                  WHERE rol = 'docente' AND estado = 'activo' 
                  ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerDisponibilidad($docente_id, $fecha = null) {
        $query = "SELECT d.*, 
                         DATE_FORMAT(d.hora_inicio, '%H:%i') as hora_inicio_format,
                         DATE_FORMAT(d.hora_fin, '%H:%i') as hora_fin_format
                  FROM disponibilidad_docentes d 
                  WHERE d.docente_id = :docente_id 
                  AND d.estado = 'disponible'";
        
        if ($fecha) {
            $dia_semana = date('N', strtotime($fecha));
            $dias_semana = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            $dia_nombre = $dias_semana[$dia_semana];
            
            $query .= " AND d.dia_semana = :dia_semana";
        }
        
        $query .= " ORDER BY d.dia_semana, d.hora_inicio";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":docente_id", $docente_id);
        
        if ($fecha) {
            $stmt->bindParam(":dia_semana", $dia_nombre);
        }
        
        $stmt->execute();
        return $stmt;
    }
}
?>