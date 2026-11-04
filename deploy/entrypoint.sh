#!/bin/sh
# Arranque del contenedor en la nube.
set -e
cd /var/www/app

# Apache toma el puerto de esta variable (ver ports.conf y el VirtualHost).
export PORT="${PORT:-8080}"

echo "[tutorias] Esperando la base de datos..."
i=0
until php bin/console migrate; do
  i=$((i + 1))
  if [ "$i" -ge 30 ]; then
    echo "[tutorias] No fue posible conectar con MySQL. Revisa la variable MYSQL_URL." >&2
    exit 1
  fi
  sleep 3
done

# Datos de demostración solo si la base está vacía (no borra nada existente).
if [ "${CARGAR_DEMO:-1}" = "1" ]; then
  php bin/console db:seed --force
fi

# Worker de notificaciones en segundo plano; si se cae, se reinicia.
( while true; do php bin/console outbox:work || true; sleep 5; done ) &

echo "[tutorias] Sistema listo en el puerto ${PORT}"
exec apache2-foreground
