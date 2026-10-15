#!/usr/bin/env bash
# ============================================================
# restaurar.sh - restaura un respaldo creado por respaldo.sh.
#
# Uso:  ./restaurar.sh /var/backups/genesys/torneos_db_2026-10-14_0200.sql.gz [--si]
#
# Antes de restaurar:
#   1) verifica el SHA-256 (si existe el .sha256)
#   2) verifica la firma GPG (si existe el .sig)
#   3) pide confirmacion, salvo que se pase --si
# ATENCION: la restauracion reemplaza los datos actuales de la base.
# ============================================================
set -euo pipefail

source "$(dirname "$(readlink -f "$0")")/lib_comun.sh"

archivo="${1:-}"
confirmar="${2:-}"

if [[ -z "$archivo" || ! -f "$archivo" ]]; then
    echo "Uso: $0 RUTA_AL_RESPALDO.sql.gz [--si]" >&2
    exit 1
fi

# 1) Integridad
if [[ -f "$archivo.sha256" ]]; then
    (cd "$(dirname "$archivo")" && sha256sum -c "$(basename "$archivo").sha256")
else
    echo "AVISO: no hay archivo .sha256; no se puede verificar la integridad." >&2
fi

# 2) Firma digital
if [[ -f "$archivo.sig" ]]; then
    gpg --verify "$archivo.sig" "$archivo"
fi

gzip -t "$archivo"

# 3) Confirmacion
if [[ "$confirmar" != "--si" ]]; then
    read -r -p "Se reemplazaran los datos de '${GENESYS_BD}'. Escriba SI para continuar: " respuesta
    if [[ "$respuesta" != "SI" ]]; then
        echo "Cancelado."
        exit 1
    fi
fi

registrar restauracion "Restaurando $archivo en ${GENESYS_BD} (modo ${GENESYS_MODO})"
gzip -dc "$archivo" | cargar_volcado
registrar restauracion "OK: restauracion completada"
