# Procedimientos de Trabajo

## Regla general

Todo cambio funcional debe cerrar con codigo, validacion y documentacion sincronizada.

## Procedimiento para nuevas funcionalidades

1. Revisar impacto en autenticacion, permisos, navegacion, base de datos, pruebas y despliegue.
2. Implementar el cambio de forma desacoplada por modulo.
3. Actualizar documentacion tecnica afectada.
4. Registrar rutas, permisos, tablas y validaciones nuevas.
5. Verificar que no se rompan rutas protegidas ni sesiones.

## Procedimiento para cambios en seguridad o acceso

1. Revisar `config/access.php`.
2. Revisar middleware, requests, rutas y seeders.
3. Validar usuarios inactivos, roles y contrasena temporal.
4. Actualizar [`ACCESS_CONTROL.md`](c:/laragon/www/SJSEGURIDAD/docs/ACCESS_CONTROL.md).
5. Documentar impacto operativo en el modulo afectado.

## Procedimiento para recuperar el acceso local

1. Confirmar que Laragon tenga `MySQL` iniciado.
2. Ejecutar `artisan app:doctor`.
3. Si el diagnostico marca errores, ejecutar `artisan app:stabilize-local`.
4. Verificar de nuevo con `artisan app:doctor`.
5. Probar inicio de sesion con el admin semilla definido en `.env`.

## Procedimiento para pruebas automatizadas

1. Ejecutar pruebas con `php artisan test` (`phpunit.xml` usa `sqlite` en memoria).
2. No ejecutar pruebas automatizadas contra la base local `sjseguridad`, porque `RefreshDatabase` puede limpiar tablas reales de desarrollo.
3. En Laragon con PHP 8.3, verificar que `pdo_sqlite` y `sqlite3` esten habilitados en `php.ini`. Si no, habilitarlos y reiniciar la terminal.

```powershell
php artisan test
```

## Entorno de produccion (aclaracion)

La pagina en produccion corre hoy en un **servidor propio Linux** (VPS/dedicado con SSH). Hostinger compartido es un escenario documentado como legado/alternativo; no es el hosting actual.

## Procedimiento para despliegue en el Linux Server (produccion actual)

