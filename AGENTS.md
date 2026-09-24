# Bases del Proyecto

## Objetivo base
- Este repositorio es la base de una plataforma web modular para `SJ Seguridad` (`APP_NAME`: `SJ StatFlow`), desarrollada bajo un enfoque SCRUM e incremental.
- Stack base: `Laragon 8.6+`, `Laravel 13`, `PHP 8.3`, `MySQL 8` (dev local) y `Blade` + `Alpine.js` + `Tailwind 3` + `Vite 6`.
- Despliegue real: **hosting compartido Hostinger** (checklist en `docs/LOCAL_SETUP.md`, procedimiento en `docs/PROCEDURES.md`). No asumir Laravel Cloud ni Sail.

## Comandos exactos
- Dev local: `.\dev.bat` (fija PHP 8.3 y Node de Laragon y levanta `artisan serve` + Vite). Alternativas: `composer run dev`, `npm run dev`.
- Si la UI no refleja cambios de frontend: `npm run build` (manifest de Vite).
- Tests (PHPUnit 12, nunca Pest): `php artisan test --compact` (todos) · `php artisan test --compact tests/Feature/Ruta.php` (archivo) · `--filter=nombre` (uno).
- Tras modificar PHP: `vendor/bin/pint --dirty --format agent` antes de cerrar el cambio.
- Diagnóstico local: `php artisan app:doctor` y `php artisan app:stabilize-local` (closures definidos en `routes/console.php`, no en `app/Console/Commands`; sirven si el login falla tras cambios de entorno). Otros: `app:sync-permissions`, `app:restore-admin`.
- MCP Laravel Boost activo (`opencode.json`): usar `search-docs` antes de tocar código y `database-schema` antes de escribir migraciones o modelos.

## Precedencia de instrucciones
- Este repo **exige** actualizar documentación en la misma entrega (ver "Reglas para cualquier cambio"). Si una guía genérica de la herramienta dice "no crear documentación sin pedirla", prevalece la regla del repo.
- Las guías genéricas de Laravel no reemplazan las convenciones de este archivo.

## Reglas para cualquier cambio futuro
- Antes de modificar una funcionalidad, revisar impacto global en autenticacion, permisos, navegacion, base de datos, pruebas y despliegue.
- No introducir dependencias innecesarias si el mismo resultado puede lograrse con Laravel core, Blade, middleware, policies o JS simple.
- Mantener los modulos por area desacoplados: rutas, controladores, vistas, validaciones y permisos deben poder crecer sin mezclar responsabilidades.
- Todo cambio nuevo debe considerar seguridad, errores probables, validacion de entradas y efectos colaterales sobre usuarios existentes.
- Cuando se agregue una nueva area del negocio, actualizar `config/access.php`, seeders, permisos, navegacion y pruebas relacionadas.
- Toda modificacion funcional debe actualizar la documentacion viva en `docs/` dentro de la misma entrega.
- La documentacion debe permitir que cualquier IA entienda el proyecto, sus modulos, sus dependencias, sus permisos y su estado actual sin depender del historial de chat.
- Si el cambio afecta un modulo existente, actualizar su archivo en `docs/modules/` y, si aplica visible al usuario, en `docs/user/`. Si crea un modulo nuevo, crear ambos documentos correspondientes.

## Seguridad obligatoria
- No habilitar registro publico salvo instruccion expresa del usuario.
- Todo acceso sensible debe quedar protegido por autenticacion y permisos.
- Los usuarios inactivos no pueden operar (middleware `active`).
- Las contrasenas temporales deben forzar cambio al primer ingreso (middleware `password.changed`).
- Revisar periodicamente logs, pruebas y validaciones de permisos antes de cerrar cada sprint.

