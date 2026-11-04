<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('docente');

$database = new Database();
$db = $database->getConnection();

// Estadísticas COMPLETAS del docente
$query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'programada' THEN 1 ELSE 0 END) as pendientes,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas,
            SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
            AVG(CASE WHEN calificacion IS NOT NULL THEN calificacion ELSE NULL END) as promedio,
            COUNT(CASE WHEN calificacion IS NOT NULL THEN 1 ELSE NULL END) as total_calificadas
          FROM tutorias 
          WHERE docente_id = :docente_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":docente_id", $_SESSION['user_id']);
$stmt->execute();
$estadisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Materias asignadas al docente - MEJORADA para diseño horizontal
$query_materias = "SELECT m.codigo, m.nombre 
                   FROM materias m
                   JOIN docente_materia dm ON m.id = dm.materia_id
                   WHERE dm.docente_id = :docente_id
                   AND m.estado = 'activa'
                   ORDER BY m.codigo";
$stmt_materias = $db->prepare($query_materias);
$stmt_materias->bindParam(":docente_id", $_SESSION['user_id']);
$stmt_materias->execute();
$materias = $stmt_materias->fetchAll(PDO::FETCH_ASSOC);

// Próximas tutorías (máximo 5 para ocupar más espacio)
$query_proximas = "SELECT t.*, m.nombre as materia_nombre, u.nombre as estudiante_nombre,
                          dd.hora_inicio, dd.hora_fin, dd.lugar, t.tema_tratar
                   FROM tutorias t
                   JOIN materias m ON t.materia_id = m.id
                   JOIN usuarios u ON t.estudiante_id = u.id
                   JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
                   WHERE t.docente_id = :docente_id 
                   AND t.estado = 'programada'
                   AND CONCAT(t.fecha_tutoria, ' ', dd.hora_inicio) > NOW()
                   ORDER BY t.fecha_tutoria, dd.hora_inicio
                   LIMIT 5";
