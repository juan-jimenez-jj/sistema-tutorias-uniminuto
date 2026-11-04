# 04 · Modelo de seguridad

## Matriz de permisos (RBAC)

El rol se valida **dos veces**: en el grupo de rutas (`RequireRole`) y dentro del caso de uso (`Actor::mustBe`). Las reglas de propiedad ("solo tus tutorías", "solo tus franjas") las aplica el dominio.

| Operación | Estudiante | Docente | Coordinación |
|---|:---:|:---:|:---:|
| Registrarse | ✔ (público) | — | — |
| Consultar materias y docentes | ✔ | ✔ | ✔ |
| Ver cupos y reservar | ✔ | — | — |
| Ver tutorías | Solo las suyas | Solo su agenda | Todas |
| Cancelar | Las suyas, ≥ 3 h antes | Las de su agenda, con motivo | Cualquiera |
| Registrar asistencia | — | Solo su agenda, después de iniciar | — |
| Calificar | Las suyas completadas, una vez | — | — |
| Publicar / editar franjas | — | Solo las suyas, de materias asignadas | — |
| Usuarios, materias, asignaciones | — | — | ✔ |
| Reportes, exportación CSV, auditoría | — | — | ✔ |

## Modelo de amenazas (STRIDE)

| Amenaza | Ejemplo en este sistema | Control | Evidencia |
|---|---|---|---|
| **S**uplantación | Iniciar sesión como otro usuario | bcrypt costo 12, límite de 5 intentos/15 min, mensaje de error uniforme, hash ficticio para igualar tiempos (evita enumerar correos) | `AuthenticateUser`, `AuthApiTest` |
| **T**ampering (manipulación) | Alterar el JWT para cambiar el rol; enviar `docenteId` o `materiaId` falsos al reservar | Firma HS256 verificada; la reserva solo recibe `franjaId` y deriva docente y materia del servidor | `TokenServiceTest`, `BookTutoring` |
| **R**epudio | "Yo no cancelé esa tutoría" | Bitácora de auditoría con usuario, IP, fecha y detalle | `PdoAuditLog`, `/admin/auditoria` |
| **I**nformation disclosure | Ver tutorías ajenas (IDOR); trazas de error con rutas o SQL | Respuesta 404 para recursos ajenos; errores RFC 7807 sin detalles internos en producción; `expose_php=Off` | `BookingApiTest`, `ErrorHandlerMiddleware` |
| **D**enial of service | Ráfagas de peticiones al login o a la oferta | `limit_req` en nginx (20 r/s por IP), límite de intentos de login, paginación máxima de 100 | `frontend/nginx.conf` |
| **E**levation of privilege | Estudiante llamando endpoints de coordinación; docente editando franjas de otro | RBAC en rutas y casos de uso; verificación de propiedad en el dominio | `AuthApiTest`, `AvailabilityApiTest` |

### OWASP Top 10 (2021)

| Riesgo | Control principal |
|---|---|
| A01 Control de acceso roto | RBAC doble, verificación de propiedad, 404 ante IDOR |
| A02 Fallas criptográficas | bcrypt, JWT con secreto ≥ 32 caracteres (validado al arrancar), cookies `HttpOnly`, `Secure` y `SameSite=Strict`, HSTS en producción |
| A03 Inyección | Consultas preparadas nativas (`EMULATE_PREPARES=false`); los únicos valores interpolados en SQL son enteros convertidos con `intval`; neutralización de fórmulas en el CSV exportado |
| A04 Diseño inseguro | Reglas de negocio en el dominio, cupo garantizado bajo concurrencia, el cliente nunca decide docente ni materia |
| A05 Configuración insegura | Cabeceras OWASP, CSP estricta en nginx, `display_errors=Off`, secretos solo por variables de entorno |
| A06 Componentes vulnerables | `composer audit` y `npm audit` en CI, Dependabot semanal |
| A07 Fallas de identificación | Política de contraseñas, bloqueo por intentos, expiración de sesión a las 8 h |
| A08 Integridad de software y datos | Pipeline con checks obligatorios, migraciones con checksum |
| A09 Registro y monitoreo | Logs JSON con `request_id`, auditoría, métrica `login_fallidos_15m` |
| A10 SSRF | El servidor no hace peticiones a URLs que provengan del usuario |

### CSRF

La sesión viaja en una cookie `HttpOnly`, inaccesible para JavaScript, lo que mitiga el robo de sesión por XSS. Como el navegador envía esa cookie automáticamente, se añade protección CSRF con dos capas: `SameSite=Strict` y el patrón *double-submit*. Al iniciar sesión se crea una segunda cookie legible (`tut_csrf`) cuyo valor el frontend copia en el header `X-CSRF-Token`. Un sitio externo no puede leer esa cookie y, por tanto, no puede construir el header (ver [ADR-0005](adr/0005-sesion-jwt-cookie-httponly-csrf.md)).

## Vulnerabilidades del sistema heredado y su corrección

| # | Hallazgo en el código heredado | Severidad | Corrección |
|---|---|---|---|
| V1 | `Usuario::login()` concatenaba el correo en el SQL (`WHERE email = '$this->email'`) | Crítica | Consultas preparadas nativas |
| V2 | `Usuario::login()` **nunca verificaba la contraseña**: bastaba un correo existente para entrar, incluso como administrador | Crítica | `password_verify` + límite de intentos + auditoría |
| V3 | `solicitar_tutoria.php` tomaba `docente_id`, `materia_id` y `fecha_tutoria` del formulario sin validarlos contra la franja (manipulación de parámetros) | Alta | La API solo recibe `franjaId` y `fecha`; el resto lo deriva el servidor y lo valida el dominio |
| V4 | Sin control de cupo ni de concurrencia: dos estudiantes podían ocupar la misma franja | Alta | Cupo por franja + bloqueo de fila + índice único |
| V5 | Ningún formulario tenía protección CSRF | Media | Cookie `SameSite=Strict` + token double-submit |
| V6 | Credenciales de BD fijas en el código (`root` sin contraseña) y mensaje de error de conexión mostrado al usuario | Media | Variables de entorno, usuario de BD con privilegios mínimos, errores genéricos |
| V7 | `htmlspecialchars` aplicado al guardar en la BD (datos corrompidos y doble codificación al mostrar) | Baja | Se guarda el dato original; React escapa al mostrar |
| V8 | Sin registro de quién hizo qué | Media | Tabla `auditoria` |

La reconciliación de la migración confirma el impacto real de V3 y V4: **998 de las 1000 tutorías del sistema heredado** tienen docente, materia o día inconsistentes con su franja (ver [06 · Modernización](06-modernizacion-y-trazabilidad.md#hallazgos-de-la-migración-de-datos)).

## Datos personales (Ley 1581 de 2012)

- **Consentimiento:** el registro exige la autorización explícita y guarda la fecha (`consentimiento_datos_en`).
- **Minimización:** se guardan solo los datos necesarios para la tutoría (ID institucional, nombre, correo y programa).
- **Acceso restringido:** un estudiante ve únicamente sus datos; el docente ve nombre y programa de quienes reservan con él.
- **Trazabilidad:** toda consulta administrativa y todo cambio queda en la auditoría.
