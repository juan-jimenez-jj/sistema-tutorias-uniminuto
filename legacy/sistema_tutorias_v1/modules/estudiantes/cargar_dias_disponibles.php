<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

checkSession();
checkRole('estudiante');

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['docente_id']) && isset($_GET['materia_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener días disponibles agrupados
    // Cambia la consulta a:
$query = "SELECT dd.dia_semana, 
                 COUNT(DISTINCT dd.id) as total_horarios,
                 MIN(TIME(dd.hora_inicio)) as hora_minima,
                 MAX(TIME(dd.hora_fin)) as hora_maxima
          FROM disponibilidad_docentes dd 
          WHERE dd.docente_id = :docente_id 
          AND dd.materia_id = :materia_id
          AND dd.estado = 'disponible'
          GROUP BY dd.dia_semana
          ORDER BY FIELD(dd.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado')";
          
    $stmt = $db->prepare($query);
    $stmt->bindParam(':docente_id', $_GET['docente_id']);
    $stmt->bindParam(':materia_id', $_GET['materia_id']);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo '<div class="dias-grid">';
        
        $iconos = [
            'Lunes' => 'calendar-day', 
            'Martes' => 'calendar-day', 
            'Miércoles' => 'calendar-day',
            'Jueves' => 'calendar-day', 
            'Viernes' => 'calendar-day', 
            'Sábado' => 'calendar-week'
        ];
        
        while ($dia = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $icono = $iconos[$dia['dia_semana']] ?? 'calendar-day';
            $hora_minima = date('g:i A', strtotime($dia['hora_minima']));
            $hora_maxima = date('g:i A', strtotime($dia['hora_maxima']));
            
            echo '<div class="dia-item">';
            echo '<div class="dia-nombre">';
            echo '<i class="bi bi-' . $icono . ' me-1"></i>';
            echo $dia['dia_semana'];
            echo '</div>';
            echo '<div class="dia-horario">';
            echo $hora_minima . ' - ' . $hora_maxima;
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        
    } else {
        echo '<div class="alert alert-warning text-center small mb-0">';
        echo '<i class="bi bi-exclamation-triangle me-1"></i>';
        echo 'El docente no tiene horarios configurados para esta materia.';
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-warning small">Parámetros incompletos.</div>';
}
?>