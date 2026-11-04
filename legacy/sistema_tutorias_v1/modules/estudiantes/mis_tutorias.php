<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../modelos/Tutoria.php';
checkSession();
checkRole('estudiante');

$database = new Database();
$db = $database->getConnection();

$tutoria = new Tutoria($db);
$tutorias = $tutoria->obtenerPorEstudiante($_SESSION['user_id']);

// Procesar calificación y comentarios del estudiante
if ($_POST && isset($_POST['calificar_tutoria'])) {
    $tutoria_id = $_POST['tutoria_id'];
    $calificacion = $_POST['calificacion'];
    $comentarios = $_POST['comentarios_estudiante'] ?? '';
    
    $query = "UPDATE tutorias SET calificacion = :calificacion, 
                                 comentarios_estudiante = :comentarios
              WHERE id = :id AND estudiante_id = :estudiante_id AND estado = 'completada'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $tutoria_id);
    $stmt->bindParam(":estudiante_id", $_SESSION['user_id']);
    $stmt->bindParam(":calificacion", $calificacion);
    $stmt->bindParam(":comentarios", $comentarios);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "¡Gracias por tu calificación!";
    } else {
        $_SESSION['error'] = "Error al enviar la calificación";
    }
    redirect('mis_tutorias.php');
}

// Procesar cancelación estudiante
if ($_POST && isset($_POST['cancelar_tutoria'])) {
    $tutoria_id = $_POST['tutoria_id'];
    
    // Validar 3 horas de anticipación
    $query = "SELECT TIME_TO_SEC(TIMEDIFF(CONCAT(t.fecha_tutoria, ' ', dd.hora_inicio), NOW())) as segundos_restantes 
              FROM tutorias t 
              JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id 
              WHERE t.id = :id AND t.estudiante_id = :estudiante_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $tutoria_id);
    $stmt->bindParam(":estudiante_id", $_SESSION['user_id']);
    $stmt->execute();
    $tutoria_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tutoria_info && $tutoria_info['segundos_restantes'] > 10800) {
        $update_query = "UPDATE tutorias SET estado = 'cancelada', 
                         fecha_cancelacion = NOW(), 
                         motivo_cancelacion = 'Cancelado por el estudiante' 
                         WHERE id = :id AND estudiante_id = :estudiante_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":id", $tutoria_id);
        $update_stmt->bindParam(":estudiante_id", $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Tutoría cancelada exitosamente";
        } else {
            $_SESSION['error'] = "Error al cancelar la tutoría";
        }
    } else {
        $_SESSION['error'] = "No puedes cancelar la tutoría con menos de 3 horas de anticipación";
    }
    redirect('mis_tutorias.php');
}

// Obtener datos para estadísticas
$tutorias_data = $tutorias->fetchAll(PDO::FETCH_ASSOC);
$total_tutorias = count($tutorias_data);
$completadas = 0;
$programadas = 0;
$canceladas = 0;

foreach ($tutorias_data as $tut) {
    if ($tut['estado'] == 'completada') $completadas++;
    if ($tut['estado'] == 'programada') $programadas++;
    if ($tut['estado'] == 'cancelada') $canceladas++;
}

