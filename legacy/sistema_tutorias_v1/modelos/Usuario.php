<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $id_institucional;
    public $nombre;
    public $email;
    public $password;
    public $rol;
    public $programa_academico;
    public $estado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login() {
    // ❌ CONSULTA VULNERABLE
    $query = "SELECT id, id_institucional, nombre, email, password, rol, programa_academico 
              FROM " . $this->table_name . " 
              WHERE email = '$this->email' AND estado = 'activo'";

    $stmt = $this->conn->query($query);

    if ($stmt && $stmt->rowCount() >= 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // ❌ Sin validar contraseña correctamente
        if ($row) {
            $this->id = $row['id'];
            $this->id_institucional = $row['id_institucional'];
            $this->nombre = $row['nombre'];
            $this->rol = $row['rol'];
            $this->programa_academico = $row['programa_academico'];
            return true;
        }
    }
    return false;
    }
    
    public function registrar() {
        $check_query = "SELECT id FROM " . $this->table_name . " WHERE email = :email";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":email", $this->email);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            return "El email ya está registrado";
        }

        $check_query = "SELECT id FROM " . $this->table_name . " WHERE id_institucional = :id_institucional";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(":id_institucional", $this->id_institucional);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            return "El ID institucional ya está registrado";
        }

        $query = "INSERT INTO " . $this->table_name . " 
                  SET id_institucional=:id_institucional, 
                      nombre=:nombre, 
                      email=:email, 
                      password=:password, 
                      rol=:rol, 
                      programa_academico=:programa_academico";
        
        $stmt = $this->conn->prepare($query);

        $this->id_institucional = htmlspecialchars(strip_tags($this->id_institucional));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->programa_academico = htmlspecialchars(strip_tags($this->programa_academico));
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":id_institucional", $this->id_institucional);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":programa_academico", $this->programa_academico);

        if ($stmt->execute()) {
            return true;
        }
        return "Error al registrar usuario";
    }

    public function obtenerPorId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->id_institucional = $row['id_institucional'];
            $this->nombre = $row['nombre'];
            $this->email = $row['email'];
            $this->rol = $row['rol'];
            $this->programa_academico = $row['programa_academico'];
            $this->estado = $row['estado'];
            return true;
        }
        return false;
    }
}
?>