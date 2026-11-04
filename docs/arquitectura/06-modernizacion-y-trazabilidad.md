# 06 · Modernización y trazabilidad

## Antes y después

| Aspecto | Sistema heredado (v1) | Sistema modernizado (v2) |
|---|---|---|
| Estilo arquitectónico | Páginas PHP que mezclan HTML, SQL y lógica (archivos de 600–800 líneas) | Monolito modular hexagonal + API REST + SPA |
| Backend | PHP sin Composer, `require_once` manual, rutas fijas `/sistema_tutorias/` | PHP 8.3, Slim 4, Composer, PSR-4/PSR-7/PSR-12, inyección de dependencias |
| Frontend | Bootstrap por CDN, JavaScript embebido, endpoints AJAX que devuelven HTML | React 18 + TypeScript, React Query, diseño propio responsivo, fuentes autoalojadas |
| Reglas de negocio | Dispersas en las vistas (regla de 3 h dentro de `mis_tutorias.php`) | Concentradas en el agregado `Tutoring` y probadas en aislamiento |
| Concurrencia | Ninguna | Bloqueo de fila + índice único + bloqueo optimista |
| Seguridad | SQLi en el login, contraseña sin verificar, sin CSRF | Ver [04 · Seguridad](04-seguridad.md) |
| Notificaciones | Prometidas en el documento, no implementadas | Outbox transaccional + worker + correo HTML + notificaciones en la app |
| Reportes | Consultas sueltas en una página | Panel con evolución mensual, mapa de calor día × hora, desempeño por docente y exportación CSV |
| Pruebas | Ninguna | 70 pruebas de backend (43 unitarias y 27 de integración con MySQL real) + 4 de frontend |
| Despliegue | Copiar carpeta a `htdocs` de XAMPP, importar SQL a mano | `docker compose up`; migraciones versionadas; CI/CD |
| Operación | Sin logs ni monitoreo | Health checks, métricas Prometheus, logs JSON con `request_id`, respaldos diarios |

## Hallazgos de la migración de datos

El comando `php bin/console legacy:import --dry-run` (tarea T40) leyó el volcado del sistema heredado y validó cada registro contra las reglas del nuevo modelo:

```
Leídos del sistema heredado: 1011 usuarios, 41 franjas, 1000 tutorías.

REPORTE DE CONCILIACIÓN (simulación, no se escribió nada)
  Estudiantes nuevos migrados        1000
  Tutorías aceptadas                    2
  Tutorías rechazadas                 998
  Motivos de rechazo:
    La materia no coincide con la de la franja             957
    El docente no coincide con el de la franja             874
    La fecha no cae en el día de la franja                 866
```

**Interpretación.** El 99,8 % de las tutorías heredadas son internamente inconsistentes. Esto se explica por la vulnerabilidad V3 (el formulario de reserva confiaba en los datos del cliente) y porque los datos de prueba se generaron sin respetar las relaciones. Es la evidencia más contundente de por qué las reglas deben vivir en el servidor y validarse también en la base de datos. El comando genera además un CSV con cada rechazo y su motivo, para que coordinación decida qué registros corregir a mano.

**Segundo hallazgo.** El docente con más horarios tenía **franjas superpuestas**: tres materias distintas a la misma hora y el mismo día. El nuevo dominio lo prohíbe (`slot_overlap`), así que el cargador conserva una franja publicada por grupo, rotando la materia según el día, y deja las demás pausadas (8 de 41). El historial no se pierde.

**Las contraseñas se migran sin conocerlas:** los hashes bcrypt heredados se copian tal cual y se re-hashean con el costo nuevo la primera vez que cada usuario inicia sesión (`AuthenticateUser` → `needsRehash`).

## Trazabilidad plan → evidencia

| Tarea | Entregable del plan | Evidencia en este repositorio |
|---|---|---|
| T01–T02 | Drivers y RNF medibles | [01 · Atributos de calidad](01-atributos-de-calidad.md) |
| T04–T05 | Escenarios SEI y tácticas | 11 escenarios, cada uno con táctica, código y prueba |
| T07 | ATAM ligera | Tabla de compromisos en el documento 01 |
| T08, T10, T11 | C4 niveles 1–3 | [02 · Vistas C4](02-vistas-c4.md) |
| T09 | ADR de stack y patrón | [adr/](adr/) (10 decisiones) |
| T12 | ERD y script SQL | [03 · Modelo de datos](03-modelo-de-datos.md), `backend/database/migrations/` |
| T13 | Contrato OpenAPI 3 | `docs/api/openapi.yaml` (31 operaciones, verificado contra las rutas reales) |
| T14 | Modelo de amenazas y matriz de permisos | [04 · Seguridad](04-seguridad.md) |
| T16 | Repositorio, ramas y plantilla de PR | `.github/pull_request_template.md`, `.github/dependabot.yml` |
| T17 | Entornos como código | `docker-compose.yml`, `docker-compose.prod.yml`, Dockerfiles |
| T18 | Pipeline CI/CD | `.github/workflows/ci.yml` |
| T19 | DevSecOps | Gitleaks, Semgrep, `composer audit`, `npm audit` en CI |
| T20 | Walking skeleton | Flujo login → reserva → MySQL cubierto por `BookingApiTest` |
| T21 | Respaldos y RPO/RTO | Servicio `backup`, procedimiento en el documento 05 |
| T22 | Observabilidad | `/health`, `/health/ready`, `/metrics`, logs JSON |
| T25 | Autenticación y roles, cobertura ≥ 80 % | Módulo `Identity`, `AuthApiTest`, cobertura en el job de CI |
| T26 | Usuarios y materias | Módulos `Identity` y `Catalog`, `AdminApiTest` |
| T27 | Disponibilidad del docente | Módulo `Scheduling`, `AvailabilityApiTest` |
| T28 | Reserva con control de colisiones | `BookTutoring`, `ConcurrentBookingTest` |
| T29 | Cancelación (3 h), historial y calificación | Agregado `Tutoring`, `TutoringTest` |
| T30 | Notificaciones con cola de eventos | Outbox + `OutboxProcessor` + `TutoringNotifier` |
| T31 | Reportes y estadísticas | Módulo `Reporting`, pantalla de reportes, CSV |
| T32 | Interfaces por rol contra la API | `frontend/` |
| T33 | Mitigación de vulnerabilidades + ZAP | Tabla V1–V8, `.github/workflows/dast.yml` |
| T34 | Deuda técnica | PHPStan nivel 6 y PSR-12 sin errores |
| T35 | Pruebas de integración en el pipeline | Suite `integration` en CI con MySQL 8.4 |
| T40 | Migración con validación de integridad | `legacy:import` y su reporte de conciliación |
| T42 | Documentación | Esta carpeta y el README |

### Pendiente para fases posteriores del plan

- **T36 · Pruebas de carga con k6:** definir el script contra Staging con el escenario E8 (p95 < 500 ms con 200 usuarios).
- **T38 · Prueba de estrés y failover:** ejecutar el simulacro de restauración del documento 05 y medir el RTO real.
- **T39, T43 · Usuarios piloto y capacitación:** requieren a la comunidad académica.
- **T41 · Despliegue a producción:** depende de que la universidad asigne un servidor y el SMTP institucional.
