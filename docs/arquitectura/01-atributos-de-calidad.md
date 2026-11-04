# 01 · Drivers y atributos de calidad

## Drivers funcionales (T01)

Los 9 requerimientos funcionales del proyecto de Ingeniería de Software se conservan y se priorizan por su peso arquitectónico:

| # | Requerimiento | Peso arquitectónico | Módulo |
|---|---|---|---|
| RF1 | Registro de estudiantes con correo institucional | Medio — seguridad, datos personales | Identidad |
| RF2 | Inicio de sesión por rol | **Alto** — seguridad | Identidad |
| RF3 | Reserva de tutoría según disponibilidad | **Alto** — concurrencia, integridad | Reservas |
| RF4 | Consulta de tutorías | Medio — rendimiento | Reservas |
| RF5 | Cancelación con mínimo 3 horas de anticipación | **Alto** — regla de negocio temporal | Reservas |
| RF6 | Historial y calificación | Medio | Reservas |
| RF7 | Gestión de disponibilidad del docente | **Alto** — consistencia de horarios | Disponibilidad |
| RF8 | Gestión de usuarios y materias | Medio | Identidad, Catálogo |
| RF9 | Reportes y notificaciones | Medio — disponibilidad, rendimiento | Reportes, Notificaciones |

## Requisitos no funcionales medibles (T02 · ISO/IEC 25010)

| Característica | Requisito | Métrica / umbral | Cómo se verifica |
|---|---|---|---|
| Seguridad · confidencialidad | Ninguna consulta SQL se arma concatenando datos del usuario | 0 hallazgos de SQLi en SAST y DAST | Semgrep (CI), OWASP ZAP (`dast.yml`), `AuthApiTest::login_resiste_inyeccion_sql` |
| Seguridad · autenticidad | Contraseñas con bcrypt (costo 12), sesión con JWT de 8 h | 100 % de contraseñas con hash; tokens alterados rechazados | `TokenServiceTest`, `AuthApiTest` |
| Seguridad · no repudio | Toda acción sensible queda auditada con usuario, IP y fecha | 100 % de reservas, cancelaciones y cambios administrativos | `BookingApiTest::docente_cancela_con_motivo_y_auditoria_registra_la_accion` |
| Integridad de datos | Nunca se supera el cupo de una franja | 0 sobrecupos con 12 reservas simultáneas | `ConcurrentBookingTest` |
| Rendimiento | Consulta de cupos y reserva | p95 < 500 ms con 200 usuarios concurrentes (inicio de semestre) | Prueba de carga k6 (T36) contra Staging |
| Disponibilidad | Servicio disponible en horario académico | 99,5 % mensual; RTO ≤ 1 h; RPO ≤ 24 h | Health checks, respaldos diarios, prueba de restauración (T21, T38) |
| Fiabilidad | Una falla del servidor de correo no pierde notificaciones | 100 % de eventos entregados tras reintentos | Outbox con 5 reintentos y backoff; `BookingApiTest::la_reserva_genera_notificaciones_via_outbox` |
| Mantenibilidad | Módulos con dependencias explícitas; estilo y tipos verificados | PSR-12 sin errores; PHPStan nivel 6 sin errores | `phpcs`, `phpstan` en CI |
| Comprobabilidad | Reglas de negocio probadas sin base de datos | Dominio de Reservas cubierto por pruebas unitarias | `TutoringTest` (21 casos) |
| Usabilidad | Reserva en ≤ 3 pasos desde cualquier dispositivo | Flujo materia → cupo → tema; diseño responsivo hasta 360 px | Pruebas con usuarios piloto (T39) |
| Cumplimiento | Ley 1581 de 2012 (habeas data) | Consentimiento registrado con fecha en el 100 % de registros | `RegisterStudent` exige `aceptaTratamientoDatos` |

## Escenarios de atributos de calidad (T04 · plantilla SEI)

Cada escenario indica la **táctica** elegida (T05) y **dónde está implementada y probada**.

### E1 · Doble reserva concurrente (integridad)
- **Fuente:** 12 estudiantes. **Estímulo:** reservan el último cupo de la misma franja en el mismo instante.
- **Artefacto:** caso de uso `BookTutoring`. **Entorno:** operación normal, inicio de semestre.
- **Respuesta:** exactamente una reserva se confirma; las demás reciben `409 slot_full`.
- **Medida:** 0 sobrecupos.
- **Táctica:** bloqueo pesimista de la fila de la franja (`SELECT … FOR UPDATE`) + validación dentro de la misma transacción + índice único `uq_tutorias_reserva_unica` como red de seguridad.
- **Evidencia:** `tests/Integration/ConcurrentBookingTest.php`. Al quitar el `FOR UPDATE` la prueba falla con 12 reservas sobre un cupo de 1, lo que demuestra que la táctica es la que evita el problema.

### E2 · Doble clic del mismo estudiante
- **Estímulo:** el estudiante envía 6 veces la misma reserva en paralelo. **Respuesta:** se crea una sola. **Medida:** 1 fila.
- **Táctica:** columna generada `reserva_activa` + índice único; detección de cruces del estudiante.
- **Evidencia:** `ConcurrentBookingTest::doble_clic_del_mismo_estudiante_no_crea_dos_reservas`.

### E3 · Intento de inyección SQL en el login
- **Fuente:** atacante externo. **Estímulo:** `' OR '1'='1` en el correo.
- **Respuesta:** `401` genérico, sin filtrar información. **Medida:** 0 accesos indebidos.
- **Táctica:** consultas preparadas reales (`PDO::ATTR_EMULATE_PREPARES = false`), validación en el borde, mensaje de error uniforme.
- **Evidencia:** `AuthApiTest::login_resiste_inyeccion_sql`. En el sistema heredado este ataque daba acceso como administrador.

