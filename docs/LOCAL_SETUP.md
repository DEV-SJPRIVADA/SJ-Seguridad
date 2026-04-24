# Configuracion Local

## Entorno objetivo

- `Laragon`
- `PHP 8.2`
- `MySQL`
- `Node.js` para assets

## Variables clave

```env
APP_URL=http://sjseguridad.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sjseguridad
DB_USERNAME=root
DB_PASSWORD=
```

## Preparacion recomendada

1. Seleccionar `PHP 8.2` en Laragon.
2. Confirmar que `MySQL` este iniciado.
3. Crear la base `sjseguridad` si no existe.
4. Limpiar cache de configuracion.
5. Ejecutar migraciones y seeders.

## Comandos utiles en PowerShell

```powershell
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan config:clear
& "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS sjseguridad CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan migrate --seed
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan app:doctor
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan serve
```

## Comandos de estabilizacion

Si el entorno local pierde el admin semilla, cambia de estado o vuelve a mostrar `auth.failed`, usar:

```powershell
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan app:restore-admin
```

Si ademas quieres limpiar caches y verificar el entorno completo:

```powershell
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" artisan app:stabilize-local
```

## Problemas ya detectados

- Si `php` no existe en el `PATH`, PowerShell no reconoce `php artisan`.
- Si `.env` apunta a `sqlite` y PHP no tiene `pdo_sqlite`, Laravel falla con `could not find driver`.
- Si la base `sjseguridad` no existe, Laravel falla con `Unknown database 'sjseguridad'`.
- Si la tabla `users` queda vacia o el admin semilla no coincide con `.env`, el login devolvera `auth.failed`.
