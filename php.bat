@echo off
setlocal

set "PHP_BIN=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64"

if not exist "%PHP_BIN%\php.exe" (
    echo.
    echo [ERROR] PHP 8.3 no encontrado en %PHP_BIN%
    echo Laragon: Menu -^> PHP -^> Version -^> 8.3.30
    echo.
    exit /b 1
)

"%PHP_BIN%\php.exe" %*

endlocal
