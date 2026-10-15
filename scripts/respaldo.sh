#!/usr/bin/env bash
# ============================================================
# respaldo.sh - respaldo completo de la base de datos de GeneSysTM.
#
#  * Genera  torneos_db_AAAA-MM-DD_HHMM.sql.gz  en la carpeta de respaldos
#  * Escribe un archivo .sha256 para verificar integridad
#  * (Opcional) firma digital con GPG:  GENESYS_GPG_FIRMAR=1  [GENESYS_GPG_CLAVE=<id>]
#  * Borra los respaldos mas viejos que GENESYS_RETENCION_DIAS
#
# Uso:        ./respaldo.sh
# Cron diario a las 02:00:
#   0 2 * * * /opt/genesys/scripts/respaldo.sh >> /var/log/respaldo.log 2>&1
# ============================================================
set -euo pipefail
umask 077

source "$(dirname "$(readlink -f "$0")")/lib_comun.sh"

fecha="$(date +%F_%H%M)"
nombre="${GENESYS_BD}_${fecha}.sql.gz"
mkdir -p "$GENESYS_RESPALDOS"
chmod 700 "$GENESYS_RESPALDOS"
temporal="$GENESYS_RESPALDOS/.${nombre}.tmp"
destino="$GENESYS_RESPALDOS/$nombre"

# Si algo falla, no queda un archivo a medias que parezca valido
trap 'rm -f "$temporal"' EXIT

registrar respaldo "Iniciando respaldo de ${GENESYS_BD} (modo ${GENESYS_MODO})"
volcar_bd | gzip > "$temporal"

# Comprobaciones: que el gzip sea valido y que no este vacio
gzip -t "$temporal"
if [[ "$(gzip -dc "$temporal" | wc -c)" -lt 200 ]]; then
    registrar respaldo "ERROR: el volcado salio vacio" >&2
    exit 1
fi
mv "$temporal" "$destino"

# Integridad (nombre relativo, para poder verificar con sha256sum -c desde la carpeta)
(cd "$GENESYS_RESPALDOS" && sha256sum "$nombre" > "$nombre.sha256")

# Firma digital opcional
if [[ "${GENESYS_GPG_FIRMAR:-0}" == "1" ]]; then
    firma_args=(--batch --yes --detach-sign --output "$destino.sig")
    if [[ -n "${GENESYS_GPG_CLAVE:-}" ]]; then
        firma_args+=(--local-user "$GENESYS_GPG_CLAVE")
    fi
    gpg "${firma_args[@]}" "$destino"
    registrar respaldo "Firma digital creada: $nombre.sig"
fi

# Retencion
borrados="$(find "$GENESYS_RESPALDOS" -maxdepth 1 -name "${GENESYS_BD}_*" -type f \
            -mtime +"$GENESYS_RETENCION_DIAS" -print -delete | wc -l)"

tamano="$(du -h "$destino" | cut -f1)"
registrar respaldo "OK: $destino ($tamano). Respaldos antiguos eliminados: $borrados"
