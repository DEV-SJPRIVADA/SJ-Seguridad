# Bases del Proyecto

## Objetivo base
- Este repositorio es la base de una plataforma web modular para `SJ Seguridad`, desarrollada bajo un enfoque SCRUM e incremental.
- El stack base debe priorizar compatibilidad con `Laragon 8.6+`, `Laravel 13`, `PHP 8.3`, `MySQL/Hostinger` y `JavaScript` orientado a componentes simples y reutilizables.

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
- Los usuarios inactivos no pueden operar.
- Las contrasenas temporales deben forzar cambio al primer ingreso cuando aplique.
- Revisar periodicamente logs, pruebas y validaciones de permisos antes de cerrar cada sprint.

## Proteccion de datos (obligatorio para humanos e IA)
- **Nunca** borrar, truncar, resetear ni reemplazar datos de la base de datos **sin consultar al usuario y obtener autorizacion explicita**.
- Comandos **prohibidos** salvo OK del usuario: `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `db:wipe`, `DROP DATABASE`, `DROP TABLE`, `TRUNCATE`, restauracion de backups SQL sobre la BD activa.
- Si una migracion falla: corregir el archivo y ejecutar `migrate`; **no** usar fresh como atajo.
- En desarrollo local, Laragon guarda backups en `C:\laragon\backup\mysql\`. Ante perdida de datos, informar al usuario y ofrecer restauracion; no restaurar sin su confirmacion.
- Regla Cursor: [`.cursor/rules/database-safety.mdc`](.cursor/rules/database-safety.mdc).

## Convenciones actuales
- El control de acceso usa `spatie/laravel-permission`.
- Los roles base son `super-admin`, `administrador` y `usuario` (los antiguos `coordinador` y `consulta` fueron migrados a `usuario`).
- Los permisos del sistema y por area viven en `config/access.php`.
- La gestion inicial de usuarios esta en el modulo `admin/users`.
- **Navegacion chrome:** areas (sidebar `.sidebar-link`), tableros y pestanas (`.module-tab`) comparten el mismo estilo pill; padding del contenedor a los botones `0.2rem`. Ver `.cursor/rules/nav-chrome-ui.mdc` y `docs/modules/branding.md`.

## Estructura y Arquitectura Modular (Híbrida)
- **Módulos Compartidos:** Funcionalidades usadas por múltiples áreas (ej. Requisiciones). Se ubican en:
    - Controladores: `App\Http\Controllers\{Modulo}\`
    - Vistas: `resources/views/modules/{modulo}/`
    - Rutas: `routes/modules/{modulo}.php`
- **Funcionalidades Únicas de Área:** Lógica exclusiva de un departamento. Se ubican en:
    - Controladores: `App\Http\Controllers\{Area}\`
    - Vistas: `resources/views/areas/{area}/`
    - Rutas: `routes/areas/{area}.php`
- **Visión SaaS:** El código debe estar preparado para escalar a múltiples clientes y ser gestionado por un SuperUsuario global.
- **Navegación Dinámica:** El sidebar se genera automáticamente basándose en `config/access.php` y los permisos del usuario.

## Exportacion a Excel
- Toda exportacion a Excel debe usar `App\Exports\BaseExport` con columnas configurables.
- Exportaciones con formato complejo requieren clase dedicada en `app/Exports/`.
- Boton reutilizable mediante el componente Blade `<x-export-excel>`.
- No agregar dependencias adicionales de exportacion; usar `PhpSpreadsheet` (ya incluido via `phpoffice/phpspreadsheet`).
- El boton DataTables `excelHtml5` esta deprecado; no reintroducirlo.

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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/breeze (BREEZE) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