### E4 · Fuerza bruta de contraseñas
- **Estímulo:** 6 intentos fallidos seguidos sobre una cuenta. **Respuesta:** `429` con `Retry-After`; se desbloquea a los 15 minutos.
- **Táctica:** limitar intentos por correo y por IP (`PdoLoginThrottle`) + limitación de peticiones en nginx.
- **Evidencia:** `AuthApiTest::bloquea_tras_cinco_intentos_fallidos`.

### E5 · Escalamiento de privilegios
- **Estímulo:** un estudiante modifica el JWT para decir `rol: administrador`, o llama directamente `/admin/reportes`.
- **Respuesta:** `401` (firma inválida) o `403`. **Medida:** 0 accesos.
- **Táctica:** firma HS256 verificada en cada petición; RBAC en el grupo de rutas (`RequireRole`) y de nuevo en el caso de uso (`Actor::mustBe`).
- **Evidencia:** `TokenServiceTest::rechaza_token_alterado_para_escalar_privilegios`, `AuthApiTest::rutas_protegidas_exigen_autenticacion_y_rol`.

### E6 · Acceso a datos de otro estudiante (IDOR)
- **Estímulo:** un estudiante pide `/tutorias/{id}` de otra persona. **Respuesta:** `404` (no revela que existe).
- **Evidencia:** `BookingApiTest::un_estudiante_no_ve_ni_cancela_tutorias_ajenas`.

### E7 · Caída del servidor de correo
- **Fuente:** SMTP institucional. **Estímulo:** no responde durante 20 minutos.
- **Respuesta:** la reserva se confirma igual; el correo se reintenta (30 s, 60 s, 120 s, 240 s) y se envía al recuperarse. **Medida:** 0 notificaciones perdidas; la reserva nunca espera al correo.
- **Táctica:** Transactional Outbox + worker asíncrono + consumidor idempotente.
- **Evidencia:** `OutboxProcessor`, `BookingApiTest::la_reserva_genera_notificaciones_via_outbox` (también comprueba que reprocesar no duplica).

### E8 · Pico de inicio de semestre (rendimiento)
- **Estímulo:** 200 usuarios consultan cupos durante 10 minutos. **Medida:** p95 < 500 ms.
- **Tácticas:** índices para las consultas reales (`ix_franjas_busqueda`, `ix_tutorias_cupo`), consulta de cupos agregada en una sola pasada (`PdoOfferQueries::takenSeats`), caché del catálogo (5 min) y de los reportes (60 s), API sin estado → escalamiento horizontal (`docker-compose.prod.yml` con 2 réplicas).
- **Evidencia:** prueba de carga k6 programada en T36.

### E9 · Pérdida de la base de datos
- **Estímulo:** corrupción del volumen de MySQL. **Respuesta:** restauración desde el último respaldo. **Medida:** RPO ≤ 24 h, RTO ≤ 1 h.
- **Táctica:** respaldo lógico diario (`infra/backup/backup.sh`) con retención de 7 días; procedimiento de restauración documentado.
- **Evidencia:** [05 · Despliegue y operación](05-despliegue-y-operacion.md#respaldos-y-recuperación).

### E10 · Cambio de proveedor de correo (modificabilidad)
- **Fuente:** la universidad migra de SMTP a un servicio de correo en la nube.
- **Respuesta:** se escribe un adaptador nuevo que implementa `Mailer` y se cambia una línea en `config/container.php`. **Medida:** 0 cambios en dominio y casos de uso; < 1 día de trabajo.
- **Táctica:** puertos y adaptadores (arquitectura hexagonal), raíz de composición única.
- **Evidencia:** existen ya dos adaptadores intercambiables (`SmtpMailer`, `LogMailer`); las pruebas usan `LogMailer` sin tocar el código de producción.

### E11 · Regla de cancelación de 3 horas (corrección temporal)
- **Estímulo:** el estudiante cancela 2 h 30 min antes. **Respuesta:** `422 cancellation_window_closed`; a las 3 h 01 min sí puede.
- **Táctica:** reloj inyectado (`Clock`) → la regla se prueba con el tiempo congelado, sin esperas.
- **Evidencia:** `TutoringTest`, `BookingApiTest::regla_de_cancelacion_de_tres_horas`.

## Compromisos entre atributos (T07 · ATAM ligera)

| Decisión | Atributo que gana | Atributo que cede | Punto de sensibilidad / riesgo |
|---|---|---|---|
| Bloqueo pesimista por franja | Integridad | Rendimiento bajo alta contención | La contención es por franja, no global: dos franjas distintas no se bloquean entre sí. Riesgo bajo dado el cupo (≤ 30). |
| Monolito modular en vez de microservicios | Simplicidad operativa, consistencia transaccional | Escalamiento independiente por módulo | Punto de compromiso: si Reportes satura la BD, se extrae a una réplica de lectura (ADR-0001). |
| JWT en cookie HttpOnly | Resistencia a robo por XSS | Exige protección CSRF y mismo origen | Se resuelve con SameSite=Strict + double-submit token (ADR-0005). |
| Notificaciones asíncronas | Disponibilidad y latencia de la reserva | Consistencia inmediata del correo | El usuario ve la confirmación en pantalla de inmediato; el correo llega segundos después. |
| Caché de reportes de 60 s | Rendimiento | Frescura de los datos | Aceptable para coordinación; el panel muestra `generadoEn`. |
