# 02 · Vistas C4

Los diagramas están en [Mermaid](https://mermaid.js.org/): GitHub y VS Code (extensión *Markdown Mermaid*) los dibujan directamente. Para Lucidchart (entregable T08–T11) basta con copiar la estructura.

## Nivel 1 · Contexto

```mermaid
flowchart TB
    est([Estudiante<br/><small>reserva, cancela y califica tutorías</small>])
    doc([Docente<br/><small>publica horarios y registra asistencia</small>])
    coord([Coordinación académica<br/><small>gestiona usuarios, materias y reportes</small>])

    sis[["Sistema de Gestión de Tutorías<br/><small>Ciencias Básicas · UNIMINUTO Zipaquirá</small>"]]

    smtp[(Correo institucional<br/><small>SMTP / Microsoft 365</small>)]
    teams[(Microsoft Teams<br/><small>enlace de tutorías virtuales</small>)]

    est -->|HTTPS| sis
    doc -->|HTTPS| sis
    coord -->|HTTPS| sis
    sis -->|envía confirmaciones y avisos| smtp
    sis -.->|registra el enlace de la sesión| teams

    classDef sistema fill:#12305c,color:#fff,stroke:#12305c
    classDef externo fill:#eef1f6,stroke:#7a8396,color:#232937
    class sis sistema
    class smtp,teams externo
```

## Nivel 2 · Contenedores

```mermaid
flowchart LR
    user([Navegador<br/>estudiante · docente · coordinación])

    subgraph docker["Docker Compose · red interna 'backend'"]
        web["<b>web</b><br/>nginx 1.27<br/><small>SPA React + proxy inverso<br/>CSP, límite de peticiones</small>"]
        api["<b>api</b><br/>Apache + PHP 8.3 · Slim 4<br/><small>API REST /api/v1<br/>monolito modular hexagonal</small>"]
        worker["<b>worker</b><br/>PHP 8.3 CLI<br/><small>procesa la outbox<br/>reintentos con backoff</small>"]
        db[("<b>db</b><br/>MySQL 8.4<br/><small>datos, outbox, auditoría</small>")]
        backup["<b>backup</b><br/><small>mysqldump diario<br/>retención 7 días</small>"]
        mailpit["<b>mailpit</b><br/><small>SMTP de pruebas<br/>(solo desarrollo)</small>"]
    end
    smtp[(SMTP institucional)]

    user -->|HTTPS :8080| web
    web -->|/api/* HTTP| api
    api -->|PDO · transacciones| db
    worker -->|SELECT … FOR UPDATE SKIP LOCKED| db
    worker -->|SMTP| mailpit
    worker -.->|producción| smtp
    backup -->|mysqldump| db

    classDef app fill:#12305c,color:#fff,stroke:#12305c
    classDef store fill:#fdf1c7,stroke:#9a6a00,color:#232937
    class web,api,worker app
    class db store
```

| Contenedor | Responsabilidad | Escala |
|---|---|---|
| `web` | Sirve la SPA compilada y reenvía `/api` a la API en el mismo origen (cookies `SameSite=Strict`) | Horizontal |
| `api` | Casos de uso síncronos; sin estado de sesión en memoria (JWT) | Horizontal (2 réplicas en producción) |
| `worker` | Efectos secundarios asíncronos (correo, notificaciones internas) | Horizontal: `SKIP LOCKED` evita procesar dos veces |
| `db` | Fuente única de verdad, consistencia transaccional | Vertical; réplica de lectura para Reportes si se requiere |
| `migrate` | Contenedor de una sola ejecución: aplica migraciones antes de iniciar la API | — |

## Nivel 3 · Componentes del backend

Cada módulo sigue la misma estructura hexagonal: **Domain** (entidades y reglas, sin dependencias), **Application** (casos de uso y puertos), **Infrastructure** (adaptadores PDO, SMTP…) y **Http** (controladores y rutas).

```mermaid
flowchart TB
    subgraph http["Entrada HTTP · Shared/Infrastructure/Http"]
        mw["Middleware<br/><small>RequestId → SecurityHeaders → ErrorHandler (RFC 7807)<br/>→ Auth (JWT) → CSRF → RequireRole</small>"]
    end

    subgraph identity["Identidad"]
        idc["AuthController<br/>UserAdminController"] --> iduc["RegisterStudent · AuthenticateUser<br/>ChangeOwnPassword · ManageUsers"]
        iduc --> iddom["User · InstitutionalEmail<br/>PasswordPolicy"]
    end
    subgraph catalog["Catálogo"]
        cc["CatalogController"] --> cuc["ManageCatalog"] --> cdom["Subject"]
    end
    subgraph scheduling["Disponibilidad"]
        sc["AvailabilityController"] --> suc["ManageAvailability"] --> sdom["AvailabilitySlot<br/>TimeRange · Weekday"]
    end
    subgraph booking["Reservas · núcleo"]
        bc["BookingController"] --> buc["BookTutoring · CancelTutoring<br/>CloseTutoring · RateTutoring · FindOffers"]
        buc --> bdom["<b>Tutoring</b> (agregado)<br/>BookingPolicy · eventos"]
    end
    subgraph notif["Notificaciones"]
        nh["TutoringEventsHandler"] --> nuc["TutoringNotifier"]
    end
    subgraph reporting["Reportes"]
        rc["ReportController"] --> ruc["GenerateReports"]
    end
    subgraph shared["Kernel compartido"]
        ports["Puertos: TransactionManager · EventPublisher<br/>AuditLog · Cache · Clock"]
        outbox["OutboxEventPublisher → outbox_eventos<br/>OutboxProcessor (worker)"]
    end
    db[(MySQL)]

    mw --> idc & cc & sc & bc & rc
    buc -->|publica eventos en la misma transacción| ports
    ports --> outbox --> db
    outbox -->|worker| nh
    suc -.->|puerto BookingsOfSlot<br/>lectura| db
    nuc -->|Mailer · NotificationStore| db
```

### Reglas de dependencia

1. **El dominio no depende de nada** externo: ni de PDO, ni de HTTP, ni de Slim. Por eso `TutoringTest` corre en milisegundos sin base de datos.
2. **Los módulos no importan clases de otros módulos.** Cuando Disponibilidad necesita saber si una franja tiene reservas, declara su propio puerto (`BookingsOfSlot`) y lo implementa con una consulta de lectura. Cuando necesita saber si el docente dicta la materia, usa `TeachingAssignments`. Así, extraer un módulo como servicio independiente solo exige cambiar el adaptador (ADR-0001).
3. **Solo `config/container.php` conoce las implementaciones concretas** (raíz de composición, ADR-0002).
4. **Lectura y escritura separadas (CQRS ligero):** los listados y reportes usan consultas optimizadas (`*Queries`) sin pasar por los agregados; las escrituras siempre pasan por el agregado que protege las reglas.

### Estructura de carpetas

```
backend/src/
├── Shared/          Kernel: errores de dominio, puertos, middleware, outbox, logging, seguridad
├── Identity/        Domain · Application · Infrastructure · Http
├── Catalog/         …
├── Scheduling/      …
├── Booking/         …  (núcleo: agregado Tutoring, control de concurrencia)
├── Notifications/   …
├── Reporting/       …
├── Platform/        Health checks, métricas, consola (seed, migración heredada)
└── Kernel.php       Arranque HTTP y registro de rutas por módulo
```
