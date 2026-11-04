<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/functions.php';
require_once __DIR__ . '/../../modelos/Materia.php';
require_once __DIR__ . '/../../modelos/Docente.php';
require_once __DIR__ . '/../../modelos/Tutoria.php';
checkSession();
checkRole('estudiante');

$database = new Database();
$db = $database->getConnection();

$materia = new Materia($db);
$docente = new Docente($db);
$docentes = $docente->obtenerTodos();

// Verificar disponibilidad antes de procesar la reserva
if ($_POST && isset($_POST['solicitar_tutoria'])) {
    // Verificar que el estudiante no tenga ya una tutoría programada en este mismo horario
    $query_verificar_estudiante = "SELECT COUNT(*) as count 
                                  FROM tutorias 
                                  WHERE estudiante_id = :estudiante_id 
                                  AND disponibilidad_id = :disponibilidad_id 
                                  AND estado = 'programada'";
    $stmt_verificar_estudiante = $db->prepare($query_verificar_estudiante);
    $stmt_verificar_estudiante->bindParam(':estudiante_id', $_SESSION['user_id']);
    $stmt_verificar_estudiante->bindParam(':disponibilidad_id', $_POST['disponibilidad_id']);
    $stmt_verificar_estudiante->execute();
    $tutoria_existente = $stmt_verificar_estudiante->fetch(PDO::FETCH_ASSOC);
    
    if ($tutoria_existente && $tutoria_existente['count'] > 0) {
        $_SESSION['error'] = "Ya tienes una tutoría programada en este horario.";
        redirect('solicitar_tutoria.php');
    }
    
    $tutoria = new Tutoria($db);
    $tutoria->estudiante_id = $_SESSION['user_id'];
    $tutoria->docente_id = $_POST['docente_id'];
    $tutoria->materia_id = $_POST['materia_id'];
    $tutoria->disponibilidad_id = $_POST['disponibilidad_id'];
    $tutoria->fecha_tutoria = $_POST['fecha_tutoria'];
    $tutoria->tema_tratar = $_POST['tema_tratar'];
    $tutoria->estado = 'programada';
    
    if ($tutoria->crear()) {
        $_SESSION['success'] = "¡Tutoría reservada exitosamente!";
        redirect('mis_tutorias.php');
    } else {
        $_SESSION['error'] = "Error al reservar la tutoría. Intenta nuevamente.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar Tutoría - UNIMINUTO</title>
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
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 86, 163, 0.15);
        }
        
        .form-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
            background: white;
        }
        
        .form-card .card-header {
            background: linear-gradient(135deg, var(--uniminuto-azul) 0%, var(--uniminuto-azul-oscuro) 100%);
            color: white;
            border: none;
            padding: 1.5rem 2rem;
        }
        
        .form-label {
            color: var(--uniminuto-azul);
            font-weight: 600;
            margin-bottom: 0.5rem;
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
        
        .horario-card {
            border: 2px solid #e3f2fd;
            border-radius: 12px;
            padding: 1.25rem;
            transition: all 0.3s ease;
            background: white;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        
        .horario-card:hover {
            border-color: var(--uniminuto-azul);
            box-shadow: 0 5px 15px rgba(0,86,163,0.1);
            transform: translateY(-2px);
        }
        
        .horario-card.selected {
            border-color: var(--uniminuto-verde);
            background: linear-gradient(135deg, #f0f9f0 0%, #e8f5e8 100%);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.2);
        }
        
        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.3em;
        }
        
        .form-check-input:checked {
            background-color: var(--uniminuto-verde);
            border-color: var(--uniminuto-verde);
        }
        
        .btn-reservar {
            background: linear-gradient(135deg, var(--uniminuto-verde) 0%, #20c997 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        
        .btn-reservar:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        
        .btn-cancelar {
            border: 2px solid var(--uniminuto-azul);
            color: var(--uniminuto-azul);
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-cancelar:hover {
            background: var(--uniminuto-azul);
            color: white;
        }
        
        .steps-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .step-item {
            text-align: center;
            flex: 1;
        }
        
        .step-number {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin: 0 auto 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .step-item.active .step-number {
            background: var(--uniminuto-azul);
            color: white;
        }
        
        .step-item.completed .step-number {
            background: var(--uniminuto-verde);
            color: white;
        }
        
        .step-label {
            font-size: 0.85rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .step-item.active .step-label {
            color: var(--uniminuto-azul);
            font-weight: 600;
        }
        
        .step-item.completed .step-label {
            color: var(--uniminuto-verde);
        }
        
        .empty-horarios {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 12px;
            border: 2px dashed #dee2e6;
        }
        
        .empty-horarios i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .horario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .fecha-info {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            border-left: 4px solid var(--uniminuto-amarillo);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        /* ESTILOS PARA GUÍA DE DÍAS DISPONIBLES */
        .guia-dias-container {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0f9f0 100%);
            border: 2px solid #d4edda;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .dias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .dia-item {
            background: white;
            border: 2px solid #28a745;
            border-radius: 8px;
            padding: 0.75rem 0.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .dia-item:hover {
            background: #28a745;
            color: white;
            transform: translateY(-2px);
        }
        
        .dia-nombre {
            font-weight: 700;
            font-size: 0.9rem;
            color: #28a745;
            margin-bottom: 0.25rem;
        }
        
        .dia-item:hover .dia-nombre {
            color: white;
        }
        
        .dia-horario {
            font-size: 0.7rem;
            color: #6c757d;
        }
        
        .dia-item:hover .dia-horario {
            color: rgba(255,255,255,0.9);
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid var(--uniminuto-azul);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .calendar-container {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
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
                        <i class="bi bi-calendar-plus me-3"></i>Reservar Tutoría
                    </h1>
                    <p class="fs-5 mb-0 opacity-90">Selecciona docente, materia y fecha para ver horarios disponibles</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn btn-light btn-lg">
                        <i class="bi bi-arrow-left me-2"></i> Volver al Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php showMessage(); ?>

        <!-- Indicador de pasos -->
        <div class="steps-container">
            <div class="row">
                <div class="col-md-3 step-item active" id="step1">
                    <div class="step-number">1</div>
                    <div class="step-label">Docente & Materia</div>
                </div>
                <div class="col-md-3 step-item" id="step2">
                    <div class="step-number">2</div>
                    <div class="step-label">Seleccionar Fecha</div>
                </div>
                <div class="col-md-3 step-item" id="step3">
                    <div class="step-number">3</div>
                    <div class="step-label">Elegir Horario</div>
                </div>
                <div class="col-md-3 step-item" id="step4">
                    <div class="step-number">4</div>
                    <div class="step-label">Confirmar</div>
                </div>
            </div>
        </div>
        
        <!-- Formulario de reserva -->
        <div class="form-card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i>Completa los datos de la tutoría
                </h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" id="tutoriaForm">
                    <input type="hidden" name="solicitar_tutoria" value="1">
                    <input type="hidden" name="fecha_tutoria" id="fecha_tutoria" value="">
                    
                    <!-- Paso 1: Docente y Materia -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-person me-2"></i>Docente *
                                </label>
                                <select class="form-select" name="docente_id" id="docente_id" required>
                                    <option value="">Selecciona un docente</option>
                                    <?php 
                                    $docentes = $docente->obtenerTodos();
                                    while ($row = $docentes->fetch(PDO::FETCH_ASSOC)): ?>
                                        <option value="<?php echo $row['id']; ?>">
                                            <?php echo htmlspecialchars($row['nombre'] . ' - ' . $row['programa_academico']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-book me-2"></i>Materia *
                                </label>
                                <select class="form-select" name="materia_id" id="materia_id" required disabled>
                                    <option value="">Primero selecciona un docente</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Guía de Días Disponibles (Solo informativa) -->
                    <div class="mb-4" id="guia-dias-container" style="display: none;">
                        <label class="form-label">
                            <i class="bi bi-info-circle me-2"></i>Días Disponibles del Docente
                        </label>
                        <div class="guia-dias-container">
                            <p class="text-muted mb-3">
                                <i class="bi bi-lightbulb me-1"></i>
                                <strong>Consejo:</strong> El docente tiene disponibilidad en los siguientes días. Selecciona una fecha en el calendario que coincida con estos días para encontrar horarios disponibles.
                            </p>
                            <div id="dias-content">
                                <!-- Los días se cargarán aquí dinámicamente -->
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Selector de Fecha -->
                    <div class="mb-4" id="fecha-container" style="display: none;">
                        <label class="form-label">
                            <i class="bi bi-calendar-date me-2"></i>Seleccionar Fecha *
                        </label>
                        <div class="calendar-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="date" class="form-control" name="fecha_seleccionada" id="fecha_seleccionada" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                    <div class="form-text mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Selecciona cualquier fecha a partir de mañana. El sistema mostrará horarios disponibles automáticamente.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <h6 class="text-primary mb-2">
                                            <i class="bi bi-question-circle me-2"></i>¿Cómo funciona?
                                        </h6>
                                        <ul class="small text-muted mb-0">
                                            <li>Selecciona una fecha en el calendario</li>
                                            <li>El sistema mostrará horarios para ese día</li>
                                            <li>Elige el horario que prefieras</li>
                                            <li>Completa los temas a tratar</li>
                                            <li>¡Reserva tu tutoría!</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Horarios Disponibles -->
                    <div class="mb-4" id="horarios-container">
                        <label class="form-label">
                            <i class="bi bi-clock me-2"></i>Horarios Disponibles
                        </label>
                        <div id="horarios-content">
                            <div class="empty-horarios">
                                <i class="bi bi-calendar"></i>
                                <h5 class="text-muted mb-3">Selecciona una fecha</h5>
                                <p class="text-muted">Elige una fecha en el calendario para ver los horarios disponibles</p>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4: Temas a tratar -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-journal-text me-2"></i>Temas a Tratar *
                        </label>
                        <textarea class="form-control" name="tema_tratar" id="tema_tratar" rows="3" 
                                  placeholder="Ejemplo: Derivadas en cálculo diferencial, problemas de física mecánica, balanceo de ecuaciones químicas..." required></textarea>
                        <div class="form-text">
                            Describe los temas específicos que quieres tratar en la tutoría.
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="dashboard.php" class="btn btn-cancelar me-md-2">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-reservar" id="submitBtn" disabled>
                            <i class="bi bi-calendar-check me-2"></i>Reservar Tutoría
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Actualizar pasos
        function updateSteps() {
            const docenteId = document.getElementById('docente_id').value;
            const materiaId = document.getElementById('materia_id').value;
            const fechaSeleccionada = document.getElementById('fecha_seleccionada').value;
            const horarioSeleccionado = document.querySelector('input[name="disponibilidad_id"]:checked');
            const temas = document.getElementById('tema_tratar').value.trim();
            
            // Reset todos los pasos
            document.querySelectorAll('.step-item').forEach(step => {
                step.classList.remove('active', 'completed');
            });
            
            // Paso 1: Docente y Materia
            if (docenteId && materiaId) {
                document.getElementById('step1').classList.add('completed');
                document.getElementById('step2').classList.add('active');
            } else {
                document.getElementById('step1').classList.add('active');
                return;
            }
            
            // Paso 2: Fecha Seleccionada
            if (docenteId && materiaId && fechaSeleccionada) {
                document.getElementById('step2').classList.add('completed');
                document.getElementById('step3').classList.add('active');
            } else if (docenteId && materiaId) {
                document.getElementById('step2').classList.add('active');
                return;
            }
            
            // Paso 3: Horario Seleccionado
            if (docenteId && materiaId && fechaSeleccionada && horarioSeleccionado) {
                document.getElementById('step3').classList.add('completed');
                document.getElementById('step4').classList.add('active');
            } else if (docenteId && materiaId && fechaSeleccionada) {
                document.getElementById('step3').classList.add('active');
                return;
            }
            
            // Paso 4: Todo listo (solo visual)
            if (docenteId && materiaId && fechaSeleccionada && horarioSeleccionado && temas.length > 0) {
                document.getElementById('step4').classList.add('completed');
            } else if (docenteId && materiaId && fechaSeleccionada && horarioSeleccionado) {
                document.getElementById('step4').classList.add('active');
            }
        }

        // Cargar materias cuando se selecciona docente
        document.getElementById('docente_id').addEventListener('change', function() {
            const docenteId = this.value;
            const materiaSelect = document.getElementById('materia_id');
            const guiaDiasContainer = document.getElementById('guia-dias-container');
            const fechaContainer = document.getElementById('fecha-container');
            
            // Reset todo
            materiaSelect.innerHTML = '<option value="">Selecciona una materia</option>';
            materiaSelect.disabled = true;
            guiaDiasContainer.style.display = 'none';
            fechaContainer.style.display = 'none';
            document.getElementById('horarios-content').innerHTML = 
                '<div class="empty-horarios">' +
                '<i class="bi bi-book me-2"></i>' +
                '<h5 class="text-muted mb-3">Selecciona una materia</h5>' +
                '<p class="text-muted">Primero elige una materia del docente seleccionado</p>' +
                '</div>';
            
            if (docenteId) {
                materiaSelect.disabled = false;
                
                // Cargar materias del docente seleccionado
                fetch(`cargar_materias.php?docente_id=${docenteId}`)
                    .then(response => response.json())
                    .then(materias => {
                        materiaSelect.innerHTML = '<option value="">Selecciona una materia</option>';
                        materias.forEach(materia => {
                            materiaSelect.innerHTML += `<option value="${materia.id}">${materia.codigo} - ${materia.nombre}</option>`;
                        });
                        
                        updateSteps();
                        validarFormulario();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        materiaSelect.innerHTML = '<option value="">Error al cargar materias</option>';
                        updateSteps();
                        validarFormulario();
                    });
            }
            
            updateSteps();
            validarFormulario();
        });

        // Cargar días disponibles cuando se selecciona materia
        document.getElementById('materia_id').addEventListener('change', function() {
            const docenteId = document.getElementById('docente_id').value;
            const materiaId = this.value;
            const guiaDiasContainer = document.getElementById('guia-dias-container');
            const fechaContainer = document.getElementById('fecha-container');
            
            if (docenteId && materiaId) {
                // Mostrar guía de días y selector de fecha
                guiaDiasContainer.style.display = 'block';
                fechaContainer.style.display = 'block';
                
                // Cargar días disponibles (solo informativo)
                cargarDiasDisponibles(docenteId, materiaId);
                
                // Configurar evento para el selector de fecha
                configurarSelectorFecha();
                
            } else {
                guiaDiasContainer.style.display = 'none';
                fechaContainer.style.display = 'none';
            }
            
            updateSteps();
            validarFormulario();
        });

        // Función para cargar días disponibles (solo informativo)
        function cargarDiasDisponibles(docenteId, materiaId) {
            const diasContent = document.getElementById('dias-content');
            
            diasContent.innerHTML = 
                '<div class="text-center py-2">' +
                '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
                '<p class="mt-2 text-muted small">Cargando información de días...</p>' +
                '</div>';
            
            fetch(`cargar_dias_disponibles.php?docente_id=${docenteId}&materia_id=${materiaId}`)
                .then(response => response.text())
                .then(html => {
                    diasContent.innerHTML = html;
                    updateSteps();
                })
                .catch(error => {
                    console.error('Error:', error);
                    diasContent.innerHTML = 
                        '<div class="alert alert-warning small mb-0">No se pudieron cargar los días disponibles</div>';
                    updateSteps();
                });
        }

        // Configurar selector de fecha
        function configurarSelectorFecha() {
            const fechaInput = document.getElementById('fecha_seleccionada');
            
            // Establecer fecha mínima como mañana
            const manana = new Date();
            manana.setDate(manana.getDate() + 1);
            fechaInput.min = manana.toISOString().split('T')[0];
            
            // Limpiar fecha anterior
            fechaInput.value = '';
            
            // Event listener para cuando se selecciona fecha
            fechaInput.addEventListener('change', function() {
                if (this.value) {
                    cargarHorariosPorFecha();
                } else {
                    document.getElementById('horarios-content').innerHTML = 
                        '<div class="empty-horarios">' +
                        '<i class="bi bi-calendar"></i>' +
                        '<h5 class="text-muted mb-3">Selecciona una fecha</h5>' +
                        '<p class="text-muted">Elige una fecha en el calendario para ver los horarios disponibles</p>' +
                        '</div>';
                }
                updateSteps();
                validarFormulario();
            });
        }

        // Función para cargar horarios por fecha
        function cargarHorariosPorFecha() {
            const docenteId = document.getElementById('docente_id').value;
            const materiaId = document.getElementById('materia_id').value;
            const fecha = document.getElementById('fecha_seleccionada').value;
            
            if (docenteId && materiaId && fecha) {
                document.getElementById('horarios-content').innerHTML = 
                    '<div class="text-center py-4">' +
                    '<div class="spinner-border text-primary" role="status"></div>' +
                    '<p class="mt-2 text-muted">Buscando horarios para ' + formatFecha(fecha) + '...</p>' +
                    '</div>';
                
                fetch(`cargar_horarios_fecha.php?docente_id=${docenteId}&materia_id=${materiaId}&fecha=${fecha}`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('horarios-content').innerHTML = html;
                        
                        // Agregar event listeners a los horarios
                        document.addEventListener('change', function(e) {
                            if (e.target.name === 'disponibilidad_id') {
                                const fechaTutoria = e.target.getAttribute('data-fecha-tutoria');
                                if (fechaTutoria) {
                                    document.getElementById('fecha_tutoria').value = fechaTutoria;
                                }
                                
                                updateSteps();
                                validarFormulario();
                                
                                // Agregar clase selected al horario seleccionado
                                document.querySelectorAll('.horario-card').forEach(card => {
                                    card.classList.remove('selected');
                                });
                                if (e.target.checked) {
                                    e.target.closest('.horario-card').classList.add('selected');
                                }
                            }
                        });
                        
                        updateSteps();
                        validarFormulario();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('horarios-content').innerHTML = 
                            '<div class="alert alert-danger">Error al cargar horarios. Intenta nuevamente.</div>';
                        updateSteps();
                        validarFormulario();
                    });
            }
        }

        // Función para formatear fecha
        function formatFecha(fechaISO) {
            const fecha = new Date(fechaISO + 'T00:00:00');
            const opciones = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            return fecha.toLocaleDateString('es-ES', opciones);
        }

        // Validar formulario en tiempo real
        function validarFormulario() {
            const docenteId = document.getElementById('docente_id').value;
            const materiaId = document.getElementById('materia_id').value;
            const fechaSeleccionada = document.getElementById('fecha_seleccionada').value;
            const horarioSeleccionado = document.querySelector('input[name="disponibilidad_id"]:checked');
            const temas = document.getElementById('tema_tratar').value.trim();
            
            const formularioValido = docenteId && materiaId && fechaSeleccionada && horarioSeleccionado && temas.length > 0;
            document.getElementById('submitBtn').disabled = !formularioValido;
        }

        // Validar textarea en tiempo real
        document.getElementById('tema_tratar').addEventListener('input', validarFormulario);

        // Prevenir envío doble y validar antes de enviar
        document.getElementById('tutoriaForm').addEventListener('submit', function(e) {
            if (!document.querySelector('input[name="disponibilidad_id"]:checked')) {
                e.preventDefault();
                showAlert('Por favor selecciona un horario disponible', 'warning');
                return false;
            }
            
            const temas = document.getElementById('tema_tratar').value.trim();
            if (temas.length === 0) {
                e.preventDefault();
                showAlert('Por favor describe los temas que necesitas tratar', 'warning');
                return false;
            }
            
            const fechaTutoria = document.getElementById('fecha_tutoria').value;
            if (!fechaTutoria) {
                e.preventDefault();
                showAlert('Error: No se ha seleccionado una fecha válida', 'danger');
                return false;
            }
            
            document.getElementById('submitBtn').disabled = true;
            document.getElementById('submitBtn').innerHTML = 
                '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Procesando...';
        });

        function showAlert(message, type) {
            // Remover alertas existentes
            document.querySelectorAll('.alert').forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.container').insertBefore(alertDiv, document.querySelector('.page-header').nextSibling);
            
            setTimeout(() => {
                if (alertDiv.parentElement) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Inicializar pasos
        updateSteps();
    </script>
</body>
</html>