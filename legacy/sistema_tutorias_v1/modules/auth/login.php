<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../modelos/Usuario.php';

if ($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $usuario = new Usuario($db);
    $usuario->email = $_POST['email'];
    $usuario->password = $_POST['password'];
    
    if ($usuario->login()) {
        $_SESSION['user_id'] = $usuario->id;
        $_SESSION['user_name'] = $usuario->nombre;
        $_SESSION['user_role'] = $usuario->rol;
        $_SESSION['user_email'] = $usuario->email;
        $_SESSION['user_programa'] = $usuario->programa_academico;
        
        switch($usuario->rol) {
            case 'estudiante':
                redirect('/sistema_tutorias/modules/estudiantes/dashboard.php');
                break;
            case 'docente':
                redirect('/sistema_tutorias/modules/docentes/dashboard.php');
                break;
            case 'administrador':
                redirect('/sistema_tutorias/modules/admin/dashboard.php');
                break;
        }
    } else {
        $role = $_POST['role'];
        $error = urlencode("Email o contraseña incorrectos");
        redirect('/sistema_tutorias/index.php?role=' . $role . '&error=' . $error);
    }
} else {
    // Si se accede directamente sin POST, redirige al inicio
    redirect('/sistema_tutorias/index.php');
}
?>