<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
checkSession();
checkRole('docente');

$database = new Database();
$db = $database->getConnection();

// Obtener materias del docente
$query_materias = "SELECT m.id, m.codigo, m.nombre 
                   FROM materias m
                   JOIN docente_materia dm ON m.id = dm.materia_id
                   WHERE dm.docente_id = :docente_id
                   ORDER BY m.codigo";
$stmt_materias = $db->prepare($query_materias);
$stmt_materias->bindParam(":docente_id", $_SESSION['user_id']);
$stmt_materias->execute();
$materias = $stmt_materias;

// Obtener disponibilidad actual del docente
$query = "SELECT dd.*, m.codigo, m.nombre as materia_nombre 
          FROM disponibilidad_docentes dd 
          LEFT JOIN materias m ON dd.materia_id = m.id
          WHERE dd.docente_id = :docente_id 
          ORDER BY FIELD(dd.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'), 
                   dd.hora_inicio";
$stmt = $db->prepare($query);
$stmt->bindParam(":docente_id", $_SESSION['user_id']);
$stmt->execute();
$disponibilidad = $stmt;

// Agregar nueva disponibilidad
if ($_POST && isset($_POST['agregar_horario'])) {
    // Validar que hora_fin > hora_inicio
    if ($_POST['hora_fin'] <= $_POST['hora_inicio']) {
        $_SESSION['error'] = "La hora de fin debe ser posterior a la hora de inicio";
        redirect('disponibilidad.php');
    }
    
    // Validar solapamiento de horarios
    $query_verificar = "SELECT COUNT(*) as count 
                       FROM disponibilidad_docentes 
                       WHERE docente_id = :docente_id 
                       AND dia_semana = :dia_semana 
                       AND ((hora_inicio < :hora_fin AND hora_fin > :hora_inicio))";
    $stmt_verificar = $db->prepare($query_verificar);
    $stmt_verificar->bindParam(":docente_id", $_SESSION['user_id']);
    $stmt_verificar->bindParam(":dia_semana", $_POST['dia_semana']);
    $stmt_verificar->bindParam(":hora_inicio", $_POST['hora_inicio']);
    $stmt_verificar->bindParam(":hora_fin", $_POST['hora_fin']);
    $stmt_verificar->execute();
    $existe = $stmt_verificar->fetch(PDO::FETCH_ASSOC);
    
    if ($existe['count'] > 0) {
        $_SESSION['error'] = "Ya tienes un horario en ese rango de tiempo";
        redirect('disponibilidad.php');
    }
    
    $query = "INSERT INTO disponibilidad_docentes (docente_id, materia_id, dia_semana, hora_inicio, hora_fin, lugar) 
              VALUES (:docente_id, :materia_id, :dia_semana, :hora_inicio, :hora_fin, :lugar)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":docente_id", $_SESSION['user_id']);
    $stmt->bindParam(":materia_id", $_POST['materia_id']);
    $stmt->bindParam(":dia_semana", $_POST['dia_semana']);
    $stmt->bindParam(":hora_inicio", $_POST['hora_inicio']);
    $stmt->bindParam(":hora_fin", $_POST['hora_fin']);
    $stmt->bindParam(":lugar", $_POST['lugar']);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Horario agregado exitosamente";
    } else {
        $_SESSION['error'] = "Error al agregar el horario";
    }
    redirect('disponibilidad.php');
}

