#!/usr/bin/env bash
# ============================================================
# gestion_servidor.sh - menu general de administracion de GeneSysTM.
#
# Uso interactivo:   sudo ./gestion_servidor.sh
# Uso directo:       sudo ./gestion_servidor.sh {estado|levantar|detener|logs|respaldo|
#                                                 restaurar ARCHIVO|usuarios ...|monitoreo|
#                                                 firewall|selinux|fail2ban}
# Cada accion queda registrada con el nombre de quien la ejecuto.
# ============================================================
set -uo pipefail

AQUI="$(dirname "$(readlink -f "$0")")"
source "$AQUI/lib_comun.sh"

QUIEN="${SUDO_USER:-$(id -un)}"

# Ejecuta un comando dentro de la carpeta del proyecto (modo docker)
compose() { (cd "$GENESYS_DIR" && docker compose "$@"); }

accion() {
    local nombre="$1"; shift
    registrar gestion_servidor "$QUIEN ejecuto: $nombre"
    case "$nombre" in
        estado)     compose ps ;;
        levantar)   compose up -d ;;
        detener)    compose down ;;
        logs)       compose logs --tail 100 ;;
        respaldo)   "$AQUI/respaldo.sh" ;;
        restaurar)
            local archivo="${1:-}"
            if [[ -z "$archivo" ]]; then
                ls -1t "$GENESYS_RESPALDOS"/*.sql.gz 2>/dev/null | head -10
                read -r -p "Ruta del respaldo a restaurar: " archivo
            fi
            "$AQUI/restaurar.sh" "$archivo"
            ;;
        usuarios)
            if [[ $# -eq 0 ]]; then
                read -r -p "Accion (alta|baja|bloquear|desbloquear|listar): " a
                case "$a" in
                    listar) set -- listar ;;
                    alta)   read -r -p "Usuario: " u; read -r -p "Grupo: " g; set -- alta "$u" "$g" ;;
                    *)      read -r -p "Usuario: " u; set -- "$a" "$u" ;;
                esac
            fi
            "$AQUI/gestionar_usuarios.sh" "$@"
            ;;
        monitoreo)  "$AQUI/monitoreo_bd.sh" ;;
        firewall)
            # AlmaLinux/RHEL usa firewalld; en Ubuntu/Debian se usa UFW
            if command -v firewall-cmd >/dev/null 2>&1; then
                firewall-cmd --state && firewall-cmd --list-all
            elif command -v ufw >/dev/null 2>&1; then
                ufw status verbose
            else
                echo "No se encontro firewalld ni UFW."
            fi
            ;;
        selinux)
            if command -v getenforce >/dev/null 2>&1; then
                echo "SELinux: $(getenforce)"
                ausearch -m avc -ts recent 2>/dev/null | tail -5 || true
            else
                echo "SELinux no esta disponible en este sistema."
            fi
            ;;
        fail2ban)
            if command -v fail2ban-client >/dev/null 2>&1; then fail2ban-client status sshd
            else echo "fail2ban no esta instalado."; fi
            ;;
        *)
            echo "Accion desconocida: $nombre" >&2
            return 1
            ;;
    esac
}

# Modo directo (por ejemplo desde cron o para pruebas)
if [[ $# -gt 0 ]]; then
    accion "$@"
    exit $?
fi

# Modo interactivo
exigir_root
PS3=$'\nElija una opcion: '
opciones=("Estado de contenedores" "Levantar sistema" "Detener sistema" "Ver registros"
          "Ejecutar respaldo" "Restaurar respaldo" "Gestionar usuarios del sistema"
          "Monitoreo de la base de datos" "Estado del firewall" "Estado de SELinux"
          "Estado de fail2ban" "Salir")
select opcion in "${opciones[@]}"; do
    case "$opcion" in
        "Estado de contenedores")           accion estado ;;
        "Levantar sistema")                 accion levantar ;;
        "Detener sistema")
            read -r -p "Detener el sistema? (s/N) " r
            [[ "$r" == "s" || "$r" == "S" ]] && accion detener ;;
        "Ver registros")                    accion logs ;;
        "Ejecutar respaldo")                accion respaldo ;;
        "Restaurar respaldo")               accion restaurar ;;
        "Gestionar usuarios del sistema")   accion usuarios ;;
        "Monitoreo de la base de datos")    accion monitoreo ;;
        "Estado del firewall")              accion firewall ;;
        "Estado de SELinux")                accion selinux ;;
        "Estado de fail2ban")               accion fail2ban ;;
        "Salir")                            break ;;
        *)                                  echo "Opcion invalida" ;;
    esac
done
