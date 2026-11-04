<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';

checkSession();
checkRole('estudiante');

if (isset($_GET['docente_id']) && isset($_GET['materia_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener información del docente y materia
    $query_info = "SELECT u.nombre as docente_nombre, u.programa_academico, 
                          m.nombre as materia_nombre, m.codigo as materia_codigo
                   FROM usuarios u, materias m
                   WHERE u.id = :docente_id 
                   AND m.id = :materia_id";
    $stmt_info = $db->prepare($query_info);
    $stmt_info->bindParam(':docente_id', $_GET['docente_id']);
    $stmt_info->bindParam(':materia_id', $_GET['materia_id']);
    $stmt_info->execute();
    $info = $stmt_info->fetch(PDO::FETCH_ASSOC);
    
    if (!$info) {
        echo '<div class="alert alert-danger">Error: Docente o materia no encontrados.</div>';
        exit;
    }
    
    // CONSULTA SIMPLIFICADA SIN CUPOS - SOLO DISPONIBILIDAD
    $query = "SELECT dd.*
              FROM disponibilidad_docentes dd 
              WHERE dd.docente_id = :docente_id 
              AND dd.materia_id = :materia_id
              AND dd.estado = 'disponible'
              ORDER BY 
                FIELD(dd.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'),
                dd.hora_inicio";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':docente_id', $_GET['docente_id']);
    $stmt->bindParam(':materia_id', $_GET['materia_id']);
    $stmt->execute();
    
    $horarios = $stmt;
    
    if ($horarios->rowCount() > 0) {
        $dias_agrupados = [];
        
        while ($horario = $horarios->fetch(PDO::FETCH_ASSOC)) {
            $dias_agrupados[$horario['dia_semana']][] = $horario;
        }
        
        // Header informativo del docente y materia
        echo '<div class="docente-info mb-4 p-3 rounded" style="background: linear-gradient(135deg, #e3f2fd 0%, #f0f9ff 100%); border-left: 4px solid var(--uniminuto-azul);">';
        echo '<div class="row align-items-center">';
        echo '<div class="col-md-8">';
        echo '<h6 class="text-primary mb-1">';
        echo '<i class="bi bi-person-check me-2"></i>';
        echo '<strong>' . htmlspecialchars($info['docente_nombre']) . '</strong>';
        echo '</h6>';
        echo '<p class="text-muted small mb-0">';
        echo '<i class="bi bi-book me-1"></i>';
        echo '<strong>' . htmlspecialchars($info['materia_codigo'] . ' - ' . $info['materia_nombre']) . '</strong>';
        echo '</p>';
        echo '</div>';
        echo '<div class="col-md-4 text-end">';
        echo '<span class="badge bg-success">';
        echo '<i class="bi bi-clock-history me-1"></i>';
        echo $horarios->rowCount() . ' horario' . ($horarios->rowCount() > 1 ? 's' : '') . ' disponible' . ($horarios->rowCount() > 1 ? 's' : '');
        echo '</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Mostrar horarios agrupados por día
        foreach ($dias_agrupados as $dia => $horarios_dia) {
            echo '<div class="horario-dia">';
            echo '<div class="dia-header">';
            echo '<h6 class="mb-1 text-primary">';
            echo '<i class="bi bi-calendar-day me-2"></i>' . $dia;
            echo '</h6>';
            echo '<small class="text-muted">' . count($horarios_dia) . ' horario' . (count($horarios_dia) > 1 ? 's' : '') . ' disponible' . (count($horarios_dia) > 1 ? 's' : '') . '</small>';
            echo '</div>';
            
            echo '<div class="horario-grid">';
            foreach ($horarios_dia as $horario) {
                $hora_inicio = date('g:i A', strtotime($horario['hora_inicio']));
                $hora_fin = date('g:i A', strtotime($horario['hora_fin']));
                $horario_id = $horario['id'];
                
                echo '<div class="horario-card">';
                echo '<div class="form-check mb-0">';
                echo '<input class="form-check-input" type="radio" name="disponibilidad_id" 
                             value="' . $horario_id . '" id="horario' . $horario_id . '" required>';
                echo '<label class="form-check-label w-100" for="horario' . $horario_id . '">';
                
                // Información principal del horario
                echo '<div class="d-flex justify-content-between align-items-start mb-2">';
                echo '<span class="fw-bold text-dark fs-6">' . $hora_inicio . ' - ' . $hora_fin . '</span>';
                
                // Badge de disponibilidad SIMPLE (sin cupos)
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
            echo '</div>'; // .horario-dia
        }
        
    } else {
        echo '<div class="empty-state text-center py-5">';
        echo '<i class="bi bi-calendar-x display-4 text-muted mb-3"></i>';
        echo '<h5 class="text-muted mb-3">No hay horarios disponibles</h5>';
        echo '<p class="text-muted mb-4">No hay horarios disponibles para ' . htmlspecialchars($info['materia_codigo']) . ' con este docente.</p>';
        echo '</div>';
    }
} else {
    echo '<div class="empty-state text-center py-4">';
    echo '<i class="bi bi-info-circle display-6 text-muted mb-3"></i>';
    echo '<h5 class="text-muted mb-3">Selecciona docente y materia</h5>';
    echo '<p class="text-muted">Elige un docente y una materia para ver los horarios disponibles</p>';
    echo '</div>';
}
?>