# ADR-0004 · SPA en React + TypeScript que consume una API REST

**Estado:** aceptada · **Tarea:** T13, T32

## Contexto
v1 generaba HTML en el servidor y además tenía endpoints AJAX que devolvían fragmentos HTML. El plan exige "interfaces de los tres roles operando contra la API".

## Decisión
- Backend: **API REST JSON** versionada (`/api/v1`) y documentada en OpenAPI 3.
- Frontend: **React 18 + TypeScript + Vite**, React Query para la caché de servidor y React Router para las rutas por rol. Sin librería de componentes: un sistema visual propio ("cuaderno cuadriculado") en una hoja de estilos con tokens.
- nginx sirve la SPA y reenvía `/api` a la API **en el mismo origen** (sin CORS).

## Consecuencias
- ✅ El contrato OpenAPI permite que otros clientes (app móvil, integración con el SIA) reutilicen la API.
- ✅ TypeScript detecta en compilación las rupturas de contrato con la API.
- ⚠️ Dos tecnologías en el equipo; se mitiga con un único repositorio y un pipeline común.
