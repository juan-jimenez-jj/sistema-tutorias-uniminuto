# ADR-0009 · Docker Compose como infraestructura como código

**Estado:** aceptada · **Tarea:** T17

## Contexto
v1 se instalaba copiando la carpeta a XAMPP e importando el SQL a mano. El plan pide Dev, Staging y Prod reproducibles. La universidad no cuenta con Kubernetes.

## Decisión
- `docker-compose.yml` describe la topología completa (web, api, worker, db, migrate, backup, mailpit).
- `docker-compose.prod.yml` sobrescribe lo necesario para Staging/Producción (réplicas, cookies Secure, SMTP real, sin datos demo).
- Imágenes multi-etapa: `composer` → `php:8.3-apache` y `node` → `nginx`.
- Ansible queda como paso opcional para aprovisionar el host (instalar Docker, firewall, certificados).

## Consecuencias
- ✅ `docker compose up` deja el sistema funcionando con datos de demostración en minutos.
- ✅ Los mismos artefactos pasan por CI, Staging y Producción.
- ⚠️ Compose no ofrece auto-escalado ni alta disponibilidad multi-nodo. Si la carga lo exige, los manifiestos se traducen a Kubernetes sin cambiar las imágenes.
