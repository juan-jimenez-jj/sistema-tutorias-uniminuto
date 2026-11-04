<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

checkSession();
checkRole('estudiante');

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['docente_id']) && isset($_GET['materia_id']) && isset($_GET['fecha'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $fecha = $_GET['fecha'];
    $diaSemanaIngles = date('l', strtotime($fecha));
    
    // Mapear días de inglés a español
    $diasEspanol = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes', 
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    
    $diaSemanaEspanol = $diasEspanol[$diaSemanaIngles] ?? $diaSemanaIngles;
    
    // Consulta simplificada SIN cupos
    $query = "SELECT dd.*
              FROM disponibilidad_docentes dd 
              WHERE dd.docente_id = :docente_id 
              AND dd.materia_id = :materia_id
              AND dd.dia_semana = :dia_semana
              AND dd.estado = 'disponible'
              ORDER BY dd.hora_inicio";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':docente_id', $_GET['docente_id']);
    $stmt->bindParam(':materia_id', $_GET['materia_id']);
    $stmt->bindParam(':dia_semana', $diaSemanaEspanol);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo '<div class="fecha-info">';
        echo '<h6 class="text-primary mb-1">';
        echo '<i class="bi bi-calendar-check me-2"></i>';
        echo 'Horarios disponibles para ' . date('d/m/Y', strtotime($fecha)) . ' (' . $diaSemanaEspanol . ')';
        echo '</h6>';
        echo '<small class="text-muted">' . $stmt->rowCount() . ' horario' . ($stmt->rowCount() > 1 ? 's' : '') . ' disponible' . ($stmt->rowCount() > 1 ? 's' : '') . '</small>';
        echo '</div>';
        
        echo '<div class="horario-grid">';
        while ($horario = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $hora_inicio = date('g:i A', strtotime($horario['hora_inicio']));
            $hora_fin = date('g:i A', strtotime($horario['hora_fin']));
            
            echo '<div class="horario-card">';
            echo '<div class="form-check mb-0">';
            echo '<input class="form-check-input" type="radio" name="disponibilidad_id" 
                     value="' . $horario['id'] . '" 
                     data-fecha-tutoria="' . $fecha . '"
                     id="horario' . $horario['id'] . '" required>';
            echo '<label class="form-check-label w-100" for="horario' . $horario['id'] . '">';
            
            // Información principal del horario
            echo '<div class="d-flex justify-content-between align-items-start mb-2">';
            echo '<span class="fw-bold text-dark fs-6">' . $hora_inicio . ' - ' . $hora_fin . '</span>';
            echo '<span class="badge bg-success">';
            echo '<i class="bi bi-check-circle me-1"></i>Disponible';
            echo '</span>';
            echo '</div>';
            
            // Información adicional
            echo '<div class="horario-info">';
            echo '<div class="d-flex align-items-center text-muted small mb-1">';
            echo '<i class="bi bi-geo-alt me-2"></i>';
            echo '<span>' . htmlspecialchars($horario['lugar']) . '</span>';
            echo '</div>';
            
            // Duración de la tutoría
            $inicio = new DateTime($horario['hora_inicio']);
            $fin = new DateTime($horario['hora_fin']);
            $duracion = $inicio->diff($fin);
            $horas = $duracion->h;
            $minutos = $duracion->i;
            
            echo '<div class="d-flex align-items-center text-muted small mb-2">';
            echo '<i class="bi bi-clock me-2"></i>';
            echo '<span>';
            if ($horas > 0) {
                echo $horas . 'h' . ($minutos > 0 ? ' ' . $minutos . 'm' : '');
            } else {
                echo $minutos . ' min';
            }
            echo '</span>';
            echo '</div>';
            echo '</div>'; // .horario-info
            
            echo '</label>'; // .form-check-label
            echo '</div>'; // .form-check
            echo '</div>'; // .horario-card
        }
        echo '</div>'; // .horario-grid
    } else {
        echo '<div class="empty-state text-center py-5">';
        echo '<i class="bi bi-calendar-x display-4 text-muted mb-3"></i>';
        echo '<h5 class="text-muted mb-3">No hay horarios disponibles</h5>';
        echo '<p class="text-muted">No hay horarios para ' . date('d/m/Y', strtotime($fecha)) . ' (' . $diaSemanaEspanol . ').</p>';
        echo '<p class="text-muted">Prueba con otra fecha o verifica con el docente.</p>';
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-warning">Parámetros incompletos para cargar horarios.</div>';
}
?>