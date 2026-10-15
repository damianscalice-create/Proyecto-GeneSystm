#!/usr/bin/env bash
# ============================================================
# lib_comun.sh - funciones compartidas por los scripts de GeneSysTM.
# No se ejecuta directamente: los demas scripts la cargan con  source.
#
# Variables de entorno reconocidas (todas opcionales):
#   GENESYS_DIR          carpeta del proyecto con docker-compose.yml  (def: /opt/genesys)
#   GENESYS_MODO         docker | local                                (def: docker)
#   GENESYS_BD           nombre de la base                             (def: torneos_db)
#   GENESYS_MYSQL_CNF    archivo de credenciales de MySQL (modo local, permisos 600)
#   GENESYS_RESPALDOS    carpeta de respaldos                          (def: /var/backups/genesys)
#   GENESYS_RETENCION_DIAS  dias que se conservan los respaldos        (def: 14)
# ============================================================

GENESYS_DIR="${GENESYS_DIR:-/opt/genesys}"
GENESYS_MODO="${GENESYS_MODO:-docker}"
GENESYS_BD="${GENESYS_BD:-torneos_db}"
GENESYS_RESPALDOS="${GENESYS_RESPALDOS:-/var/backups/genesys}"
GENESYS_RETENCION_DIAS="${GENESYS_RETENCION_DIAS:-14}"

# Registra en pantalla y en el registro del sistema (journal/syslog)
registrar() {
    local etiqueta="$1"; shift
    printf '%s [%s] %s\n' "$(date '+%F %T')" "$etiqueta" "$*"
    if command -v logger >/dev/null 2>&1; then
        logger -t "$etiqueta" "$*" || true
    fi
}

# Argumentos del cliente MySQL para el modo local
_args_mysql_local() {
    ARGS_MYSQL=()
    if [[ -n "${GENESYS_MYSQL_CNF:-}" ]]; then
        # --defaults-extra-file debe ir siempre primero
        ARGS_MYSQL+=("--defaults-extra-file=${GENESYS_MYSQL_CNF}")
    fi
}

# Ejecuta SQL leido desde la entrada estandar, en formato de tabla
ejecutar_sql() {
    if [[ "$GENESYS_MODO" == "docker" ]]; then
        (cd "$GENESYS_DIR" && docker compose exec -T db \
            sh -c 'mysql -t -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"')
    else
        _args_mysql_local
        mysql "${ARGS_MYSQL[@]}" -t "$GENESYS_BD"
    fi
}

# Vuelca la base completa (rutinas y triggers incluidos) a la salida estandar
volcar_bd() {
    if [[ "$GENESYS_MODO" == "docker" ]]; then
        (cd "$GENESYS_DIR" && docker compose exec -T db \
            sh -c 'mysqldump -u root -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers "$MYSQL_DATABASE"')
    else
        _args_mysql_local
        mysqldump "${ARGS_MYSQL[@]}" --single-transaction --routines --triggers "$GENESYS_BD"
    fi
}

# Carga un volcado SQL leido desde la entrada estandar
cargar_volcado() {
    if [[ "$GENESYS_MODO" == "docker" ]]; then
        (cd "$GENESYS_DIR" && docker compose exec -T db \
            sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"')
    else
        _args_mysql_local
        mysql "${ARGS_MYSQL[@]}" "$GENESYS_BD"
    fi
}

exigir_root() {
    if [[ $EUID -ne 0 ]]; then
        echo "Este script debe ejecutarse con sudo (como root)." >&2
        exit 1
    fi
}
