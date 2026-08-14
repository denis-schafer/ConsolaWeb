<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consola Web - Emulador local</title>
    <style>
        :root {
            --bg: #0f1115;
            --panel: #181b21;
            --accent: #3b82f6;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --danger: #ef4444;
            --success: #22c55e;
            --warning: #f59e0b;
        }
        * { box-sizing: border-box; }
        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 1 auto;
            min-height: 0;
            display: grid;
            grid-template-columns: 360px 1fr;
            gap: 1rem;
            padding: 1rem;
            overflow: hidden;
        }
        aside {
            min-height: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        section {
            min-height: 0;
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        @media (max-width: 960px) {
            main {
                grid-template-columns: 1fr;
                grid-template-rows: minmax(auto, 50vh) 1fr;
                overflow-y: auto;
            }
            aside { overflow-y: visible; }
        }
        .panel {
            background: var(--panel);
            border-radius: .75rem;
            padding: 1rem;
            border: 1px solid #272b33;
            flex: 0 0 auto;
        }
        .panel h2 {
            margin: 0 0 .75rem;
            font-size: .95rem;
            color: var(--text);
        }
        label { display: block; margin-bottom: .35rem; font-size: .85rem; color: var(--muted); }
        input[type="file"], select {
            width: 100%;
            background: #0f1115;
            color: var(--text);
            border: 1px solid #272b33;
            border-radius: .5rem;
            padding: .55rem;
            margin-bottom: .65rem;
            font-size: .85rem;
        }
        button {
            background: var(--accent);
            color: white;
            border: none;
            border-radius: .5rem;
            padding: .55rem .9rem;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            font-size: .85rem;
        }
        button:hover { opacity: .9; }
        button:disabled { opacity: .5; cursor: not-allowed; }
        .secondary { background: #272b33; }
        .danger { background: var(--danger); }
        .success { background: var(--success); }
        .small { padding: .35rem .6rem; font-size: .75rem; width: auto; }
        .hidden { display: none !important; }
        #rom-list, #save-list { list-style: none; padding: 0; margin: 0; }
        #rom-list li, #save-list li {
            background: #0f1115;
            border: 1px solid #272b33;
            border-radius: .5rem;
            padding: .65rem;
            margin-bottom: .4rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .4rem;
        }
        #rom-list li .info, #save-list li .info { flex: 1; min-width: 0; }
        #rom-list li .name, #save-list li .name { font-weight: 600; word-break: break-all; font-size: .85rem; }
        #rom-list li .meta, #save-list li .meta { font-size: .72rem; color: var(--muted); }
        #rom-list li .actions, #save-list li .actions { display: flex; gap: .3rem; flex-wrap: wrap; }
        .empty { color: var(--muted); font-size: .85rem; text-align: center; padding: .75rem; }
        .controls-bar {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        .controls-bar button { width: auto; flex: 1 1 auto; min-width: 110px; }
        #emulator-host {
            flex: 1 1 auto;
            min-height: 0;
            background: #000;
            border-radius: .75rem;
            overflow: hidden;
            border: 1px solid #272b33;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #emulator-host iframe {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
        }
        #placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--muted);
            text-align: center;
            padding: 1rem;
        }
        #placeholder strong { color: var(--text); margin-bottom: .5rem; }
        #loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: var(--text);
            text-align: center;
            padding: 1rem;
            z-index: 10;
        }
        #loading-overlay.active { display: flex; }
        #loading-overlay .game-info {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        #loading-overlay .platform {
            color: var(--muted);
            font-size: .85rem;
            font-weight: 400;
        }
        #loading-overlay .slider {
            width: 80%;
            max-width: 300px;
            height: 6px;
            background: #272b33;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 1rem;
        }
        #loading-overlay .slider-bar {
            width: 0%;
            height: 100%;
            background: var(--accent);
            border-radius: 3px;
            animation: loading-slide 1.5s infinite ease-in-out;
        }
        @keyframes loading-slide {
            0% { width: 0%; margin-left: 0%; }
            50% { width: 70%; margin-left: 15%; }
            100% { width: 0%; margin-left: 100%; }
        }
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        .spinner {
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }
        .spinner circle {
            fill: none;
            stroke: currentColor;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-dasharray: 31.4 31.4;
        }
        .filter-wrap {
            position: relative;
            margin-bottom: .5rem;
        }
        .filter-wrap input {
            width: 100%;
            background: #0f1115;
            color: var(--text);
            border: 1px solid #272b33;
            border-radius: .5rem;
            padding: .55rem .75rem;
            font-size: .85rem;
            outline: none;
        }
        .filter-wrap input:focus {
            border-color: var(--accent);
        }
        .filter-wrap input::placeholder {
            color: var(--muted);
        }
        .filter-wrap .slider {
            position: absolute;
            left: 0;
            right: 0;
            bottom: -3px;
            height: 3px;
            background: transparent;
            border-radius: 0 0 .5rem .5rem;
            overflow: hidden;
            margin-top: 0;
        }
        .filter-wrap .slider-bar {
            width: 0%;
            height: 100%;
            background: var(--accent);
            border-radius: 0 0 .5rem .5rem;
            animation: loading-slide 1.2s infinite ease-in-out;
        }
        .player-tabs {
            display: flex;
            gap: .35rem;
            margin-bottom: .6rem;
        }
        .player-tab {
            flex: 1;
            background: #0f1115;
            border: 1px solid #272b33;
            color: var(--muted);
            border-radius: .35rem;
            padding: .4rem;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 600;
        }
        .player-tab.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }
        .status {
            font-size: .75rem;
            margin-top: .4rem;
            min-height: 1rem;
        }
        .status.success { color: var(--success); }
        .status.error { color: var(--danger); }
        .status.warning { color: var(--warning); }
        .bios-status {
            font-size: .75rem;
            color: var(--muted);
            margin-bottom: .65rem;
        }
        .bios-status.ok { color: var(--success); }
        .bios-status.missing { color: var(--warning); }
        .hint {
            color: var(--muted);
            font-size: .75rem;
            line-height: 1.4;
            margin: 0 0 .6rem;
        }
        details {
            border-radius: .5rem;
        }
        summary {
            cursor: pointer;
            font-weight: 600;
            font-size: .95rem;
            color: var(--text);
            user-select: none;
        }
        .panel summary {
            margin: 0 0 .75rem;
        }
        details > div {
            margin-top: .6rem;
            font-size: .8rem;
            color: var(--muted);
            line-height: 1.45;
        }
        .panel details > div {
            color: inherit;
        }
        #controls-list {
            list-style: none;
            padding: 0;
            margin: 0 0 .6rem;
        }
        #controls-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            padding: .45rem .5rem;
            border-bottom: 1px solid #272b33;
            font-size: .85rem;
        }
        #controls-list li:last-child { border-bottom: 0; }
        #controls-list .action { flex: 1; color: var(--text); }
        .key-input {
            background: #0f1115;
            border: 1px solid #272b33;
            border-radius: .35rem;
            padding: .25rem .6rem;
            font-family: monospace;
            font-size: .8rem;
            min-width: 140px;
            text-align: center;
            color: var(--text);
            cursor: pointer;
            width: auto;
        }
        .key-input:hover { border-color: #3b82f6; }
        .key-input.waiting {
            border-color: var(--accent);
            color: var(--accent);
        }
        ol.help {
            padding-left: 1.2rem;
            margin: .4rem 0 0;
            color: var(--muted);
            line-height: 1.5;
        }
        ol.help li { margin-bottom: .35rem; }
        ol.help code {
            background: #0f1115;
            padding: .1rem .3rem;
            border-radius: .25rem;
            font-family: monospace;
            color: var(--text);
        }
        .library-slider {
            width: 100%;
            height: 4px;
            background: #272b33;
            border-radius: 2px;
            overflow: hidden;
            margin: .5rem 0;
            display: none;
        }
        .library-slider.active { display: block; }
        .library-slider .slider-bar {
            width: 0%;
            height: 100%;
            background: var(--accent);
            border-radius: 2px;
            animation: loading-slide 1.2s infinite ease-in-out;
        }
        .rom-list-scroll {
            max-height: 170px;
            overflow-y: auto;
            border: 1px solid #272b33;
            border-radius: .5rem;
            background: #0f1115;
        }
        .rom-list-scroll:empty,
        .rom-list-scroll .empty {
            border: none;
            background: transparent;
        }
        #rom-list li {
            position: relative;
            padding: 0;
            overflow: hidden;
            align-items: center;
            min-height: 48px;
        }
        #rom-list li .thumb {
            width: 64px;
            min-width: 64px;
            height: 64px;
            margin: .4rem 0 .4rem .4rem;
            background: #0f1115;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: .35rem;
        }
        #rom-list li .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        #rom-list li .info {
            padding: .65rem 2.2rem .65rem .65rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
            min-width: 0;
        }
        #rom-list li .actions {
            position: absolute;
            bottom: .4rem;
            right: .4rem;
            padding: 0;
        }
        .icon-btn {
            width: 28px;
            height: 28px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: .35rem;
            min-width: auto;
        }
        .icon-btn svg {
            width: 14px;
            height: 14px;
            fill: currentColor;
        }
        .rom-edit-btn {
            position: absolute;
            top: .4rem;
            right: .4rem;
            z-index: 2;
        }
        .rom-play-btn {
            align-self: flex-end;
        }
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 1rem;
        }
        .modal-overlay.active { display: flex; }
        .modal {
            background: var(--panel);
            border: 1px solid #272b33;
            border-radius: .75rem;
            padding: 1.25rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .modal h3 {
            margin: 0 0 .6rem;
            font-size: 1rem;
        }
        .modal p {
            margin: 0 0 1rem;
            color: var(--muted);
            font-size: .85rem;
            line-height: 1.45;
        }
        .modal-body { margin-bottom: 1rem; }
        .modal-body label { margin-top: .6rem; }
        .modal-body input[type="text"] {
            width: 100%;
            background: #0f1115;
            color: var(--text);
            border: 1px solid #272b33;
            border-radius: .5rem;
            padding: .55rem;
            margin-bottom: .65rem;
            font-size: .85rem;
        }
        .modal-preview {
            width: 100%;
            max-height: 160px;
            object-fit: contain;
            background: #0f1115;
            border: 1px solid #272b33;
            border-radius: .5rem;
            margin-bottom: .65rem;
        }
        .modal-actions {
            display: flex;
            gap: .5rem;
            justify-content: flex-end;
        }
        .modal-actions button { width: auto; min-width: 90px; }
        .modal-actions .danger { margin-left: auto; }
    </style>
</head>
<body>
    <main>
        <aside>
            <div class="panel" id="rom-upload-panel">
                <details>
                    <summary>Cargar ROM</summary>
                    <div>
                        <label for="rom-file">Archivo ROM / ISO</label>
                        <input id="rom-file" type="file">

                        <label for="rom-core">Plataforma / Core</label>
                        <select id="rom-core">
                            <option value="nestopia">Nintendo Entertainment System (NES)</option>
                            <option value="snes9x">Super Nintendo (SNES)</option>
                            <option value="mgba">Game Boy / Game Boy Color / Advance</option>
                            <option value="picodrive">Sega Genesis / Mega Drive</option>
                            <option value="n64">Nintendo 64</option>
                            <option value="desmuME">Nintendo DS</option>
                            <option value="pcsx_rearmed">PlayStation 1</option>
                        </select>

                        <div id="bios-panel" class="hidden">
                            <label for="bios-file">Archivo BIOS requerido (<span id="bios-name"></span>)</label>
                            <input id="bios-file" type="file">
                            <div id="bios-status" class="bios-status missing">No se ha guardado BIOS para esta consola.</div>
                            <button id="btn-save-bios" type="button" class="secondary">Guardar BIOS</button>
                        </div>

                        <button id="btn-save" type="button">Guardar ROM localmente</button>
                        <div id="save-status" class="status"></div>
                        <div id="env-warning" class="status warning hidden"></div>
                    </div>
                </details>
            </div>

            <div class="panel">
                <details open>
                    <summary>Biblioteca local</summary>
                    <div>
                        <div id="library-slider" class="library-slider"><div class="slider-bar"></div></div>
                        <div class="filter-wrap">
                            <input id="rom-filter" type="text" placeholder="Filtrar ROMs..." autocomplete="off">
                            <div id="filter-slider" class="slider hidden"><div class="slider-bar"></div></div>
                        </div>
                        <div id="rom-list-scroll" class="rom-list-scroll">
                            <ul id="rom-list"></ul>
                        </div>
                        <div class="controls-bar" style="margin-top:.5rem;">
                            <button id="btn-export" type="button" class="secondary small">Exportar</button>
                            <button id="btn-import" type="button" class="secondary small">Importar</button>
                        </div>
                        <input id="import-file" type="file" accept=".zip" class="hidden">
                        <div id="import-status" class="status"></div>
                    </div>
                </details>
            </div>

            <div class="panel">
                <details>
                    <summary>¿Cómo iniciar un juego?</summary>
                    <div>
                        <ol class="help">
                            <li>Selecciona el archivo ROM/ISO de tu disco.</li>
                            <li>Elige la plataforma correcta en el selector.</li>
                            <li>Si juegas PlayStation 1, carga primero el archivo BIOS (recomendado <code>scph5501.bin</code>; también sirve <code>scph1001.bin</code>) y guárdalo.</li>
                            <li>Pulsa <strong>Guardar ROM localmente</strong>. El archivo se almacena en el navegador.</li>
                            <li>En la biblioteca aparecerá el juego; pulsa <strong>Jugar</strong>.</li>
                            <li>Usa el botón <strong>Pantalla completa</strong> que aparece arriba del emulador para expandir el juego.</li>
                            <li>Si usas <code>consola-web.test</code> sin HTTPS, algunos cores exigentes (PS1, N64, etc.) pueden no arrancar. En ese caso usa <code>https://consola-web.test</code> o <code>http://127.0.0.1:8000</code>.</li>
                            <li>Si cambiás de protocolo (HTTP ↔ HTTPS) o de navegador, usá <strong>Exportar</strong> para descargar un archivo <code>.zip</code> con toda tu biblioteca, y luego <strong>Importar</strong> en la nueva URL para restaurarla.</li>
                        </ol>
                        <p class="hint">Las ROMs, BIOS y partidas guardadas nunca salen de tu equipo; se guardan en IndexedDB.</p>
                    </div>
                </details>
            </div>

            <div id="saves-panel" class="panel">
                <details>
                    <summary>Partidas guardadas</summary>
                    <div>
                        <div class="controls-bar">
                            <button id="btn-quick-save" type="button" class="success">Guardar partida</button>
                            <button id="btn-refresh-saves" type="button" class="secondary">Actualizar</button>
                        </div>
                        <ul id="save-list"></ul>
                    </div>
                </details>
            </div>

            <div class="panel">
                <details>
                    <summary>Controles del teclado</summary>
                    <div>
                        <div class="player-tabs">
                            <button type="button" class="player-tab active" data-player="0">Jugador 1</button>
                            <button type="button" class="player-tab" data-player="1">Jugador 2</button>
                        </div>
                        <p class="hint">Haz clic sobre una tecla para cambiarla. Si la tecla ya está en uso en el mismo jugador, no se asignará.</p>
                        <ul id="controls-list"></ul>
                        <button id="btn-reset-controls" type="button" class="secondary small">Restaurar valores por defecto</button>
                        <div id="controls-status" class="status"></div>
                    </div>
                </details>
            </div>
        </aside>

        <section>
            <div class="controls-bar" id="emulator-controls" style="display:none;">
                <button id="btn-fullscreen" type="button" class="secondary">Pantalla completa</button>
                <button id="btn-exit" type="button" class="danger">Salir del juego</button>
            </div>

            <div id="emulator-host">
                <div id="placeholder">
                    <strong>Ninguna ROM en ejecución</strong>
                    <span>Selecciona un juego de tu biblioteca local para comenzar.</span>
                </div>
                <div id="loading-overlay">
                    <img id="loading-game-image" class="hidden" alt="" style="max-height:120px;max-width:80%;object-fit:contain;border-radius:.5rem;margin-bottom:.75rem;">
                    <div class="game-info">
                        <div>Cargando: <span id="loading-game-name"></span></div>
                        <div class="platform">(<span id="loading-game-platform"></span>)</div>
                    </div>
                    <div class="slider"><div class="slider-bar"></div></div>
                </div>
            </div>
        </section>

    </main>

    <div id="modal-overlay" class="modal-overlay">
        <div class="modal">
            <h3 id="modal-title"></h3>
            <p id="modal-message"></p>
            <div id="modal-body" class="modal-body"></div>
            <div class="modal-actions">
                <button id="modal-delete" type="button" class="danger" style="display:none;">Eliminar</button>
                <button id="modal-cancel" type="button" class="secondary">Cancelar</button>
                <button id="modal-confirm" type="button" class="success">Aceptar</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/jszip.min.js') }}"></script>
    <script>
        const DB_NAME = 'ConsoleWebDB';
        const DB_VERSION = 5;
        const ROMS_STORE = 'roms';
        const ROMS_DATA_STORE = 'roms_data';
        const SAVES_STORE = 'saves';
        const BIOS_STORE = 'bios';
        const SETTINGS_STORE = 'settings';

        const modalOverlay = document.getElementById('modal-overlay');
        const modalTitle = document.getElementById('modal-title');
        const modalMessage = document.getElementById('modal-message');
        const modalBody = document.getElementById('modal-body');
        const modalCancel = document.getElementById('modal-cancel');
        const modalConfirm = document.getElementById('modal-confirm');
        const modalDelete = document.getElementById('modal-delete');

        const dataPath = '{{ url('/emulatorjs/data') }}/';

        // Fallback de BIOS en el servidor. Subir los archivos a public/bios/.
        // Orden: primero la mejor/más compatible, luego alternativas.
        const biosBaseUrl = '{{ url('/bios') }}';
        const serverBiosFiles = {
            pcsx_rearmed: ['scph5501.bin', 'scph1001.bin']
        };

        const controlNames = {
            0: 'B', 1: 'Y', 2: 'Select', 3: 'Start',
            4: 'D-Pad Arriba', 5: 'D-Pad Abajo', 6: 'D-Pad Izquierda', 7: 'D-Pad Derecha',
            8: 'A', 9: 'X', 10: 'L (Shoulder)', 11: 'R (Shoulder)', 12: 'L2', 13: 'R2'
        };

        const defaultControls = {
            0: {
                0: { value: 'x', value2: 'BUTTON_2' },
                1: { value: 's', value2: 'BUTTON_4' },
                2: { value: 'v', value2: 'SELECT' },
                3: { value: 'enter', value2: 'START' },
                4: { value: 'up arrow', value2: 'DPAD_UP' },
                5: { value: 'down arrow', value2: 'DPAD_DOWN' },
                6: { value: 'left arrow', value2: 'DPAD_LEFT' },
                7: { value: 'right arrow', value2: 'DPAD_RIGHT' },
                8: { value: 'z', value2: 'BUTTON_1' },
                9: { value: 'a', value2: 'BUTTON_3' },
                10: { value: 'q', value2: 'LEFT_TOP_SHOULDER' },
                11: { value: 'e', value2: 'RIGHT_TOP_SHOULDER' },
                12: { value: 'tab', value2: 'LEFT_BOTTOM_SHOULDER' },
                13: { value: 'r', value2: 'RIGHT_BOTTOM_SHOULDER' }
            },
            1: {
                0: { value: 'o', value2: 'BUTTON_2' },
                1: { value: 'p', value2: 'BUTTON_4' },
                2: { value: 'n', value2: 'SELECT' },
                3: { value: 'm', value2: 'START' },
                4: { value: 'i', value2: 'DPAD_UP' },
                5: { value: 'k', value2: 'DPAD_DOWN' },
                6: { value: 'j', value2: 'DPAD_LEFT' },
                7: { value: 'l', value2: 'DPAD_RIGHT' },
                8: { value: 'u', value2: 'BUTTON_1' },
                9: { value: 'y', value2: 'BUTTON_3' },
                10: { value: '7', value2: 'LEFT_TOP_SHOULDER' },
                11: { value: '9', value2: 'RIGHT_TOP_SHOULDER' },
                12: { value: '0', value2: 'LEFT_BOTTOM_SHOULDER' },
                13: { value: '8', value2: 'RIGHT_BOTTOM_SHOULDER' }
            }
        };

        const defaultExtraControls = {
            0: {
                14: { value: '', value2: 'LEFT_STICK' },
                15: { value: '', value2: 'RIGHT_STICK' },
                16: { value: 'h', value2: 'LEFT_STICK_X:+1' },
                17: { value: 'f', value2: 'LEFT_STICK_X:-1' },
                18: { value: 'g', value2: 'LEFT_STICK_Y:+1' },
                19: { value: 't', value2: 'LEFT_STICK_Y:-1' },
                20: { value: 'l', value2: 'RIGHT_STICK_X:+1' },
                21: { value: 'j', value2: 'RIGHT_STICK_X:-1' },
                22: { value: 'k', value2: 'RIGHT_STICK_Y:+1' },
                23: { value: 'i', value2: 'RIGHT_STICK_Y:-1' },
                24: { value: '1' },
                25: { value: '2' },
                26: { value: '3' },
                27: { value: 'add' },
                28: { value: 'space' },
                29: { value: 'subtract' }
            },
            1: {
                14: { value: '', value2: 'LEFT_STICK' },
                15: { value: '', value2: 'RIGHT_STICK' },
                16: { value: '', value2: 'LEFT_STICK_X:+1' },
                17: { value: '', value2: 'LEFT_STICK_X:-1' },
                18: { value: '', value2: 'LEFT_STICK_Y:+1' },
                19: { value: '', value2: 'LEFT_STICK_Y:-1' },
                20: { value: '', value2: 'RIGHT_STICK_X:+1' },
                21: { value: '', value2: 'RIGHT_STICK_X:-1' },
                22: { value: '', value2: 'RIGHT_STICK_Y:+1' },
                23: { value: '', value2: 'RIGHT_STICK_Y:-1' },
                24: { value: '' },
                25: { value: '' },
                26: { value: '' },
                27: { value: '' },
                28: { value: '' },
                29: { value: '' }
            }
        };

        function buildEjsControls(controls) {
            const result = [];
            for (let player = 0; player < 4; player++) {
                result[player] = [];
                const user = controls[player] || {};
                const base = defaultControls[player] || {};
                const extra = defaultExtraControls[player] || defaultExtraControls[1] || {};
                for (let id = 0; id < 30; id++) {
                    if (user[id]) {
                        result[player][id] = { ...user[id] };
                    } else if (base[id]) {
                        result[player][id] = { ...base[id] };
                    } else if (extra[id]) {
                        result[player][id] = { ...extra[id] };
                    } else {
                        result[player][id] = { value: '' };
                    }
                }
            }
            return result;
        }

        let currentControls = JSON.parse(JSON.stringify(defaultControls));
        let currentControlsPlayer = 0;
        let awaitingKey = null;

        function openDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);
                request.onerror = () => reject(request.error);
                request.onsuccess = () => resolve(request.result);
                request.onupgradeneeded = (e) => {
                    const db = e.target.result;
                    if (!db.objectStoreNames.contains(ROMS_STORE)) {
                        db.createObjectStore(ROMS_STORE, { keyPath: 'id', autoIncrement: true });
                    }
                    if (!db.objectStoreNames.contains(ROMS_DATA_STORE)) {
                        db.createObjectStore(ROMS_DATA_STORE, { keyPath: 'id' });
                    }
                    if (!db.objectStoreNames.contains(SAVES_STORE)) {
                        const savesStore = db.createObjectStore(SAVES_STORE, { keyPath: 'id', autoIncrement: true });
                        savesStore.createIndex('romId', 'romId', { unique: false });
                    }
                    if (!db.objectStoreNames.contains(BIOS_STORE)) {
                        db.createObjectStore(BIOS_STORE, { keyPath: 'core' });
                    }
                    if (!db.objectStoreNames.contains(SETTINGS_STORE)) {
                        db.createObjectStore(SETTINGS_STORE, { keyPath: 'id' });
                    }

                    if (e.oldVersion < 5 && db.objectStoreNames.contains(ROMS_STORE) && db.objectStoreNames.contains(ROMS_DATA_STORE)) {
                        const tx = e.target.transaction;
                        const store = tx.objectStore(ROMS_STORE);
                        const dataStore = tx.objectStore(ROMS_DATA_STORE);
                        const cursorReq = store.openCursor();
                        cursorReq.onsuccess = (ev) => {
                            const cursor = ev.target.result;
                            if (!cursor) return;
                            const rec = cursor.value;
                            if (rec.data) {
                                dataStore.put({ id: rec.id, data: rec.data });
                                delete rec.data;
                                cursor.update(rec);
                            }
                            cursor.continue();
                        };
                    }
                };
            });
        }

        function putRecord(storeName, record) {
            return new Promise(async (resolve, reject) => {
                const db = await openDB();
                const tx = db.transaction(storeName, 'readwrite');
                const store = tx.objectStore(storeName);
                const req = store.put(record);
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }

        function addRecord(storeName, record) {
            return new Promise(async (resolve, reject) => {
                const db = await openDB();
                const tx = db.transaction(storeName, 'readwrite');
                const store = tx.objectStore(storeName);
                const req = store.add(record);
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }

        function getRecord(storeName, key) {
            return new Promise(async (resolve, reject) => {
                const db = await openDB();
                const tx = db.transaction(storeName, 'readonly');
                const store = tx.objectStore(storeName);
                const req = store.get(key);
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }

        function deleteRecord(storeName, key) {
            return new Promise(async (resolve, reject) => {
                const db = await openDB();
                const tx = db.transaction(storeName, 'readwrite');
                const store = tx.objectStore(storeName);
                const req = store.delete(key);
                req.onsuccess = () => resolve();
                req.onerror = () => reject(req.error);
            });
        }

        function getAll(storeName) {
            return new Promise(async (resolve, reject) => {
                const db = await openDB();
                const tx = db.transaction(storeName, 'readonly');
                const store = tx.objectStore(storeName);
                const req = store.getAll();
                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }

        function getSavesByRom(romId) {
            return new Promise(async (resolve, reject) => {
                const db = await openDB();
                const tx = db.transaction(SAVES_STORE, 'readonly');
                const store = tx.objectStore(SAVES_STORE);
                const idx = store.index('romId');
                const req = idx.getAll(romId);
                req.onsuccess = () => resolve(req.result.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt)));
                req.onerror = () => reject(req.error);
            });
        }

        async function saveRom(name, core, buffer) {
            const id = await addRecord(ROMS_STORE, { name, core, size: buffer.byteLength, createdAt: new Date().toISOString() });
            await putRecord(ROMS_DATA_STORE, { id, data: buffer });
            return id;
        }

        async function updateRom(id, updates) {
            const rom = await getRecord(ROMS_STORE, id);
            if (!rom) throw new Error('ROM no encontrada.');
            return putRecord(ROMS_STORE, { ...rom, ...updates });
        }

        async function getAllRoms() {
            const localRoms = await getAll(ROMS_STORE);
            const roms = [...serverRoms, ...localRoms];
            return roms.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
        }

        async function getRomFull(id) {
            const meta = await getRecord(ROMS_STORE, id);
            if (!meta) return null;
            const dataRec = await getRecord(ROMS_DATA_STORE, id);
            return { ...meta, data: dataRec ? dataRec.data : null };
        }

        async function deleteRom(id) {
            await deleteRecord(ROMS_STORE, id);
            await deleteRecord(ROMS_DATA_STORE, id);
            const saves = await getSavesByRom(id);
            for (const s of saves) await deleteRecord(SAVES_STORE, s.id);
        }

        async function saveBios(core, name, buffer) {
            return putRecord(BIOS_STORE, { core, name, size: buffer.byteLength, data: buffer, createdAt: new Date().toISOString() });
        }

        async function getBios(core) {
            return getRecord(BIOS_STORE, core);
        }

        async function saveGameState(romId, data) {
            return addRecord(SAVES_STORE, { romId, size: data.byteLength, data, createdAt: new Date().toISOString() });
        }

        async function getLatestSave(romId) {
            const saves = await getSavesByRom(romId);
            return saves[0] || null;
        }

        async function loadControls() {
            const setting = await getRecord(SETTINGS_STORE, 'keyboardControls');
            if (setting && setting.value) {
                try {
                    const parsed = JSON.parse(setting.value);
                    // Formato nuevo: {0:{0:{...}, ...}, 1:{...}}
                    if (parsed && parsed[0] && parsed[0][0] && typeof parsed[0][0].value === 'string') {
                        currentControls = parsed;
                    } else if (parsed && typeof parsed[0] === 'object' && typeof parsed[0].value === 'string') {
                        // Formato antiguo plano: migrar a player 0
                        currentControls = { 0: parsed };
                    } else {
                        currentControls = JSON.parse(JSON.stringify(defaultControls));
                    }
                } catch (e) {
                    currentControls = JSON.parse(JSON.stringify(defaultControls));
                }
            }
        }

        async function saveControls() {
            await putRecord(SETTINGS_STORE, { id: 'keyboardControls', value: JSON.stringify(currentControls) });
            if (currentRom) {
                if (controlsReloadTimeout) clearTimeout(controlsReloadTimeout);
                controlsReloadTimeout = setTimeout(() => {
                    launchGame(currentRom);
                    setControlsStatus('Juego reiniciado con los nuevos controles.', false, true);
                }, 800);
            }
        }

        function formatSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function iconEdit() {
            return `<svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>`;
        }

        function iconDelete() {
            return `<svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>`;
        }

        function iconPlay() {
            return `<svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>`;
        }

        function iconSpinner() {
            return `<svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>`;
        }

        function setButtonLoading(btn, loading) {
            if (!btn) return;
            if (loading) {
                btn.disabled = true;
                if (!btn.dataset.original) btn.dataset.original = btn.innerHTML;
                btn.innerHTML = iconSpinner();
            } else {
                btn.disabled = false;
                if (btn.dataset.original) {
                    btn.innerHTML = btn.dataset.original;
                    delete btn.dataset.original;
                }
            }
        }

        let modalResolve = null;
        function showModal({ title, message, bodyHTML = '', confirmText = 'Aceptar', cancelText = 'Cancelar', danger = false, showCancel = true, deleteText = '' }) {
            return new Promise((resolve) => {
                modalResolve = resolve;
                modalTitle.textContent = title || '';
                modalMessage.textContent = message || '';
                modalMessage.style.display = message ? 'block' : 'none';
                modalBody.innerHTML = bodyHTML;
                modalBody.style.display = bodyHTML ? 'block' : 'none';
                modalConfirm.textContent = confirmText;
                modalConfirm.className = danger ? 'danger' : 'success';
                modalConfirm.disabled = false;
                modalDelete.disabled = false;
                modalCancel.disabled = false;
                modalCancel.style.display = showCancel ? 'inline-flex' : 'none';
                if (deleteText) {
                    modalDelete.textContent = deleteText;
                    modalDelete.style.display = 'inline-flex';
                } else {
                    modalDelete.style.display = 'none';
                }
                modalOverlay.classList.add('active');
            });
        }

        function showAlert(message, title = 'Aviso') {
            return showModal({ title, message, confirmText: 'Aceptar', showCancel: false });
        }

        function showConfirm(message, title = 'Confirmar') {
            return showModal({ title, message, confirmText: 'Eliminar', cancelText: 'Cancelar', danger: true });
        }

        function closeModal(confirmed) {
            modalOverlay.classList.remove('active');
            if (modalResolve) {
                modalResolve(confirmed);
                modalResolve = null;
            }
        }

        modalCancel.addEventListener('click', () => closeModal(false));
        modalConfirm.addEventListener('click', () => closeModal(true));
        modalDelete.addEventListener('click', () => closeModal('delete'));
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) closeModal(false);
        });

        function arrayBufferToBase64(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            const len = bytes.byteLength;
            for (let i = 0; i < len; i++) {
                binary += String.fromCharCode(bytes[i]);
            }
            return window.btoa(binary);
        }

        function base64ToArrayBuffer(base64) {
            const binary = window.atob(base64);
            const len = binary.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) {
                bytes[i] = binary.charCodeAt(i);
            }
            return bytes.buffer;
        }

        function eventKeyToEjs(e) {
            const map = {
                'ArrowUp': 'up arrow', 'ArrowDown': 'down arrow', 'ArrowLeft': 'left arrow', 'ArrowRight': 'right arrow',
                'Enter': 'enter', 'Shift': 'shift', 'Control': 'ctrl', 'Alt': 'alt',
                ' ': 'space', 'Spacebar': 'space', 'Tab': 'tab', 'Escape': 'esc',
                'Backspace': 'backspace', 'Delete': 'delete', 'Home': 'home', 'End': 'end',
                'PageUp': 'pageup', 'PageDown': 'pagedown', 'Insert': 'insert'
            };
            if (map[e.key]) return map[e.key];
            if (e.key && e.key.length === 1) return e.key.toLowerCase();
            if (e.code && e.code.startsWith('Digit')) return e.code.replace('Digit', '');
            if (e.code && e.code.startsWith('Key')) return e.code.replace('Key', '').toLowerCase();
            if (e.code && e.code.startsWith('Numpad')) return e.code.replace('Numpad', '');
            if (e.key) return e.key.toLowerCase();
            return null;
        }

        function displayKey(value) {
            const map = {
                'up arrow': '↑', 'down arrow': '↓', 'left arrow': '←', 'right arrow': '→',
                'enter': 'Enter', 'shift': 'Shift', 'ctrl': 'Ctrl', 'alt': 'Alt',
                'space': 'Espacio', 'tab': 'Tab', 'esc': 'Esc'
            };
            return map[value] || value.toUpperCase();
        }

        function isKeyUsed(player, excludeId, key) {
            const playerControls = currentControls[player] || {};
            const fallback = defaultControls[player] || {};
            for (let i = 0; i <= 13; i++) {
                if (i === excludeId) continue;
                const cfg = playerControls[i] || fallback[i];
                if (cfg && cfg.value === key) return i;
            }
            return -1;
        }

        function showLoadingOverlay(rom) {
            loadingGameName.textContent = displayRomName(rom.name);
            loadingGamePlatform.textContent = coreLabel(rom.core);
            if (rom.image) {
                loadingGameImage.src = rom.image;
                loadingGameImage.classList.remove('hidden');
            } else {
                loadingGameImage.src = '';
                loadingGameImage.classList.add('hidden');
            }
            loadingOverlay.classList.add('active');
        }

        function hideLoadingOverlay() {
            loadingOverlay.classList.remove('active');
            loadingGameImage.src = '';
            loadingGameImage.classList.add('hidden');
        }

        async function exportLibrary() {
            try {
                btnExport.disabled = true;
                btnExport.textContent = 'Exportando...';
                const roms = await getAll(ROMS_STORE);
                const saves = await getAll(SAVES_STORE);
                const bios = await getAll(BIOS_STORE);
                const settings = await getAll(SETTINGS_STORE);

                const zip = new JSZip();
                const metadata = {
                    version: 2,
                    exportedAt: new Date().toISOString(),
                    roms: roms.map(r => ({ id: r.id, name: r.name, core: r.core, size: r.size, createdAt: r.createdAt, image: r.image || '', file: `roms/${r.id}.bin` })),
                    saves: saves.map(s => ({ id: s.id, romId: s.romId, size: s.size, createdAt: s.createdAt, file: `saves/${s.id}.bin` })),
                    bios: bios.map(b => ({ core: b.core, name: b.name, size: b.size, createdAt: b.createdAt, file: `bios/${b.core}.bin` })),
                    settings
                };

                zip.file('metadata.json', JSON.stringify(metadata, null, 2));
                for (const r of roms) {
                    const dataRec = await getRecord(ROMS_DATA_STORE, r.id);
                    zip.file(`roms/${r.id}.bin`, new Blob([dataRec ? dataRec.data : new ArrayBuffer(0)]));
                }
                for (const s of saves) zip.file(`saves/${s.id}.bin`, new Blob([s.data]));
                for (const b of bios) zip.file(`bios/${b.core}.bin`, new Blob([b.data]));

                const blob = await zip.generateAsync({ type: 'blob' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `consola-web-backup-${new Date().toISOString().slice(0, 10)}.zip`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                URL.revokeObjectURL(url);
            } catch (err) {
                console.error(err);
                await showAlert('Error al exportar: ' + err.message, 'Error');
            } finally {
                btnExport.disabled = false;
                btnExport.textContent = 'Exportar';
            }
        }

        async function importLibrary(file) {
            try {
                btnImport.disabled = true;
                btnImport.textContent = 'Importando...';
                importStatus.textContent = 'Leyendo archivo zip...';
                importStatus.className = 'status';
                const zip = await JSZip.loadAsync(file);
                const metaFile = zip.file('metadata.json');
                if (!metaFile) throw new Error('Archivo no válido: falta metadata.json');
                const metaText = await metaFile.async('text');
                const metadata = JSON.parse(metaText);
                if (!metadata || metadata.version !== 2) throw new Error('Formato de backup no compatible.');

                importStatus.textContent = 'Importando ROMs...';
                if (metadata.roms) {
                    for (const r of metadata.roms) {
                        const bin = await zip.file(r.file).async('uint8array');
                        await putRecord(ROMS_STORE, { id: r.id, name: r.name, core: r.core, size: bin.byteLength, createdAt: r.createdAt, image: r.image || '' });
                        await putRecord(ROMS_DATA_STORE, { id: r.id, data: bin.buffer });
                    }
                }
                importStatus.textContent = 'Importando partidas...';
                if (metadata.saves) {
                    for (const s of metadata.saves) {
                        const bin = await zip.file(s.file).async('uint8array');
                        await putRecord(SAVES_STORE, { id: s.id, romId: s.romId, size: bin.byteLength, data: bin.buffer, createdAt: s.createdAt });
                    }
                }
                importStatus.textContent = 'Importando BIOS y ajustes...';
                if (metadata.bios) {
                    for (const b of metadata.bios) {
                        const bin = await zip.file(b.file).async('uint8array');
                        await putRecord(BIOS_STORE, { core: b.core, name: b.name, size: bin.byteLength, data: bin.buffer, createdAt: b.createdAt });
                    }
                }
                if (metadata.settings) {
                    for (const s of metadata.settings) {
                        await putRecord(SETTINGS_STORE, s);
                    }
                }

                await loadControls();
                renderControls();
                await renderLibrary();
                importStatus.textContent = 'Importación completada.';
                importStatus.className = 'status success';
            } catch (err) {
                console.error(err);
                importStatus.textContent = 'Error al importar: ' + err.message;
                importStatus.className = 'status error';
            } finally {
                btnImport.disabled = false;
                btnImport.textContent = 'Importar';
                importFile.value = '';
                if (importStatus.className.includes('success')) {
                    setTimeout(() => { importStatus.textContent = ''; importStatus.className = 'status'; }, 3000);
                }
            }
        }

        const fileInput = document.getElementById('rom-file');
        const coreSelect = document.getElementById('rom-core');
        const saveBtn = document.getElementById('btn-save');
        const statusEl = document.getElementById('save-status');
        const listEl = document.getElementById('rom-list');
        const hostEl = document.getElementById('emulator-host');
        const biosPanel = document.getElementById('bios-panel');
        const biosFile = document.getElementById('bios-file');
        const biosStatus = document.getElementById('bios-status');
        const biosNameEl = document.getElementById('bios-name');
        const saveBiosBtn = document.getElementById('btn-save-bios');
        const savesPanel = document.getElementById('saves-panel');
        const saveList = document.getElementById('save-list');
        const quickSaveBtn = document.getElementById('btn-quick-save');
        const refreshSavesBtn = document.getElementById('btn-refresh-saves');
        const emulatorControls = document.getElementById('emulator-controls');
        const fullscreenBtn = document.getElementById('btn-fullscreen');
        const exitBtn = document.getElementById('btn-exit');
        const controlsList = document.getElementById('controls-list');
        const playerTabs = document.querySelectorAll('.player-tab');
        const resetControlsBtn = document.getElementById('btn-reset-controls');
        const controlsStatus = document.getElementById('controls-status');
        const loadingOverlay = document.getElementById('loading-overlay');
        const loadingGameName = document.getElementById('loading-game-name');
        const loadingGamePlatform = document.getElementById('loading-game-platform');
        const btnExport = document.getElementById('btn-export');
        const btnImport = document.getElementById('btn-import');
        const importFile = document.getElementById('import-file');
        const importStatus = document.getElementById('import-status');
        const romFilter = document.getElementById('rom-filter');
        const filterSlider = document.getElementById('filter-slider');
        const librarySlider = document.getElementById('library-slider');
        const romListScroll = document.getElementById('rom-list-scroll');
        const loadingGameImage = document.getElementById('loading-game-image');

        let currentRomId = null;
        let filteredRoms = [];
        let currentFilterTerm = '';
        let visibleRomCount = 2;
        let currentRom = null;
        let emulatorFrame = null;
        let emulatorReady = false;
        let controlsReloadTimeout = null;
        let serverRoms = [];

        const coreByExtension = {
            nes: 'nestopia',
            smc: 'snes9x', sfc: 'snes9x', fig: 'snes9x',
            gb: 'mgba', gbc: 'mgba', gba: 'mgba',
            gen: 'picodrive', md: 'picodrive',
            n64: 'n64', z64: 'n64', v64: 'n64',
            nds: 'desmuME',
            cue: 'pcsx_rearmed', chd: 'pcsx_rearmed', pbp: 'pcsx_rearmed'
        };

        function coreRequiresBios(core) {
            return core === 'pcsx_rearmed';
        }

        async function isServerBiosAvailable(core) {
            const candidates = serverBiosFiles[core];
            if (!candidates) return false;
            const list = Array.isArray(candidates) ? candidates : [candidates];
            for (const name of list) {
                try {
                    const res = await fetch(biosBaseUrl + '/' + name, { method: 'HEAD' });
                    if (res.ok) return true;
                } catch (e) {
                    // ignorar errores de red
                }
            }
            return false;
        }

        function coreLabel(core) {
            const labels = {
                nestopia: 'NES',
                snes9x: 'SNES',
                mgba: 'Game Boy / Advance',
                picodrive: 'Sega Genesis',
                n64: 'Nintendo 64',
                desmuME: 'Nintendo DS',
                pcsx_rearmed: 'PlayStation 1'
            };
            return labels[core] || core;
        }

        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) return;
            const ext = file.name.split('.').pop().toLowerCase();
            if (coreByExtension[ext]) {
                coreSelect.value = coreByExtension[ext];
            }
            updateBiosPanel();
        });

        coreSelect.addEventListener('change', updateBiosPanel);

        async function updateBiosPanel() {
            const core = coreSelect.value;
            if (!coreRequiresBios(core)) {
                biosPanel.classList.add('hidden');
                return;
            }
            biosPanel.classList.remove('hidden');
            biosNameEl.textContent = core === 'pcsx_rearmed' ? 'scph5501.bin (recomendado) / scph1001.bin' : 'BIOS';
            const bios = await getBios(core).catch(() => null);
            const serverAvailable = await isServerBiosAvailable(core);
            if (bios) {
                biosStatus.textContent = `BIOS guardada: ${escapeHtml(bios.name)} (${formatSize(bios.size)})`;
                biosStatus.className = 'bios-status ok';
            } else if (serverAvailable) {
                biosStatus.textContent = 'BIOS disponible en el servidor; se usará automáticamente al jugar.';
                biosStatus.className = 'bios-status ok';
            } else {
                biosStatus.textContent = 'No se ha guardado BIOS para esta consola.';
                biosStatus.className = 'bios-status missing';
            }
        }

        saveBiosBtn.addEventListener('click', async () => {
            const file = biosFile.files[0];
            if (!file) {
                setStatus('Selecciona un archivo BIOS.', true);
                return;
            }
            try {
                const buffer = await file.arrayBuffer();
                await saveBios(coreSelect.value, file.name, buffer);
                biosFile.value = '';
                await updateBiosPanel();
                setStatus('BIOS guardada correctamente.', false, true);
            } catch (err) {
                setStatus('Error al guardar BIOS: ' + err.message, true);
            }
        });

        saveBtn.addEventListener('click', async () => {
            const file = fileInput.files[0];
            if (!file) {
                setStatus('Selecciona un archivo ROM primero.', true);
                return;
            }
            const core = coreSelect.value;
            if (coreRequiresBios(core)) {
                const bios = await getBios(core).catch(() => null);
                const serverAvailable = await isServerBiosAvailable(core);
                if (!bios && !serverAvailable) {
                    setStatus('Esta consola requiere BIOS. Cárgala antes de guardar la ROM.', true);
                    return;
                }
            }

            saveBtn.disabled = true;
            setStatus('Leyendo archivo...');
            try {
                const buffer = await file.arrayBuffer();
                setStatus('Guardando en almacenamiento local...');
                await saveRom(file.name, core, buffer);
                setStatus('ROM guardada correctamente.', false, true);
                fileInput.value = '';
                await renderLibrary();
            } catch (err) {
                console.error(err);
                setStatus('Error al guardar: ' + err.message, true);
            } finally {
                saveBtn.disabled = false;
            }
        });

        function setStatus(msg, isError = false, isSuccess = false) {
            statusEl.textContent = msg;
            statusEl.className = 'status' + (isError ? ' error' : '') + (isSuccess ? ' success' : '');
        }

        function setControlsStatus(msg, isError = false, isSuccess = false) {
            controlsStatus.textContent = msg;
            controlsStatus.className = 'status' + (isError ? ' error' : '') + (isSuccess ? ' success' : '');
        }

        function renderControls() {
            controlsList.innerHTML = '';
            const playerControls = currentControls[currentControlsPlayer] || {};
            const fallback = defaultControls[currentControlsPlayer] || defaultControls[0];
            for (let i = 0; i <= 13; i++) {
                const cfg = playerControls[i] || fallback[i];
                const li = document.createElement('li');
                li.innerHTML = `
                    <span class="action">${controlNames[i]}</span>
                    <button class="key-input" id="key-${i}" data-id="${i}" type="button">${escapeHtml(displayKey(cfg.value))}</button>
                `;
                controlsList.appendChild(li);
            }
        }

        function cancelKeyCapture() {
            if (!awaitingKey) return;
            const playerControls = currentControls[awaitingKey.player] || {};
            const fallback = defaultControls[awaitingKey.player] || defaultControls[0];
            const cfg = playerControls[awaitingKey.id] || fallback[awaitingKey.id];
            awaitingKey.btn.textContent = displayKey(cfg.value);
            awaitingKey.btn.classList.remove('waiting');
            awaitingKey = null;
            setControlsStatus('Cambio cancelado.');
        }

        function startKeyCapture(btn) {
            if (awaitingKey) cancelKeyCapture();
            const id = Number(btn.dataset.id);
            awaitingKey = { id, player: currentControlsPlayer, btn };
            btn.textContent = 'Presiona una tecla...';
            btn.classList.add('waiting');
            window.focus();
            setControlsStatus('Presiona la tecla que quieres asignar...');
        }

        controlsList.addEventListener('click', (e) => {
            const btn = e.target.closest('.key-input');
            if (!btn) return;
            startKeyCapture(btn);
        });

        playerTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                currentControlsPlayer = Number(tab.dataset.player);
                playerTabs.forEach(t => t.classList.toggle('active', t === tab));
                renderControls();
            });
        });

        window.addEventListener('keydown', (e) => {
            if (!awaitingKey) return;
            e.preventDefault();

            if (e.key === 'Escape') {
                cancelKeyCapture();
                return;
            }

            const mapped = eventKeyToEjs(e);
            if (!mapped) {
                setControlsStatus('Tecla no reconocida. Prueba con otra.', true);
                return;
            }

            const usedBy = isKeyUsed(awaitingKey.player, awaitingKey.id, mapped);
            if (usedBy !== -1) {
                setControlsStatus(`La tecla "${displayKey(mapped)}" ya está asignada a ${controlNames[usedBy]} en el jugador ${awaitingKey.player + 1}.`, true);
                return;
            }

            if (!currentControls[awaitingKey.player]) {
                currentControls[awaitingKey.player] = {};
            }
            currentControls[awaitingKey.player][awaitingKey.id] = {
                value: mapped,
                value2: (defaultControls[awaitingKey.player]?.[awaitingKey.id] || defaultControls[0][awaitingKey.id]).value2
            };
            awaitingKey.btn.textContent = displayKey(mapped);
            awaitingKey.btn.classList.remove('waiting');
            const id = awaitingKey.id;
            const player = awaitingKey.player;
            awaitingKey = null;
            saveControls();
            setControlsStatus(`"${controlNames[id]}" ahora usa ${displayKey(mapped)} (Jugador ${player + 1}).`, false, true);
        });

        resetControlsBtn.addEventListener('click', async () => {
            currentControls[currentControlsPlayer] = JSON.parse(JSON.stringify(defaultControls[currentControlsPlayer] || defaultControls[0]));
            await saveControls();
            renderControls();
            setControlsStatus(`Valores por defecto restaurados para el Jugador ${currentControlsPlayer + 1}.`, false, true);
        });

        function displayRomName(name) {
            return name.replace(/\.[^/.]+$/, '');
        }

        function renderVisibleRoms() {
            listEl.innerHTML = '';
            const roms = filteredRoms.slice(0, visibleRomCount);

            if (filteredRoms.length === 0) {
                listEl.innerHTML = currentFilterTerm.length >= 3
                    ? '<li class="empty">Ninguna ROM coincide con el filtro.</li>'
                    : '<li class="empty">Todavía no has guardado ninguna ROM.</li>';
                return;
            }

            roms.forEach((rom) => {
                const thumbHTML = rom.image
                    ? `<div class="thumb"><img src="${escapeHtml(rom.image)}" alt=""></div>`
                    : '';
                const sourceLabel = rom.server ? 'Servidor' : 'Local';
                const editButton = rom.server
                    ? ''
                    : `<button class="edit icon-btn rom-edit-btn secondary" data-id="${rom.id}" title="Editar">${iconEdit()}</button>`;
                const li = document.createElement('li');
                li.innerHTML = `
                    ${thumbHTML}
                    <div class="info">
                        <div class="name">${escapeHtml(displayRomName(rom.name))}</div>
                        <div class="meta">${coreLabel(rom.core)} · ${formatSize(rom.size)} · ${sourceLabel} · ${new Date(rom.createdAt).toLocaleString()}</div>
                    </div>
                    <div class="actions">
                        <button class="play icon-btn rom-play-btn" data-id="${rom.id}" title="Jugar">${iconPlay()}</button>
                    </div>
                    ${editButton}
                `;
                listEl.appendChild(li);
            });
        }

        async function renderLibrary(filterText = romFilter.value) {
            listEl.innerHTML = '';
            librarySlider.classList.add('active');
            const start = Date.now();
            let roms = [];
            try {
                roms = await getAllRoms();
            } catch (err) {
                console.error(err);
                listEl.innerHTML = '<li class="empty">No se pudo acceder al almacenamiento local.</li>';
                librarySlider.classList.remove('active');
                return;
            }

            const term = filterText.trim().toLowerCase();
            if (term.length >= 3) {
                roms = roms.filter(r =>
                    displayRomName(r.name).toLowerCase().includes(term) ||
                    coreLabel(r.core).toLowerCase().includes(term)
                );
            }

            filteredRoms = roms;
            currentFilterTerm = term;
            visibleRomCount = 2;

            const elapsed = Date.now() - start;
            const remaining = Math.max(0, 300 - elapsed);
            setTimeout(() => {
                librarySlider.classList.remove('active');
                renderVisibleRoms();
            }, remaining);
        }

        listEl.addEventListener('click', async (e) => {
            const target = e.target.closest('button');
            if (!target) return;
            const rawId = target.dataset.id;
            const id = rawId.startsWith('server:') ? rawId : Number(rawId);
            if (target.classList.contains('edit')) {
                if (target.disabled || String(id).startsWith('server:')) return;
                await editRom(id, target);
                return;
            }
            if (target.classList.contains('delete')) {
                if (String(id).startsWith('server:')) return;
                const confirmed = await showConfirm('¿Eliminar esta ROM y todas sus partidas guardadas?', 'Eliminar ROM');
                if (!confirmed) return;
                await deleteRom(id);
                if (currentRomId === id) exitGame();
                await renderLibrary();
                return;
            }
            if (target.classList.contains('play')) {
                if (target.disabled) return;
                const rom = filteredRoms.find(r => r.id === id);
                if (rom) {
                    setButtonLoading(target, true);
                    await launchGame(rom);
                }
            }
        });

        async function editRom(id, btnEdit) {
            setButtonLoading(btnEdit, true);

            let rom;
            try {
                rom = await getRecord(ROMS_STORE, id);
            } catch (err) {
                setButtonLoading(btnEdit, false);
                await showAlert('Error al cargar la ROM: ' + err.message, 'Error');
                return;
            }
            if (!rom) {
                setButtonLoading(btnEdit, false);
                return;
            }

            const ext = rom.name.includes('.') ? rom.name.split('.').pop() : '';
            const currentName = displayRomName(rom.name);
            let newImage = rom.image || '';

            const bodyHTML = `
                <label for="edit-rom-name">Nombre</label>
                <input id="edit-rom-name" type="text" value="${escapeHtml(currentName)}">
                <label for="edit-rom-image">Imagen (opcional)</label>
                <input id="edit-rom-image" type="file" accept="image/*">
                ${newImage ? `<img id="edit-rom-preview" class="modal-preview" src="${escapeHtml(newImage)}" alt="">` : ''}
            `;

            const result = await showModal({
                title: 'Editar ROM',
                message: '',
                bodyHTML,
                confirmText: 'Guardar',
                cancelText: 'Cancelar',
                danger: false,
                deleteText: 'Eliminar ROM'
            });

            if (result === 'delete') {
                setButtonLoading(btnEdit, false);
                const confirmed = await showConfirm('¿Eliminar esta ROM y todas sus partidas guardadas?', 'Eliminar ROM');
                if (!confirmed) return;
                setButtonLoading(btnEdit, true);
                await deleteRom(id);
                if (currentRomId === id) exitGame();
                await renderLibrary();
                setButtonLoading(btnEdit, false);
                return;
            }

            if (result !== true) {
                setButtonLoading(btnEdit, false);
                return;
            }

            modalConfirm.disabled = true;
            modalDelete.disabled = true;
            modalCancel.disabled = true;
            modalConfirm.dataset.originalText = modalConfirm.textContent;
            modalConfirm.innerHTML = `<div class="btn-slider"><div class="slider-bar"></div></div>`;

            try {
                const nameInput = document.getElementById('edit-rom-name');
                const imageInput = document.getElementById('edit-rom-image');
                let newName = nameInput.value.trim();
                if (!newName) throw new Error('El nombre no puede estar vacío.');

                if (ext) newName += '.' + ext;

                if (imageInput.files && imageInput.files[0]) {
                    newImage = await fileToDataURL(imageInput.files[0]);
                }

                await updateRom(id, { name: newName, image: newImage });
                if (currentRomId === id && currentRom) {
                    currentRom.name = newName;
                    currentRom.image = newImage;
                }
                closeModal(true);
                await renderLibrary();
            } catch (err) {
                await showAlert(err.message || 'Error al guardar la ROM.', 'Error');
            } finally {
                modalConfirm.disabled = false;
                modalDelete.disabled = false;
                modalCancel.disabled = false;
                if (modalConfirm.dataset.originalText) {
                    modalConfirm.textContent = modalConfirm.dataset.originalText;
                    delete modalConfirm.dataset.originalText;
                }
                setButtonLoading(btnEdit, false);
            }
        }

        function fileToDataURL(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => reject(reader.error);
                reader.readAsDataURL(file);
            });
        }

        async function launchGame(rom) {
            currentRom = rom;
            currentRomId = rom.id;
            emulatorReady = false;

            showLoadingOverlay(rom);

            let gameUrl;
            if (rom.server) {
                gameUrl = rom.url;
            } else {
                const fullRom = await getRomFull(rom.id);
                if (!fullRom || !fullRom.data) {
                    hideLoadingOverlay();
                    await showAlert('No se pudo cargar la ROM.', 'Error');
                    restorePlayButtons();
                    return;
                }
                const gameBlob = new Blob([fullRom.data]);
                gameUrl = URL.createObjectURL(gameBlob);
            }

            let biosUrl = '';
            if (coreRequiresBios(rom.core)) {
                let bios = await getBios(rom.core).catch(() => null);
                const candidates = serverBiosFiles[rom.core] || [];
                if (!bios && candidates.length) {
                    for (const biosName of candidates) {
                        try {
                            const serverBiosUrl = biosBaseUrl + '/' + biosName;
                            const headRes = await fetch(serverBiosUrl, { method: 'HEAD' });
                            if (!headRes.ok) continue;
                            const res = await fetch(serverBiosUrl);
                            if (res.ok) {
                                const buffer = await res.arrayBuffer();
                                await saveBios(rom.core, biosName, buffer);
                                bios = await getBios(rom.core);
                                break;
                            }
                        } catch (err) {
                            console.error('Error descargando BIOS del servidor:', err);
                        }
                    }
                }
                if (bios) {
                    biosUrl = URL.createObjectURL(new Blob([bios.data]));
                } else if (candidates.length) {
                    biosUrl = biosBaseUrl + '/' + candidates[0];
                }
            }

            const latestSave = await getLatestSave(rom.id);
            let loadStateUrl = '';
            if (latestSave) {
                loadStateUrl = URL.createObjectURL(new Blob([latestSave.data]));
            }

            hostEl.innerHTML = `<iframe id="emulator-frame" src="{{ url('/play') }}" allow="fullscreen; gamepad" title="Emulador"></iframe>`;
            emulatorFrame = document.getElementById('emulator-frame');
            emulatorControls.style.display = 'flex';

            const controlsForEjs = buildEjsControls(currentControls);

            const initListener = (e) => {
                if (!e.data || e.data.type !== 'playReady') return;
                window.removeEventListener('message', initListener);
                if (!emulatorFrame) return;
                emulatorFrame.contentWindow.postMessage({
                    type: 'init',
                    rom: { id: rom.id, name: rom.name, core: rom.core, url: gameUrl },
                    biosUrl,
                    loadStateUrl,
                    controls: controlsForEjs,
                    dataPath
                }, '*');
            };
            window.addEventListener('message', initListener);
            await renderSaves();
        }

        window.addEventListener('message', async (e) => {
            if (!e.data || !e.data.type) return;
            if (e.data.type === 'ejsReady') {
                emulatorReady = true;
                restorePlayButtons();
                hideLoadingOverlay();
            } else if (e.data.type === 'ejsSaveState') {
                try {
                    const buffer = base64ToArrayBuffer(e.data.data);
                    await saveGameState(e.data.romId, buffer);
                    if (e.data.romId === currentRomId) await renderSaves();
                } catch (err) {
                    console.error('Error guardando partida:', err);
                }
            }
        });

        quickSaveBtn.addEventListener('click', () => {
            if (!emulatorFrame || !emulatorReady) return;
            emulatorFrame.contentWindow.postMessage({ type: 'saveState' }, '*');
        });

        refreshSavesBtn.addEventListener('click', renderSaves);

        async function renderSaves() {
            saveList.innerHTML = '';
            if (!currentRomId) {
                saveList.innerHTML = '<li class="empty">Inicia un juego para ver sus partidas guardadas.</li>';
                return;
            }
            const saves = await getSavesByRom(currentRomId).catch(() => []);
            if (saves.length === 0) {
                saveList.innerHTML = '<li class="empty">No hay partidas guardadas.</li>';
                return;
            }
            saves.forEach((save) => {
                const li = document.createElement('li');
                li.innerHTML = `
                    <div class="info">
                        <div class="name">Partida #${save.id}</div>
                        <div class="meta">${formatSize(save.size)} · ${new Date(save.createdAt).toLocaleString()}</div>
                    </div>
                    <div class="actions">
                        <button class="load-save small" data-id="${save.id}">Cargar</button>
                        <button class="delete-save danger small" data-id="${save.id}">Borrar</button>
                    </div>
                `;
                saveList.appendChild(li);
            });
        }

        saveList.addEventListener('click', async (e) => {
            const target = e.target.closest('button');
            if (!target) return;
            const id = Number(target.dataset.id);
            if (target.classList.contains('delete-save')) {
                if (target.disabled) return;
                setButtonLoading(target, true);
                const confirmed = await showConfirm('¿Eliminar esta partida guardada?', 'Eliminar partida');
                if (!confirmed) {
                    setButtonLoading(target, false);
                    return;
                }
                await deleteRecord(SAVES_STORE, id);
                await renderSaves();
                setButtonLoading(target, false);
                return;
            }
            if (target.classList.contains('load-save')) {
                const saves = await getSavesByRom(currentRomId);
                const save = saves.find(s => s.id === id);
                if (save && emulatorFrame) {
                    emulatorFrame.contentWindow.postMessage({ type: 'loadState', data: arrayBufferToBase64(save.data) }, '*');
                }
            }
        });

        function restorePlayButtons() {
            document.querySelectorAll('#rom-list .play').forEach(btn => {
                setButtonLoading(btn, false);
            });
        }

        function exitGame() {
            if (controlsReloadTimeout) {
                clearTimeout(controlsReloadTimeout);
                controlsReloadTimeout = null;
            }
            currentRom = null;
            hostEl.innerHTML = `
                <div id="placeholder">
                    <strong>Ninguna ROM en ejecución</strong>
                    <span>Selecciona un juego de tu biblioteca local para comenzar.</span>
                </div>
            `;
            emulatorFrame = null;
            emulatorReady = false;
            currentRomId = null;
            currentRom = null;
            emulatorControls.style.display = 'none';
            renderSaves();
            restorePlayButtons();
        }

        exitBtn.addEventListener('click', async () => {
            const confirmed = await showConfirm('¿Salir del juego actual?', 'Salir del juego');
            if (confirmed) exitGame();
        });

        fullscreenBtn.addEventListener('click', () => {
            if (!emulatorFrame) return;
            if (emulatorFrame.requestFullscreen) {
                emulatorFrame.requestFullscreen().catch(err => console.error('Fullscreen error:', err));
            } else if (emulatorFrame.webkitRequestFullscreen) {
                emulatorFrame.webkitRequestFullscreen();
            }
        });

        btnExport.addEventListener('click', exportLibrary);
        btnImport.addEventListener('click', () => importFile.click());
        importFile.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) importLibrary(file);
        });
        romFilter.addEventListener('input', () => {
            const value = romFilter.value.trim();
            if (value.length > 0 && value.length < 3) {
                filterSlider.classList.add('hidden');
                return;
            }
            if (value.length >= 3) {
                filterSlider.classList.remove('hidden');
                clearTimeout(window.filterSliderTimeout);
                window.filterSliderTimeout = setTimeout(() => {
                    filterSlider.classList.add('hidden');
                }, 400);
            } else {
                filterSlider.classList.add('hidden');
            }
            renderLibrary(value);
        });
        romListScroll.addEventListener('scroll', () => {
            if (visibleRomCount >= filteredRoms.length) return;
            const nearBottom = romListScroll.scrollTop + romListScroll.clientHeight >= romListScroll.scrollHeight - 12;
            if (nearBottom) {
                visibleRomCount += 2;
                renderVisibleRoms();
            }
        });

        function checkEnvironment() {
            const el = document.getElementById('env-warning');
            if (!el) return;
            if (!window.isSecureContext) {
                el.textContent = 'Para máximo rendimiento usa https://consola-web.test o http://127.0.0.1:8000 (contexto seguro requerido para threads).';
                el.classList.remove('hidden');
            } else if (!window.crossOriginIsolated) {
                el.textContent = 'El aislamiento cross-origin no está activo; algunos cores pueden usar modo legacy.';
                el.classList.remove('hidden');
            }
        }

        async function loadServerRoms() {
            try {
                const res = await fetch('{{ url('/api/roms') }}');
                if (!res.ok) return;
                const roms = await res.json();
                serverRoms = roms.map(r => ({
                    ...r,
                    id: 'server:' + r.id,
                    server: true,
                    createdAt: r.createdAt || new Date().toISOString()
                }));
                if (serverRoms.length > 0) {
                    const uploadPanel = document.getElementById('rom-upload-panel');
                    if (uploadPanel) uploadPanel.classList.add('hidden');
                }
            } catch (err) {
                console.error('Error cargando ROMs del servidor:', err);
            }
        }

        async function init() {
            await loadServerRoms();
            await loadControls();
            renderControls();
            renderLibrary();
            updateBiosPanel();
            checkEnvironment();
        }

        init();
    </script>
</body>
</html>
