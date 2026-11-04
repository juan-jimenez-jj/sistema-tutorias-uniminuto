<?php
// Funciones globales del sistema

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function checkSession() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/sistema_tutorias/index.php');
    }
}

function checkRole($rol_permitido) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != $rol_permitido) {
        redirect('/sistema_tutorias/index.php');
    }
}

function showMessage() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                ' . $_SESSION['success'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                ' . $_SESSION['error'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['error']);
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatHora($hora) {
    return date('H:i', strtotime($hora));
}
?>