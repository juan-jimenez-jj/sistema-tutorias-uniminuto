# ADR-0006 · Bloqueo pesimista por franja para las reservas

**Estado:** aceptada · **Tarea:** T28 · **Riesgo:** R2

## Contexto
En el inicio de semestre varios estudiantes compiten por los mismos cupos. Leer el cupo y luego insertar en pasos separados genera una condición de carrera: todos leen "queda 1 cupo" y todos insertan.

Opciones evaluadas:
1. **Restricción única** (docente, fecha, hora): sirve con cupo 1, no con cupos > 1.
2. **Bloqueo optimista** con reintentos: muchos conflictos en momentos de alta demanda.
3. **Bloqueo pesimista** de la fila de la franja (`SELECT … FOR UPDATE`) y validación de cupo en la misma transacción.

## Decisión
Opción 3, complementada con un **índice único** sobre `(franja_id, fecha, estudiante_id, reserva_activa)` como red de seguridad contra el doble envío del mismo estudiante. Para las transiciones posteriores (cancelar, completar, calificar) se usa **bloqueo optimista** con la columna `version`.

## Consecuencias
- ✅ Garantía demostrada: `ConcurrentBookingTest` lanza 12 procesos PHP reales contra MySQL y solo uno obtiene el último cupo. Sin el `FOR UPDATE` la misma prueba produce 12 reservas.
- ✅ El bloqueo es por franja: reservas en franjas distintas no se esperan entre sí.
- ⚠️ Bajo contención extrema las peticiones sobre una misma franja se serializan (milisegundos cada una). `innodb_lock_wait_timeout = 10` evita esperas indefinidas.
