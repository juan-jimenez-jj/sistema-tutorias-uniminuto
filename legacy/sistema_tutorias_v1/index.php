<?php 
session_start();

// Limpiar mensajes de sesión si no hay parámetros de error/success en la URL
if (!isset($_GET['error']) && !isset($_GET['success'])) {
    if (isset($_SESSION['error'])) unset($_SESSION['error']);
    if (isset($_SESSION['success'])) unset($_SESSION['success']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Tutorías - UNIMINUTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        :root {
            --uniminuto-azul: #0056a3;
            --uniminuto-azul-oscuro: #7391acff;
            --uniminuto-azul-claro: #0077cc;
            --uniminuto-blanco: #ffffff;
            --uniminuto-gris: #f8f9fa;
            --uniminuto-gris-oscuro: #6c757d;
            --uniminuto-verde: #28a745;
            --uniminuto-amarillo: #ffc107;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="rgba(255,255,255,0.05)"/></svg>');
            background-size: cover;
        }
        
        /* RECUADRO DE LOGIN MÁS PEQUEÑO Y COMPACTO */
        .login-card {
            background: var(--uniminuto-blanco);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 45, 105, 0.15);
            border: none;
            position: relative;
            z-index: 2;
            padding: 1.5rem;
            max-width: 380px;
            margin: 0 auto;
        }
        
        .role-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 2.5rem 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            height: 100%;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: var(--uniminuto-blanco);
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.4);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        .role-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            opacity: 0.9;
        }
        
        .role-card.estudiante .role-icon { color: #4cd964; }
        .role-card.docente .role-icon { color: #5ac8fa; }
        .role-card.administrador .role-icon { color: #ff9500; }
        
        .role-title {
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            opacity: 0.95;
        }
        
        .role-description {
            opacity: 0.8;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 0;
        }
        
        .uniminuto-logo {
            font-size: 3rem;
            font-weight: 700;
            color: var(--uniminuto-blanco);
            margin-bottom: 1.5rem;
        }
        
        .uniminuto-logo span {
            color: #ffd700;
        }
        
        .hero-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .institutional-section {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .institutional-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            opacity: 0.9;
            text-align: center;
        }
        
        .login-container {
            display: none;
        }
        
        .login-container.active {
            display: block;
            animation: fadeInUp 0.5s ease-out;
        }
        
        .back-to-roles {
            background: none;
            border: none;
            color: var(--uniminuto-azul);
            font-weight: 500;
            cursor: pointer;
            padding: 0;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .back-to-roles:hover {
            color: var(--uniminuto-azul-oscuro);
        }
        
        .btn-uniminuto {
            background: var(--uniminuto-azul);
            border-color: var(--uniminuto-azul);
            color: var(--uniminuto-blanco);
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }
        
        .btn-uniminuto:hover {
            background: var(--uniminuto-azul-oscuro);
            border-color: var(--uniminuto-azul-oscuro);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 45, 105, 0.3);
        }
        
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 8px 12px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .form-control:focus {
            border-color: var(--uniminuto-azul);
            box-shadow: 0 0 0 0.2rem rgba(0, 86, 163, 0.25);
        }
        
        .input-group-text {
            background: var(--uniminuto-gris);
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 8px 0 0 8px;
            padding: 8px 12px;
            font-size: 0.9rem;
        }
        
        /* Logo más pequeño en login */
        .uniminuto-logo-login {
            max-width: 90px;
            margin: 0 auto 0.75rem auto;
            display: block;
        }
        
        .uniminuto-logo-login img {
            max-width: 100%;
            height: auto;
        }
        
        /* Textos más compactos */
        .login-title {
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-size: 0.85rem;
            margin-bottom: 0.4rem;
        }
        
        .mb-3 {
            margin-bottom: 1rem !important;
        }
        
        .alert {
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <!-- Logo UNIMINUTO -->
                    <div class="uniminuto-logo fade-in-up">
                        <i class="bi bi-mortarboard-fill"></i> UNIMINUTO<span>Tutorías</span>
                    </div>
                    
                    <p class="hero-subtitle fade-in-up">
                        Sistema de Gestión de Tutorías Académicas
                    </p>
                    
                    <!-- Contenedor Principal -->
                    <div id="main-container">
                        <!-- Sección de Roles (Pantalla inicial) -->
                        <div class="institutional-section fade-in-up" id="role-selection">
                            <div class="institutional-title">
                                <i class="bi bi-award-fill me-2"></i>Área de Ciencias Básicas
                            </div>
                            
                            <div class="row g-4 justify-content-center">
                                <!-- Estudiante -->
                                <div class="col-md-4">
                                    <div class="role-card estudiante" onclick="showLoginForm('estudiante')">
                                        <div class="role-icon">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <h3 class="role-title">Estudiante</h3>
                                        <p class="role-description">
                                            Accede para reservar tutorías y gestionar tu aprendizaje académico
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Docente -->
                                <div class="col-md-4">
                                    <div class="role-card docente" onclick="showLoginForm('docente')">
                                        <div class="role-icon">
                                            <i class="bi bi-person-badge"></i>
                                        </div>
                                        <h3 class="role-title">Docente</h3>
                                        <p class="role-description">
                                            Gestiona tu disponibilidad y tutorías programadas con estudiantes
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Administrador -->
                                <div class="col-md-4">
                                    <div class="role-card administrador" onclick="showLoginForm('administrador')">
                                        <div class="role-icon">
                                            <i class="bi bi-gear-fill"></i>
                                        </div>
                                        <h3 class="role-title">Administrador</h3>
                                        <p class="role-description">
                                            Administra el sistema, usuarios y reportes institucionales
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Formulario de Login (Se muestra cuando se selecciona un rol) -->
                        <div class="login-container fade-in-up" id="login-container">
                            <!-- El contenido del login se carga dinámicamente -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para obtener el rol actual
        function getCurrentRole() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('role');
        }

        // Función para mostrar mensajes de alerta específicos por rol
        function getAlertMessages(role) {
            const urlParams = new URLSearchParams(window.location.search);
            const error = urlParams.get('error');
            const success = urlParams.get('success');
            const currentRole = getCurrentRole();
            
            let alerts = '';
            
            // Solo muestra alertas si estamos en el rol correcto
            if (currentRole === role) {
                if (error) {
                    alerts += '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
                    alerts += '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + decodeURIComponent(error);
                    alerts += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    alerts += '</div>';
                }
                
                if (success) {
                    alerts += '<div class="alert alert-success alert-dismissible fade show" role="alert">';
                    alerts += '<i class="bi bi-check-circle-fill me-2"></i>' + decodeURIComponent(success);
                    alerts += '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
                    alerts += '</div>';
                }
            }
            
            return alerts;
        }

        // Plantillas de login para cada rol
        const loginTemplates = {
            estudiante: `
                <div class="login-card">
                    <button type="button" class="back-to-roles" onclick="showRoleSelection()">
                        <i class="bi bi-arrow-left"></i> Volver a selección de roles
                    </button>
                    
                    <!-- Logo UNIMINUTO -->
                    <div class="uniminuto-logo-login">
                        <img src="assets/images/logo_uniminuto.png" alt="UNIMINUTO" class="img-fluid">
                    </div>
                    
                    <div class="text-center mb-3">
                        <h2 class="text-primary fw-bold login-title">Inicio de Sesión</h2>
                        <p class="text-muted login-subtitle">Estudiante - Accede a tu cuenta</p>
                    </div>
                    
                    ${getAlertMessages('estudiante')}
                    
                    <form action="modules/auth/login.php" method="POST">
                        <input type="hidden" name="role" value="estudiante">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Institucional</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope-fill text-primary"></i>
                                </span>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="usuario@uniminuto.edu.co" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock-fill text-primary"></i>
                                </span>
                                <input type="password" class="form-control" name="password" 
                                       placeholder="Ingresa tu contraseña" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-uniminuto w-100 py-2 fw-bold">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted mb-2 small">¿Eres estudiante nuevo?</p>
                        <a href="registro.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-person-plus me-1"></i>Crear Cuenta
                        </a>
                    </div>
                </div>
            `,
            
            docente: `
                <div class="login-card">
                    <button type="button" class="back-to-roles" onclick="showRoleSelection()">
                        <i class="bi bi-arrow-left"></i> Volver a selección de roles
                    </button>

                    <!-- Logo UNIMINUTO -->
                    <div class="uniminuto-logo-login">
                        <img src="assets/images/logo_uniminuto.png" alt="UNIMINUTO" class="img-fluid">
                    </div>
                    
                    <div class="text-center mb-3">
                        <h2 class="text-primary fw-bold login-title">Inicio de Sesión</h2>
                        <p class="text-muted login-subtitle">Docente - Accede a tu cuenta</p>
                    </div>
                    
                    ${getAlertMessages('docente')}
                    
                    <form action="modules/auth/login.php" method="POST">
                        <input type="hidden" name="role" value="docente">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Institucional</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope-fill text-primary"></i>
                                </span>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="usuario@uniminuto.edu.co" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock-fill text-primary"></i>
                                </span>
                                <input type="password" class="form-control" name="password" 
                                       placeholder="Ingresa tu contraseña" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-uniminuto w-100 py-2 fw-bold">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted small">
                            <i class="bi bi-info-circle me-1"></i>
                            Solo para docentes autorizados
                        </p>
                    </div>
                </div>
            `,
            
            administrador: `
                <div class="login-card">
                    <button type="button" class="back-to-roles" onclick="showRoleSelection()">
                        <i class="bi bi-arrow-left"></i> Volver a selección de roles
                    </button>

                    <!-- Logo UNIMINUTO -->
                    <div class="uniminuto-logo-login">
                        <img src="assets/images/logo_uniminuto.png" alt="UNIMINUTO" class="img-fluid">
                    </div>

                    <div class="text-center mb-3">
                        <h2 class="text-primary fw-bold login-title">Inicio de Sesión</h2>
                        <p class="text-muted login-subtitle">Administrador - Panel de control</p>
                    </div>
                    
                    ${getAlertMessages('administrador')}
                    
                    <form action="modules/auth/login.php" method="POST">
                        <input type="hidden" name="role" value="administrador">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Institucional</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-envelope-fill text-primary"></i>
                                </span>
                                <input type="email" class="form-control" name="email" 
                                       placeholder="admin@uniminuto.edu.co" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="bi bi-lock-fill text-primary"></i>
                                </span>
                                <input type="password" class="form-control" name="password" 
                                       placeholder="Ingresa tu contraseña" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-uniminuto w-100 py-2 fw-bold">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                        </button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted small">
                            <i class="bi bi-shield-lock me-1"></i>
                            Acceso restringido al personal autorizado
                        </p>
                    </div>
                </div>
            `
        };

        // Función para mostrar el formulario de login
        function showLoginForm(role) {
            const roleSelection = document.getElementById('role-selection');
            const loginContainer = document.getElementById('login-container');
            
            roleSelection.style.display = 'none';
            loginContainer.style.display = 'block';
            loginContainer.classList.add('active');
            
            // Limpiar parámetros de error/success al cambiar de rol
            const newUrl = window.location.pathname + '?role=' + role;
            window.history.pushState({}, '', newUrl);
            
            loginContainer.innerHTML = loginTemplates[role];
            
            // Aplicar validaciones al formulario cargado
            applyFormValidations();
        }

        // Función para volver a la selección de roles
        function showRoleSelection() {
            const roleSelection = document.getElementById('role-selection');
            const loginContainer = document.getElementById('login-container');
            
            roleSelection.style.display = 'block';
            loginContainer.style.display = 'none';
            loginContainer.classList.remove('active');
            
            // Limpiar parámetros de la URL
            window.history.pushState({}, '', window.location.pathname);
        }

        // Aplicar validaciones a los formularios
        function applyFormValidations() {
            // Loading states para todos los formularios
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Procesando...';
                    }
                });
            });
        }

        // Verificar si hay un parámetro de rol en la URL y mostrar el login automáticamente
        const urlParams = new URLSearchParams(window.location.search);
        const roleParam = urlParams.get('role');

        if (roleParam && (roleParam === 'estudiante' || roleParam === 'docente' || roleParam === 'administrador')) {
            showLoginForm(roleParam);
        }

        // Aplicar validaciones iniciales
        document.addEventListener('DOMContentLoaded', function() {
            applyFormValidations();
        });
    </script>
</body>
</html>