<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emulador</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: #000;
            overflow: hidden;
        }
        #game {
            width: 100%;
            height: 100vh;
        }
        #error {
            color: #ef4444;
            font-family: system-ui, sans-serif;
            padding: 1rem;
            text-align: center;
            display: none;
        }
    </style>
</head>
<body>
    <div id="game"></div>
    <div id="error"></div>

    <script>
        function arrayBufferToBase64(buffer) {
            const bytes = new Uint8Array(buffer);
            let binary = '';
            for (let i = 0; i < bytes.byteLength; i++) binary += String.fromCharCode(bytes[i]);
            return window.btoa(binary);
        }

        function base64ToArrayBuffer(base64) {
            const binary = window.atob(base64);
            const len = binary.length;
            const bytes = new Uint8Array(len);
            for (let i = 0; i < len; i++) bytes[i] = binary.charCodeAt(i);
            return bytes.buffer;
        }

        function showError(msg) {
            const err = document.getElementById('error');
            err.textContent = msg;
            err.style.display = 'block';
            console.error(msg);
        }

        let initialized = false;

        window.addEventListener('message', (e) => {
            if (initialized || !e.data) return;

            if (e.data.type === 'init') {
                initialized = true;
                const { rom, biosUrl, loadStateUrl, controls, dataPath } = e.data;

                const canThread = window.crossOriginIsolated && typeof SharedArrayBuffer !== 'undefined';
                const useLegacy = !canThread;

                window.EJS_player = '#game';
                window.EJS_gameUrl = rom.url;
                window.EJS_core = rom.core;
                window.EJS_gameName = rom.name;
                window.EJS_gameID = rom.id;
                window.EJS_pathtodata = dataPath;
                window.EJS_language = 'es-ES';
                window.EJS_disableAutoLang = false;
                window.EJS_startOnLoaded = true;
                window.EJS_disableLocalStorage = true;
                window.EJS_cacheConfig = { enabled: true, cacheMaxSizeMB: 4096, cacheMaxAgeMins: 7200 };
                window.EJS_threads = canThread;
                window.EJS_forceLegacyCores = useLegacy;
                window.EJS_defaultControls = controls;
                window.EJS_volume = 1;
                window.EJS_defaultOptions = { 'audio_latency': '128' };

                if (biosUrl) window.EJS_biosUrl = biosUrl;
                if (loadStateUrl) window.EJS_loadStateURL = loadStateUrl;

                window.EJS_ready = () => {
                    window.parent.postMessage({ type: 'ejsReady', romId: rom.id }, '*');
                };

                window.EJS_onSaveState = (data) => {
                    const state = data[1];
                    const buffer = state instanceof ArrayBuffer
                        ? state
                        : state.buffer.slice(state.byteOffset, state.byteOffset + state.byteLength);
                    window.parent.postMessage({ type: 'ejsSaveState', romId: rom.id, data: arrayBufferToBase64(buffer) }, '*');
                };

                const script = document.createElement('script');
                script.src = dataPath + 'loader.js';
                script.onerror = () => showError('No se pudo cargar EmulatorJS. Revisa la consola.');
                document.head.appendChild(script);
            }
        });

        window.parent.postMessage({ type: 'playReady' }, '*');
    </script>
</body>
</html>