// Eliminar disponibilidad
if ($_POST && isset($_POST['eliminar_horario'])) {
    try {
        // Verificar si hay tutorías asociadas antes de eliminar
        $query_check = "SELECT COUNT(*) as count FROM tutorias WHERE disponibilidad_id = :disponibilidad_id";
        $stmt_check = $db->prepare($query_check);
        $stmt_check->bindParam(":disponibilidad_id", $_POST['horario_id']);
        $stmt_check->execute();
        $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "No se puede eliminar este horario porque tiene tutorías programadas. Primero cancela las tutorías asociadas.";
            redirect('disponibilidad.php');
            exit;
        }
        
        // Si no hay tutorías, proceder con la eliminación
        $query = "DELETE FROM disponibilidad_docentes WHERE id = :id AND docente_id = :docente_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $_POST['horario_id']);
        $stmt->bindParam(":docente_id", $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Horario eliminado exitosamente";
        } else {
            $_SESSION['error'] = "Error al eliminar el horario";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: No se puede eliminar el horario porque tiene tutorías asociadas. " . $e->getMessage();
    }
    
    redirect('disponibilidad.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Disponibilidad - Docente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/styles.css" rel="stylesheet">
    <style>
    :root {
        --uniminuto-azul: #0056a3;
        --uniminuto-azul-oscuro: #7391ac;
        --uniminuto-verde: #28a745;
        --uniminuto-amarillo: #ffc107;
        --uniminuto-rojo: #dc3545;
    }
    
    .page-header {
        background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
        color: white;
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 8px 25px rgba(0, 86, 163, 0.15);
    }
    
    .table-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
    
    .badge-estado {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        border: 2px solid transparent;
    }
    
    .bg-success {
        background: linear-gradient(135deg, var(--uniminuto-verde) 0%, #34ce87 100%) !important;
        color: white !important;
        border-color: #1e7e34 !important;
    }
    
    .bg-warning {
        background: linear-gradient(135deg, var(--uniminuto-amarillo) 0%, #ffd54f 100%) !important;
        color: #000 !important;
        border-color: #d39e00 !important;
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
    
    .modal-header {
        background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
        color: white;
    }
    
    .btn-danger {
        background: var(--uniminuto-rojo);
        border: none;
    }
    
    .btn-primary {
        background: var(--uniminuto-azul);
        border: none;
    }
    
    .btn-primary:hover {
        background: var(--uniminuto-azul-oscuro);
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
                        <i class="bi bi-clock me-3"></i>Mi Disponibilidad
                    </h1>
                    <p class="fs-5 mb-0 opacity-90">Configura tus horarios para tutorías por materia</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex gap-2 justify-content-end">
                        <a href="dashboard.php" class="btn btn-volver">
                            <i class="bi bi-arrow-left me-2"></i> Volver
                        </a>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#agregarHorarioModal">
                            <i class="bi bi-plus-circle me-2"></i>Agregar Horario
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>
        
        <!-- Tabla de Horarios -->
        <div class="table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i> Horarios de Tutoría</h5>
            </div>
            <div class="card-body p-0">
                <?php if ($disponibilidad->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Materia</th>
                                    <th>Día</th>
                                    <th>Hora Inicio</th>
                                    <th>Hora Fin</th>
                                    <th>Lugar</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $disponibilidad->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td>
                                        <?php if ($row['materia_id']): ?>
                                            <strong><?php echo htmlspecialchars($row['codigo']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($row['materia_nombre']); ?></small>
                                        <?php else: ?>
                                            <span class="text-danger">⚠️ Sin materia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['dia_semana']); ?></td>
                                    <td><?php echo date('H:i', strtotime($row['hora_inicio'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($row['hora_fin'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['lugar']); ?></td>
                                    <td>
                                        <span class="badge-estado bg-<?php echo $row['estado'] == 'disponible' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($row['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="horario_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="eliminar_horario" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('¿Estás seguro de eliminar este horario?')">
                                                <i class="bi bi-trash me-1"></i> Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-clock"></i>
                        <h4 class="text-muted mb-3">No tienes horarios configurados</h4>
                        <p class="text-muted mb-4">Agrega tus horarios de disponibilidad para que los estudiantes puedan reservar tutorías</p>
                        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#agregarHorarioModal">
                            <i class="bi bi-plus-circle me-2"></i>Agregar Primer Horario
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Horario -->
    <div class="modal fade" id="agregarHorarioModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="horarioForm">
                    <input type="hidden" name="agregar_horario" value="1">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-plus-circle me-2"></i>Agregar Horario de Disponibilidad
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Materia *</label>
                            <select class="form-select" name="materia_id" required>
                                <option value="">Selecciona una materia</option>
                                <?php while ($materia = $materias->fetch(PDO::FETCH_ASSOC)): ?>
                                    <option value="<?php echo $materia['id']; ?>">
                                        <?php echo htmlspecialchars($materia['codigo'] . ' - ' . $materia['nombre']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div class="form-text">Selecciona la materia para esta tutoría</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Día de la Semana *</label>
                            <select class="form-select" name="dia_semana" required>
                                <option value="">Selecciona un día</option>
                                <option value="Lunes">Lunes</option>
                                <option value="Martes">Martes</option>
                                <option value="Miércoles">Miércoles</option>
                                <option value="Jueves">Jueves</option>
                                <option value="Viernes">Viernes</option>
                                <option value="Sábado">Sábado</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Hora Inicio *</label>
                                    <input type="time" class="form-control" name="hora_inicio" id="hora_inicio" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Hora Fin *</label>
                                    <input type="time" class="form-control" name="hora_fin" id="hora_fin" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Lugar *</label>
                            <input type="text" class="form-control" name="lugar" value="Salón B402" required>
                            <div class="form-text">Ej: Salón B402, Biblioteca, Laboratorio</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Agregar Horario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar que hora_fin > hora_inicio
        document.getElementById('horarioForm').addEventListener('submit', function(e) {
            const horaInicio = document.getElementById('hora_inicio').value;
            const horaFin = document.getElementById('hora_fin').value;
            
            if (horaFin <= horaInicio) {
                e.preventDefault();
                alert('La hora de fin debe ser posterior a la hora de inicio');
                return false;
            }
        });

        // Resetear el formulario cuando se cierra el modal
        const modal = document.getElementById('agregarHorarioModal');
        if (modal) {
            modal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('horarioForm').reset();
            });
        }
    </script>
</body>
</html>