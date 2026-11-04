<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('docente');

$database = new Database();
$db = $database->getConnection();

// Obtener tutorías del docente con calificaciones
$query = "SELECT t.*, u.nombre as estudiante_nombre, u.id_institucional, m.nombre as materia_nombre,
                 dd.dia_semana, dd.hora_inicio, dd.hora_fin, dd.lugar
          FROM tutorias t
          JOIN usuarios u ON t.estudiante_id = u.id
          JOIN materias m ON t.materia_id = m.id
          JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
          WHERE t.docente_id = :docente_id
          ORDER BY t.fecha_tutoria DESC, dd.hora_inicio DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":docente_id", $_SESSION['user_id']);
$stmt->execute();
$tutorias = $stmt;

// Procesar cancelación docente
if ($_POST && isset($_POST['cancelar_tutoria'])) {
    $tutoria_id = $_POST['tutoria_id'];
    
    // Validar 3 horas de anticipación
    $query = "SELECT TIME_TO_SEC(TIMEDIFF(CONCAT(t.fecha_tutoria, ' ', dd.hora_inicio), NOW())) as segundos_restantes 
              FROM tutorias t 
              JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id 
              WHERE t.id = :id AND t.docente_id = :docente_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $tutoria_id);
    $stmt->bindParam(":docente_id", $_SESSION['user_id']);
    $stmt->execute();
    $tutoria_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tutoria_info && $tutoria_info['segundos_restantes'] > 10800) {
        $update_query = "UPDATE tutorias SET estado = 'cancelada', 
                         fecha_cancelacion = NOW(), 
                         motivo_cancelacion = 'Cancelado por el docente' 
                         WHERE id = :id AND docente_id = :docente_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":id", $tutoria_id);
        $update_stmt->bindParam(":docente_id", $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Tutoría cancelada exitosamente";
        } else {
            $_SESSION['error'] = "Error al cancelar la tutoría";
        }
    } else {
        $_SESSION['error'] = "No puedes cancelar la tutoría con menos de 3 horas de anticipación";
    }
    redirect('tutorias.php');
}

// Procesar completación de tutoría
if ($_POST && isset($_POST['completar_tutoria'])) {
    $tutoria_id = $_POST['tutoria_id'];
    $comentarios = $_POST['comentarios_docente'] ?? '';
    
    $update_query = "UPDATE tutorias SET estado = 'completada', 
                     fecha_completacion = NOW(), 
                     comentarios_docente = :comentarios
                     WHERE id = :id AND docente_id = :docente_id AND estado = 'programada'";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":id", $tutoria_id);
    $update_stmt->bindParam(":docente_id", $_SESSION['user_id']);
    $update_stmt->bindParam(":comentarios", $comentarios);
    
    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Tutoría marcada como completada";
    } else {
        $_SESSION['error'] = "Error al completar la tutoría";
    }
    redirect('tutorias.php');
}

// Calcular estadísticas de calificaciones SIMPLIFICADAS
$query_stats = "SELECT 
                COUNT(*) as total_completadas,
                AVG(calificacion) as promedio_calificacion,
                COUNT(calificacion) as tutorias_calificadas
            FROM tutorias 
            WHERE docente_id = :docente_id 
            AND estado = 'completada'";
