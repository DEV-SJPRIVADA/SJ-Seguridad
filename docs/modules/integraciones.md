# Integraciones (salida hacia SJ LegalSuite)

## Objetivo

Sincronizar el catalogo de empleados de **SJ StatFlow** (maestro, servidor interno) hacia **SJ LegalSuite** (Hostinger, publico) sin compartir base de datos ni exponer el servidor interno.

StatFlow **empuja** por HTTPS saliente. No hay pull, ni tunel, ni relay de archivos.

## Alcance

- Origen: `employee_ficha_profiles` (4154 perfiles, lectura).
- Destino: tabla `employees` de LegalSuite (upsert por `document_number`).
- Fuera de alcance: datos de emergencia (`emergency_contact_*`), `department_area`, documentos, nomina, usuarios.

## Principios de diseno

1. **StatFlow solo lee.** No hay observers, listeners ni cambios en el guardado de la ficha. La deteccion de cambios es por **sondeo de `updated_at`** (polling).
2. **Sin colas.** `QUEUE_CONNECTION=sync`. Los comandos se ejecutan en el scheduler del servidor.
3. **Sin cambios de esquema existente.** Solo se agregaron dos tablas de control: `integration_sync_state` e `integration_sync_log`.
4. **Idempotencia.** LegalSuite hace upsert por `document_number`; reenviar el mismo registro no duplica.

## Contrato del payload

`POST {INTEGRATION_LEGALSUITE_URL}/api/integraciones/empleados`

Headers:

| Header | Valor |
| --- | --- |
| `X-Integration-Key` | token estatico (`INTEGRATION_LEGALSUITE_TOKEN`) |
| `X-Signature` | `hash_hmac('sha256', $bodyCrudo, INTEGRATION_LEGALSUITE_SECRET)` en hex |
| `X-Timestamp` | unix en segundos; el receptor tolera +-300 s |

Body (lotes de 500):

```json
{
  "run_id": "uuid",
  "mode": "incremental",
  "batch": 1,
  "total_batches": 9,
  "sent_at": "2026-11-18T10:00:00-05:00",
  "records": [ { "...": "..." } ]
}
```

Whitelist estricta por registro (ningun otro campo viaja):

| Origen (`employee_ficha_profiles`) | Destino (`employees`) | Regla |
| --- | --- | --- |
| `id` | `external_id` | trazabilidad |
| `document_number` | `document_number` | solo digitos; si queda vacio el registro se descarta |
| `document_type` | `document_type` | codigo crudo; LegalSuite lo mapea |
| `first_name` + `second_name` | `first_name` | concatenado, 100 caracteres |
| `first_surname` + `second_surname` | `last_name` | concatenado, 100 caracteres |
| `sex` M/F | `gender` | `masculino` / `femenino`; otro valor -> null |
| `birth_date` | `birth_date` | `Y-m-d` |
| `address` | `address` | |
| `residence_city_code` | `residence_municipality_code` | 5 digitos o null |
| (derivado en LegalSuite) | `residence_department_code` | StatFlow lo envia null |
| `work_city_code` | `municipality_code` | 5 digitos o null |
| (derivado en LegalSuite) | `work_department_code` | StatFlow lo envia null |
| `phone` | `phone` | vacio -> null |
| `email` | `email` | vacio -> null |
| `hire_date` | `hired_at` | `Y-m-d` |
| `contract_type_name` | `contract_type` | `termino_fijo`, `termino_indefinido`, `obra_labor`, `aprendizaje_lectiva`, `aprendizaje_practica` o null |
| `position_code` + `position_name` | `position_code` + `job_title` | el cargo se resuelve/crea en LegalSuite |
| `salary` | `base_salary` | string decimal con 2 decimales |
| `employment_status` | `is_active` | `activo` -> true; cualquier otro -> false |
| `termination_date` | `termination_at` | `Y-m-d`; se envia null si el registro esta activo o si la fecha es el centinela `9999-12-31` |

`employee_scope` **no se envia**: LegalSuite lo deriva del cargo resuelto.

## Flujo

1. `integracion:push-cambios` (cada minuto) lee `integration_sync_state.key = 'employees'`.
2. `watermark = last_watermark ?? now()->subDay()`.
3. Selecciona perfiles con `updated_at > watermark`, ordenados por `updated_at` e `id`.
4. Envia en lotes de 500 con `Http::retry(3, 2000)` y `timeout=60`.
5. **Solo si todos los lotes responden 2xx** actualiza `last_watermark = max(updated_at enviado)`. Si algo falla, el watermark no se mueve y el siguiente ciclo reintenta.
6. Cada corrida escribe en `integration_sync_log`.

La comparacion es **estrictamente mayor**. Con `>=` (o con un solape de un minuto) el perfil cuyo `updated_at` coincidia con el watermark volvia a entrar en cada corrida y el watermark nunca avanzaba, generando un reenvio infinito del mismo registro cada minuto. Contrapartida asumida: un perfil actualizado en el mismo segundo del watermark y confirmado despues de la consulta puede quedar fuera del incremental; el full semanal de los lunes lo recupera y el receptor es idempotente, asi que no hay duplicados ni perdida definitiva.

`integracion:push-empleados --full` envia todos los perfiles sin filtro de fecha (9 lotes con el volumen actual) y actualiza el watermark al terminar.

## Tablas nuevas

`integration_sync_state`: `key` (unique, `employees`), `last_watermark`, `last_run_at`, `last_status` (`ok` / `failed`), `last_sent`, `last_failed`.

`integration_sync_log`: `mode` (`incremental` / `full` / `dry-run`), `started_at`, `finished_at`, `status`, `records_sent`, `records_failed`, `batches`, `message`.

