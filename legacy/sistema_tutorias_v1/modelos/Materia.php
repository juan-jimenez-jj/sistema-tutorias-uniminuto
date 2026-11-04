<?php
class Materia {
    private $conn;
    private $table_name = "materias";

    public $id;
    public $codigo;
    public $nombre;
    public $descripcion;
    public $creditos;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function obtenerTodas() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE estado = 'activa' ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->codigo = $row['codigo'];
            $this->nombre = $row['nombre'];
            $this->descripcion = $row['descripcion'];
            $this->creditos = $row['creditos'];
            return true;
        }
        return false;
    }
}
?>