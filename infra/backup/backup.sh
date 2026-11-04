#!/bin/sh
# Respaldo lógico de MySQL (táctica de disponibilidad / tarea T21 del plan).
# RPO objetivo: 24 h (un respaldo diario). Retención: BACKUP_KEEP_DAYS días.
# Restaurar:  gunzip -c /backups/tutorias_AAAAMMDD_HHMM.sql.gz | mysql -h db -u root -p tutorias
set -eu
KEEP="${BACKUP_KEEP_DAYS:-7}"
INTERVAL="${BACKUP_INTERVAL_SECONDS:-86400}"
mkdir -p /backups
while true; do
  FILE="/backups/tutorias_$(date +%Y%m%d_%H%M).sql.gz"
  echo "[backup] Generando ${FILE}"
  if mysqldump -h db -u root -p"${MYSQL_ROOT_PASSWORD}" --single-transaction --routines --triggers --set-gtid-purged=OFF "${MYSQL_DATABASE}" | gzip > "${FILE}.tmp"; then
    mv "${FILE}.tmp" "${FILE}"
    echo "[backup] OK ($(du -h "${FILE}" | cut -f1))"
  else
    rm -f "${FILE}.tmp"
    echo "[backup] ERROR: el respaldo falló" >&2
  fi
  find /backups -name 'tutorias_*.sql.gz' -mtime +"${KEEP}" -delete
  sleep "${INTERVAL}"
done