1. Confirmar PHP 8.3+ CLI y extensiones (`pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `zip`).
2. Configurar `.env` de produccion (`APP_ENV=production`, `APP_DEBUG=false`, BD, `CACHE_PREFIX`, `REDIS_PREFIX`, `SESSION_COOKIE`).
3. Desplegar codigo (git pull / rsync / pipeline) a la raiz Laravel del servidor.
4. Ejecutar en el servidor (ajustar ruta):

```bash
cd /ruta/al/proyecto
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
```

5. Validar login, permisos y modulos criticos antes de cerrar el deploy.
6. Mantener plan de rollback (rama/tag anterior) hasta validar produccion.
7. **Prohibido** sin autorizacion explicita: `migrate:fresh`, `db:wipe`, TRUNCATE, restaurar backup sobre la BD activa.

## Procedimiento para despliegue en Hostinger (legado / alternativo)

Mismos pasos de `composer` / `migrate --force` / caches / `npm` que en el Linux Server, adaptados al panel File Manager o SSH de Hostinger si aplica. Ver tambien checklist en [`LOCAL_SETUP.md`](LOCAL_SETUP.md).

## Procedimiento: sync diario de estados GH (scheduler)

En producción el cron debe ejecutar `php artisan schedule:run` cada minuto. Los comandos de recálculo de estados viven en `bootstrap/app.php` (`withSchedule`), zona `America/Bogota`, `withoutOverlapping()`:

| Comando | Hora | Modulo |
| --- | --- | --- |
| `cursos:sync-estados` | 06:15 | Cursos |
| `acreditaciones:sync-estados` | 06:20 | Acreditaciones |
| `mt_st_04:sync-estados` | 06:25 | MT-ST-04 |

### MT-ST-04 — sync manual

```bash
php artisan mt_st_04:sync-estados
php artisan mt_st_04:sync-estados --dry-run
php artisan mt_st_04:sync-estados --date=2026-10-09
```

Tras deploy de FEAT-045: `php artisan migrate --force`, `php artisan app:sync-permissions`, asignar board + `mt_st_04.view` / `.edit` en Admin, verificar `php artisan schedule:list`. Detalle: [`modules/mt_st_04.md`](modules/mt_st_04.md).

## Procedimiento: import historico de desvinculaciones

Carga de la hoja **NOVEDADES** (≥ 2025-05-01) a Seguimientos + Retiros **sin generar cartas**. Detalle tecnico: [`modules/desvinculaciones.md`](modules/desvinculaciones.md).

### Via UI (recomendado en produccion)

1. Desplegar el codigo con el endpoint `POST .../seguimientos/importar-historico`.
2. Entrar a **Gestion humana → Desvinculaciones → Seguimientos** con permiso `desvinculaciones.seguimientos.edit`.
3. Pulsar el icono de **Importar histórico** (toolbar junto a Exportar).
4. Elegir el Excel (`.xlsx` / `.xls` / `.xlsm`), pulsar **Simular** y revisar metricas.
5. Marcar la confirmacion y pulsar **Cargar**.
6. Validar filas en Seguimientos y en **MT-GH-04 Novedades → Retiros**.

### Via SSH / Artisan (alternativa tecnica)

1. Subir el Excel a `storage/app/tmp/` en el Linux Server.
2. Simular: `php artisan desvinculaciones:import-historico storage/app/tmp/ARCHIVO.xlsm --dry-run`
3. Carga real: mismo comando sin `--dry-run` y con `--user=ID_USUARIO`.
4. Borrar el Excel del servidor al terminar.

## Procedimiento para nuevas areas del negocio

1. Agregar area en `config/access.php`.
2. Actualizar permisos derivados y seeders.
3. Crear rutas, controladores, vistas y validaciones del modulo.
4. Incluir la nueva area en dashboard o navegacion si aplica.
5. Crear un archivo del modulo en `docs/modules`.

## Procedimiento de documentacion obligatoria

Cada cambio debe responder, dentro del repositorio, estas preguntas:

- Que cambio se hizo
- Por que se hizo
- Que archivos o capas toca
- Que permisos, rutas o tablas afecta
- Como se valida localmente
- Que riesgos o dependencias deja

## Convencion para modulos documentados

Cada modulo debe tener un archivo `docs/modules/<modulo>.md` con:

- objetivo
- alcance actual
- rutas
- permisos
- controladores y requests
- vistas
- tablas implicadas
- riesgos y pendientes

## Procedimiento multi-agente (features y modulos)

Roles: Analista → Arquitecto → AgentSj → Agente Feature → Revisor → Documentador.

1. Registrar feature en [`docs/TASKS.md`](c:/laragon/www/SJSEGURIDAD/docs/TASKS.md) (AgentSj).
2. Analista cierra vacios; pausa si hay preguntas al usuario.
3. Arquitecto entrega Feature Brief en `docs/briefs/FEAT-XXX.md`.
4. AgentSj genera plan y Task Cards; Agente Feature implementa vertical slice por tarea.
5. Revisor emite reporte en `docs/reviews/FEAT-XXX.md`.
6. Documentador actualiza `docs/modules/{modulo}.md` y crea/actualiza `docs/user/{modulo}.md`.
7. AgentSj ejecuta checklist de [`AGENT_WORKFLOW.md`](c:/laragon/www/SJSEGURIDAD/docs/AGENT_WORKFLOW.md) e integra.

Modo recomendado: chat maestro en Agent mode con [`docs/agents/prompts/start-feature.md`](c:/laragon/www/SJSEGURIDAD/docs/agents/prompts/start-feature.md).

## Carril rapido

No usar flujo multi-agente cuando:

- Es consulta sobre codigo o documentacion → Ask mode.
- Es fix pequeno en 1–3 archivos sin permisos, migraciones ni rutas nuevas → Agent mode con alcance explicito.

Ver [`docs/agents/prompts/fast-lane.md`](c:/laragon/www/SJSEGURIDAD/docs/agents/prompts/fast-lane.md).

## Convencion doble documentacion

Ver guia completa en [`docs/DOCUMENTATION.md`](c:/laragon/www/SJSEGURIDAD/docs/DOCUMENTATION.md).

| Tipo | Ubicacion | Audiencia | Plantilla |
| --- | --- | --- | --- |
| Tecnica | `docs/modules/<modulo>.md` | IAs y desarrolladores | `docs/templates/TECHNICAL_MODULE_DOC.md` |
| Usuario | `docs/user/<modulo>.md` | Usuarios finales, capacitacion | `docs/templates/USER_MODULE_DOC.md` |

Orden obligatorio doc usuario: Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios.

Matriz de modulos documentados: [`docs/DOCUMENTATION.md#matriz-modulo-tecnico--usuario`](c:/laragon/www/SJSEGURIDAD/docs/DOCUMENTATION.md).
