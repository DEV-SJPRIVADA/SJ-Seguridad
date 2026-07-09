@echo off
setlocal

rem Asegura Node/npm en PATH (Laragon + instalacion global)
set "PATH=C:\laragon\bin\nodejs\node-v22;C:\laragon\bin\nodejs\node-v20;C:\laragon\bin\nodejs\node-v18;C:\Program Files\nodejs;%PATH%"

cd /d "%~dp0"

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
echo - App:  http://127.0.0.1:8000
echo - Vite: http://localhost:5173
echo.
echo Manten esta ventana abierta. Cierra con Ctrl+C.
echo.

call npm run serve

endlocal
