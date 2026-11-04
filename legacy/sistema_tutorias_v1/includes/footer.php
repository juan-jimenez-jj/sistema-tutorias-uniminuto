<footer class="footer-uniminuto">
    <div class="container">
        <div class="footer-content">
            <div class="footer-main">
                <div class="footer-brand">
                    <div class="footer-logo-container">
                        <img src="../../assets/images/logo_uniminuto.png" alt="UNIMINUTO" class="footer-logo">
                    </div>
                    <div class="footer-brand-text">
                        <h5 class="footer-title">UNIMINUTO - Zipaquirá</h5>
                        <p class="footer-subtitle">Sistema de Gestión de Tutorías Académicas</p>
                    </div>
                </div>
                <div class="footer-info">
                    <p class="footer-department">
                        <i class="bi bi-geo-alt me-1"></i>Área de Ciencias Básicas - Vicerrectoría Académica
                    </p>
                </div>
            </div>
            <div class="footer-copyright">
                <p class="copyright-text">
                    &copy; 2025 Corporación Universitaria Minuto de Dios
                </p>
                <p class="copyright-subtext">
                    Educación de calidad para todos
                </p>
            </div>
        </div>
        <div class="footer-divider"></div>
        <div class="footer-security">
            <div class="security-badge">
                <i class="bi bi-shield-check"></i>
                <span>Sistema seguro - Comprometidos con tu éxito académico</span>
            </div>
        </div>
    </div>
</footer>

<style>
:root {
    --uniminuto-azul: #0056a3;
    --uniminuto-azul-oscuro: #003366;
    --uniminuto-dorado: #FFD700;
    --uniminuto-blanco: #ffffff;
}

.footer-uniminuto {
    background: linear-gradient(135deg, var(--uniminuto-azul-oscuro) 0%, var(--uniminuto-azul) 100%);
    color: var(--uniminuto-blanco);
    padding: 3rem 0 2rem;
    margin-top: 4rem;
    border-top: 4px solid var(--uniminuto-dorado);
    position: relative;
    overflow: hidden;
}

.footer-uniminuto::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
}

.footer-main {
    flex: 1;
}

.footer-brand {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.footer-logo-container {
    width: 50px;
    height: 50px;
    background: var(--uniminuto-blanco);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    margin-right: 1.2rem;
    border: 2px solid rgba(255, 255, 255, 0.4);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    transition: all 0.3s ease;
}

.footer-logo-container:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35);
}

.footer-logo {
    width: 100%;
    height: auto;
}

.footer-brand-text {
    line-height: 1.3;
}

.footer-title {
    font-size: 1.2rem;
    font-weight: 700;
    margin-bottom: 0.3rem;
    color: var(--uniminuto-blanco);
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.footer-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-bottom: 0;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 500;
}

.footer-info {
    margin-bottom: 1rem;
}

.footer-department {
    font-size: 0.95rem;
    opacity: 0.8;
    margin-bottom: 0;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.footer-copyright {
    text-align: right;
}

.copyright-text {
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--uniminuto-blanco);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.copyright-subtext {
    font-size: 0.85rem;
    opacity: 0.7;
    margin-bottom: 0;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 500;
}

.footer-divider {
    height: 1px;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.4) 50%, transparent 100%);
    margin: 2rem 0;
}

.footer-security {
    text-align: center;
}

.security-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
    background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.1) 100%);
    padding: 0.8rem 1.5rem;
    border-radius: 25px;
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(15px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s ease;
}

.security-badge:hover {
    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.15) 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}

.security-badge i {
    color: var(--uniminuto-dorado);
    font-size: 1.1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
        gap: 2rem;
    }
    
    .footer-brand {
        justify-content: center;
    }
    
    .footer-copyright {
        text-align: center;
    }
    
    .footer-uniminuto {
        padding: 2.5rem 0 1.5rem;
    }
    
    .footer-logo-container {
        width: 45px;
        height: 45px;
        padding: 5px;
    }
    
    .security-badge {
        padding: 0.7rem 1.2rem;
        font-size: 0.85rem;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.footer-uniminuto {
    animation: fadeInUp 0.6s ease-out;
}
</style>