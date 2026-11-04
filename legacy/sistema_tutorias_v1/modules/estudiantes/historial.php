<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../modelos/Tutoria.php';
checkSession();
checkRole('estudiante');

$database = new Database();
$db = $database->getConnection();

$tutoria = new Tutoria($db);
$historial = $tutoria->obtenerHistorialEstudiante($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Tutorías - UNIMINUTO</title>
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
            border-radius: 20px;
            padding: 3rem 2rem;
            margin-bottom: 3rem;
            box-shadow: 0 15px 35px rgba(0, 86, 163, 0.15);
        }
        
        .table-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
        
        .table tbody tr:hover {
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
        
        .bg-danger {
            background: linear-gradient(135deg, var(--uniminuto-rojo) 0%, #ff6b6b 100%) !important;
            color: white !important;
            border-color: #c82333 !important;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .tema-preview {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }
        
        .btn-ver-temas {
            border-radius: 8px;
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }
        
        .calificacion-estrellas {
            color: #ffc107;
            font-size: 1.1rem;
        }
        
        .historial-item {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }
        
        .historial-item.completada {
            border-left-color: var(--uniminuto-verde);
        }
        
        .historial-item.cancelada {
            border-left-color: var(--uniminuto-rojo);
        }
        
        .historial-item:hover {
            background-color: rgba(0, 86, 163, 0.02);
        }
        
        .modal-temas .modal-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
        }
        
        .temas-content {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            white-space: pre-wrap;
            line-height: 1.6;
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
    </style>
</head>
<body style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <?php include '../../includes/header.php'; ?>
    
    <div class="container py-4">
        <!-- Header de la página -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-5 fw-bold mb-3">
                        <i class="bi bi-clock-history me-3"></i>Historial de Tutorías
                    </h1>
                    <p class="fs-4 mb-0 opacity-90">Registro completo de todas tus tutorías y progreso académico</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-volver btn-lg">
                        <i class="bi bi-arrow-left me-2"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>

        <!-- Tabla de Historial -->
        <div class="table-card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i> Todas mis Tutorías</h5>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="bi bi-filter me-1"></i>
                            Mostrando <?php echo $historial->rowCount(); ?> registros
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if ($historial->rowCount() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Materia</th>
                                    <th>Docente</th>
                                    <th>Horario</th>
                                    <th>Estado</th>
                                    <th>Calificación</th>
                                    <th>Temas Tratados</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $historial->fetch(PDO::FETCH_ASSOC)): 
                                    $clase_estado = $row['estado'] == 'completada' ? 'completada' : 
                                                  ($row['estado'] == 'cancelada' ? 'cancelada' : '');
                                ?>
                                <tr class="historial-item <?php echo $clase_estado; ?>">
                                    <td>
                                        <div class="fw-bold"><?php echo date('d/m/Y', strtotime($row['fecha_tutoria'])); ?></div>
                                        <div class="text-muted small">
                                            <?php echo date('H:i', strtotime($row['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($row['hora_fin'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?php echo htmlspecialchars($row['materia_nombre']); ?></strong>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?php echo htmlspecialchars($row['docente_nombre']); ?></div>
                                        <div class="text-muted small">
                                            <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['lugar']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted">
                                            <?php echo date('H:i', strtotime($row['hora_inicio'])); ?> - <?php echo date('H:i', strtotime($row['hora_fin'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge-estado bg-<?php 
                                            echo $row['estado'] == 'completada' ? 'success' : 
                                                 ($row['estado'] == 'cancelada' ? 'danger' : 'warning'); 
                                        ?>">
                                            <i class="bi bi-<?php 
                                                echo $row['estado'] == 'completada' ? 'check-circle' : 
                                                     ($row['estado'] == 'cancelada' ? 'x-circle' : 'clock'); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst($row['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['calificacion'])): ?>
                                            <div class="calificacion-estrellas text-center">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?php echo $i <= $row['calificacion'] ? '-fill' : ''; ?>"></i>
                                                <?php endfor; ?>
                                                <div class="small text-muted mt-1"><?php echo $row['calificacion']; ?>/5</div>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted text-center d-block">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['tema_tratar'])): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="tema-preview" title="<?php echo htmlspecialchars($row['tema_tratar']); ?>">
                                                    <?php echo htmlspecialchars(substr($row['tema_tratar'], 0, 50)) . (strlen($row['tema_tratar']) > 50 ? '...' : ''); ?>
                                                </span>
                                                <button type="button" class="btn btn-sm btn-outline-info btn-ver-temas" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#temasModal"
                                                        data-materia="<?php echo htmlspecialchars($row['materia_nombre']); ?>"
                                                        data-fecha="<?php echo date('d/m/Y', strtotime($row['fecha_tutoria'])); ?>"
                                                        data-docente="<?php echo htmlspecialchars($row['docente_nombre']); ?>"
                                                        data-temas="<?php echo htmlspecialchars($row['tema_tratar']); ?>">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Sin temas registrados</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-journal-x"></i>
                        <h4 class="text-muted mb-3">Aún no tienes tutorías en tu historial</h4>
                        <p class="text-muted mb-4">Tus tutorías completadas y canceladas aparecerán aquí</p>
                        <a href="solicitar_tutoria.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle me-2"></i> Reservar Primera Tutoría
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para ver temas completos -->
    <div class="modal fade modal-temas" id="temasModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-journal-text me-2"></i>Temas Solicitados
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <p><strong><i class="bi bi-book me-2"></i>Materia:</strong><br>
                            <span id="modalMateria" class="text-primary"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong><i class="bi bi-calendar me-2"></i>Fecha:</strong><br>
                            <span id="modalFecha"></span></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong><i class="bi bi-person me-2"></i>Docente:</strong><br>
                            <span id="modalDocente"></span></p>
                        </div>
                    </div>
                    <hr>
                    <h6 class="mb-3"><i class="bi bi-bookmark me-2"></i>Temas Solicitados:</h6>
                    <div class="temas-content">
                        <span id="modalTemas"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Configurar modal de temas
        const temasModal = document.getElementById('temasModal');
        if (temasModal) {
            temasModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                document.getElementById('modalMateria').textContent = button.getAttribute('data-materia');
                document.getElementById('modalFecha').textContent = button.getAttribute('data-fecha');
                document.getElementById('modalDocente').textContent = button.getAttribute('data-docente');
                document.getElementById('modalTemas').textContent = button.getAttribute('data-temas');
            });
        }

        // Ordenar tabla por fecha (más reciente primero)
        document.addEventListener('DOMContentLoaded', function() {
            const tabla = document.querySelector('table');
            if (tabla) {
                const filas = Array.from(tabla.querySelectorAll('tbody tr'));
                
                // Ordenar por fecha (más reciente primero)
                filas.sort((a, b) => {
                    const fechaA = new Date(a.cells[0].textContent.split('/').reverse().join('-'));
                    const fechaB = new Date(b.cells[0].textContent.split('/').reverse().join('-'));
                    return fechaB - fechaA;
                });
                
                // Reinsertar filas ordenadas
                const tbody = tabla.querySelector('tbody');
                tbody.innerHTML = '';
                filas.forEach(fila => tbody.appendChild(fila));
            }
        });
    </script>
</body>
</html>