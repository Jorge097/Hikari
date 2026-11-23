# Arma el paquete para Neubox desde el último commit (no toca tu carpeta de trabajo)
# Uso: powershell -ExecutionPolicy Bypass -File scripts\build-release.ps1
# Resultado: dist\hikari-<commit>.zip con dos carpetas:
#   Hikari\       -> ~/Hikari        (app, vendor sin dev; SIN storage ni .env)
#   public_html\  -> ~/public_html   (index.php, .htaccess, build, assets de Filament)

param(
    [string]$Php = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

# Solo lo que está en git
if (git status --porcelain --untracked-files=no) {
    Write-Warning 'Hay cambios sin commit; el paquete usa el último commit, no tu carpeta.'
}

$sha = (git rev-parse --short HEAD).Trim()
$work = Join-Path $env:TEMP "hikari-release-$sha"
$dist = Join-Path $root 'dist'
$zip = Join-Path $dist "hikari-$sha.zip"

if (Test-Path $work) { Remove-Item -Recurse -Force $work }
New-Item -ItemType Directory -Force $work, $dist | Out-Null

# Exporta el commit
$src = Join-Path $work 'src'
git archive --format=zip -o "$work\src.zip" HEAD
Expand-Archive "$work\src.zip" $src
Remove-Item "$work\src.zip"

Push-Location $src
try {
    # Dependencias PHP de producción (artisan corre sin .env ni DB)
    $env:APP_ENV = 'production'
    $env:CACHE_STORE = 'array'
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = ':memory:'
    $composer = Join-Path (Split-Path (Get-Command composer).Source) 'composer.phar'
    & $Php $composer install --no-dev --optimize-autoloader --no-interaction --no-progress
    if ($LASTEXITCODE) { throw 'composer install falló' }

    # Assets de Vite
    npm ci --no-audit --no-fund
    if ($LASTEXITCODE) { throw 'npm ci falló' }
    npm run build
    if ($LASTEXITCODE) { throw 'npm run build falló' }
}
finally {
    Pop-Location
    Remove-Item Env:APP_ENV, Env:CACHE_STORE, Env:DB_CONNECTION, Env:DB_DATABASE -ErrorAction SilentlyContinue
}

# Arma las dos carpetas
$out = Join-Path $work 'out'
$app = Join-Path $out 'Hikari'
$web = Join-Path $out 'public_html'
New-Item -ItemType Directory -Force $app, $web | Out-Null

Copy-Item "$src\public\*" $web -Recurse -Force
Get-ChildItem -Force $web -Filter 'hot' | Remove-Item -Force

$skip = @('public', 'node_modules', 'tests', 'storage', 'scripts', 'docs', '.env', '.env.example', '.github',
          'phpunit.xml', 'vite.config.js', 'package.json', 'package-lock.json', 'tailwind.config.js', 'postcss.config.js')
Get-ChildItem -Force $src | Where-Object { $skip -notcontains $_.Name } | ForEach-Object {
    Copy-Item $_.FullName $app -Recurse -Force
}

# Sin cachés del build
Get-ChildItem "$app\bootstrap\cache" -Filter '*.php' | Remove-Item -Force

if (Test-Path $zip) { Remove-Item $zip }
Compress-Archive -Path "$out\*" -DestinationPath $zip
Remove-Item -Recurse -Force $work

Write-Host "Paquete listo: $zip"
Write-Host 'No incluye storage/ ni .env: en el servidor se conservan los existentes.'