## Proteccion de datos (obligatorio para humanos e IA)
- **Nunca** borrar, truncar, resetear ni reemplazar datos de la base de datos **sin consultar al usuario y obtener autorizacion explicita**.
- Comandos **prohibidos** salvo OK del usuario: `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, `DROP DATABASE`, `DROP TABLE`, `TRUNCATE`, restauracion de backups SQL sobre la BD activa.
- Si una migracion falla: corregir el archivo y ejecutar `migrate`; **no** usar fresh como atajo.
- En desarrollo local, Laragon guarda backups en `C:\laragon\backup\mysql\`. Ante perdida de datos, informar al usuario y ofrecer restauracion; no restaurar sin su confirmacion.
- Regla Cursor: [`.cursor/rules/database-safety.mdc`](.cursor/rules/database-safety.mdc).

## Convenciones actuales
- El control de acceso usa `spatie/laravel-permission`. Roles base `super-admin`, `administrador`, `usuario` los crea `database/seeders/RoleAndPermissionSeeder.php` (los antiguos `coordinador` y `consulta` fueron migrados a `usuario`).
- Permisos del sistema, areas y tableros viven en `config/access.php` (`system_permissions`, `areas`, `boards`, `area_actions`).
- La gestion inicial de usuarios esta en el modulo `admin/users`.
- **Navegacion chrome:** areas (sidebar `.sidebar-link`), tableros y pestanas (`.module-tab`) comparten el mismo estilo pill; padding del contenedor a los botones `0.2rem`. Ver `.cursor/rules/nav-chrome-ui.mdc` y `docs/modules/branding.md`.
- **Botones icon-only:** chrome/filtros usan `.req-manage-filters__icon-btn` (`--primary` / `--ghost` / `--danger`); acciones de fila usan `.cursos-catalogo-page__icon-btn`. No crear familias CSS por modulo. Ver `.cursor/rules/icon-buttons-ui.mdc` y `docs/modules/branding.md`.

## Estructura y Arquitectura Modular (Híbrida)
- **Módulos Compartidos:** Funcionalidades usadas por múltiples áreas (ej. Requisiciones). Se ubican en:
    - Controladores: `App\Http\Controllers\{Modulo}\`
    - Vistas: `resources/views/modules/{modulo}/` (existentes: `development-requests`, `purchase-requests`, `quality-documents`, `requisitions`, `supplies`)
    - Rutas: `routes/modules/{modulo}.php`
- **Funcionalidades Únicas de Área:** Lógica exclusiva de un departamento. Se ubican en:
    - Controladores: `App\Http\Controllers\{Area}\`
    - Vistas: `resources/views/areas/{area}/` (existentes: `comercial`, `compras`, `gestion_humana`, `operaciones`)
    - Rutas: `routes/areas/{area}.php`
- **Carga de rutas:** `routes/web.php` hace `require` de todos los archivos de `modules/` y `areas/` **dentro del grupo `['auth', 'active']`**. Un archivo de rutas nuevo que no se declare ahí no carga; una ruta declarada fuera del grupo queda publicamente accesible.
- **Visión SaaS:** El código debe estar preparado para escalar a múltiples clientes y ser gestionado por un SuperUsuario global.
- **Navegación Dinámica:** El sidebar se genera automáticamente basándose en `config/access.php` y los permisos del usuario.

## Migraciones: siempre multi-driver
- Los tests corren en `sqlite :memory:` (`phpunit.xml`); dev y producción en MySQL. Las migraciones existentes ramifican por `Schema::getConnection()->getDriverName()`.
- Toda migración nueva debe funcionar en ambos drivers: `enum()`, `change()` sobre columnas MySQL y alter de tipos fallan en sqlite. Ejecutarla con `RefreshDatabase` antes de cerrar.
- Si una migracion falla en un entorno: corregir el archivo; nunca usar `fresh`.

## Pruebas
- Tests Feature usan `RefreshDatabase` sobre sqlite; si el test asigna roles, sembrarlos en `setUp()`: `$this->seed(RoleAndPermissionSeeder::class)`.
- No borrar ni vaciar tests sin aprobacion; usar `--filter` para ejecutar solo lo afectado.

## Exportacion a Excel
- Toda exportacion a Excel debe usar `App\Exports\BaseExport` con columnas configurables.
- Exportaciones con formato complejo requieren clase dedicada en `app/Exports/`.
- Boton reutilizable mediante el componente Blade `<x-export-excel>`.
- No agregar dependencias adicionales de exportacion; usar `PhpSpreadsheet` (ya incluido via `phpoffice/phpspreadsheet`).
- El boton DataTables `excelHtml5` esta deprecado; no reintroducirlo.

## Estandar de Componentes de Seleccion (Searchable Select)
- **Todos los selectores (selects)** en formularios, modales y filtros deben construirse utilizando el componente Blade `<x-searchable-select>` con soporte de Alpine.js (`resources/views/components/searchable-select.blade.php` y `resources/js/components/searchable-select.js`).
- **Prohibido reintroducir la libreria Select2** (ni por CDN ni por paquete npm/scripts jQuery).
- `<x-searchable-select>` incluye buscador integrado en tiempo real, soporte de navegacion por teclado, compatibilidad con validacion nativa HTML5 (`required`), emision de eventos `change`/`input` y enlace con `<input type="hidden">` para envio estandar de formularios.
- Parametros principales: `:options`, `:value`, `placeholder`, `searchPlaceholder`, `:required`, `:disabled`, `:allowClear`.

## Listados DataTables server-side
- Tablas operativas que pueden crecer (empleados, cursos, archivo, seguimientos, etc.) deben usar DataTables **`serverSide: true`** con endpoint JSON; no renderizar miles de filas en Blade + `js-datatable` client-side.
- Referencia de implementacion: Ficha empleados, Cursos registros (`EmployeeCursoDatatableService`).
- Regla Cursor (incluye cuando avisar antes de codear): [`.cursor/rules/datatables-server-side.mdc`](.cursor/rules/datatables-server-side.mdc).
- Catalogos pequenos fijos pueden seguir con `js-datatable` client-side.

## Auditoria central
- Modulos nuevos deben registrar eventos via `App\Services\Audit\SystemAuditService` (wrapper delgado por modulo con `module`/`area` fijos).
- No crear tablas de audit duplicadas salvo historiales de dominio embebidos (ej. cambios campo a campo en requisiciones).
- Ver `config/audit.php` y `docs/modules/audit-log.md`.

## Criterio de revision continua
- Verificar que no se rompan rutas protegidas ni estados de sesion.
- Verificar migraciones nuevas y compatibilidad con despliegue en hosting compartido.
- Verificar pruebas de seguridad y acceso al tocar autenticacion, usuarios o permisos.
- Si un cambio afecta varias capas, documentarlo en el cierre del trabajo.
- Verificar en cada cierre que `README.md`, `docs/INDEX.md` y el documento del modulo afectado sigan alineados con el codigo real.

## Trabajo con agentes
- Guia de documentacion (IA, desarrollador, usuario): [`docs/DOCUMENTATION.md`](docs/DOCUMENTATION.md).
- Workflow multi-agente: [`docs/AGENT_WORKFLOW.md`](docs/AGENT_WORKFLOW.md).
- **Feature o modulo nuevo:** flujo completo (Analista → Arquitecto → AgentSj → Feature → Revisor → Documentador). Inicio en Agent mode: **`@agent-sj`** + descripcion (skill en `.cursor/skills/agent-sj/`) o **`AgentSj`** + descripcion.
- **Consulta o fix pequeno:** carril rapido (Ask/Agent directo); no exige Feature Brief ni entrada en `docs/TASKS.md`. Ver [`docs/agents/prompts/fast-lane.md`](docs/agents/prompts/fast-lane.md).
- Un Agente Feature = un modulo + Feature Brief + Task Card. No editar archivos compartidos (`config/access.php`, `routes/web.php`, layouts) sin flag `shared-files` en `docs/TASKS.md`.
- Cierre de feature: doc tecnica en `docs/modules/{modulo}.md` y doc usuario en `docs/user/{modulo}.md` (Objetivo, Alcance, Definiciones, Responsabilidades, Desarrollo, Control de cambios), generadas por el Documentador.
- AgentSj es dueno de `docs/TASKS.md` en features nuevas.
