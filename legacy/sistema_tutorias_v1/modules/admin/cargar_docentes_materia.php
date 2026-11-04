<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('administrador');

$database = new Database();
$db = $database->getConnection();

$materia_id = $_GET['materia_id'] ?? 0;

// Obtener docentes asignados a la materia
$query_asignados = "SELECT u.id, u.nombre 
                   FROM usuarios u 
                   INNER JOIN docente_materia dm ON u.id = dm.docente_id 
                   WHERE dm.materia_id = :materia_id 
                   ORDER BY u.nombre";
$stmt_asignados = $db->prepare($query_asignados);
$stmt_asignados->bindParam(":materia_id", $materia_id);
$stmt_asignados->execute();
$docentes_asignados = $stmt_asignados->fetchAll(PDO::FETCH_ASSOC);

// Obtener docentes disponibles
$query_disponibles = "SELECT id, nombre 
                     FROM usuarios 
                     WHERE rol = 'docente' 
                     AND estado = 'activo' 
                     AND id NOT IN (
                         SELECT docente_id 
                         FROM docente_materia 
                         WHERE materia_id = :materia_id
                     )
                     ORDER BY nombre";
$stmt_disponibles = $db->prepare($query_disponibles);
$stmt_disponibles->bindParam(":materia_id", $materia_id);
$stmt_disponibles->execute();
$docentes_disponibles = $stmt_disponibles->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-6">
        <h6 class="fw-bold text-primary mb-3">
            <i class="bi bi-person-check me-2"></i>Docentes Asignados
        </h6>
        <?php if (count($docentes_asignados) > 0): ?>
            <div class="docentes-list">
                <?php foreach ($docentes_asignados as $docente): ?>
                    <div class="docente-item">
                        <span><?php echo htmlspecialchars($docente['nombre']); ?></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="gestionarDocente(<?php echo $materia_id; ?>, <?php echo $docente['id']; ?>, 'desasignar')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>
                No hay docentes asignados a esta materia
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-6">
        <h6 class="fw-bold text-success mb-3">
            <i class="bi bi-person-plus me-2"></i>Docentes Disponibles
        </h6>
        <?php if (count($docentes_disponibles) > 0): ?>
            <div class="docentes-list">
                <?php foreach ($docentes_disponibles as $docente): ?>
                    <div class="docente-item">
                        <span><?php echo htmlspecialchars($docente['nombre']); ?></span>
                        <button type="button" class="btn btn-sm btn-outline-success" 
                                onclick="gestionarDocente(<?php echo $materia_id; ?>, <?php echo $docente['id']; ?>, 'asignar')">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                No hay docentes disponibles para asignar
            </div>
        <?php endif; ?>
    </div>
</div>