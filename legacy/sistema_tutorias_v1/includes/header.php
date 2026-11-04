<?php
// Verificar si hay sesión activa
$user_logged_in = isset($_SESSION['user_id']);
$user_name = $user_logged_in ? $_SESSION['user_name'] : '';
$user_role = $user_logged_in ? $_SESSION['user_role'] : '';
?>

<nav class="navbar navbar-expand-lg navbar-uniminuto">
    <div class="container">
        <!-- Logo y Brand -->
        <a class="navbar-brand" href="../../index.php">
            <div class="d-flex align-items-center">
                <div class="logo-container">
                    <img src="../../assets/images/logo_uniminuto.png" alt="UNIMINUTO" class="navbar-logo">
                </div>
                <div class="brand-text ms-3">
                    <span class="brand-main">UNIMINUTO</span>
                    <span class="brand-sub">Sistema de Tutorías</span>
                </div>
            </div>
        </a>

        <?php if ($user_logged_in): ?>
        <!-- User Info -->
        <div class="navbar-user-info">
            <div class="user-welcome">
                <span class="welcome-text">Bienvenido,</span>
                <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
            </div>
            <div class="user-badge">
                <i class="bi bi-person-badge me-1"></i>
                <span class="user-role"><?php echo ucfirst($user_role); ?></span>
            </div>
            <a class="btn-logout" href="../../modules/auth/logout.php" title="Cerrar Sesión">
                <i class="bi bi-box-arrow-right"></i>
                <span class="logout-text">Salir</span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<style>
:root {
    --uniminuto-azul: #0056a3;
    --uniminuto-azul-oscuro: #003366;
    --uniminuto-dorado: #FFD700;
    --uniminuto-blanco: #ffffff;
}

/* Navbar Styles - MEJORADO */
.navbar-uniminuto {
    background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
    border-bottom: 4px solid var(--uniminuto-dorado);
    padding: 0.8rem 0;
    box-shadow: 0 4px 20px rgba(0, 45, 105, 0.25);
    backdrop-filter: blur(10px);
    position: relative;
    overflow: hidden;
}

.navbar-uniminuto::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
}

/* Brand Styles - MEJORADO */
.navbar-brand {
    text-decoration: none;
    padding: 0.5rem 0;
    transition: all 0.3s ease;
}

.navbar-brand:hover {
    transform: translateY(-2px);
}

.logo-container {
    width: 55px;
    height: 55px;
    background: var(--uniminuto-blanco);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    border: 2px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    transition: all 0.3s ease;
}

.navbar-brand:hover .logo-container {
    transform: scale(1.05);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
}

.navbar-logo {
    width: 100%;
    height: auto;
    transition: transform 0.3s ease;
}

.brand-text {
    line-height: 1.2;
}

.brand-main {
    display: block;
    font-size: 1.4rem;
    font-weight: 800;
    color: var(--uniminuto-blanco);
    letter-spacing: -0.5px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.brand-sub {
    display: block;
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
    margin-top: -2px;
    letter-spacing: 0.5px;
}

/* User Info - MEJORADO */
.navbar-user-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.user-welcome {
    text-align: right;
    color: var(--uniminuto-blanco);
}

.welcome-text {
    display: block;
    font-size: 0.8rem;
    opacity: 0.8;
    margin-bottom: 0.1rem;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.user-name {
    display: block;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--uniminuto-blanco);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.user-badge {
    background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.1) 100%);
    color: var(--uniminuto-blanco);
    padding: 0.6rem 1.2rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(15px);
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.user-badge:hover {
    background: linear-gradient(135deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.2) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}

.btn-logout {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    background: linear-gradient(135deg, rgba(16, 67, 143, 0.9) 0%, rgba(85, 116, 184, 0.9) 100%);
    color: var(--uniminuto-blanco);
    padding: 0.7rem 1.2rem;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(15px);
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
}

.btn-logout:hover {
    background: linear-gradient(135deg, rgba(7, 9, 124, 0.9) 0%, rgba(45, 32, 168, 0.9) 100%);
    color: var(--uniminuto-blanco);
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(220, 53, 69, 0.4);
}

.logout-text {
    font-size: 0.85rem;
    font-weight: 600;
}

/* Responsive - MEJORADO */
@media (max-width: 768px) {
    .navbar-user-info {
        gap: 1rem;
    }
    
    .user-welcome {
        display: none;
    }
    
    .logout-text {
        display: none;
    }
    
    .btn-logout {
        padding: 0.7rem;
        border-radius: 50%;
        width: 45px;
        height: 45px;
        justify-content: center;
    }
    
    .user-badge {
        padding: 0.6rem 1rem;
        font-size: 0.8rem;
    }
    
    .brand-main {
        font-size: 1.2rem;
    }
    
    .brand-sub {
        font-size: 0.75rem;
    }
    
    .logo-container {
        width: 45px;
        height: 45px;
        padding: 6px;
    }
    
    .navbar-uniminuto {
        padding: 0.6rem 0;
    }
}

/* Animaciones adicionales */
@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.navbar-uniminuto {
    animation: fadeInDown 0.5s ease-out;
}
</style>