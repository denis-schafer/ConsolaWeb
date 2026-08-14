# ConsolaWeb

Emulador multiplataforma 100 % local que corre en el navegador. Basado en **Laravel**, **EmulatorJS** y **IndexedDB**: las ROMs, BIOS y partidas guardadas nunca salen de tu equipo.

![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=flat&logo=laravel&logoColor=white)
![EmulatorJS](https://img.shields.io/badge/EmulatorJS-4.2+-blue?style=flat)
![License](https://img.shields.io/badge/license-MIT-green?style=flat)

## Características

- **Multiplataforma**: NES, SNES, Game Boy / Color / Advance, Sega Genesis, Nintendo 64, Nintendo DS y PlayStation 1.
- **100 % local**: las ROMs, BIOS y partidas se almacenan en **IndexedDB** del navegador.
- **Sin conexión requerida**: EmulatorJS y los cores se sirven desde el propio proyecto.
- **Controles configurables**: mapeo de teclado para dos jugadores con validación de duplicados.
- **Partidas guardadas**: guardado y carga de estados por ROM.
- **Backup portable**: exportá e importá tu biblioteca completa como un archivo `.zip`.
- **Imágenes por ROM**: asigná una carátula a cada juego; se muestra en la biblioteca y en la pantalla de carga.
- **Seguridad por contexto**: cabeceras COOP/COEP para habilitar `SharedArrayBuffer` y cores con threads.

## Requisitos

- PHP >= 8.2
- Composer
- Extensiones PHP habituales de Laravel (`mbstring`, `openssl`, `pdo`, etc.)
- Navegador moderno (Chrome, Edge, Firefox)

## Instalación

```bash
# Clonar el repositorio
git clone https://github.com/denis-schafer/ConsolaWeb.git
cd ConsolaWeb

# Instalar dependencias de PHP
composer install

# Copiar configuración y generar key
cp .env.example .env
php artisan key:generate

# Descargar los cores de EmulatorJS (Windows)
.\download-cores.ps1

# En Linux/macOS o si preferís usar curl/wget, descargá
# https://cdn.emulatorjs.org/latest/data/cores.zip
# y extraelo en public/emulatorjs/data/cores/

# Iniciar servidor de desarrollo
php artisan serve
```

Abrir `http://127.0.0.1:8000` en el navegador.

> **Nota**: para usar `consola-web.test` con HTTPS, configurá el virtual host en Laragon/XAMPP y asegurate de que el servidor envíe las cabeceras `Cross-Origin-Opener-Policy: same-origin` y `Cross-Origin-Embedder-Policy: require-corp`. El middleware `EnableCrossOriginIsolation` ya está incluido.
>
> **Cores**: este repositorio no incluye los cores de EmulatorJS (`public/emulatorjs/data/cores/`) por su tamaño. Descargalos antes de usar el emulador.

## Deploy en VPS (producción)

1. Subí el script al servidor:

```bash
scp update-consola-web.sh root@tuvps:/home/
```

2. (Opcional/recomendado) Copiá tu `.env` local al VPS antes de correr el script por primera vez:

```bash
ssh root@tuvps "mkdir -p /var/www/html/consola-web"
scp .env root@tuvps:/var/www/html/consola-web/.env
```

3. Ejecutá el script como root:

```bash
ssh root@tuvps
chmod +x /home/update-consola-web.sh
/home/update-consola-web.sh
```

El script:

- Clona o actualiza el repositorio desde `origin/main`.
- **No elimina** los cores de EmulatorJS ya descargados.
- **No elimina** archivos no rastreados (por ejemplo, imágenes o archivos que hayas subido a mano).
- Respeta tu `.env` si ya existe; si no, crea uno básico con SQLite.
- Ejecuta migraciones, optimiza cachés y ajusta permisos.

Para forzar la re-descarga de cores, borrá la carpeta `public/emulatorjs/data/cores/` antes de correr el script.

## Uso

1. **Cargar ROM**: seleccioná el archivo y la plataforma correcta.
2. **BIOS (solo PS1)**: cargá primero un BIOS como `scph1001.bin` y guardalo.
3. **Guardar ROM**: la ROM se almacena localmente en IndexedDB.
4. **Jugar**: desde la biblioteca local, presioná el botón de play.
5. **Controles**: abrí la card *Controles del teclado* para personalizar el mapeo del Jugador 1 y Jugador 2.
6. **Partidas guardadas**: guardá estados en cualquier momento y cargalos después.
7. **Backup**: usá *Exportar* / *Importar* para mover tu biblioteca entre navegadores o entre HTTP y HTTPS.

## Estructura relevante

```
consola-web/
├── app/
│   └── Http/
│       └── Middleware/
│           └── EnableCrossOriginIsolation.php   # COOP/COEP
├── resources/
│   └── views/
│       ├── emulator.blade.php                  # Interfaz principal
│       └── play.blade.php                      # Iframe del emulador
├── public/
│   └── emulatorjs/
│       └── data/                               # EmulatorJS + cores (offline)
├── routes/
│   └── web.php
└── README.md
```

## Sobre EmulatorJS

Este proyecto incluye una copia local de [EmulatorJS](https://github.com/EmulatorJS/EmulatorJS) bajo sus respectivas licencias. Los cores se encuentran en `public/emulatorjs/data/cores/`.

## Licencia

MIT
