# ADR-0005 · Sesión con JWT en cookie HttpOnly + token CSRF

**Estado:** aceptada · **Tarea:** T14, T25

## Contexto
Opciones: sesión PHP en el servidor (estado en disco, complica el escalamiento horizontal), JWT en `localStorage` (vulnerable a robo por XSS) o JWT en cookie `HttpOnly` (requiere protección CSRF).

## Decisión
- JWT HS256 de 8 horas con `sub`, `rol`, `iss` y `exp`, firmado con `JWT_SECRET` (≥ 32 caracteres, validado al arrancar).
- Viaja en la cookie `tut_session` con `HttpOnly; SameSite=Strict; Secure` (en producción).
- CSRF con *double-submit*: cookie legible `tut_csrf` y header `X-CSRF-Token` obligatorio en métodos que modifican datos cuando la autenticación es por cookie (`CsrfMiddleware`).
- Clientes de API pueden usar `Authorization: Bearer` (no son vulnerables a CSRF).

## Consecuencias
- ✅ API sin estado → réplicas sin sesiones compartidas.
- ✅ Un XSS no puede leer el token de sesión.
- ✅ Revocación inmediata: `AuthenticationMiddleware` consulta el estado del usuario (búsqueda por clave primaria) en cada petición, así que desactivar una cuenta cierra sus sesiones abiertas al instante (`AdminApiTest`).
- ⚠️ El cambio de rol sí espera a que el usuario vuelva a iniciar sesión (el rol viaja en el token). Mejora futura: incluir una versión de credenciales en el token.
