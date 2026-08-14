# Descarga los cores de EmulatorJS para uso offline.
# Ejecutar desde la raíz del proyecto: .\download-cores.ps1

$ErrorActionPreference = "Stop"

$coresDir = Join-Path $PSScriptRoot "public\emulatorjs\data\cores"
$zipPath = Join-Path $env:TEMP "emulatorjs-cores.zip"

if (-not (Test-Path $coresDir)) {
    New-Item -ItemType Directory -Path $coresDir | Out-Null
}

Write-Host "Descargando cores de EmulatorJS..."
Invoke-WebRequest -Uri "https://cdn.emulatorjs.org/latest/data/cores.zip" -OutFile $zipPath -TimeoutSec 300

Write-Host "Extrayendo cores en $coresDir ..."
Expand-Archive -Path $zipPath -DestinationPath $coresDir -Force

Remove-Item $zipPath -Force
Write-Host "Cores listos."