$stmt_stats = $db->prepare($query_stats);
$stmt_stats->bindParam(":docente_id", $_SESSION['user_id']);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tutorías - Docente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        :root {
            --uniminuto-azul: #0056a3;
            --uniminuto-azul-oscuro: #7391ac;
            --uniminuto-verde: #28a745;
            --uniminuto-amarillo: #ffc107;
            --uniminuto-rojo: #dc3545;
            --uniminuto-morado: #6f42c1;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 86, 163, 0.15);
        }
        
        .table-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-card .card-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border: none;
            padding: 1.5rem 2rem;
        }
        
        .table th {
            background: var(--uniminuto-azul);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
        }
        
        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-color: #f1f3f4;
        }
        
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 86, 163, 0.05);
        }
        
        .badge-estado {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            border: 2px solid transparent;
        }
        
        .bg-success {
            background: linear-gradient(135deg, var(--uniminuto-verde) 0%, #34ce87 100%) !important;
            color: white !important;
            border-color: #1e7e34 !important;
        }
        
        .bg-warning {
            background: linear-gradient(135deg, var(--uniminuto-amarillo) 0%, #ffd54f 100%) !important;
            color: #000 !important;
            border-color: #d39e00 !important;
        }
        
        .bg-danger {
            background: linear-gradient(135deg, var(--uniminuto-rojo) 0%, #ff6b6b 100%) !important;
            color: white !important;
            border-color: #c82333 !important;
        }
        
        /* ESTILOS MEJORADOS PARA BOTONES */
        .btn-volver {
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .btn-volver:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,255,255,0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, var(--uniminuto-verde) 0%, #34ce87 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #2ea44f 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(40, 167, 69, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, var(--uniminuto-rojo) 0%, #ff6b6b 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #e35d6a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(220, 53, 69, 0.4);
        }
        
        .btn-outline-info {
            border: 2px solid var(--uniminuto-azul);
            color: var(--uniminuto-azul);
            font-weight: 600;
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            transition: all 0.3s ease;
        }
        
        .btn-outline-info:hover {
            background: var(--uniminuto-azul);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 86, 163, 0.3);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);
            border: none;
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268 0%, #727b84 100%);
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .calificacion-estrellas {
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        .comentario-docente {
            background: #e8f5e8;
            border-left: 4px solid var(--uniminuto-verde);
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        
        .tema-tratar {
            background: #e3f2fd;
            border-left: 4px solid var(--uniminuto-azul);
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Header de la página -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-2">
                        <i class="bi bi-people me-3"></i>Mis Tutorías
                    </h1>
                    <p class="fs-5 mb-0 opacity-90">Gestiona las tutorías programadas con tus estudiantes</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-volver">
                        <i class="bi bi-arrow-left me-2"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>
        
        <!-- Estadísticas de Calificaciones - MANTENIENDO EL ESTILO ORIGINAL -->
        <?php if ($stats['total_completadas'] > 0): ?>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-star"></i> Mis Calificaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6">
                                <h3 class="text-warning"><?php echo number_format($stats['promedio_calificacion'], 1); ?>/5</h3>
                                <p class="text-muted">Calificación Promedio</p>
                                <div class="text-warning" style="font-size: 1.5rem;">
                                    <?php 
                                    $promedio_redondeado = round($stats['promedio_calificacion']);
                                    for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $promedio_redondeado ? '-fill' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h3><?php echo $stats['tutorias_calificadas']; ?></h3>
                                <p class="text-muted">Tutorías Calificadas</p>
                                <small class="text-muted">
                                    de <?php echo $stats['total_completadas']; ?> tutorías completadas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Tabla de Tutorías -->
        <div class="table-card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> Lista de Tutorías</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="bi bi-people me-1"></i>
                            <?php echo $tutorias->rowCount(); ?> tutorías en total
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($tutorias->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Estudiante</th>
                                    <th>Materia</th>
                                    <th>Fecha</th>
                                    <th>Horario</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tutoria = $tutorias->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tutoria['estudiante_nombre']); ?></strong><br>
                                        <small class="text-muted">ID: <?php echo $tutoria['id_institucional']; ?></small>
                                        <?php if ($tutoria['estado'] == 'programada' && !empty($tutoria['tema_tratar'])): ?>
                                            <div class="tema-tratar">
                                                <small class="text-primary">
                                                    <strong><i class="bi bi-bookmark me-1"></i>Temas:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars(substr($tutoria['tema_tratar'], 0, 100))); ?>
                                                    <?php if (strlen($tutoria['tema_tratar']) > 100): ?>...<?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['comentarios_docente'])): ?>
                                            <div class="comentario-docente">
                                                <small class="text-success">
                                                    <strong><i class="bi bi-chat-quote me-1"></i>Mi comentario:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars(substr($tutoria['comentarios_docente'], 0, 100))); ?>
                                                    <?php if (strlen($tutoria['comentarios_docente']) > 100): ?>...<?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($tutoria['materia_nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo date('d/m/Y', strtotime($tutoria['fecha_tutoria'])); ?></div>
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['fecha_completacion'])): ?>
                                            <div class="text-success small mt-1">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Completada: <?php echo date('d/m H:i', strtotime($tutoria['fecha_completacion'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-muted">
                                            <?php echo date('H:i', strtotime($tutoria['hora_inicio'])) . ' - ' . date('H:i', strtotime($tutoria['hora_fin'])); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?php echo htmlspecialchars($tutoria['lugar']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-estado bg-<?php 
                                            echo $tutoria['estado'] == 'programada' ? 'warning' : 
                                                 ($tutoria['estado'] == 'completada' ? 'success' : 'danger'); 
                                        ?>">
                                            <i class="bi bi-<?php 
                                                echo $tutoria['estado'] == 'programada' ? 'clock' : 
                                                     ($tutoria['estado'] == 'completada' ? 'check-circle' : 'x-circle'); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst($tutoria['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['calificacion'])): ?>
                                            <div class="calificacion-estrellas text-center">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $tutoria['calificacion'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                                <div class="small text-muted mt-1"><?php echo $tutoria['calificacion']; ?>/5</div>
                                            </div>
                                            <?php if (!empty($tutoria['comentarios_estudiante'])): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info mt-2 w-100" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#feedbackModal"
                                                        data-comentario="<?php echo htmlspecialchars($tutoria['comentarios_estudiante']); ?>"
                                                        data-estudiante="<?php echo htmlspecialchars($tutoria['estudiante_nombre']); ?>"
                                                        data-calificacion="<?php echo $tutoria['calificacion']; ?>">
                                                    <i class="bi bi-chat me-1"></i> Ver Feedback
                                                </button>
                                            <?php endif; ?>
                                        <?php elseif ($tutoria['estado'] == 'completada'): ?>
                                            <span class="text-muted text-center d-block">Esperando calificación</span>
                                        <?php else: ?>
                                            <span class="text-muted text-center d-block">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tutoria['estado'] == 'programada'): ?>
                                        <div class="d-flex flex-column gap-2">
                                            <button type="button" class="btn btn-success btn-sm" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#completarModal"
                                                    data-tutoria-id="<?php echo $tutoria['id']; ?>">
                                                <i class="bi bi-check-circle me-1"></i> Completar
                                            </button>
                                            <form method="POST" class="d-grid">
                                                <input type="hidden" name="tutoria_id" value="<?php echo $tutoria['id']; ?>">
                                                <input type="hidden" name="cancelar_tutoria" value="1">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('¿Estás seguro de cancelar esta tutoría?')">
                                                    <i class="bi bi-x-circle me-1"></i> Cancelar
                                                </button>
                                            </form>
                                        </div>
                                        <?php elseif ($tutoria['estado'] == 'completada'): ?>
                                        <span class="text-success text-center d-block">
                                            <i class="bi bi-check-circle"></i><br>
                                            Completada
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted text-center d-block">No disponible</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        <h4 class="text-muted mb-3">No tienes tutorías programadas</h4>
                        <p class="text-muted mb-4">Los estudiantes podrán solicitar tutorías contigo</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Completar Tutoría -->
    <div class="modal fade" id="completarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="completar_tutoria" value="1">
                    <input type="hidden" name="tutoria_id" id="modal_tutoria_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle me-2"></i>Marcar Tutoría como Completada
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Comentarios (opcional)</label>
                            <textarea class="form-control" name="comentarios_docente" rows="4" 
                                      placeholder="Comentarios sobre la tutoría realizada..."></textarea>
                            <div class="form-text">Puedes agregar observaciones sobre los temas tratados.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Marcar como Completada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ver Feedback del Estudiante -->
    <div class="modal fade" id="feedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="bi bi-chat-heart me-2"></i> Feedback del Estudiante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <h6 id="estudianteNombre"></h6>
                        <div class="text-warning" id="estrellasFeedback" style="font-size: 1.5rem;">
                            <!-- Las estrellas se llenarán con JavaScript -->
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><strong>Comentario del estudiante:</strong></label>
                        <div class="alert alert-light border" id="comentarioEstudiante" style="min-height: 100px;">
                            <!-- El comentario se mostrará aquí -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de completar tutoría
        const completarModal = document.getElementById('completarModal');
        if (completarModal) {
            completarModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const tutoriaId = button.getAttribute('data-tutoria-id');
                document.getElementById('modal_tutoria_id').value = tutoriaId;
            });
        }

        // Configurar modal de feedback del estudiante
        const feedbackModal = document.getElementById('feedbackModal');
        if (feedbackModal) {
            feedbackModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const comentario = button.getAttribute('data-comentario');
                const estudiante = button.getAttribute('data-estudiante');
                const calificacion = button.getAttribute('data-calificacion');
                
                document.getElementById('estudianteNombre').textContent = 'Feedback de: ' + estudiante;
                document.getElementById('comentarioEstudiante').textContent = comentario;
                
                // Generar estrellas
                const estrellasContainer = document.getElementById('estrellasFeedback');
                estrellasContainer.innerHTML = '';
                for (let i = 1; i <= 5; i++) {
                    const star = document.createElement('i');
                    star.className = `bi bi-star${i <= calificacion ? '-fill' : ''}`;
                    star.style.margin = '0 2px';
                    estrellasContainer.appendChild(star);
                }
            });
        }
    </script>
</body>
</html>