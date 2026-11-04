# ADR-0002 · Arquitectura hexagonal por módulo

**Estado:** aceptada · **Tarea:** T09, T11

## Contexto
En v1 las reglas de negocio (p. ej. "cancelar con 3 horas de anticipación") vivían dentro de vistas con HTML y SQL. Eso impedía probarlas y reutilizarlas.

## Decisión
Cada módulo se divide en cuatro capas con dependencias hacia adentro:
`Http → Application → Domain ← Infrastructure`.
- **Domain:** entidades, value objects y reglas, sin dependencias externas.
- **Application:** casos de uso y **puertos** (interfaces) como `TutoringRepository`, `Mailer`, `Clock`.
- **Infrastructure:** **adaptadores** (PDO, PHPMailer, caché en disco).
- La única pieza que une puertos con adaptadores es `config/container.php` (raíz de composición). El contenedor resuelve automáticamente las clases concretas (autowiring) y las interfaces se declaran explícitamente.

## Consecuencias
- ✅ El dominio se prueba en milisegundos sin base de datos (`TutoringTest`).
- ✅ El reloj inyectado permite probar reglas temporales de forma determinista.
- ✅ Cambiar el proveedor de correo, la caché o el motor de BD no toca las reglas (escenario E10).
- ⚠️ Más archivos que un CRUD directo. Se acepta por la mantenibilidad que se gana.
