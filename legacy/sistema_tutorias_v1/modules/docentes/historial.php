<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('docente');

$database = new Database();
$db = $database->getConnection();

// Obtener historial completo de tutorías
$query_tutorias = "
    SELECT t.*, u.nombre as estudiante_nombre, u.id_institucional, 
           m.nombre as materia_nombre, m.codigo as materia_codigo,
           dd.dia_semana, dd.hora_inicio, dd.hora_fin, dd.lugar
    FROM tutorias t
    JOIN usuarios u ON t.estudiante_id = u.id
    JOIN materias m ON t.materia_id = m.id
    JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
    WHERE t.docente_id = :docente_id 
    ORDER BY t.fecha_tutoria DESC, dd.hora_inicio DESC
";

$stmt_tutorias = $db->prepare($query_tutorias);
$stmt_tutorias->bindParam(":docente_id", $_SESSION['user_id']);
$stmt_tutorias->execute();
$tutorias = $stmt_tutorias;

// Estadísticas generales - ACTUALIZADAS con nuevo estilo
$query_estadisticas = "
    SELECT 
        COUNT(*) as total_tutorias,
        SUM(CASE WHEN t.estado = 'completada' THEN 1 ELSE 0 END) as completadas,
        SUM(CASE WHEN t.estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
        SUM(CASE WHEN t.estado = 'programada' THEN 1 ELSE 0 END) as programadas,
        AVG(CASE WHEN t.calificacion IS NOT NULL THEN t.calificacion ELSE NULL END) as promedio_calificacion,
        COUNT(t.calificacion) as tutorias_calificadas
    FROM tutorias t
    WHERE t.docente_id = :docente_id
";

$stmt_estadisticas = $db->prepare($query_estadisticas);
$stmt_estadisticas->bindParam(":docente_id", $_SESSION['user_id']);
$stmt_estadisticas->execute();
$estadisticas = $stmt_estadisticas->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Tutorías - Docente</title>
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
            grid-template-columns: repeat(5, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        @media (max-width: 1400px) {
            .stats-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 992px) {
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
            min-height: 200px;
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
        
        .bg-uniminuto-info {
            background: linear-gradient(135deg, var(--uniminuto-cyan) 0%, #4fc3f7 100%);
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
        
        .stats-subtitle {
            font-size: 0.75rem;
            opacity: 0.8;
            margin-top: 0.3rem;
            font-weight: 500;
            text-transform: none;
            letter-spacing: 0.5px;
        }
        
        .calificacion-promedio {
            font-size: 2.2rem;
            font-weight: 800;
            color: #ffc107;
            text-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 1.1rem;
            margin-top: 0.3rem;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
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
        
        /* Estilos existentes del historial */
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
        
        .bg-uniminuto-purple {
            background: linear-gradient(135deg, var(--uniminuto-morado) 0%, #9d7ce6 100%);
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
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-clock-history me-3"></i>Historial de Tutorías
                    </h1>
                    <p class="fs-4 mb-0 opacity-90">Registro completo de todas tus tutorías</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-volver">
                        <i class="bi bi-arrow-left me-2"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>

        <!-- Estadísticas Principales - 5 TARJETAS EN UNA LÍNEA -->
        <div class="stats-grid">
            <!-- Total Tutorías -->
            <div class="stats-card bg-uniminuto-primary">
                <div class="stats-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $estadisticas['total_tutorias'] ?? 0; ?></h3>
                    <p>Total Tutorías</p>
                    <div class="stats-subtitle">
                        Historial completo
                    </div>
                </div>
            </div>
            
            <!-- Tutorías Completadas -->
            <div class="stats-card bg-uniminuto-success">
                <div class="stats-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $estadisticas['completadas'] ?? 0; ?></h3>
                    <p>Completadas</p>
                    <div class="stats-subtitle">
                        <?php echo $estadisticas['total_tutorias'] ? round(($estadisticas['completadas'] / $estadisticas['total_tutorias']) * 100) : 0; ?>% del total
                    </div>
                </div>
            </div>
            
            <!-- Calificación Promedio -->
            <div class="stats-card bg-uniminuto-purple">
                <div class="stats-icon">
                    <i class="bi bi-star"></i>
                </div>
                <div class="stats-content">
                    <?php if ($estadisticas['promedio_calificacion']): ?>
                        <div class="calificacion-promedio">
                            <?php echo number_format($estadisticas['promedio_calificacion'], 1); ?>
                        </div>
                        <p>Calificación</p>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?php echo $i <= round($estadisticas['promedio_calificacion']) ? '-fill' : ''; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="stats-subtitle">
                            <?php echo $estadisticas['tutorias_calificadas']; ?> evaluaciones
                        </div>
                    <?php else: ?>
                        <h3>-</h3>
                        <p>Sin calificaciones</p>
                        <div class="stats-subtitle">
                            Aún no hay evaluaciones
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tutorías Programadas -->
            <div class="stats-card bg-uniminuto-info">
                <div class="stats-icon">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $estadisticas['programadas'] ?? 0; ?></h3>
                    <p>Programadas</p>
                    <div class="stats-subtitle">
                        Próximas sesiones
                    </div>
                </div>
            </div>
            
            <!-- Tutorías Canceladas -->
            <div class="stats-card bg-uniminuto-danger">
                <div class="stats-icon">
                    <i class="bi bi-x-circle"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $estadisticas['canceladas'] ?? 0; ?></h3>
                    <p>Canceladas</p>
                    <div class="stats-subtitle">
                        <?php echo $estadisticas['total_tutorias'] ? round(($estadisticas['canceladas'] / $estadisticas['total_tutorias']) * 100) : 0; ?>% del total
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Historial (MANTENIENDO ESTILO ORIGINAL) -->
        <div class="table-card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i> Todas mis Tutorías</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="bi bi-list-ul me-1"></i>
                            <?php echo $tutorias->rowCount(); ?> registros en total
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
                                    <th>Fecha</th>
                                    <th>Estudiante</th>
                                    <th>Materia</th>
                                    <th>Horario</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                    <th>Temas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($tutoria = $tutorias->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo date('d/m/Y', strtotime($tutoria['fecha_tutoria'])); ?></div>
                                        <div class="text-muted small">
                                            <?php echo date('H:i', strtotime($tutoria['hora_inicio'])) . ' - ' . date('H:i', strtotime($tutoria['hora_fin'])); ?>
                                        </div>
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['fecha_completacion'])): ?>
                                            <div class="text-success small mt-1">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Completada: <?php echo date('d/m H:i', strtotime($tutoria['fecha_completacion'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($tutoria['estudiante_nombre']); ?></strong><br>
                                        <small class="text-muted">ID: <?php echo $tutoria['id_institucional']; ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($tutoria['materia_nombre']); ?></strong><br>
                                        <small class="text-muted"><?php echo $tutoria['materia_codigo']; ?></small>
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
                                        <?php elseif ($tutoria['estado'] == 'completada'): ?>
                                            <span class="text-muted text-center d-block">Esperando calificación</span>
                                        <?php else: ?>
                                            <span class="text-muted text-center d-block">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tutoria['tema_tratar'])): ?>
                                            <div class="tema-tratar">
                                                <small class="text-primary">
                                                    <strong><i class="bi bi-bookmark me-1"></i>Temas:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars(substr($tutoria['tema_tratar'], 0, 100))); ?>
                                                    <?php if (strlen($tutoria['tema_tratar']) > 100): ?>...<?php endif; ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin temas registrados</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($tutoria['estado'] == 'completada' && !empty($tutoria['comentarios_docente'])): ?>
                                            <div class="comentario-docente mt-2">
                                                <small class="text-success">
                                                    <strong><i class="bi bi-chat-quote me-1"></i>Mi comentario:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars(substr($tutoria['comentarios_docente'], 0, 100))); ?>
                                                    <?php if (strlen($tutoria['comentarios_docente']) > 100): ?>...<?php endif; ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x"></i>
                        <h4 class="text-muted mb-3">Aún no tienes tutorías</h4>
                        <p class="text-muted mb-4">Tu historial de tutorías aparecerá aquí</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>