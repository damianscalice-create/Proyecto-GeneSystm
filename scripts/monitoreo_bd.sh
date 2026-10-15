#!/usr/bin/env bash
# ============================================================
# monitoreo_bd.sh - consultas de estado y monitoreo de la base de GeneSysTM.
#
# Muestra: version, conexiones, tamano y cantidad de filas por tabla,
# cuentas bloqueadas y los ultimos cambios de la auditoria.
# Codigo de salida:  0 = todo normal,  2 = se supero algun umbral.
#
# Umbrales (variables opcionales):
#   GENESYS_UMBRAL_CONEXIONES   (def: 100)
#   GENESYS_UMBRAL_MB           tamano total de la base en MB (def: 2048)
#
# Cron cada 15 minutos:
#   */15 * * * * /opt/genesys/scripts/monitoreo_bd.sh >> /var/log/genesys_monitoreo.log 2>&1
# ============================================================
set -euo pipefail

source "$(dirname "$(readlink -f "$0")")/lib_comun.sh"

umbral_conexiones="${GENESYS_UMBRAL_CONEXIONES:-100}"
umbral_mb="${GENESYS_UMBRAL_MB:-2048}"
alerta=0

seccion() { printf '\n== %s ==\n' "$1"; }

# Devuelve un unico valor (sin formato de tabla) para comparar umbrales
valor() {
    if [[ "$GENESYS_MODO" == "docker" ]]; then
        (cd "$GENESYS_DIR" && docker compose exec -T db \
            sh -c 'mysql -N -B -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"') <<< "$1"
    else
        _args_mysql_local
        mysql "${ARGS_MYSQL[@]}" -N -B "$GENESYS_BD" <<< "$1"
    fi
}

echo "Monitoreo de ${GENESYS_BD} - $(date '+%F %T') (modo ${GENESYS_MODO})"

seccion "Servicio"
ejecutar_sql <<< "SELECT VERSION() AS version, NOW() AS ahora, DATABASE() AS base;"

seccion "Conexiones"
ejecutar_sql <<< "SHOW STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Uptime');"
conexiones="$(valor "SHOW GLOBAL STATUS LIKE 'Threads_connected';" | awk '{print $2}')"
conexiones="${conexiones:-0}"
if [[ "$conexiones" =~ ^[0-9]+$ ]] && (( conexiones > umbral_conexiones )); then
    registrar monitoreo "ALERTA: ${conexiones} conexiones (umbral ${umbral_conexiones})"
    alerta=2
fi

seccion "Tamano por tabla (MB)"
ejecutar_sql <<< "SELECT table_name AS tabla,
       ROUND((data_length + index_length) / 1024 / 1024, 2) AS mb,
       table_rows AS filas_aprox
FROM information_schema.tables
WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
ORDER BY (data_length + index_length) DESC;"
total_mb="$(valor "SELECT COALESCE(ROUND(SUM(data_length + index_length) / 1024 / 1024, 2), 0)
FROM information_schema.tables WHERE table_schema = DATABASE();")"
total_mb="${total_mb:-0}"
echo "Total: ${total_mb} MB (umbral ${umbral_mb} MB)"
if awk -v a="$total_mb" -v b="$umbral_mb" 'BEGIN { exit !(a > b) }'; then
    registrar monitoreo "ALERTA: la base ocupa ${total_mb} MB (umbral ${umbral_mb})"
    alerta=2
fi

seccion "Registros por tabla (conteo exacto)"
ejecutar_sql <<< "SELECT 'usuarios' AS tabla, COUNT(*) AS filas FROM usuarios
UNION ALL SELECT 'participantes', COUNT(*) FROM participantes
UNION ALL SELECT 'equipos', COUNT(*) FROM equipos
UNION ALL SELECT 'torneos', COUNT(*) FROM torneos
UNION ALL SELECT 'enfrentamientos', COUNT(*) FROM enfrentamientos
UNION ALL SELECT 'resultados', COUNT(*) FROM resultados
UNION ALL SELECT 'auditoria', COUNT(*) FROM auditoria;"

seccion "Cuentas bloqueadas ahora"
ejecutar_sql <<< "SELECT nombre_usuario, intentos_fallidos, bloqueado_hasta
FROM usuarios WHERE bloqueado_hasta IS NOT NULL AND bloqueado_hasta > NOW();"

seccion "Ultimos 10 cambios (auditoria)"
ejecutar_sql <<< "SELECT id, fecha, usuario_id, accion, entidad, entidad_id
FROM auditoria ORDER BY id DESC LIMIT 10;"

echo
if (( alerta != 0 )); then
    echo "RESULTADO: HAY ALERTAS (ver registros)"
else
    echo "RESULTADO: normal"
fi
exit "$alerta"
