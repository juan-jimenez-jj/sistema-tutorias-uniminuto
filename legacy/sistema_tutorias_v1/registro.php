<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Estudiante - UNIMINUTO Tutorías</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --uniminuto-azul: #0056a3;
            --uniminuto-azul-oscuro: #004085;
            --uniminuto-azul-claro: #0077cc;
            --uniminuto-dorado: #FFD700;
            --uniminuto-blanco: #ffffff;
            --uniminuto-gris: #f8f9fa;
            --uniminuto-gris-oscuro: #6c757d;
            --uniminuto-verde: #28a745;
            --uniminuto-rojo: #dc3545;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            color: var(--uniminuto-blanco);
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 215, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(0, 86, 163, 0.1) 0%, transparent 50%);
            background-size: cover;
        }
        
        .registro-container {
            background: var(--uniminuto-blanco);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 45, 105, 0.15);
            border: none;
            position: relative;
            z-index: 2;
            overflow: hidden;
            max-width: 900px;
            margin: 2rem auto;
        }
        
        .registro-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: var(--uniminuto-blanco);
            padding: 2.5rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .registro-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,215,0,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        .registro-body {
            padding: 3rem;
        }
        
        /* Logo en el header del registro */
        .logo-title-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .header-logo-container {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
        }

        .header-logo {
            width: 100%;
            height: auto;
            /* Logo en colores originales */
        }

        .header-title {
            text-align: left;
        }

        .uniminuto-logo {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .uniminuto-logo span {
            color: var(--uniminuto-dorado);
        }
        
        .institutional-badge {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 1rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-uniminuto {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            border: none;
            color: var(--uniminuto-blanco);
            font-weight: 600;
            padding: 14px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(0, 86, 163, 0.3);
        }
        
        .btn-uniminuto:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 86, 163, 0.4);
            color: var(--uniminuto-blanco);
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: var(--uniminuto-azul);
            box-shadow: 0 0 0 0.3rem rgba(0, 86, 163, 0.15);
        }
        
        .input-group-text {
            background: var(--uniminuto-gris);
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 12px 15px;
        }
        
        .back-link {
            color: var(--uniminuto-blanco);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .back-link:hover {
            color: var(--uniminuto-blanco);
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(-5px);
        }
        
        .section-card {
            background: var(--uniminuto-gris);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--uniminuto-azul);
            transition: all 0.3s ease;
        }
        
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            font-size: 1.1rem;
            color: var(--uniminuto-azul);
            margin-bottom: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            background: var(--uniminuto-azul);
            color: white;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        
        .form-label {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--uniminuto-azul-oscuro);
        }
        
        .form-text {
            font-size: 0.8rem;
            color: var(--uniminuto-gris-oscuro);
            margin-top: 0.25rem;
        }
        
        .alert {
            border-radius: 10px;
            padding: 1rem 1.5rem;
            font-size: 0.9rem;
            margin-bottom: 2rem;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .progress-container {
            background: var(--uniminuto-gris);
            border-radius: 50px;
            height: 8px;
            margin: 2rem 0;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-dorado) 100%);
            height: 100%;
            border-radius: 50px;
            transition: width 0.5s ease;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--uniminuto-gris);
            color: var(--uniminuto-gris-oscuro);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin: 0 auto 0.5rem;
            border: 3px solid var(--uniminuto-blanco);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .step.active .step-number {
            background: var(--uniminuto-azul);
            color: white;
        }
        
        .step-label {
            font-size: 0.8rem;
            color: var(--uniminuto-gris-oscuro);
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: var(--uniminuto-azul);
            font-weight: 600;
        }
        
        .terms-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 1.5rem;
            border: 2px dashed #dee2e6;
        }
        
        .form-check-input:checked {
            background-color: var(--uniminuto-azul);
            border-color: var(--uniminuto-azul);
        }
        
        .form-check-label {
            font-size: 0.9rem;
            color: var(--uniminuto-gris-oscuro);
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease-out;
        }
        
        /* Responsive para el logo del header */
        @media (max-width: 768px) {
            .logo-title-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .header-logo-container {
                width: 60px;
                height: 60px;
                padding: 6px;
            }
            
            .header-title {
                text-align: center;
            }
            
            .uniminuto-logo {
                font-size: 1.8rem;
            }
            
            .registro-body {
                padding: 2rem 1.5rem;
            }
            
            .registro-header {
                padding: 2rem 1.5rem;
            }
            
            .section-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <!-- Enlace para volver -->
            <a href="index.php?role=estudiante" class="back-link fade-in-up">
                <i class="bi bi-arrow-left"></i> Volver al inicio de sesión
            </a>
            
            <div class="registro-container fade-in-up">
                <!-- Header -->
                <div class="registro-header">
                    <div class="institutional-badge">
                        <i class="bi bi-award-fill me-2"></i>Vicerrectoría Académica
                    </div>
                    <div class="logo-title-container">
                        <div class="header-logo-container">
                            <img src="assets/images/logo_uniminuto.png" alt="UNIMINUTO" class="header-logo">
                        </div>
                        <div class="header-title">
                            <div class="uniminuto-logo">
                                UNIMINUTO<span>Tutorías</span>
                            </div>
                            <p class="mb-0 mt-2 opacity-75">Sistema Integral de Gestión de Tutorías Académicas</p>
                        </div>
                    </div>
                </div>
                
                <!-- Body -->
                <div class="registro-body">
                    <!-- Progress Indicator -->
                    <div class="step-indicator">
                        <div class="step active">
                            <div class="step-number">1</div>
                            <div class="step-label">Información</div>
                        </div>
                        <div class="step active">
                            <div class="step-number">2</div>
                            <div class="step-label">Seguridad</div>
                        </div>
                        <div class="step active">
                            <div class="step-number">3</div>
                            <div class="step-label">Términos</div>
                        </div>
                    </div>
                    
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 100%"></div>
                    </div>
                    
                    <!-- Mensajes de alerta -->
                    <?php
                    if (isset($_GET['error'])) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                        echo '<i class="bi bi-exclamation-triangle-fill me-2"></i>' . htmlspecialchars(urldecode($_GET['error']));
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    if (isset($_GET['success'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                        echo '<i class="bi bi-check-circle-fill me-2"></i>' . htmlspecialchars(urldecode($_GET['success']));
                        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                        echo '</div>';
                    }
                    ?>
                    
                    <form action="modules/auth/registrar.php" method="POST" id="formRegistro">
                        <!-- Información Personal -->
                        <div class="section-card">
                            <h5 class="section-title">
                                <i class="bi bi-person-vcard"></i>
                                Información Personal del Estudiante
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-person-badge me-1"></i>ID Institucional
                                        </label>
                                        <input type="text" class="form-control" name="id_institucional" 
                                               value="<?php echo isset($_GET['id_institucional']) ? htmlspecialchars($_GET['id_institucional']) : ''; ?>"
                                               placeholder="Ej: 202410001" required>
                                        <div class="form-text">
                                            Número de identificación institucional asignado
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-book me-1"></i>Programa Académico
                                        </label>
                                        <input type="text" class="form-control" name="programa_academico" 
                                               value="<?php echo isset($_GET['programa_academico']) ? htmlspecialchars($_GET['programa_academico']) : ''; ?>"
                                               placeholder="Ej: Ingeniería de Software" required>
                                        <div class="form-text">
                                            Programa académico matriculado
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person me-1"></i>Nombre Completo
                                </label>
                                <input type="text" class="form-control" name="nombre" 
                                       value="<?php echo isset($_GET['nombre']) ? htmlspecialchars($_GET['nombre']) : ''; ?>"
                                       placeholder="Ej: Juan Pérez García" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-envelope me-1"></i>Correo Institucional
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-at text-primary"></i>
                                    </span>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>"
                                           placeholder="usuario@uniminuto.edu.co" required>
                                </div>
                                <div class="form-text">
                                    <i class="bi bi-shield-check me-1"></i>Correo institucional oficial de UNIMINUTO
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seguridad -->
                        <div class="section-card">
                            <h5 class="section-title">
                                <i class="bi bi-shield-lock"></i>
                                Configuración de Seguridad
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-lock me-1"></i>Contraseña
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-key text-primary"></i>
                                            </span>
                                            <input type="password" class="form-control" name="password" 
                                                   minlength="8" placeholder="Mínimo 8 caracteres" required>
                                        </div>
                                        <div class="form-text">
                                            La contraseña debe contener al menos 8 caracteres
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-lock-fill me-1"></i>Confirmar Contraseña
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light">
                                                <i class="bi bi-key-fill text-primary"></i>
                                            </span>
                                            <input type="password" class="form-control" name="confirm_password" 
                                                   placeholder="Repite tu contraseña" required>
                                        </div>
                                        <div class="form-text">
                                            Verifica que las contraseñas coincidan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Términos y Condiciones -->
                        <div class="section-card">
                            <h5 class="section-title">
                                <i class="bi bi-check-circle"></i>
                                Términos y Condiciones
                            </h5>
                            
                            <div class="terms-card">
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="terminos" name="terminos" 
                                           <?php echo (isset($_GET['terminos']) && $_GET['terminos'] === 'on') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="terminos">
                                        He leído y acepto los 
                                        <a href="#" class="text-primary fw-bold">Términos y Condiciones</a>, 
                                        la <a href="#" class="text-primary fw-bold">Política de Privacidad</a> 
                                        y el <a href="#" class="text-primary fw-bold">Reglamento del Sistema de Tutorías</a> 
                                        de la Corporación Universitaria Minuto de Dios.
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-uniminuto py-3 fw-bold" id="btnRegistro">
                                <i class="bi bi-person-check me-2"></i>Completar Registro Institucional
                            </button>
                        </div>
                        
                        <div class="text-center mt-4">
                            <p class="text-muted">
                                ¿Ya tienes cuenta institucional? 
                                <a href="index.php?role=estudiante" class="text-primary text-decoration-none fw-bold">
                                    Inicia sesión aquí
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formRegistro = document.getElementById('formRegistro');
            const btnRegistro = document.getElementById('btnRegistro');
            
            if (formRegistro) {
                formRegistro.addEventListener('submit', function(e) {
                    // Validación básica antes de enviar
                    const password = this.querySelector('input[name="password"]').value;
                    const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                    const email = this.querySelector('input[name="email"]').value;
                    const terminos = this.querySelector('#terminos').checked;
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Las contraseñas no coinciden');
                        return false;
                    }
                    
                    if (password.length < 8) {
                        e.preventDefault();
                        alert('La contraseña debe tener al menos 8 caracteres');
                        return false;
                    }
                    
                    if (!email.endsWith('@uniminuto.edu.co')) {
                        e.preventDefault();
                        alert('Debes usar un correo institucional @uniminuto.edu.co');
                        return false;
                    }
                    
                    if (!terminos) {
                        e.preventDefault();
                        alert('Debes aceptar los términos y condiciones');
                        return false;
                    }
                    
                    // Mostrar loading
                    if (btnRegistro) {
                        btnRegistro.disabled = true;
                        btnRegistro.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Procesando registro...';
                    }
                });
            }
        });
    </script>
</body>
</html>