#!/bin/bash

# update-consola-web.sh - Script para instalar/actualizar ConsolaWeb en VPS
# Compatible con Rocky Linux, AlmaLinux, CentOS Stream, Ubuntu, Debian
#
# IMPORTANTE: este script NO elimina los cores descargados ni archivos no rastreados.
# Solo actualiza el código trackeado por Git desde origin/main.

RUTA_PROYECTO="/var/www/html/consola-web"
USUARIO_GIT="denis-schafer"
TOKEN_GIT=""  # Opcional: token de GitHub para repos privados
CARPETA_BACKUP="/tmp/consola-web-backups"
PROPIETARIO="apache:apache"  # Cambiar a www-data:www-data en Ubuntu/Debian si aplica

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "${2}[${1}]${NC} $3" >&2
}

# Construir URL del repo (con o sin token)
if [ -n "$TOKEN_GIT" ]; then
    REPO_URL="https://${USUARIO_GIT}:${TOKEN_GIT}@github.com/denis-schafer/ConsolaWeb.git"
else
    REPO_URL="https://github.com/denis-schafer/ConsolaWeb.git"
fi

# Verificar que se ejecute como root
verificar_root() {
    if [ "$EUID" -ne 0 ]; then
        log "ERROR" "$RED" "Este script debe ejecutarse como root o con sudo"
        exit 1
    fi
}

# Verificar dependencias mínimas
verificar_dependencias() {
    log "CHECK" "$YELLOW" "Verificando dependencias..."
    command -v git >/dev/null 2>&1 || { log "ERROR" "$RED" "Git no está instalado"; exit 1; }
    command -v php >/dev/null 2>&1 || { log "ERROR" "$RED" "PHP no está instalado"; exit 1; }
    command -v composer >/dev/null 2>&1 || { log "ERROR" "$RED" "Composer no está instalado"; exit 1; }
    command -v curl >/dev/null 2>&1 || { log "ERROR" "$RED" "curl no está instalado"; exit 1; }
    command -v unzip >/dev/null 2>&1 || { log "ERROR" "$RED" "unzip no está instalado"; exit 1; }
    log "SUCCESS" "$GREEN" "Dependencias verificadas"
}

# Configurar Git seguro
configurar_git_seguro() {
    log "GIT" "$YELLOW" "Configurando Git seguro..."
    git config --global --add safe.directory "$RUTA_PROYECTO"
}

# Backup de archivos críticos
backup_archivos_criticos() {
    local backup_dir="${CARPETA_BACKUP}/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"

    if [ -f "$RUTA_PROYECTO/.env" ]; then
        cp "$RUTA_PROYECTO/.env" "$backup_dir/.env"
        log "INFO" "$GREEN" "Backup de .env"
    fi

    echo "$backup_dir"
}

# Restaurar archivos críticos
restaurar_archivos_criticos() {
    local backup_dir="$1"

    if [ -f "$backup_dir/.env" ]; then
        cp -f "$backup_dir/.env" "$RUTA_PROYECTO/.env"
        log "INFO" "$GREEN" "Restaurado .env"
    fi
}

# Instalar o actualizar desde Git
actualizar_desde_git() {
    log "GIT" "$YELLOW" "Actualizando desde repositorio..."

    if [ ! -d "$RUTA_PROYECTO" ]; then
        log "INFO" "$YELLOW" "El directorio no existe. Clonando..."
        mkdir -p "$(dirname "$RUTA_PROYECTO")"
        git clone "$REPO_URL" "$RUTA_PROYECTO"
        if [ $? -ne 0 ]; then
            log "ERROR" "$RED" "Error al clonar el repositorio"
            exit 1
        fi
    else
        cd "$RUTA_PROYECTO"
        if [ ! -d ".git" ]; then
            log "ERROR" "$RED" "El directorio existe pero no es un repositorio Git"
            exit 1
        fi
        git config --local safe.directory "$RUTA_PROYECTO"
        # NOTA: no usamos 'git clean' para no borrar archivos no trackeados del usuario.
        # Los cores, .env y otros archivos ignorados quedan intactos.
        git fetch origin
        git reset --hard origin/main
        if [ $? -ne 0 ]; then
            log "ERROR" "$RED" "Error al actualizar desde Git"
            exit 1
        fi
    fi

    log "SUCCESS" "$GREEN" "Repositorio listo"
}

# Instalar dependencias PHP
instalar_dependencias_php() {
    log "COMPOSER" "$YELLOW" "Instalando dependencias PHP..."
    cd "$RUTA_PROYECTO"

    composer install --no-dev --optimize-autoloader
    if [ $? -ne 0 ]; then
        log "ERROR" "$RED" "Error en composer install, intentando composer update..."
        composer update --no-dev --optimize-autoloader
    fi

    composer dump-autoload --optimize
    log "SUCCESS" "$GREEN" "Dependencias PHP instaladas"
}

# Configurar .env (respeta el existente)
configurar_env() {
    log "ENV" "$YELLOW" "Verificando .env..."
    cd "$RUTA_PROYECTO"

    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            cp .env.example .env
            log "INFO" "$GREEN" ".env creado desde .env.example"
        else
            cat > .env <<EOF
APP_NAME=ConsolaWeb
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
EOF
            log "INFO" "$GREEN" ".env básico creado"
        fi
    else
        log "INFO" "$GREEN" ".env ya existe (respetando configuración manual)"
    fi
}

