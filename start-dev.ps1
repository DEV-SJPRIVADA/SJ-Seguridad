$php = "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe"

if (-not (Test-Path $php)) {
    Write-Error "No se encontro PHP en: $php"
    exit 1
}

Write-Host "Usando PHP:" $php
& $php artisan serve
