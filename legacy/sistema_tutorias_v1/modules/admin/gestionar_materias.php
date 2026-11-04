<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('administrador');

$database = new Database();
$db = $database->getConnection();

// Configuración de paginación
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$registros_por_pagina = 10;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Obtener el total de materias
$query_total = "SELECT COUNT(*) as total FROM materias";
$stmt_total = $db->query($query_total);
$total_materias = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_materias / $registros_por_pagina);

// Obtener materias para la página actual
$query = "SELECT m.*, 
                 GROUP_CONCAT(DISTINCT u.nombre SEPARATOR ', ') as docentes_asignados,
                 COUNT(DISTINCT dm.docente_id) as total_docentes
          FROM materias m 
          LEFT JOIN docente_materia dm ON m.id = dm.materia_id 
          LEFT JOIN usuarios u ON dm.docente_id = u.id 
          GROUP BY m.id 
          ORDER BY m.nombre
          LIMIT :offset, :limit";
$stmt = $db->prepare($query);
$stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
$stmt->bindParam(":limit", $registros_por_pagina, PDO::PARAM_INT);
$stmt->execute();
$materias = $stmt;

// Obtener docentes para el select
$query_docentes = "SELECT id, nombre FROM usuarios WHERE rol = 'docente' AND estado = 'activo' ORDER BY nombre";
$docentes = $db->query($query_docentes);

// Crear nueva materia
if ($_POST && isset($_POST['crear_materia'])) {
    try {
        $db->beginTransaction();
        
        // Insertar la materia
        $query = "INSERT INTO materias (codigo, nombre, descripcion) 
                  VALUES (:codigo, :nombre, :descripcion)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":codigo", $_POST['codigo']);
        $stmt->bindParam(":nombre", $_POST['nombre']);
        $stmt->bindParam(":descripcion", $_POST['descripcion']);
        $stmt->execute();
        
        $materia_id = $db->lastInsertId();
        
        // Asignar docentes si se seleccionaron
        if (isset($_POST['docentes']) && is_array($_POST['docentes'])) {
            $query_asignar = "INSERT INTO docente_materia (docente_id, materia_id) VALUES (:docente_id, :materia_id)";
            $stmt_asignar = $db->prepare($query_asignar);
            
            foreach ($_POST['docentes'] as $docente_id) {
                $stmt_asignar->bindParam(":docente_id", $docente_id);
                $stmt_asignar->bindParam(":materia_id", $materia_id);
                $stmt_asignar->execute();
            }
        }
        
        $db->commit();
        $_SESSION['success'] = "✅ Materia creada exitosamente" . 
                              (isset($_POST['docentes']) ? " con " . count($_POST['docentes']) . " docente(s) asignado(s)" : "");
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = "❌ Error al crear la materia: " . $e->getMessage();
    }
    redirect('gestionar_materias.php');
}

// Cambiar estado de materia
if ($_POST && isset($_POST['cambiar_estado'])) {
    $materia_id = $_POST['materia_id'];
    $nuevo_estado = $_POST['nuevo_estado'];
    
    $query = "UPDATE materias SET estado = :estado WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":estado", $nuevo_estado);
    $stmt->bindParam(":id", $materia_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Estado de la materia actualizado";
    } else {
        $_SESSION['error'] = "❌ Error al actualizar el estado";
    }
    redirect('gestionar_materias.php');
}

