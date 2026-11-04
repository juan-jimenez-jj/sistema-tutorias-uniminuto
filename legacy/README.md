# Sistema heredado (v1) — solo referencia

Código original del proyecto de Ingeniería de Software (PHP + XAMPP), conservado **sin cambios** para comparar el antes y el después. No se despliega ni se mantiene.

Contiene vulnerabilidades conocidas (inyección SQL en el login, contraseña sin verificar, sin CSRF). El análisis está en [docs/arquitectura/04-seguridad.md](../docs/arquitectura/04-seguridad.md#vulnerabilidades-del-sistema-heredado-y-su-corrección). El volcado SQL original está en `backend/database/legacy/` y lo usa el comando `legacy:import`.
