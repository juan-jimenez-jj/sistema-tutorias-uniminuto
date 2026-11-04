<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('administrador');

$database = new Database();
$db = $database->getConnection();

// Estadísticas generales
$stats = [];

// Total de usuarios por rol
$query = "SELECT rol, COUNT(*) as total FROM usuarios WHERE estado = 'activo' GROUP BY rol";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total de tutorías por estado
$query = "SELECT estado, COUNT(*) as total FROM tutorias GROUP BY estado";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['tutorias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tutorías por materia
$query = "SELECT m.nombre, COUNT(t.id) as total 
          FROM materias m 
          LEFT JOIN tutorias t ON m.id = t.materia_id 
          GROUP BY m.id, m.nombre 
          ORDER BY total DESC 
          LIMIT 8";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['materias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular estadísticas para las tarjetas
$estudiantes = 0;
$docentes = 0;
$total_tutorias = 0;
$materias_activas = 0;

foreach($stats['usuarios'] as $usuario) {
    if($usuario['rol'] == 'estudiante') $estudiantes = $usuario['total'];
    if($usuario['rol'] == 'docente') $docentes = $usuario['total'];
}

foreach($stats['tutorias'] as $tutoria) {
    $total_tutorias += $tutoria['total'];
}

// Contar materias activas
$query_materias_count = "SELECT COUNT(*) as total FROM materias WHERE estado = 'activa'";
$stmt_materias = $db->prepare($query_materias_count);
$stmt_materias->execute();
$materias_activas = $stmt_materias->fetch(PDO::FETCH_ASSOC)['total'];

// Obtener últimas tutorías para la tabla
$query_ultimas_tutorias = "SELECT t.*, u1.nombre as estudiante, u2.nombre as docente, m.nombre as materia 
                          FROM tutorias t
                          JOIN usuarios u1 ON t.estudiante_id = u1.id
                          JOIN usuarios u2 ON t.docente_id = u2.id
                          JOIN materias m ON t.materia_id = m.id
                          ORDER BY t.fecha_solicitud DESC 
                          LIMIT 8";
$stmt_ultimas = $db->prepare($query_ultimas_tutorias);
$stmt_ultimas->execute();
$ultimas_tutorias = $stmt_ultimas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes - Sistema de Tutorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stats-card {
            border: none;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
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
            transform: translateY(-12px);
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
        
        .stats-icon {
            font-size: 3.5rem;
            opacity: 0.9;
            margin-bottom: 1.5rem;
            text-align: center;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
        }
        
        .stats-content {
            text-align: center;
            position: relative;
            z-index: 2;
        }
        
        .stats-content h3 {
            font-size: 3rem;
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
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border: none;
            padding: 1.5rem 2rem;
            font-weight: 600;
        }
        
        .card-header h5 {
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1.3rem;
        }
        
        .table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            color: var(--uniminuto-azul);
            font-weight: 700;
            border: none;
            padding: 1.2rem 1rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table td {
            padding: 1.2rem 1rem;
            border-color: #f1f3f4;
            vertical-align: middle;
        }
        
        .badge-estado {
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.8rem;
            border: 2px solid transparent;
        }
        
        .chart-container {
            position: relative;
            height: 350px;
            padding: 1.5rem;
        }
        
        .btn-volver {
            background: rgba(255,255,255,0.15);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .btn-volver:hover {
            background: rgba(255,255,255,0.25);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,255,255,0.2);
        }
        
        .section-title {
            color: var(--uniminuto-azul);
            font-weight: 700;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--uniminuto-azul);
            display: inline-block;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(0, 86, 163, 0.04);
            transform: translateX(5px);
            transition: all 0.3s ease;
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #fafbfc 0%, #f1f3f4 100%); min-height: 100vh;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-bar-chart-fill me-3"></i>Panel de Reportes
                    </h1>
                    <p class="fs-4 mb-0 opacity-90">Métricas y análisis del sistema de tutorías</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-volver btn-lg">
                        <i class="bi bi-arrow-left me-2"></i>Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>
        
        <!-- Tarjetas de Resumen -->
        <div class="stats-grid">
            <!-- Estudiantes -->
            <div class="stats-card bg-uniminuto-primary">
                <div class="stats-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $estudiantes; ?></h3>
                    <p>Estudiantes Activos</p>
                </div>
            </div>
            
            <!-- Docentes -->
            <div class="stats-card bg-uniminuto-success">
                <div class="stats-icon">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $docentes; ?></h3>
                    <p>Docentes Activos</p>
                </div>
            </div>
            
            <!-- Tutorías -->
            <div class="stats-card bg-uniminuto-warning">
                <div class="stats-icon">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_tutorias; ?></h3>
                    <p>Total de Tutorías</p>
                </div>
            </div>
            
            <!-- Materias -->
            <div class="stats-card bg-uniminuto-info">
                <div class="stats-icon">
                    <i class="bi bi-book-fill"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $materias_activas; ?></h3>
                    <p>Materias Activas</p>
                </div>
            </div>
        </div>

        <!-- Gráficos y Tablas -->
        <div class="row">
            <!-- Gráfico de Estados -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart-fill me-2"></i>Estados de Tutorías</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="estadoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de Materias -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Tutorías por Materia</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="materiaChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Últimas Tutorías -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Actividad Reciente de Tutorías</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Docente</th>
                                        <th>Materia</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ultimas_tutorias as $tutoria): ?>
                                        <tr>
                                            <td class="fw-semibold"><?php echo $tutoria['estudiante']; ?></td>
                                            <td><?php echo $tutoria['docente']; ?></td>
                                            <td>
                                                <span class="fw-medium"><?php echo $tutoria['materia']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge-estado bg-<?php 
                                                    switch($tutoria['estado']) {
                                                        case 'programada': echo 'primary'; break;
                                                        case 'completada': echo 'success'; break;
                                                        case 'cancelada': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <i class="bi bi-<?php 
                                                        switch($tutoria['estado']) {
                                                            case 'programada': echo 'clock'; break;
                                                            case 'completada': echo 'check-circle'; break;
                                                            case 'cancelada': echo 'x-circle'; break;
                                                            default: echo 'circle';
                                                        }
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($tutoria['estado']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($tutoria['fecha_solicitud'])); ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gráfico de estados de tutorías - Diseño profesional
        const estadoCtx = document.getElementById('estadoChart').getContext('2d');
        const estadoChart = new Chart(estadoCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    $labels = [];
                    $data = [];
                    $colors = ['#28a745', '#007bff', '#ffc107', '#dc3545', '#6f42c1'];
                    $i = 0;
                    
                    foreach($stats['tutorias'] as $tutoria) {
                        $labels[] = "'" . ucfirst($tutoria['estado']) . "'";
                        $data[] = $tutoria['total'];
                    }
                    echo implode(', ', $labels);
                    ?>
                ],
                datasets: [{
                    data: [<?php echo implode(', ', $data); ?>],
                    backgroundColor: [<?php echo "'" . implode("', '", array_slice($colors, 0, count($data))) . "'"; ?>],
                    borderWidth: 3,
                    borderColor: '#ffffff',
                    hoverBorderWidth: 4,
                    hoverBorderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 25,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: {
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });

        // Gráfico de tutorías por materia - Diseño profesional
        const materiaCtx = document.getElementById('materiaChart').getContext('2d');
        const materiaChart = new Chart(materiaCtx, {
            type: 'bar',
            data: {
                labels: [
                    <?php 
                    $labels = [];
                    $data = [];
                    $topMaterias = array_slice($stats['materias'], 0, 6);
                    
                    foreach($topMaterias as $materia) {
                        $labels[] = "'" . substr($materia['nombre'], 0, 18) . (strlen($materia['nombre']) > 18 ? '...' : '') . "'";
                        $data[] = $materia['total'];
                    }
                    echo implode(', ', $labels);
                    ?>
                ],
                datasets: [{
                    label: 'Tutorías',
                    data: [<?php echo implode(', ', $data); ?>],
                    backgroundColor: [
                        'rgba(0, 86, 163, 0.9)',
                        'rgba(40, 167, 69, 0.9)',
                        'rgba(255, 193, 7, 0.9)',
                        'rgba(111, 66, 193, 0.9)',
                        'rgba(220, 53, 69, 0.9)',
                        'rgba(23, 162, 184, 0.9)'
                    ],
                    borderColor: [
                        'rgba(0, 86, 163, 1)',
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)',
                        'rgba(111, 66, 193, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(23, 162, 184, 1)'
                    ],
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '600'
                            },
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11,
                                weight: '600'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            size: 13,
                            weight: '600'
                        },
                        bodyFont: {
                            size: 12,
                            weight: '500'
                        },
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });
    </script>
</body>
</html>