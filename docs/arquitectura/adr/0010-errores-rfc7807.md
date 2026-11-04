# ADR-0010 · Errores con RFC 7807 y códigos estables

**Estado:** aceptada · **Tarea:** T13

## Contexto
El frontend necesita distinguir "sin cupo" de "fuera de la ventana de cancelación" para mostrar mensajes útiles, sin depender del texto del error.

## Decisión
- El dominio lanza excepciones semánticas (`ValidationError`, `BusinessRuleViolation`, `Conflict`, `Forbidden`, `NotFound`…) con un **código estable** (`slot_full`, `cancellation_window_closed`, `slot_overlap`…).
- `ErrorHandlerMiddleware` las traduce a HTTP (422, 409, 403, 404…) con cuerpo `application/problem+json`. Los errores de validación incluyen `errors` por campo.
- Los errores inesperados se registran con traza y `request_id`; al cliente solo le llega el `request_id`.

## Consecuencias
- ✅ El dominio no conoce HTTP.
- ✅ Los mensajes en español son aptos para mostrar al usuario final.
- ✅ Los códigos forman parte del contrato OpenAPI.
