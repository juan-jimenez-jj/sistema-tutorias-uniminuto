# ADR-0003 · PHP 8.3 + Slim 4 en lugar de Laravel

**Estado:** aceptada · **Tarea:** T09

## Contexto
El equipo conoce PHP y el plan fija "PHP orientado a objetos". Las opciones evaluadas fueron Laravel 11 (framework completo), Slim 4 (micro-framework PSR) y Node.js con NestJS.

| Criterio | Laravel | **Slim 4** | NestJS |
|---|---|---|---|
| Continuidad con el conocimiento del equipo | Alta | **Alta** | Baja |
| Visibilidad de la arquitectura propia | Media (convenciones del framework) | **Alta** | Media |
| Estándares PSR (7, 11, 15) | Parcial | **Total** | N/A |
| Dependencias en producción | ~70 paquetes | **14** | ~300 |
| Coherencia con el plan (PHP/Apache/MySQL) | Sí | **Sí** | No |

## Decisión
**PHP 8.3 + Slim 4** (enrutamiento y PSR-7), con Monolog (logs), firebase/php-jwt (tokens) y PHPMailer (SMTP). PDO sin ORM: las consultas críticas (bloqueos, agregados de reportes) se escriben y optimizan a mano.

## Consecuencias
- ✅ La arquitectura es explícita y defendible en una sustentación: nada ocurre "por magia".
- ✅ Superficie de dependencias pequeña → menos vulnerabilidades heredadas.
- ⚠️ Sin ORM, el mapeo de filas a entidades se escribe a mano (repositorios `Pdo*`).
- ⚠️ Se escriben a mano utilidades que Laravel trae (validación, migrador); están en `Shared/Infrastructure` y probadas.
