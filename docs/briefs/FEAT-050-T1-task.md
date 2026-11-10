# Task Card — FEAT-050 T1

**Slice:** Cimientos + constructor de indicador + publicar/versionar  
**shared-files:** Sí  
**Scope lock:** no captura, no compartir, no consolidado, no dashboard de KPI. La pestaña Dashboard puede existir como placeholder 403/empty solo si hace falta el subnav; no implementar KPI.

## Objetivo

Calidad entra al tablero, crea un indicador desde cero (campos + fórmula), guarda borrador, publica v1, lista y abre la ficha de definición. Editar una versión publicada con capturas (o siempre congelada) crea borrador v2.

## Implementar

1. Permisos y nav (patrón Servicio al cliente):
   - `config/access.php`: board `planeacion_estrategica`, tabs, `system_permissions` + `permission_help`, grupo Admin Calidad, `board_canonical_areas.home = calidad`
   - `PermissionCatalog`: rechazar el board fuera de `calidad`
   - `PlaneacionEstrategicaAccessService` (`edit` implica `view`; dashboard propio)
   - `NavigationResolver` + `SidebarVisibilityService` + `User::defaultPlaneacionBoardUrl`
   - Seeder `administrador` con paquete (board + indicadores edit + dashboard + captura view/edit)
   - `config/audit.php` módulo `planeacion_estrategica`
2. Migraciones multi-driver (artisan make:migration) de las 4 tablas del brief. SQLite + MySQL.
3. Modelos `App\Models\QualityPlanning\*`, factories.
4. `FormulaEvaluator`: shunting-yard, tests unitarios del ejemplo `((campo_1 / campo_2) + campo_3) * 100`, paréntesis rotos, clave inexistente, división por cero.
5. Rutas `routes/areas/calidad.php` prefijo `calidad/planeacion`, nombres `calidad.planeacion.*`, middleware `password.changed`.
6. UI:
   - Subnav `.module-tab` (Dashboard | Indicadores | Captura). Captura/Dashboard pueden 403 si no hay permiso; Indicadores es el slice.
   - Listado DataTables server-side (patrón FO-GI-22): código, nombre, versión, estado, acciones.
   - Formulario alta/edición por secciones (patrón FO-GI-22): identidad (código a mano unique), medición (objetivo, proceso searchable-select de `quality-documents.processes`, unidad, meta, operador, crítico, frecuencia mensual), campos dinámicos, fórmula con chips + Probar.
   - Publicar; archivar.
7. MAYÚSCULAS en textos de ficha (`UppercasesValidatedText`). Except: keys técnicas (`code` si se considera técnico — **sí exceptuar `code`**, `fields.*.key`, `formula`, `target_operator`, `process_key`, `frequency`, `unit`).
8. CSS nuevo `resources/css/areas/calidad/117-calidad-planeacion.css` + `@import` **al final** de `app.css`. No reordenar imports.
9. Auditoría create/update/publish vía wrapper `PlaneacionAuditLogService`.
10. Tests Feature de acceso + CRUD/publicar + unique code. Unit fórmula.

## Referencias (leer, no copiar módulos enteros)

- Brief: `docs/briefs/FEAT-050.md`
- Acceso: `app/Services/Access/ServicioClienteAccessService.php`, `tests/Feature/Calidad/ServicioClienteBoardAccessTest.php`
- Nav: bloques `servicio_cliente` en `NavigationResolver` y `SidebarVisibilityService`
- Form: `app/Http/Requests/Calidad/ServicioCliente/StoreServicioClienteCasoRequest.php`
- Listado: FO-GI-22 + `ServicioClienteDatatableService`
- Ficha visual (solo estructura de definición en T1, sin tabla anual de capturas): headers de `capture-form.blade.php`
- Skills Laravel: `.cursor/skills/laravel-best-practices/rules/{migrations,eloquent,validation,security,routing,testing,architecture}.md`

## Prohibido

- Tocar tablas `indicators` / `indicator_captures`
- Inventar permisos fuera del brief
- Select2, `excelHtml5`, `*{modulo}*__icon-btn`
- `migrate:fresh` / wipe
- Docs de usuario/técnica final (Documentador)
- Implementar captura, assignees, consolidado, dashboard KPI

## Cierre

- `php artisan test --compact` archivos del slice
- `vendor/bin/pint --dirty --format agent`
- `npm run build` si CSS/JS Vite
- Reportar archivos, tests, pendientes T2
