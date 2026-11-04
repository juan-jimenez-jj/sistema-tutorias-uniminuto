<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('estudiante');

$database = new Database();
$db = $database->getConnection();

// Contar tutorías del estudiante para las cards principales
$query = "SELECT COUNT(*) as total FROM tutorias WHERE estudiante_id = :estudiante_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":estudiante_id", $_SESSION['user_id']);
$stmt->execute();
$total_tutorias = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM tutorias WHERE estudiante_id = :estudiante_id AND estado = 'programada'";
$stmt = $db->prepare($query);
$stmt->bindParam(":estudiante_id", $_SESSION['user_id']);
$stmt->execute();
$tutorias_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Estudiante - UNIMINUTO</title>
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
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(0, 86, 163, 0.2);
            position: relative;
            overflow: hidden;
        }
        
        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }
        
        .stats-card {
            border-radius: 16px;
            padding: 2rem 1.5rem;
            color: white;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: rgba(255,255,255,0.3);
        }
        
        .stats-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .stats-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .stats-content h3 {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .stats-content p {
            font-size: 1.1rem;
            opacity: 0.95;
            margin-bottom: 0;
            font-weight: 500;
        }
        
        .feature-card {
            border: 2px solid #f1f3f4;
            border-radius: 16px;
            padding: 2.5rem 2rem;
            text-align: center;
            height: 100%;
            transition: all 0.4s ease;
            background: white;
            position: relative;
        }
        
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--uniminuto-azul);
        }
        
        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            padding: 1.5rem;
            border-radius: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        .feature-card h5 {
            color: var(--uniminuto-azul);
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .feature-card p {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .feature-card .btn {
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .bg-uniminuto-primary { 
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-claro) 100%); 
        }
        
        .bg-uniminuto-warning { 
            background: linear-gradient(135deg, var(--uniminuto-amarillo) 0%, #fd7e14 100%); 
        }
        
        .badge-estudiante {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
        }
        
        .proxima-tutoria-card {
            border: 2px solid #e3f2fd;
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            background: white;
            height: 100%;
        }
        
        .proxima-tutoria-card:hover {
            border-color: var(--uniminuto-azul);
            box-shadow: 0 8px 25px rgba(0,86,163,0.1);
        }
        
        .section-title {
            color: var(--uniminuto-azul);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--uniminuto-azul);
            display: inline-block;
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
        
        .quick-stats {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <!-- Tarjeta de Bienvenida Mejorada -->
                <div class="welcome-card">
                    <div class="row align-items-center position-relative">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-3">
                                <i class="bi bi-emoji-smile me-3"></i>¡Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!
                            </h1>
                            <p class="fs-5 mb-0 opacity-90">
                                <i class="bi bi-mortarboard me-2"></i>
                                Estudiante - <?php echo htmlspecialchars($_SESSION['user_programa'] ?? 'Ciencias Básicas'); ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="badge-estudiante">
                                <i class="bi bi-award me-2"></i>
                                <span>ESTUDIANTE UNIMINUTO</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php showMessage(); ?>
                
            
                <!-- Acciones Rápidas Mejoradas -->
                <div class="row mb-5">
                    <div class="col-12 mb-4">
                        <h3 class="section-title">
                            <i class="bi bi-lightning me-2"></i>Gestión de Tutorías
                        </h3>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon text-primary">
                                <i class="bi bi-calendar-plus"></i>
                            </div>
                            <h5>Reservar Tutoría</h5>
                            <p>Agenda una tutoría personalizada con nuestros docentes especializados en tus materias</p>
                            <a href="solicitar_tutoria.php" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle me-2"></i>Solicitar Tutoría
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon text-success">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <h5>Mis Tutorías</h5>
                            <p>Consulta, gestiona y califica tus tutorías programadas y completadas</p>
                            <a href="mis_tutorias.php" class="btn btn-success w-100">
                                <i class="bi bi-eye me-2"></i>Ver Mis Tutorías
                            </a>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-4">
                        <div class="feature-card">
                            <div class="feature-icon text-info">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h5>Historial Académico</h5>
                            <p>Revisa tu historial completo de tutorías y tu progreso académico detallado</p>
                            <a href="historial.php" class="btn btn-info w-100">
                                <i class="bi bi-search me-2"></i>Consultar Historial
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Próximas Tutorías Mejoradas -->
                <div class="row">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-uniminuto-primary text-white py-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-alarm me-2"></i>Próximas Tutorías Programadas
                                </h5>
                            </div>
                            <div class="card-body p-4">
                                <?php
                                $query = "SELECT t.*, m.nombre as materia_nombre, u.nombre as docente_nombre,
                                                 dd.hora_inicio, dd.hora_fin, dd.lugar
                                          FROM tutorias t
                                          JOIN materias m ON t.materia_id = m.id
                                          JOIN usuarios u ON t.docente_id = u.id
                                          JOIN disponibilidad_docentes dd ON t.disponibilidad_id = dd.id
                                          WHERE t.estudiante_id = :estudiante_id 
                                          AND t.estado = 'programada'
                                          AND CONCAT(t.fecha_tutoria, ' ', dd.hora_inicio) > NOW()
                                          ORDER BY t.fecha_tutoria, dd.hora_inicio
                                          LIMIT 3";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(":estudiante_id", $_SESSION['user_id']);
                                $stmt->execute();
                                $proximas_tutorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($proximas_tutorias) > 0): 
                                ?>
                                <div class="row">
                                    <?php foreach ($proximas_tutorias as $tutoria): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="proxima-tutoria-card">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <h6 class="text-primary fw-bold mb-0">
                                                    <i class="bi bi-book me-2"></i><?php echo htmlspecialchars($tutoria['materia_nombre']); ?>
                                                </h6>
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-clock me-1"></i>Próxima
                                                </span>
                                            </div>
                                            
                                            <div class="tutoria-info">
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-person text-muted me-2"></i>
                                                    <small class="text-muted"><?php echo htmlspecialchars($tutoria['docente_nombre']); ?></small>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-calendar text-muted me-2"></i>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($tutoria['fecha_tutoria'])); ?></small>
                                                </div>
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="bi bi-clock text-muted me-2"></i>
                                                    <small class="text-muted"><?php echo date('H:i', strtotime($tutoria['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($tutoria['hora_fin'])); ?></small>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-geo-alt text-muted me-2"></i>
                                                    <small class="text-muted"><?php echo htmlspecialchars($tutoria['lugar']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-calendar-x"></i>
                                    <h5 class="text-muted mb-3">No tienes tutorías programadas</h5>
                                    <p class="text-muted mb-4">Agenda tu primera tutoría para comenzar a mejorar tu aprendizaje</p>
                                    <a href="solicitar_tutoria.php" class="btn btn-primary btn-lg">
                                        <i class="bi bi-plus-circle me-2"></i>Programar Mi Primera Tutoría
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>