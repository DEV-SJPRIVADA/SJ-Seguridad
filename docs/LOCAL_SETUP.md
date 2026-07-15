# Configuracion Local

## Entorno objetivo

- `Laragon 8.6+`
- `PHP 8.3` (requerido por Laravel 13)
- `MySQL 8`
- `Node.js` + `npm` para assets (Vite)

## PHP en Laragon 8

1. Laragon → **Menu → PHP → Version → PHP 8.3.30** (o superior 8.3.x).
2. Verificar extensiones habilitadas en `C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini`:
   - `pdo_mysql`, `pdo_sqlite`, `sqlite3`, `mbstring`, `openssl`, `fileinfo`, `curl`, `zip`
   - `upload_max_filesize` y `post_max_size` ≥ `10M` (documentos de Calidad)
3. Validar:

```powershell
php -v
php -m | findstr /i "pdo_mysql pdo_sqlite zip"
```

Si `php` no responde en PowerShell, usar la ruta completa o seleccionar PHP 8.3 en Laragon y reiniciar la terminal.

Ruta de referencia:

```text
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe
```

## Variables clave (.env)

```env
APP_URL=http://sjseguridad.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sjseguridad
DB_USERNAME=root
DB_PASSWORD=

CACHE_PREFIX=sj-seguridad-cache-
REDIS_PREFIX=sj-seguridad-database-
SESSION_COOKIE=sj_seguridad_session

# Correo local hacia Mailpit (Laragon)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="noreply@sjseguridad.local"
MAIL_FROM_NAME="${APP_NAME}"

# Para pruebas de mail sin worker: sync. En produccion preferir database/redis.
QUEUE_CONNECTION=sync
```

Los prefijos de cache/sesion evitan invalidar sesiones tras el upgrade a Laravel 13.

## Preparacion recomendada

1. **Start All** en Laragon (Apache + MySQL).
2. Confirmar virtual host `http://sjseguridad.test` apunta a `C:\laragon\www\SJSEGURIDAD`.
3. Crear la base `sjseguridad` si no existe.
4. Instalar dependencias y migrar.

## Comandos utiles en PowerShell

```powershell
cd C:\laragon\www\SJSEGURIDAD

composer install
npm install
npm run build

php artisan config:clear
php artisan migrate --seed
php artisan app:doctor
php artisan test
```

## Correo con Mailpit (Laragon)

Los mailables de Requisiciones y Suministros implementan `ShouldQueue`. Con Mailpit:

1. En Laragon, iniciar **Mailpit** (SMTP en `127.0.0.1:1025`; UI en el panel de Laragon).
2. En `.env`, usar `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025` (ver variables arriba).
3. Cola:
   - **Opcion A (recomendada para pruebas):** `QUEUE_CONNECTION=sync` — el correo se envia al instante.
   - **Opcion B:** `QUEUE_CONNECTION=database` y en otra terminal:

```powershell
php artisan queue:work
```

4. Limpiar config: `php artisan config:clear`.
5. **Requisiciones:** en Parametros → Correos de notificacion, agregar un email de prueba activo. Sin filas activas se usa el fallback hardcoded del controller.
6. Crear una requisicion (o una solicitud de suministros) y abrir la UI de Mailpit para ver el mensaje.
7. Preview HTML sin enviar (solo local + `manage.users`): `GET /mail-preview`.

Si `MAIL_MAILER=log`, el correo se escribe en el log y **no** aparece en Mailpit.

## Desarrollo con Vite (hot reload)

Opcion A — script del proyecto:

```powershell
.\dev.bat
```

Levanta Laravel (`http://127.0.0.1:8000` o `http://sjseguridad.test`) + Vite (`http://localhost:5173`). Mantener la ventana abierta.

Opcion B — manual:

```powershell
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

Si `npm` no se reconoce, agregar Node al PATH del sistema o usar la terminal de Laragon.

## Comandos de estabilizacion

Si el entorno local pierde el admin semilla, cambia de estado o vuelve a mostrar `auth.failed`:

```powershell
php artisan app:restore-admin
```

Si ademas quieres limpiar caches y verificar el entorno completo:

```powershell
php artisan app:stabilize-local
```

## Problemas ya detectados

- **`Composer detected issues: require PHP >= 8.3.0, running 8.2.30`**: Laragon sigue con PHP 8.2 en el PATH. Solucion:
  1. Laragon → **Menu → PHP → Version → 8.3.30**
  2. Cerrar y reabrir la terminal (o Cursor)
  3. Mientras tanto, usar los wrappers del proyecto: `.\artisan test`, `.\php -v` o `.\dev.bat`
- Si `php` no existe en el `PATH`, PowerShell no reconoce `php artisan`. Usar Laragon Menu → PHP → 8.3, `.\artisan` o `dev.bat`.
- Si PHP 8.3 no tiene `pdo_sqlite`/`sqlite3`, `php artisan test` falla con `could not find driver`. Habilitar en `php.ini`.
- Si PHP 8.3 no tiene `zip`, Composer falla al instalar PhpSpreadsheet. Habilitar `extension=zip`.
- Si `.env` apunta a `sqlite` y PHP no tiene `pdo_sqlite`, Laravel falla con `could not find driver`.
- Si la base `sjseguridad` no existe, Laravel falla con `Unknown database 'sjseguridad'`.
- Si la tabla `users` queda vacia o el admin semilla no coincide con `.env`, el login devolvera `auth.failed`.
- Varios `php artisan serve` en el puerto 8000 muestran otro proyecto; detener procesos duplicados o usar `http://sjseguridad.test`.

## Despliegue en Hostinger (checklist)

Antes de subir Laravel 13 a produccion:

1. Confirmar **PHP 8.3+** en el panel de Hostinger (Laravel 13 no corre en 8.2).
2. Extensiones: `pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `zip`.
3. Limites de upload ≥ 10M para documentos de Calidad.
4. Copiar al `.env` de produccion los mismos `CACHE_PREFIX`, `REDIS_PREFIX` y `SESSION_COOKIE` fijados en local.
5. Pipeline de deploy:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
```

6. Mantener tag/rama anterior hasta validar produccion (plan de rollback).
7. Si Hostinger aun no ofrece PHP 8.3, posponer deploy de L13 hasta que este disponible.