## Comandos

```bash
php artisan integracion:probar                         # valida URL, TLS, token, firma y reloj (no envia empleados)
php artisan integracion:push-empleados --full --dry-run   # simulacion del catalogo completo
php artisan integracion:push-cambios --dry-run            # simulacion incremental
php artisan integracion:push-empleados --full             # primer envio manual
php artisan integracion:push-cambios                      # lo ejecuta el scheduler cada minuto
```

Opciones: `--dry-run` (no hace HTTP, imprime conteos y 5 payloads) y `--limit=N` (maximo de perfiles, util en pruebas).

`integracion:probar` envia un lote vacio (`records: []`) firmado: no escribe empleados y funciona aunque `INTEGRATION_LEGALSUITE_ENABLED=false`, porque su objetivo es comprobar el canal antes de habilitarlo. Devuelve codigo de salida 0 solo si el receptor responde 2xx, y traduce el error: `401` token, `422` firma/reloj, `429` limite, `503` receptor sin credenciales.

## Scheduler

En `bootstrap/app.php`:

- `integracion:push-cambios` -> `everyMinute()->withoutOverlapping()->runInBackground()` (America/Bogota).
- `integracion:push-empleados --full` -> `weeklyOn(1, '03:00')->withoutOverlapping()` (America/Bogota), es decir todos los lunes a las 03:00.

El servidor debe tener el cron de Laravel activo:

```bash
* * * * * cd /ruta/statflow && php artisan schedule:run >> /dev/null 2>&1
```

## Activacion (procedimiento)

1. `php artisan integracion:probar` y confirmar `Canal OK` (no escribe datos).
2. `php artisan integracion:push-empleados --full --dry-run` y revisar los conteos con Gestion Humana.
3. Configurar en `.env` del servidor: `INTEGRATION_LEGALSUITE_URL`, `INTEGRATION_LEGALSUITE_TOKEN`, `INTEGRATION_LEGALSUITE_SECRET`. Mantener `INTEGRATION_LEGALSUITE_ENABLED=false`.
4. `php artisan config:cache` y repetir `php artisan integracion:probar` contra la URL definitiva.
5. Poner `INTEGRATION_LEGALSUITE_ENABLED=true`, `php artisan config:cache` y ejecutar `php artisan integracion:push-empleados --full` (manual; con la integracion deshabilitada la orden falla).
6. Verificar en LegalSuite (`php artisan integracion:estado`) y activar el cron.

## Prueba local end-to-end (Laragon, sin SSL)

Laragon sirve los dos proyectos por HTTP (`http://sjseguridad.test` y `http://sjlegalsuite.test`). El productor exige HTTPS, con una unica excepcion controlada: `APP_ENV=local` **y** `INTEGRATION_LEGALSUITE_ALLOW_HTTP=true`.

1. **LegalSuite** (`.env`): generar `INTEGRATION_STATFLOW_KEY` y `INTEGRATION_STATFLOW_SECRET`; `php artisan config:cache`.
2. **StatFlow** (`.env`): `INTEGRATION_LEGALSUITE_URL=http://sjlegalsuite.test`, mismo token y secret, `ENABLED=true`, `ALLOW_HTTP=true`; `php artisan config:cache`.
3. `php artisan integracion:probar` (no escribe nada; debe responder `Canal OK`).
4. `php artisan integracion:push-empleados --full --dry-run` (no envia).
5. `php artisan integracion:push-empleados --full` (envia 4.154 en 9 lotes).
6. En LegalSuite: `php artisan integracion:estado`.
7. Editar un empleado en StatFlow y ejecutar `php artisan integracion:push-cambios`; en LegalSuite deben verse los campos actualizados y `skipped` si no hubo cambios.

En produccion la bandera se deja en `false`: cualquier URL sin `https://` falla con `debe ser una URL HTTPS`.

## Desviaciones documentadas

- **Municipios:** StatFlow no tiene el catalogo `colombian_municipalities`, por lo que no puede validar el codigo DANE ni derivar el departamento. Envia el codigo tal cual (5 digitos) y `*_department_code = null`; LegalSuite valida contra su catalogo y deriva el departamento.
- **`termination_date` centinela:** la ficha usa `9999-12-31` para "sin desvinculacion"; se traduce a `null` para no guardar fechas imposibles.
- **`retry_sleep_ms`:** clave extra en `config/integrations.php` (2000 ms) para poder anular la espera entre reintentos en pruebas.

## Troubleshooting

| Sintoma | Causa probable | Accion |
| --- | --- | --- |
| `Integracion deshabilitada` | `INTEGRATION_LEGALSUITE_ENABLED=false` | activar en `.env` y `config:cache` |
| `Faltan variables de entorno` | URL/token/secret vacios | completar `.env` |
| `debe ser una URL HTTPS` | URL http o vacia | usar `https://`; en local: `APP_ENV=local` + `INTEGRATION_LEGALSUITE_ALLOW_HTTP=true` |
| HTTP 401 en el receptor | token distinto | comparar `INTEGRATION_LEGALSUITE_TOKEN` con el de LegalSuite |
| HTTP 422 en el receptor | firma invalida o reloj desfasado | revisar `INTEGRATION_LEGALSUITE_SECRET` y NTP del servidor |
| Watermark no avanza | algun lote fallo | revisar `integration_sync_log.message` y `storage/logs` |
| Muchos `document_type desconocido` | codigos fuera del mapa | ampliar el mapa en LegalSuite |

## Pruebas

`php artisan test --compact tests/Feature/Integrations` cubre whitelist, mapeo de nombres, `is_active`, lotes de 500, watermark en exito/fallo, dry-run y firma HMAC.