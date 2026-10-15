#!/usr/bin/env bash
# ============================================================
# gestionar_usuarios.sh - alta, baja, bloqueo y listado de usuarios del sistema.
#
# Uso:
#   sudo ./gestionar_usuarios.sh alta        USUARIO GRUPO
#   sudo ./gestionar_usuarios.sh baja        USUARIO
#   sudo ./gestionar_usuarios.sh bloquear    USUARIO
#   sudo ./gestionar_usuarios.sh desbloquear USUARIO
#   sudo ./gestionar_usuarios.sh listar
#
# Para automatizar el alta sin pedir la clave por teclado:
#   GENESYS_CLAVE_INICIAL='Clave-Temporal-1' sudo -E ./gestionar_usuarios.sh alta ana deploy
# En ambos casos el usuario debera cambiar la clave en su primer ingreso.
# ============================================================
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Este script debe ejecutarse con sudo (como root)." >&2
    exit 1
fi

uso() {
    echo "Uso: $0 {alta USUARIO GRUPO | baja USUARIO | bloquear USUARIO | desbloquear USUARIO | listar}" >&2
    exit 1
}

registrar() {
    echo "$*"
    if command -v logger >/dev/null 2>&1; then
        logger -t gestionar_usuarios "$*" || true
    fi
}

accion="${1:-}"
usuario="${2:-}"
grupo="${3:-}"

# Nombres validos de usuario/grupo en Linux (evita caracteres peligrosos)
validar_nombre() {
    if [[ ! "$1" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]]; then
        echo "Nombre invalido: '$1' (use minusculas, numeros, guion y guion bajo; maximo 32)." >&2
        exit 1
    fi
}

case "$accion" in
    alta)
        [[ -n "$usuario" && -n "$grupo" ]] || uso
        validar_nombre "$usuario"
        validar_nombre "$grupo"
        if id "$usuario" &>/dev/null; then
            echo "El usuario '$usuario' ya existe." >&2
            exit 1
        fi
        getent group "$grupo" >/dev/null || groupadd "$grupo"
        useradd -m -s /bin/bash -g "$grupo" "$usuario"
        if [[ -n "${GENESYS_CLAVE_INICIAL:-}" ]]; then
            echo "${usuario}:${GENESYS_CLAVE_INICIAL}" | chpasswd
        else
            passwd "$usuario"
        fi
        chage -d 0 "$usuario"        # obliga a cambiar la clave en el primer ingreso
        registrar "Usuario '$usuario' creado en el grupo '$grupo' (por ${SUDO_USER:-root})"
        ;;
    baja)
        [[ -n "$usuario" ]] || uso
        validar_nombre "$usuario"
        id "$usuario" &>/dev/null || { echo "El usuario '$usuario' no existe." >&2; exit 1; }
        userdel -r "$usuario"
        registrar "Usuario '$usuario' eliminado (por ${SUDO_USER:-root})"
        ;;
    bloquear)
        [[ -n "$usuario" ]] || uso
        validar_nombre "$usuario"
        id "$usuario" &>/dev/null || { echo "El usuario '$usuario' no existe." >&2; exit 1; }
        usermod -L -e 1 "$usuario"   # bloquea la clave y vence la cuenta
        registrar "Usuario '$usuario' bloqueado (por ${SUDO_USER:-root})"
        ;;
    desbloquear)
        [[ -n "$usuario" ]] || uso
        validar_nombre "$usuario"
        id "$usuario" &>/dev/null || { echo "El usuario '$usuario' no existe." >&2; exit 1; }
        usermod -U -e "" "$usuario"
        registrar "Usuario '$usuario' desbloqueado (por ${SUDO_USER:-root})"
        ;;
    listar)
        printf '%-16s %-6s %-14s %s\n' USUARIO UID GRUPO HOME
        awk -F: '$3 >= 1000 && $3 < 65534 {print $1, $3, $4, $6}' /etc/passwd |
        while read -r nombre uid gid home; do
            grupo_nombre="$(getent group "$gid" | cut -d: -f1)"
            printf '%-16s %-6s %-14s %s\n' "$nombre" "$uid" "${grupo_nombre:-$gid}" "$home"
        done
        ;;
    *)
        uso
        ;;
esac
