# ADR-0008 · MySQL 8 con migraciones SQL versionadas

**Estado:** aceptada · **Tarea:** T12

## Contexto
El plan fija MySQL. En v1 el esquema se distribuía como un volcado de phpMyAdmin con datos incluidos, sin forma de evolucionarlo.

## Decisión
- MySQL 8.4 (compatible con MariaDB 10.4+ de XAMPP; con MariaDB < 10.6 el worker usa `FOR UPDATE` sin `SKIP LOCKED`), InnoDB, `utf8mb4_unicode_ci`.
- Migraciones SQL puras e inmutables (`VNNN__descripcion.sql`) aplicadas por un migrador propio que registra un **checksum** y falla si una migración aplicada fue modificada.
- Datos de demostración separados del esquema (`db:seed`), con protección contra su ejecución accidental en producción.

## Consecuencias
- ✅ Los tres entornos tienen el mismo esquema, verificable.
- ✅ SQL legible para el Arquitecto de Datos, sin un DSL intermedio.
- ⚠️ No hay rollback automático: las migraciones se diseñan *expand/contract* (ver documento 05).
