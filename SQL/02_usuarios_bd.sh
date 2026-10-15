#!/usr/bin/env bash
# ============================================================
# 02_usuarios_bd.sh - DCL: crea usuarios de base de datos con minimo privilegio.
# Corre automaticamente en la inicializacion del contenedor MySQL (entrypoint),
# o a mano:  MYSQL_ROOT_PASSWORD=... APP_DB_PASS=... ./02_usuarios_bd.sh
# Las contrasenas vienen de variables de entorno: nunca se escriben en el repo.
# ============================================================
set -euo pipefail

BD="${MYSQL_DATABASE:-torneos_db}"
APP_USER="${APP_DB_USER:-genesys_app}"
BACKUP_USER="${BACKUP_DB_USER:-genesys_backup}"
LECTURA_USER="${LECTURA_DB_USER:-genesys_lectura}"
: "${APP_DB_PASS:?Falta APP_DB_PASS}"
: "${BACKUP_DB_PASS:?Falta BACKUP_DB_PASS}"
: "${LECTURA_DB_PASS:?Falta LECTURA_DB_PASS}"

CLIENTE=(mysql)
if [[ -n "${MYSQL_ROOT_PASSWORD:-}" ]]; then
    CLIENTE+=(-uroot "-p${MYSQL_ROOT_PASSWORD}")
fi
if [[ -n "${MYSQL_SOCKET:-}" ]]; then
    CLIENTE+=("--socket=${MYSQL_SOCKET}")
fi

# Tablas de negocio en las que la aplicacion puede leer y escribir
TABLAS_RW=(usuarios torneos equipos participantes inscripciones rondas enfrentamientos resultados)
# Tablas de referencia: la aplicacion solo lee
TABLAS_RO=(roles tipos_torneo)

{
    echo "CREATE USER IF NOT EXISTS '${APP_USER}'@'%'     IDENTIFIED BY '${APP_DB_PASS}';"
    echo "CREATE USER IF NOT EXISTS '${BACKUP_USER}'@'%'  IDENTIFIED BY '${BACKUP_DB_PASS}';"
    echo "CREATE USER IF NOT EXISTS '${LECTURA_USER}'@'%' IDENTIFIED BY '${LECTURA_DB_PASS}';"

    for t in "${TABLAS_RW[@]}"; do
        echo "GRANT SELECT, INSERT, UPDATE, DELETE ON \`${BD}\`.\`${t}\` TO '${APP_USER}'@'%';"
    done
    for t in "${TABLAS_RO[@]}"; do
        echo "GRANT SELECT ON \`${BD}\`.\`${t}\` TO '${APP_USER}'@'%';"
    done
    # La auditoria solo se agrega y se consulta: no se edita ni se borra
    echo "GRANT SELECT, INSERT ON \`${BD}\`.\`auditoria\` TO '${APP_USER}'@'%';"
    echo "GRANT SELECT ON \`${BD}\`.\`v_partidos\`   TO '${APP_USER}'@'%';"
    echo "GRANT SELECT ON \`${BD}\`.\`v_posiciones\` TO '${APP_USER}'@'%';"

    # Respaldo: lectura total y bloqueo para volcados consistentes
    echo "GRANT SELECT, LOCK TABLES, SHOW VIEW, TRIGGER ON \`${BD}\`.* TO '${BACKUP_USER}'@'%';"

    # Consulta publica / reportes: solo la vista de posiciones
    echo "GRANT SELECT ON \`${BD}\`.\`v_posiciones\` TO '${LECTURA_USER}'@'%';"
    echo "FLUSH PRIVILEGES;"
} | "${CLIENTE[@]}"

echo "Usuarios de BD creados: ${APP_USER}, ${BACKUP_USER}, ${LECTURA_USER}"
