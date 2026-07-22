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

## Procedimiento para despliegue en Hostinger

1. Confirmar PHP 8.3+ y extensiones (`pdo_mysql`, `mbstring`, `fileinfo`, `openssl`, `zip`).
2. Configurar `.env` de produccion con `CACHE_PREFIX`, `REDIS_PREFIX` y `SESSION_COOKIE` (mismos valores que local si se desea continuidad de sesiones).
3. Ejecutar en el servidor:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci && npm run build
```

4. Validar login, permisos y modulos criticos antes de cerrar el deploy.
5. Mantener plan de rollback (rama/tag anterior) hasta validar produccion.

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

Roles: Analista → Arquitecto → Orquestador → Agente Feature → Revisor → Documentador.

1. Registrar feature en [`docs/TASKS.md`](c:/laragon/www/SJSEGURIDAD/docs/TASKS.md) (Orquestador).
2. Analista cierra vacios; pausa si hay preguntas al usuario.
3. Arquitecto entrega Feature Brief en `docs/briefs/FEAT-XXX.md`.
4. Orquestador genera plan y Task Cards; Agente Feature implementa vertical slice por tarea.
5. Revisor emite reporte en `docs/reviews/FEAT-XXX.md`.
6. Documentador actualiza `docs/modules/{modulo}.md` y crea/actualiza `docs/user/{modulo}.md`.
7. Orquestador ejecuta checklist de [`AGENT_WORKFLOW.md`](c:/laragon/www/SJSEGURIDAD/docs/AGENT_WORKFLOW.md) e integra.

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
