# ADR-0001 · Monolito modular en lugar de microservicios

**Estado:** aceptada · **Fecha:** marzo de 2027 · **Tarea:** T09

## Contexto
El sistema atiende a una sede (cientos de estudiantes, decenas de docentes) y lo construye un equipo de cuatro personas con carga académica. La reserva necesita consistencia fuerte: cupo, cruces de horario y evento de notificación en una sola transacción. El sistema heredado era un conjunto de páginas sin límites internos.

## Decisión
Construir **un solo despliegue** organizado en **módulos con límites explícitos** (Identidad, Catálogo, Disponibilidad, Reservas, Notificaciones, Reportes). Ningún módulo importa clases de otro; cuando necesita información ajena declara un puerto propio (p. ej. `Scheduling\Application\BookingsOfSlot`).

## Consecuencias
- ✅ Transacciones ACID locales; un solo pipeline, una sola imagen, operación sencilla.
- ✅ La API no tiene estado → escala horizontalmente (varias réplicas detrás de nginx).
- ✅ Un módulo puede extraerse como servicio cambiando solo su adaptador. El candidato natural es Reportes (réplica de lectura) o Notificaciones (ya es asíncrono).
- ⚠️ Todos los módulos comparten base de datos. Se mitiga con la regla de no escribir en tablas de otro módulo (las consultas cruzadas son de solo lectura).
- ❌ Se descartan los microservicios: complejidad distribuida (consistencia eventual en la reserva, trazas distribuidas, varios pipelines) sin una carga que lo justifique.