# Generar clave de aplicación (solo si no existe)
generar_clave_app() {
    log "KEY" "$YELLOW" "Verificando APP_KEY..."
    cd "$RUTA_PROYECTO"

    if grep -q "^APP_KEY=base64:" .env; then
        log "INFO" "$GREEN" "APP_KEY ya existe"
    else
        php artisan key:generate --force
        log "SUCCESS" "$GREEN" "APP_KEY generada"
    fi
}

# Configurar SQLite y ejecutar migraciones
configurar_sqlite() {
    log "SQLITE" "$YELLOW" "Configurando base de datos SQLite..."
    cd "$RUTA_PROYECTO"

    if ! grep -q "^DB_CONNECTION=sqlite" .env; then
        log "INFO" "$YELLOW" "SQLite no está configurado en .env. Se omite."
        return 0
    fi

    mkdir -p database
    if [ ! -f "database/database.sqlite" ]; then
        touch database/database.sqlite
        log "INFO" "$GREEN" "Base de datos SQLite creada"
    fi

    php artisan migrate --force
    if [ $? -eq 0 ]; then
        log "SUCCESS" "$GREEN" "Migraciones ejecutadas"
    else
        log "ERROR" "$RED" "Error al ejecutar migraciones"
        return 1
    fi
}

# Descargar cores de EmulatorJS
# Esto se hace UNA SOLA VEZ en el VPS, luego todos los usuarios los usan desde el servidor
descargar_cores() {
    log "CORES" "$YELLOW" "Descargando cores de EmulatorJS..."
    cd "$RUTA_PROYECTO"

    local cores_dir="public/emulatorjs/data/cores"
    local zip_path="/tmp/emulatorjs-cores.zip"

    mkdir -p "$cores_dir"

    # Si ya hay cores, los conservamos para no descargarlos en cada actualización
    if [ -n "$(ls -A "$cores_dir" 2>/dev/null)" ]; then
        log "INFO" "$GREEN" "Los cores ya están presentes. Se conservan."
        log "INFO" "$YELLOW" "Si querés forzar la re-descarga, borrá $cores_dir y volvé a ejecutar el script."
        return 0
    fi

    curl -L --fail -o "$zip_path" "https://cdn.emulatorjs.org/latest/data/cores.zip"
    if [ $? -ne 0 ]; then
        log "ERROR" "$RED" "Error al descargar cores.zip"
        return 1
    fi

    unzip -o "$zip_path" -d "$cores_dir"
    if [ $? -ne 0 ]; then
        log "ERROR" "$RED" "Error al extraer cores.zip"
        return 1
    fi

    rm -f "$zip_path"
    log "SUCCESS" "$GREEN" "Cores descargados y extraídos en $cores_dir"
}

# Configurar permisos
configurar_permisos() {
    log "PERMISSIONS" "$YELLOW" "Configurando permisos..."
    cd "$RUTA_PROYECTO"

    mkdir -p storage/framework/{sessions,views,cache}
    mkdir -p storage/logs
    mkdir -p bootstrap/cache

    chmod -R 755 storage bootstrap/cache
    chown -R "$PROPIETARIO" storage bootstrap/cache public

    log "SUCCESS" "$GREEN" "Permisos configurados"
}

# Optimizar Laravel
optimizar_laravel() {
    log "OPTIMIZE" "$YELLOW" "Optimizando Laravel..."
    cd "$RUTA_PROYECTO"

    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    log "SUCCESS" "$GREEN" "Laravel optimizado"
}

# Verificar despliegue
verificar_despliegue() {
    log "VERIFY" "$YELLOW" "Verificando despliegue..."
    cd "$RUTA_PROYECTO"

    local cores_ok="FALTAN"
    if [ -d "public/emulatorjs/data/cores" ] && [ -n "$(ls -A public/emulatorjs/data/cores 2>/dev/null)" ]; then
        cores_ok="OK"
    fi

    echo "--- VERIFICACIÓN ---"
    echo "Directorio: $(pwd)"
    echo "Git: $(git log --oneline -1 2>/dev/null || echo 'No hay commits')"
    echo "Composer: $([ -f 'vendor/autoload.php' ] && echo 'OK' || echo 'FALLO')"
    echo "Laravel: $([ -f 'artisan' ] && echo 'OK' || echo 'FALLO')"
    echo ".env: $([ -f '.env' ] && echo 'OK' || echo 'FALLO')"
    echo "APP_KEY: $(grep '^APP_KEY=' .env | head -1)"
    echo "Cores: $cores_ok"
    echo "--------------------"
}

# Función principal
main() {
    log "START" "$YELLOW" "Iniciando instalación/actualización de ConsolaWeb..."

    verificar_root
    verificar_dependencias
    configurar_git_seguro

    local backup_dir
    backup_dir=$(backup_archivos_criticos)
    log "BACKUP" "$YELLOW" "Backup guardado en: $backup_dir"

    actualizar_desde_git
    restaurar_archivos_criticos "$backup_dir"
    instalar_dependencias_php
    configurar_env
    generar_clave_app
    configurar_sqlite
    descargar_cores
    configurar_permisos
    optimizar_laravel
    verificar_despliegue

    log "COMPLETE" "$GREEN" "¡Instalación/actualización de ConsolaWeb completada!"
    echo ""
    log "INFO" "$YELLOW" "Recordá configurar el virtual host HTTPS con estas cabeceras:"
    log "INFO" "$GREEN" "  Cross-Origin-Opener-Policy: same-origin"
    log "INFO" "$GREEN" "  Cross-Origin-Embedder-Policy: require-corp"
    echo ""
    log "INFO" "$YELLOW" "Las ROMs, BIOS y partidas guardadas se almacenan en el navegador de cada usuario (IndexedDB), no en el servidor."
}

main "$@"
