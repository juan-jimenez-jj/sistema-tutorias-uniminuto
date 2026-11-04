<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('administrador');

$database = new Database();
$db = $database->getConnection();

// Estadísticas generales MEJORADAS
$query = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'estudiante' AND estado = 'activo'";
$estudiantes = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'docente' AND estado = 'activo'";
$docentes = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM tutorias";
$tutorias = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM materias WHERE estado = 'activa'";
$materias = $db->query($query)->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrador - UNIMINUTO</title>
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
            /* Se quitó el border-color azul */
        }
        
        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            display: inline-block;
            padding: 1.5rem;
            border-radius: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            transition: all 0.3s ease;
            color: var(--uniminuto-azul); /* Color fijo */
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1);
            /* Se quitó el cambio de color y fondo del icono */
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
        
        .badge-admin {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
        }
        
        .section-title {
            color: var(--uniminuto-azul);
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 3px solid var(--uniminuto-azul);
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .welcome-card {
                padding: 2rem 1.5rem;
            }
            
            .feature-card {
                padding: 2rem 1.5rem;
            }
            
            .feature-icon {
                font-size: 3rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Header de Bienvenida -->
        <div class="welcome-card">
            <div class="row align-items-center position-relative">
                <div class="col-md-8">
                    <h1 class="display-6 fw-bold mb-3">
                        <i class="bi bi-speedometer2 me-3"></i>Panel de Administración
                    </h1>
                    <p class="fs-5 mb-0 opacity-90">
                        <i class="bi bi-building me-2"></i>
                        Sistema de Gestión de Tutorías - Ciencias Básicas
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="badge-admin">
                        <i class="bi bi-shield-check me-2"></i>
                        <span>ADMINISTRADOR</span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>
        
        <!-- Gestión del Sistema -->
        <div class="row">
            <div class="col-12 mb-4">
                <h3 class="section-title">
                    <i class="bi bi-gear me-2"></i>Gestión del Sistema
                </h3>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon text-primary">
                        <i class="bi bi-people"></i>
                    </div>
                    <h5>Gestión de Usuarios</h5>
                    <p>Administra estudiantes, docentes, permisos y estados de cuenta del sistema</p>
                    <a href="gestionar_usuarios.php" class="btn btn-primary w-100">
                        <i class="bi bi-person-gear me-2"></i>Gestionar Usuarios
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon text-success">
                        <i class="bi bi-book"></i>
                    </div>
                    <h5>Gestión de Materias</h5>
                    <p>Administra el catálogo completo de materias y su asignación a docentes</p>
                    <a href="gestionar_materias.php" class="btn btn-success w-100">
                        <i class="bi bi-journal-text me-2"></i>Gestionar Materias
                    </a>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="feature-card">
                    <div class="feature-icon text-info">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <h5>Reportes Generales</h5>
                    <p>Genera reportes detallados, estadísticas y análisis del sistema completo</p>
                    <a href="reportes.php" class="btn btn-info w-100">
                        <i class="bi bi-bar-chart me-2"></i>Ver Reportes
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>