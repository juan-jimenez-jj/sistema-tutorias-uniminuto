# ADR-0007 · Patrón Transactional Outbox para notificaciones

**Estado:** aceptada · **Tarea:** T30 · **Riesgo:** R9

## Contexto
Al reservar o cancelar hay que avisar por correo al estudiante y al docente. Enviar el correo dentro de la petición la hace lenta y frágil: si el SMTP cae, ¿se revierte la reserva? Y si se envía después del `COMMIT` y el proceso muere, el aviso se pierde.

## Decisión
- El agregado `Tutoring` registra **eventos de dominio** (`tutoria.reservada`, `tutoria.cancelada`, `tutoria.completada`, `tutoria.inasistencia`).
- El caso de uso los guarda en la tabla `outbox_eventos` **en la misma transacción** que la tutoría.
- Un **worker** (`bin/console outbox:work`) los toma con `FOR UPDATE SKIP LOCKED`, genera la notificación interna y envía el correo. Si falla, reintenta con backoff exponencial (30 s, 60 s, 120 s, 240 s) y tras 5 intentos lo marca `fallido` (visible en `/health/ready` y `/metrics`).
- El consumidor es **idempotente**: índice único `(origen_evento_id, usuario_id)` en `notificaciones`.

## Consecuencias
- ✅ La reserva responde rápido y no depende del correo.
- ✅ Ningún evento se pierde (entrega *at-least-once*). Varios workers pueden correr en paralelo.
- ⚠️ El correo puede llegar duplicado en un reintento parcial (*at-least-once*); la notificación interna no se duplica.
- 🔁 Si en el futuro se requiere un broker (RabbitMQ), la outbox sigue siendo la fuente y el worker publica al broker.
