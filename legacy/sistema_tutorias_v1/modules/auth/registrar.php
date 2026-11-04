<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../modelos/Usuario.php';

if ($_POST) {
    // Recoger todos los datos del formulario para preservarlos
    $form_data = [
        'id_institucional' => $_POST['id_institucional'],
        'programa_academico' => $_POST['programa_academico'],
        'nombre' => $_POST['nombre'],
        'email' => $_POST['email'],
        'terminos' => isset($_POST['terminos']) ? 'on' : ''
    ];
    
    // Validar que las contraseñas coincidan
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error = urlencode("Las contraseñas no coinciden");
        $query_string = http_build_query([
            'error' => $error
        ] + $form_data);
        redirect('/sistema_tutorias/registro.php?' . $query_string);
    }
    
    if (strlen($_POST['password']) < 8) {
        $error = urlencode("La contraseña debe tener al menos 8 caracteres");
        $query_string = http_build_query([
            'error' => $error
        ] + $form_data);
        redirect('/sistema_tutorias/registro.php?' . $query_string);
    }
    
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) || !strpos($_POST['email'], '@uniminuto.edu.co')) {
        $error = urlencode("Debe usar un correo institucional @uniminuto.edu.co");
        $query_string = http_build_query([
            'error' => $error
        ] + $form_data);
        redirect('/sistema_tutorias/registro.php?' . $query_string);
    }
    
    // CORREGIDO: Verificar términos correctamente
    if (!isset($_POST['terminos'])) {
        $error = urlencode("Debe aceptar los términos y condiciones");
        $query_string = http_build_query([
            'error' => $error
        ] + $form_data);
        redirect('/sistema_tutorias/registro.php?' . $query_string);
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $usuario = new Usuario($db);
    $usuario->id_institucional = $_POST['id_institucional'];
    $usuario->nombre = $_POST['nombre'];
    $usuario->email = $_POST['email'];
    $usuario->password = $_POST['password'];
    $usuario->rol = 'estudiante';
    $usuario->programa_academico = $_POST['programa_academico'];
    
    $resultado = $usuario->registrar();
    
    if ($resultado === true) {
        $success = urlencode("¡Registro exitoso! Ahora puedes iniciar sesión");
        redirect('/sistema_tutorias/registro.php?success=' . $success);
    } else {
        $error = urlencode($resultado);
        $query_string = http_build_query([
            'error' => $error
        ] + $form_data);
        redirect('/sistema_tutorias/registro.php?' . $query_string);
    }
} else {
    // Si se accede directamente sin POST, redirige al registro
    redirect('/sistema_tutorias/registro.php');
}
?>