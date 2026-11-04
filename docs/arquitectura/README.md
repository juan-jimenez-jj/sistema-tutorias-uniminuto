# Documentación de arquitectura

Sistema de Gestión de Tutorías de Ciencias Básicas — UNIMINUTO, sede Zipaquirá. Versión 2.0.

Esta carpeta es la evidencia técnica del **Taller de Arquitectura de Software (plan de 6 meses)**: cada decisión del plan tiene aquí su documento, y cada documento apunta al código y a la prueba automatizada que la demuestra.

| Documento | Contenido | Tareas del plan |
|---|---|---|
| [01 · Atributos de calidad](01-atributos-de-calidad.md) | Drivers, requisitos no funcionales medibles (ISO/IEC 25010), 10 escenarios SEI y sus tácticas | T01, T02, T04, T05, T07 |
| [02 · Vistas C4](02-vistas-c4.md) | Contexto, contenedores y componentes del backend | T08, T10, T11 |
| [03 · Modelo de datos](03-modelo-de-datos.md) | Diagrama entidad-relación, índices, integridad y migraciones | T12, T40 |
| [04 · Seguridad](04-seguridad.md) | Matriz RBAC, modelo de amenazas STRIDE, vulnerabilidades heredadas corregidas | T14, T19, T33 |
| [05 · Despliegue y operación](05-despliegue-y-operacion.md) | Entornos, Docker Compose, CI/CD, respaldos (RPO/RTO), observabilidad | T16–T23, T38, T41 |
| [06 · Modernización y trazabilidad](06-modernizacion-y-trazabilidad.md) | Antes y después del sistema heredado, hallazgos de la migración y mapa tarea → evidencia | T34, T40, T42 |
| [ADR](adr/) | Registros de decisiones arquitectónicas | T09 |
| [Contrato OpenAPI](../api/openapi.yaml) | 31 operaciones REST documentadas | T13 |

## Resumen en un párrafo

Es un **monolito modular** en PHP 8.3 con **arquitectura hexagonal por módulo** (Identidad, Catálogo, Disponibilidad, Reservas, Notificaciones, Reportes), que expone una **API REST** consumida por una SPA en **React + TypeScript**. Los datos viven en **MySQL 8**, las notificaciones salen de forma asíncrona mediante el patrón **Transactional Outbox**, y todo se levanta con **Docker Compose** y se verifica en **GitHub Actions** (estilo, análisis estático, 70 pruebas de backend con MySQL real —incluida una prueba de reservas concurrentes con procesos paralelos—, SAST y detección de secretos).