// Asignar/desasignar docente
if ($_POST && isset($_POST['gestionar_docente'])) {
    $materia_id = $_POST['materia_id'];
    $docente_id = $_POST['docente_id'];
    $accion = $_POST['accion'];
    
    if ($accion == 'asignar') {
        $query = "INSERT INTO docente_materia (docente_id, materia_id) VALUES (:docente_id, :materia_id)";
    } else {
        $query = "DELETE FROM docente_materia WHERE docente_id = :docente_id AND materia_id = :materia_id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":docente_id", $docente_id);
    $stmt->bindParam(":materia_id", $materia_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "✅ Docente " . ($accion == 'asignar' ? 'asignado' : 'desasignado') . " exitosamente";
    } else {
        $_SESSION['error'] = "❌ Error al gestionar el docente";
    }
    redirect('gestionar_materias.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Materias - UNIMINUTO</title>
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
        
        .badge-activa {
            background: linear-gradient(135deg, var(--uniminuto-verde) 0%, var(--uniminuto-verde-claro) 100%);
            color: white;
            border-color: rgba(255,255,255,0.2);
        }
        
        .badge-inactiva {
            background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
            color: white;
            border-color: rgba(255,255,255,0.2);
        }
        
        .badge-docentes {
            background: linear-gradient(135deg, var(--uniminuto-morado) 0%, #8b5cf6 100%);
            color: white;
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.8rem;
            border: 2px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .badge-docentes:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .badge-sin-docentes {
            background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
            color: white;
            padding: 0.6rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.8rem;
            border: 2px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
        
        .modal-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
        }
        
        .form-control, .form-select, textarea {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus, textarea:focus {
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
        
        .docentes-list {
            max-width: 100%;
        }
        
        .docente-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .actions-group {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .descripcion-materia {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .pagination-container {
            background: white;
            padding: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .page-info {
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Estilos para checkboxes de docentes */
        .docentes-checkbox {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
        }
        
        .docente-checkbox-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .docente-checkbox-item:last-child {
            border-bottom: none;
        }
        
        .form-check-input:checked {
            background-color: var(--uniminuto-azul);
            border-color: var(--uniminuto-azul);
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
                        <i class="bi bi-book me-3"></i>Gestión de Materias
                    </h1>
                    <p class="fs-5 mb-0 opacity-90">Administra el catálogo de materias del área de Ciencias Básicas</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="dashboard.php" class="btn btn-volver">
                            <i class="bi bi-arrow-left me-2"></i> Volver al Dashboard
                        </a>
                        <button class="btn btn-crear" data-bs-toggle="modal" data-bs-target="#crearMateriaModal">
                            <i class="bi bi-plus-circle me-2"></i>Crear Materia
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>
        
        <!-- Lista de materias -->
        <div class="table-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-list-ul me-2"></i>Catálogo de Materias
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_materias; ?> materias</span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if ($materias->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Docentes</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($materia = $materias->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($materia['codigo']); ?></strong>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($materia['nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <div class="descripcion-materia" title="<?php echo htmlspecialchars($materia['descripcion']); ?>">
                                            <?php echo htmlspecialchars($materia['descripcion']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($materia['docentes_asignados']): ?>
                                            <span class="badge-estado badge-docentes" title="<?php echo htmlspecialchars($materia['docentes_asignados']); ?>">
                                                <i class="bi bi-people me-1"></i>
                                                <?php echo $materia['total_docentes']; ?> docente(s)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-estado badge-sin-docentes">
                                                <i class="bi bi-person-x me-1"></i>
                                                Sin docentes
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($materia['estado'] == 'activa'): ?>
                                            <span class="badge-estado badge-activa">
                                                <i class="bi bi-check-circle badge-icon"></i>
                                                <?php echo ucfirst($materia['estado']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-estado badge-inactiva">
                                                <i class="bi bi-x-circle badge-icon"></i>
                                                <?php echo ucfirst($materia['estado']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions-group">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="materia_id" value="<?php echo $materia['id']; ?>">
                                                <input type="hidden" name="cambiar_estado" value="1">
                                                <?php if ($materia['estado'] == 'activa'): ?>
                                                    <input type="hidden" name="nuevo_estado" value="inactiva">
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('¿Estás seguro de desactivar esta materia?')">
                                                        <i class="bi bi-x-circle me-1"></i> Desactivar
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="nuevo_estado" value="activa">
                                                    <button type="submit" class="btn btn-sm btn-success" 
                                                            onclick="return confirm('¿Estás seguro de activar esta materia?')">
                                                        <i class="bi bi-check-circle me-1"></i> Activar
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                            <button class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#gestionarDocentesModal"
                                                    data-materia-id="<?php echo $materia['id']; ?>"
                                                    data-materia-nombre="<?php echo htmlspecialchars($materia['nombre']); ?>">
                                                <i class="bi bi-people me-1"></i> Gestionar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginación -->
                    <?php if ($total_paginas > 1): ?>
                    <div class="pagination-container">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <span class="page-info">
                                    Mostrando <?php echo (($pagina_actual - 1) * $registros_por_pagina) + 1; ?> - 
                                    <?php echo min($pagina_actual * $registros_por_pagina, $total_materias); ?> 
                                    de <?php echo $total_materias; ?> materias
                                </span>
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Paginación de materias">
                                    <ul class="pagination justify-content-end mb-0">
                                        <!-- Botón Anterior -->
                                        <li class="page-item <?php echo $pagina_actual == 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $pagina_actual - 1; ?>" aria-label="Anterior">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        
                                        <!-- Números de página -->
                                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                            <li class="page-item <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                                                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <!-- Botón Siguiente -->
                                        <li class="page-item <?php echo $pagina_actual == $total_paginas ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?pagina=<?php echo $pagina_actual + 1; ?>" aria-label="Siguiente">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-book"></i>
                        <h4 class="text-muted mb-3">No hay materias registradas</h4>
                        <p class="text-muted mb-4">Comienza creando la primera materia del sistema</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#crearMateriaModal">
                            <i class="bi bi-plus-circle me-2"></i>Crear Primera Materia
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Crear Materia -->
    <div class="modal fade" id="crearMateriaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="crear_materia" value="1">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Crear Nueva Materia
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Código *</label>
                                    <input type="text" class="form-control" name="codigo" 
                                           placeholder="Ej: MAT101" required>
                                    <div class="form-text">Código único de la materia</div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" 
                                   placeholder="Ej: Cálculo Diferencial" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Descripción *</label>
                            <textarea class="form-control" name="descripcion" rows="4" 
                                      placeholder="Describe los contenidos y objetivos de la materia..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Asignar Docentes (Opcional)</label>
                            <div class="docentes-checkbox">
                                <?php 
                                $docentes_list = $db->query("SELECT id, nombre FROM usuarios WHERE rol = 'docente' AND estado = 'activo' ORDER BY nombre");
                                if ($docentes_list->rowCount() > 0):
                                    while ($docente = $docentes_list->fetch(PDO::FETCH_ASSOC)): 
                                ?>
                                    <div class="docente-checkbox-item">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="docentes[]" value="<?php echo $docente['id']; ?>" id="docente_<?php echo $docente['id']; ?>">
                                            <label class="form-check-label" for="docente_<?php echo $docente['id']; ?>">
                                                <?php echo htmlspecialchars($docente['nombre']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                    <div class="text-muted text-center py-3">
                                        <i class="bi bi-person-x fs-4 d-block mb-2"></i>
                                        No hay docentes disponibles
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="form-text mt-2">
                                <i class="bi bi-info-circle me-1"></i>
                                Selecciona los docentes que impartirán esta materia
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Crear Materia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Gestionar Docentes -->
    <div class="modal fade" id="gestionarDocentesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-people me-2"></i>Gestionar Docentes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="docentesContent">
                        <!-- El contenido se cargará dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resetear el formulario cuando se cierra el modal
        const modal = document.getElementById('crearMateriaModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                this.querySelector('form').reset();
            });
        }

        // Gestionar docentes modal
        const gestionarDocentesModal = document.getElementById('gestionarDocentesModal');
        if (gestionarDocentesModal) {
            gestionarDocentesModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const materiaId = button.getAttribute('data-materia-id');
                const materiaNombre = button.getAttribute('data-materia-nombre');
                
                this.querySelector('.modal-title').innerHTML = 
                    `<i class="bi bi-people me-2"></i>Docentes - ${materiaNombre}`;
                
                // Cargar docentes de la materia
                fetch(`cargar_docentes_materia.php?materia_id=${materiaId}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('docentesContent').innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('docentesContent').innerHTML = 
                            '<div class="alert alert-danger">Error al cargar los docentes</div>';
                    });
            });
        }

        // Función para asignar/desasignar docente
        function gestionarDocente(materiaId, docenteId, accion) {
            const formData = new FormData();
            formData.append('materia_id', materiaId);
            formData.append('docente_id', docenteId);
            formData.append('accion', accion);
            formData.append('gestionar_docente', '1');

            fetch('gestionar_materias.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    location.reload();
                } else {
                    alert('Error al gestionar el docente');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al gestionar el docente');
            });
        }
    </script>
</body>
</html>