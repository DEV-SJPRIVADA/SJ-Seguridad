@echo off
setlocal

rem PHP 8.3 (Laravel 13) + Node/npm
set "PHP_BIN=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64"
set "PATH=%PHP_BIN%;C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\nodejs\node-v20;C:\laragon\bin\nodejs\node-v18;C:\Program Files\nodejs;%PATH%"

cd /d "%~dp0"

if not exist "%PHP_BIN%\php.exe" (
    echo.
    echo [ERROR] PHP 8.3 no encontrado en %PHP_BIN%
    echo Instalalo desde Laragon: Menu -^> PHP -^> Version -^> 8.3
    echo.
    pause
    exit /b 1
)

where npm >nul 2>&1
if errorlevel 1 (
    echo.
    echo [ERROR] npm no esta en el PATH.
    echo Instala Node.js desde Laragon ^(Menu -^> Tools -^> Quick add -^> Node.js^)
    echo o desde https://nodejs.org/ y vuelve a abrir la terminal.
    echo.
    pause
    exit /b 1
)

if not exist "node_modules\" (
    echo Instalando dependencias npm...
    call npm install
    if errorlevel 1 exit /b 1
)

echo.
echo Iniciando Laravel + Vite en modo desarrollo...
echo - PHP:  %PHP_BIN%\php.exe
echo - App:  http://127.0.0.1:8000  o  http://sjseguridad.test
echo - Vite: http://localhost:5173
echo.
echo Manten esta ventana abierta. Cierra con Ctrl+C.
echo.

call npx concurrently -k -n laravel,vite -c blue,magenta "%PHP_BIN%\php.exe artisan serve" "npm run dev"

endlocal