// Reconstruir el resultado para la tabla
$tutorias = new ArrayIterator($tutorias_data);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tutorías - UNIMINUTO</title>
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
            --uniminuto-cyan: #17a2b8;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 15px 35px rgba(0, 86, 163, 0.15);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .stats-card {
            border: none;
            border-radius: 20px;
            padding: 2rem 1.5rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
        }
        
        .stats-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: rotate(30deg);
            transition: all 0.6s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }
        
        .stats-card:hover::after {
            transform: rotate(30deg) translate(20px, 20px);
        }
        
        .bg-uniminuto-primary {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, #1e6fd9 100%);
        }
        
        .bg-uniminuto-success {
            background: linear-gradient(135deg, var(--uniminuto-verde) 0%, #34ce87 100%);
        }
        
        .bg-uniminuto-warning {
            background: linear-gradient(135deg, var(--uniminuto-amarillo) 0%, #ffd54f 100%);
        }
        
        .bg-uniminuto-danger {
            background: linear-gradient(135deg, var(--uniminuto-rojo) 0%, #ff6b6b 100%);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            text-align: center;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .stats-content {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .stats-content h3 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stats-content p {
            opacity: 0.95;
            margin-bottom: 0;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .table-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
        
        .table tbody tr:hover {
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
        
        .rating-stars {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        
        .rating-stars input {
            display: none;
        }
        
        .rating-stars label {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .rating-stars input:checked ~ label,
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: #ffc107;
        }
        
        .btn-action {
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
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
        
        .btn-volver {
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-volver:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.5);
        }
        
        .btn-light {
            background: rgba(255,255,255,0.9);
            border: 2px solid rgba(255,255,255,0.3);
            color: var(--uniminuto-azul);
            font-weight: 600;
        }
        
        .btn-light:hover {
            background: white;
            border-color: rgba(255,255,255,0.6);
            color: var(--uniminuto-azul);
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Header de la página -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-journal-bookmark me-3"></i>Mis Tutorías
                    </h1>
                    <p class="fs-4 mb-0 opacity-90">Historial y estado de todas tus tutorías programadas</p>
                </div>
                <div class="col-md-6 text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="dashboard.php" class="btn btn-volver btn-lg">
                            <i class="bi bi-arrow-left me-2"></i> Volver al Dashboard
                        </a>
                        <a href="solicitar_tutoria.php" class="btn btn-light btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>Nueva Tutoría
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>

        <!-- Estadísticas rápidas - NUEVO ESTILO ESPECTACULAR -->
        <div class="stats-grid">
            <div class="stats-card bg-uniminuto-primary">
                <div class="stats-icon">
                    <i class="bi bi-journal"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_tutorias; ?></h3>
                    <p>Total Tutorías</p>
                </div>
            </div>
            <div class="stats-card bg-uniminuto-success">
                <div class="stats-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $completadas; ?></h3>
                    <p>Completadas</p>
                </div>
            </div>
            <div class="stats-card bg-uniminuto-warning">
                <div class="stats-icon">
                    <i class="bi bi-clock"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $programadas; ?></h3>
                    <p>Programadas</p>
                </div>
            </div>
            <div class="stats-card bg-uniminuto-danger">
                <div class="stats-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $canceladas; ?></h3>
                    <p>Canceladas</p>
                </div>
            </div>
        </div>
        
        <!-- Tabla de tutorías -->
        <div class="table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> Historial de Tutorías</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($tutorias_data) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Docente</th>
                                    <th>Materia</th>
                                    <th>Fecha y Horario</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tutorias_data as $tutoria): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tutoria['docente_nombre']); ?></strong>
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['comentarios_docente'])): ?>
                                            <div class="comentario-docente mt-2">
                                                <small class="text-success">
                                                    <strong><i class="bi bi-chat-quote me-1"></i>Comentario del docente:</strong><br>
                                                    <?php echo htmlspecialchars($tutoria['comentarios_docente']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tutoria['materia_nombre']); ?></strong>
                                        <?php if (!empty($tutoria['tema_tratar'])): ?>
                                            <div class="tema-tratar mt-2">
                                                <small class="text-primary">
                                                    <strong><i class="bi bi-bookmark me-1"></i>Temas:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($tutoria['tema_tratar'])); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo date('d/m/Y', strtotime($tutoria['fecha_tutoria'])); ?></div>
                                        <div class="text-muted small">
                                            <i class="bi bi-clock me-1"></i>
                                            <?php echo date('H:i', strtotime($tutoria['hora_inicio'])) . ' - ' . date('H:i', strtotime($tutoria['hora_fin'])); ?>
                                        </div>
                                        <div class="text-muted small">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?php echo htmlspecialchars($tutoria['lugar']); ?>
                                        </div>
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['fecha_completacion'])): ?>
                                            <div class="text-success small mt-1">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Completada: <?php echo date('d/m H:i', strtotime($tutoria['fecha_completacion'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge-estado bg-<?php 
                                            echo $tutoria['estado'] == 'programada' ? 'warning' : 
                                                 ($tutoria['estado'] == 'completada' ? 'success' : 
                                                 ($tutoria['estado'] == 'cancelada' ? 'danger' : 'secondary')); 
                                        ?>">
                                            <i class="bi bi-<?php 
                                                echo $tutoria['estado'] == 'programada' ? 'clock' : 
                                                     ($tutoria['estado'] == 'completada' ? 'check-circle' : 
                                                     ($tutoria['estado'] == 'cancelada' ? 'x-circle' : 'dash-circle')); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst($tutoria['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['calificacion'])): ?>
                                            <div class="text-warning text-center">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $tutoria['calificacion'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                                <div class="small text-muted mt-1"><?php echo $tutoria['calificacion']; ?>/5</div>
                                                <?php if (!empty($tutoria['comentarios_estudiante'])): ?>
                                                    <small class="text-muted d-block mt-1">
                                                        <i class="bi bi-chat-left me-1"></i>
                                                        "<?php echo htmlspecialchars($tutoria['comentarios_estudiante']); ?>"
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif ($tutoria['estado'] == 'completada'): ?>
                                            <div class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-warning btn-action" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#calificarModal"
                                                        data-tutoria-id="<?php echo $tutoria['id']; ?>">
                                                    <i class="bi bi-star me-1"></i> Calificar
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted text-center d-block">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tutoria['estado'] == 'programada'): ?>
                                        <div class="text-center">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="tutoria_id" value="<?php echo $tutoria['id']; ?>">
                                                <input type="hidden" name="cancelar_tutoria" value="1">
                                                <button type="submit" class="btn btn-sm btn-danger btn-action" 
                                                        onclick="return confirm('¿Estás seguro de cancelar esta tutoría?')">
                                                    <i class="bi bi-x-circle me-1"></i> Cancelar
                                                </button>
                                            </form>
                                        </div>
                                        <?php elseif ($tutoria['estado'] == 'completada'): ?>
                                        <span class="text-success text-center d-block">
                                            <i class="bi bi-check-circle me-1"></i> Finalizada
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted text-center d-block">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x"></i>
                        <h4 class="text-muted mb-3">No tienes tutorías programadas</h4>
                        <p class="text-muted mb-4">Reserva tu primera tutoría con un docente especializado</p>
                        <a href="solicitar_tutoria.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle me-2"></i> Reservar Primera Tutoría
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Calificar Tutoría -->
    <div class="modal fade" id="calificarModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-uniminuto-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-star me-2"></i>Calificar Tutoría
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="calificar_tutoria" value="1">
                    <input type="hidden" name="tutoria_id" id="modal_tutoria_id_calificar">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4 text-center">
                                    <label class="form-label fw-bold fs-5">Calificación</label>
                                    <div class="rating-stars mb-3 d-inline-flex">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <input type="radio" id="star<?php echo $i; ?>" name="calificacion" value="<?php echo $i; ?>" required>
                                            <label for="star<?php echo $i; ?>"><i class="bi bi-star"></i></label>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="text-muted small">Selecciona de 1 a 5 estrellas</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Comentarios (opcional)</label>
                                    <textarea class="form-control" name="comentarios_estudiante" rows="5" 
                                              placeholder="¿Cómo fue tu experiencia en la tutoría?
¿El docente explicó claramente los temas?
¿Recomendarías esta tutoría a otros estudiantes?"></textarea>
                                    <div class="form-text">Tu feedback ayuda a mejorar el servicio</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-send me-2"></i>Enviar Calificación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de calificación
        const calificarModal = document.getElementById('calificarModal');
        if (calificarModal) {
            calificarModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const tutoriaId = button.getAttribute('data-tutoria-id');
                document.getElementById('modal_tutoria_id_calificar').value = tutoriaId;
                
                // Reset stars cuando se abre el modal
                document.querySelectorAll('.rating-stars input').forEach(input => {
                    input.checked = false;
                });
                document.querySelectorAll('.rating-stars label').forEach(label => {
                    label.innerHTML = '<i class="bi bi-star"></i>';
                });
            });

            // Sistema de estrellas interactivo
            const stars = document.querySelectorAll('.rating-stars input');
            stars.forEach((star, index) => {
                star.addEventListener('change', () => {
                    // Reset all stars
                    document.querySelectorAll('.rating-stars label').forEach(label => {
                        label.innerHTML = '<i class="bi bi-star"></i>';
                    });
                    
                    // Fill stars up to selected
                    for (let i = 0; i <= index; i++) {
                        document.querySelector(`label[for="star${i + 1}"]`).innerHTML = '<i class="bi bi-star-fill"></i>';
                    }
                });
            });
        }
    </script>
</body>
</html>