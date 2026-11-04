<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../modelos/Usuario.php';
checkSession();
checkRole('administrador');

$database = new Database();
$db = $database->getConnection();

// Obtener todos los usuarios con paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$query = "SELECT SQL_CALC_FOUND_ROWS * FROM usuarios ORDER BY rol, nombre LIMIT :limit OFFSET :offset";
$stmt = $db->prepare($query);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$usuarios = $stmt;

$total_query = "SELECT FOUND_ROWS() as total";
$total_stmt = $db->query($total_query);
$total_usuarios = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_usuarios / $limit);

// Crear nuevo docente
if ($_POST && isset($_POST['crear_docente'])) {
    $usuario = new Usuario($db);
    $usuario->id_institucional = $_POST['id_institucional'];
    $usuario->nombre = $_POST['nombre'];
    $usuario->email = $_POST['email'];
    $usuario->password = $_POST['password'];
    $usuario->rol = 'docente';
    $usuario->programa_academico = $_POST['programa_academico'];
    
    $resultado = $usuario->registrar();
    
    if ($resultado === true) {
        $_SESSION['success'] = "✅ Docente creado exitosamente";
    } else {
        $_SESSION['error'] = $resultado;
    }
    redirect('gestionar_usuarios.php');
}

// Cambiar estado de usuario
if ($_POST && isset($_POST['cambiar_estado'])) {
    $usuario_id = $_POST['usuario_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $query = "UPDATE usuarios SET estado = :estado WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":estado", $nuevo_estado);
    $stmt->bindParam(":id", $usuario_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Estado del usuario actualizado";
    } else {
        $_SESSION['error'] = "❌ Error al actualizar el estado";
    }
    redirect('gestionar_usuarios.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - UNIMINUTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
        :root {
            --uniminuto-azul: #0056a3;
            --uniminuto-azul-oscuro: #7391ac;
            --uniminuto-blanco: #ffffff;
            --uniminuto-gris: #f8f9fa;
            --uniminuto-verde: #28a745;
            --uniminuto-amarillo: #ffc107;
            --uniminuto-rojo: #dc3545;
            --uniminuto-morado: #6f42c1;
            --uniminuto-naranja: #fd7e14;
            --uniminuto-verde-claro: #20c997;
            --uniminuto-azul-claro: #0dcaf0;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 86, 163, 0.2);
        }
        
        .table-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
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
        
        /* BADGES MEJORADOS - ROLES */
        .badge-rol {
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.8rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .badge-rol:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .badge-administrador {
            background: linear-gradient(135deg, var(--uniminuto-morado) 0%, #8b5cf6 100%);
            color: white;
            border-color: rgba(255,255,255,0.2);
        }
        
        .badge-docente {
            background: linear-gradient(135deg, var(--uniminuto-naranja) 0%, #fb923c 100%);
            color: white;
            border-color: rgba(255,255,255,0.2);
        }
        
        .badge-estudiante {
            background: linear-gradient(135deg, var(--uniminuto-azul-claro) 0%, #38bdf8 100%);
            color: white;
            border-color: rgba(255,255,255,0.2);
        }
        
        /* BADGES MEJORADOS - ESTADOS */
        .badge-estado {
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.8rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .badge-estado:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .badge-activo {
            background: linear-gradient(135deg, var(--uniminuto-verde) 0%, var(--uniminuto-verde-claro) 100%);
            color: white;
            border-color: rgba(255,255,255,0.2);
        }
        
        .badge-inactivo {
            background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
            color: white;
            border-color: rgba(255,255,255,0.2);
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
        
        .btn-crear {
            background: linear-gradient(135deg, var(--uniminuto-verde) 0%, var(--uniminuto-verde-claro) 100%);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-crear:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        
        .pagination .page-link {
            color: var(--uniminuto-azul);
            border: 1px solid #dee2e6;
        }
        
        .pagination .page-item.active .page-link {
            background: var(--uniminuto-azul);
            border-color: var(--uniminuto-azul);
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--uniminuto-azul);
            box-shadow: 0 0 0 0.2rem rgba(0, 86, 163, 0.15);
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
        
        .badge-icon {
            font-size: 0.9rem;
            opacity: 0.9;
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
                    <h1 class="display-6 fw-bold mb-2">
                        <i class="bi bi-people me-3"></i>Gestión de Usuarios
                    </h1>
                    <p class="fs-5 mb-0 opacity-90">Administra estudiantes, docentes y permisos del sistema</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="dashboard.php" class="btn btn-volver">
                            <i class="bi bi-arrow-left me-2"></i> Volver al Dashboard
                        </a>
                        <button class="btn btn-crear" data-bs-toggle="modal" data-bs-target="#crearDocenteModal">
                            <i class="bi bi-person-plus me-2"></i>Crear Docente
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>

        <!-- Lista de usuarios -->
        <div class="table-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>Lista de Usuarios 
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_usuarios; ?> usuarios</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if ($usuarios->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>ID Institucional</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Programa</th>
                                    <th>Estado</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($usuario = $usuarios->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($usuario['id_institucional']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td>
                                        <?php if ($usuario['rol'] == 'administrador'): ?>
                                            <span class="badge-rol badge-administrador">
                                                <i class="bi bi-shield-check badge-icon"></i>
                                                <?php echo ucfirst($usuario['rol']); ?>
                                            </span>
                                        <?php elseif ($usuario['rol'] == 'docente'): ?>
                                            <span class="badge-rol badge-docente">
                                                <i class="bi bi-person-badge badge-icon"></i>
                                                <?php echo ucfirst($usuario['rol']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-rol badge-estudiante">
                                                <i class="bi bi-person badge-icon"></i>
                                                <?php echo ucfirst($usuario['rol']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $usuario['programa_academico'] ? htmlspecialchars($usuario['programa_academico']) : 'N/A'; ?></td>
                                    <td>
                                        <?php if ($usuario['estado'] == 'activo'): ?>
                                            <span class="badge-estado badge-activo">
                                                <i class="bi bi-check-circle badge-icon"></i>
                                                <?php echo ucfirst($usuario['estado']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-estado badge-inactivo">
                                                <i class="bi bi-x-circle badge-icon"></i>
                                                <?php echo ucfirst($usuario['estado']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($usuario['fecha_creacion'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <input type="hidden" name="cambiar_estado" value="1">
                                            <?php if ($usuario['estado'] == 'activo'): ?>
                                                <input type="hidden" name="nuevo_estado" value="inactivo">
                                                <button type="submit" class="btn btn-sm btn-danger" 
                                                        onclick="return confirm('¿Estás seguro de desactivar este usuario?')">
                                                    <i class="bi bi-person-x me-1"></i> Desactivar
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="nuevo_estado" value="activo">
                                                <button type="submit" class="btn btn-sm btn-success" 
                                                        onclick="return confirm('¿Estás seguro de activar este usuario?')">
                                                    <i class="bi bi-person-check me-1"></i> Activar
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Paginación de usuarios">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                            <i class="bi bi-chevron-left"></i> Anterior
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_paginas): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                            Siguiente <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-people"></i>
                        <h4 class="text-muted mb-3">No hay usuarios registrados</h4>
                        <p class="text-muted">Comienza creando el primer usuario del sistema</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Crear Docente -->
    <div class="modal fade" id="crearDocenteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="crear_docente" value="1">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-person-plus me-2"></i>Crear Nuevo Docente
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">ID Institucional *</label>
                                    <input type="text" class="form-control" name="id_institucional" 
                                           placeholder="Ej: DOC001" required>
                                    <div class="form-text">Código único del docente en el sistema</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Programa Académico *</label>
                                    <input type="text" class="form-control" name="programa_academico" 
                                           placeholder="Ej: Matemáticas" required>
                                    <div class="form-text">Área de especialización del docente</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre Completo *</label>
                            <input type="text" class="form-control" name="nombre" 
                                   placeholder="Ej: Juan Pérez García" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Institucional *</label>
                            <input type="email" class="form-control" name="email" 
                                   placeholder="usuario@uniminuto.edu.co" required>
                            <div class="form-text">
                                <i class="bi bi-shield-check me-1"></i>Debe ser un correo institucional de UNIMINUTO
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Contraseña *</label>
                            <input type="password" class="form-control" name="password" 
                                   value="" required>
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-check me-2"></i>Crear Docente
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resetear el formulario cuando se cierra el modal
        const modal = document.getElementById('crearDocenteModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                this.querySelector('form').reset();
                // Restablecer la contraseña por defecto
                this.querySelector('input[name="password"]').value = 'password123';
            });
        }

        // Validar email institucional
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = this.querySelector('input[name="email"]').value;
            if (!email.endsWith('@uniminuto.edu.co')) {
                e.preventDefault();
                alert('Debe usar un correo institucional @uniminuto.edu.co');
                return false;
            }
        });
    </script>
</body>
</html>