$stmt_proximas = $db->prepare($query_proximas);
$stmt_proximas->bindParam(":docente_id", $_SESSION['user_id']);
$stmt_proximas->execute();
$proximas_tutorias = $stmt_proximas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Docente - UNIMINUTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        :root {
            --uniminuto-azul: #0056a3;
            --uniminuto-azul-oscuro: #7391ac;
            --uniminuto-azul-claro: #0077cc;
            --uniminuto-blanco: #ffffff;
            --uniminuto-gris: #f8f9fa;
            --uniminuto-verde: #28a745;
            --uniminuto-amarillo: #ffc107;
            --uniminuto-rojo: #dc3545;
            --uniminuto-morado: #6f42c1;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 86, 163, 0.15);
        }
        
        .action-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            background: white;
        }
        
        .action-card:hover {
            border-color: var(--uniminuto-azul);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .action-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--uniminuto-azul);
        }
        
        .action-card h5 {
            color: var(--uniminuto-azul);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .action-card p {
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .action-card .btn {
            border-radius: 8px;
            padding: 10px 25px;
            font-weight: 600;
        }
        
        .info-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .info-section h6 {
            color: var(--uniminuto-azul);
            font-weight: 600;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e3f2fd;
            padding-bottom: 0.5rem;
        }
        
        /* DISEÑO MEJORADO PARA MATERIAS - HORIZONTAL */
        .materias-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 0.5rem;
        }
        
        .materia-badge {
            background: linear-gradient(135deg, #e3f2fd 0%, #f0f9ff 100%);
            border: 1px solid #b3d9ff;
            color: var(--uniminuto-azul);
            padding: 0.6rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 120px;
            text-align: center;
        }
        
        .materia-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,86,163,0.15);
            border-color: var(--uniminuto-azul);
        }
        
        .materia-codigo {
            font-weight: 700;
            font-size: 0.8rem;
            display: block;
            margin-bottom: 0.2rem;
        }
        
        .materia-nombre {
            font-size: 0.75rem;
            opacity: 0.9;
            display: block;
            line-height: 1.2;
        }
        
        /* DISEÑO MEJORADO PARA PRÓXIMAS TUTORÍAS - ANCHO COMPLETO */
        .tutorias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }
        
        .tutoria-card {
            background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
            border: 2px solid var(--uniminuto-amarillo);
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tutoria-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--uniminuto-amarillo);
        }
        
        .tutoria-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 193, 7, 0.2);
        }
        
        .estudiante-nombre {
            font-weight: 700;
            color: var(--uniminuto-azul);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .tutoria-materia {
            color: var(--uniminuto-azul);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }
        
        .tutoria-info {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .tutoria-horario {
            background: var(--uniminuto-amarillo);
            color: #000;
            font-weight: 700;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .tutoria-temas {
            background: rgba(0, 86, 163, 0.1);
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 0.75rem;
            border-left: 3px solid var(--uniminuto-azul);
        }
        
        .tutoria-temas strong {
            color: var(--uniminuto-azul);
            font-size: 0.8rem;
        }
        
        .tutoria-temas p {
            color: #495057;
            font-size: 0.8rem;
            margin-bottom: 0;
            line-height: 1.4;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .section-title {
            color: var(--uniminuto-azul);
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Header de Bienvenida -->
        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h2 fw-bold mb-2">
                        <i class="bi bi-person-badge me-2"></i>Bienvenido, Prof. <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </h1>
                    <p class="mb-0 opacity-90">
                        <i class="bi bi-mortarboard me-2"></i>
                        Panel de Control - Docente Ciencias Básicas
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <span class="badge bg-light text-dark fs-6 p-2">
                        <i class="bi bi-award me-1"></i>DOCENTE UNIMINUTO
                    </span>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>

        <!-- Gestión de Tutorías (PRIMERO) -->
        <div class="row mb-4">
            <div class="col-12">
                <h3 class="section-title">
                    <i class="bi bi-lightning me-2"></i>Gestión de Tutorías
                </h3>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5>Tutorías Asignadas</h5>
                    <p>Gestiona y completa las tutorías programadas con estudiantes</p>
                    <a href="tutorias.php" class="btn btn-primary w-100">
                        <i class="bi bi-list-check me-2"></i>Gestionar Tutorías
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h5>Mi Disponibilidad</h5>
                    <p>Configura y gestiona tus horarios disponibles para tutorías</p>
                    <a href="disponibilidad.php" class="btn btn-success w-100">
                        <i class="bi bi-gear me-2"></i>Configurar Horarios
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <h5>Historial Completo</h5>
                    <p>Consulta el historial detallado y estadísticas de todas tus tutorías</p>
                    <a href="historial.php" class="btn btn-info w-100">
                        <i class="bi bi-journal-text me-2"></i>Ver Historial
                    </a>
                </div>
            </div>
        </div>

        <!-- Información Académica (SEGUNDO) -->
        <div class="row mb-4">
            <!-- Mis Materias -->
            <div class="col-12 mb-4">
                <div class="info-section">
                    <h6>
                        <i class="bi bi-book me-2"></i>Mis Materias Asignadas
                    </h6>
                    <?php if (count($materias) > 0): ?>
                        <div class="materias-container">
                            <?php foreach ($materias as $materia): ?>
                                <div class="materia-badge">
                                    <span class="materia-codigo">
                                        <?php echo htmlspecialchars($materia['codigo']); ?>
                                    </span>
                                    <span class="materia-nombre">
                                        <?php echo htmlspecialchars($materia['nombre']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state py-3">
                            <i class="bi bi-book-x"></i>
                            <p class="mb-0">No tienes materias asignadas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Próximas Tutorías (TERCERO) - AHORA EN ANCHO COMPLETO -->
        <div class="row">
            <div class="col-12 mb-4">
                <div class="info-section">
                    <h6>
                        <i class="bi bi-alarm me-2"></i>Próximas Tutorías Programadas
                    </h6>
                    <?php if (count($proximas_tutorias) > 0): ?>
                        <div class="tutorias-grid">
                            <?php foreach ($proximas_tutorias as $tutoria): ?>
                                <div class="tutoria-card">
                                    <div class="estudiante-nombre">
                                        <?php echo htmlspecialchars($tutoria['estudiante_nombre']); ?>
                                    </div>
                                    <div class="tutoria-materia">
                                        <i class="bi bi-book me-1"></i>
                                        <?php echo htmlspecialchars($tutoria['materia_nombre']); ?>
                                    </div>
                                    <div class="tutoria-info">
                                        <i class="bi bi-calendar"></i>
                                        <?php echo date('d/m/Y', strtotime($tutoria['fecha_tutoria'])); ?>
                                    </div>
                                    <div class="tutoria-info">
                                        <i class="bi bi-geo-alt"></i>
                                        <?php echo htmlspecialchars($tutoria['lugar']); ?>
                                    </div>
                                    <div class="tutoria-horario">
                                        <i class="bi bi-clock me-1"></i>
                                        <?php echo date('H:i', strtotime($tutoria['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($tutoria['hora_fin'])); ?>
                                    </div>
                                    <?php if (!empty($tutoria['tema_tratar'])): ?>
                                        <div class="tutoria-temas">
                                            <strong><i class="bi bi-bookmark me-1"></i>Temas a tratar:</strong>
                                            <p><?php echo nl2br(htmlspecialchars(substr($tutoria['tema_tratar'], 0, 120))); ?>
                                            <?php if (strlen($tutoria['tema_tratar']) > 120): ?>...<?php endif; ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-calendar-x"></i>
                            <h5 class="text-muted mb-3">No hay tutorías programadas</h5>
                            <p class="text-muted mb-4">Los estudiantes podrán solicitar tutorías en tus horarios disponibles</p>
                            <a href="disponibilidad.php" class="btn btn-warning">
                                <i class="bi bi-clock me-2"></i>Configurar Horarios
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>