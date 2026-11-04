<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

checkSession();
checkRole('estudiante');

header('Content-Type: text/html; charset=utf-8');

if (isset($_GET['docente_id']) && isset($_GET['materia_id']) && isset($_GET['dia'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $dia = $_GET['dia'];
    
    // Mapear días español a inglés para date()
    $diasIngles = [
        'Lunes' => 'Monday',
        'Martes' => 'Tuesday', 
        'Miércoles' => 'Wednesday',
        'Jueves' => 'Thursday',
        'Viernes' => 'Friday',
        'Sábado' => 'Saturday'
    ];
    
    $diaIngles = $diasIngles[$dia] ?? $dia;
    
    // Generar las próximas 4 semanas para este día
    echo '<div class="row g-2">';
    
    for ($semana = 1; $semana <= 4; $semana++) {
        $fecha = new DateTime();
        
        // Encontrar el próximo día de la semana
        $fecha->modify('next ' . $diaIngles);
        
        // Si es la semana 2 o más, avanzar semanas
        if ($semana > 1) {
            $fecha->modify('+' . ($semana - 1) . ' weeks');
        }
        
        $fechaISO = $fecha->format('Y-m-d');
        $fechaDisplay = $fecha->format('d/m');
        
        // Verificar si hay horarios disponibles para esta fecha
        $query = "SELECT COUNT(DISTINCT dd.id) as horarios_disponibles
                  FROM disponibilidad_docentes dd 
                  LEFT JOIN tutorias t ON dd.id = t.disponibilidad_id AND t.estado = 'programada'
                  WHERE dd.docente_id = :docente_id 
                  AND dd.materia_id = :materia_id
                  AND dd.dia_semana = :dia_semana
                  AND dd.estado = 'disponible'
                  GROUP BY dd.id
                  HAVING (dd.max_estudiantes - COUNT(DISTINCT t.estudiante_id)) > 0";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':docente_id', $_GET['docente_id']);
        $stmt->bindParam(':materia_id', $_GET['materia_id']);
        $stmt->bindParam(':dia_semana', $dia);
        $stmt->execute();
        $disponibles = $stmt->rowCount();
        
        $badgeClass = $disponibles > 0 ? 'bg-success' : 'bg-secondary';
        $badgeText = $disponibles > 0 ? $disponibles . ' disp.' : 'No disp.';
        
        echo '<div class="col-md-6 col-lg-3">';
        echo '<div class="semana-btn" onclick="seleccionarFecha(\'' . $fechaISO . '\', this)">';
        echo '<div class="fw-bold">' . $fechaDisplay . '</div>';
        echo '<small class="text-muted">Semana ' . $semana . '</small>';
        echo '<div class="mt-1">';
        echo '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
} else {
    echo '<div class="alert alert-warning">Parámetros incompletos.</div>';
}
?>