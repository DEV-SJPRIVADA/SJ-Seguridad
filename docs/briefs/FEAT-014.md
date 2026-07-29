# Feature Brief — FEAT-014

> Versión final (Arquitecto). Decisiones de negocio cerradas 2026-07-29 (ver [`FEAT-014-analyst.md`](FEAT-014-analyst.md)). Supuestos explícitos donde el usuario no respondió preguntas 3, 11–15.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-014 |
| Modulo / area | **Comercial** — matriz clientes (`matriz-clientes`) |
| Titulo | Checklist documental por cliente + tablero de seguimiento |
| Solicitante | Manuel-E (via AgentSj) |
| Fecha | 2026-07-29 |

## Objetivo

Centralizar el **seguimiento documental MT-CO-01** (estados de los 10 documentos) en el **cliente (NIT)**, eliminando la edición duplicada por servicio/contrato. Comercial opera desde una **pantalla dedicada** de checklist (enlace desde el listado de clientes), con **un solo vencimiento y umbral de anticipación por cliente** para alertas en UI (badges/filtros). Alinear dashboard y filtros de vigencia de **Servicios** para que dejen de depender de columnas `doc_*` en `commercial_services`.

**Para quien:** usuarios con `comercial.matriz.view` (consulta del tablero y export) y `comercial.matriz.manage` (editar estados, fecha y días).

## Alcance

### Incluye

1. **Persistencia en cliente:** tabla hija de ítems de checklist (estado por documento) + columnas de vencimiento/anticipación en `commercial_clients`.
2. **Migración de datos** desde `commercial_services.doc_*` hacia el cliente (reglas abajo) y **retiro** de columnas documentales en servicios.
3. **Ruta/pantalla dedicada** «Checklist documental»; botón en `clients/index` **antes** de la barra de filtros que navega a esa ruta (no toggle en el mismo listado).
4. **Tabla operativa:** una fila por **documento**; agrupación visual por cliente (NIT/nombre); edición de **estado** por fila; **fecha de vencimiento** y **días de anticipación** editables a nivel cliente (mismo valor para todo el grupo del NIT en pantalla).
5. **Alertas v1:** badges/colores y filtros (`por vencer` / `vencido` documentación) usando `documentation_expires_on` + `alert_days_before` del cliente; **sin** correo (integración FEAT-013 en fase posterior).
6. **Quitar** el bloque «Checklist documental» de crear/editar **servicio** (vistas, validación, persistencia).
7. **Import MT-CO-01:** volcar estados (y fecha cliente derivada) al checklist del cliente; dejar de escribir `doc_*` en servicios.
8. **Dashboard comercial** y **`vigencia=expiring|expired`** en listado servicios: contrato del servicio **o** vencimiento documental del **cliente** (ya no columnas `doc_*` del servicio).
9. **Export Excel** del checklist con `App\Exports\BaseExport` + `<x-export-excel>`.
10. **Tests** feature/regresión en `CommercialMatrixTest` y pruebas nuevas del checklist.
11. **Documentación** `docs/modules/matriz-clientes.md` y `docs/user/matriz-clientes.md` (Agente Documentador al cierre).

### Fuera de alcance

- **Correos / FEAT-013 fase 2:** tipo de aviso «documentación comercial por vencer», destinatarios admin, jobs programados.
- Adjuntos PDF, Calidad, nuevos tipos de documento desde UI.
- Notificaciones in-app o SMS.
- Permiso nuevo distinto de `comercial.matriz.view` / `comercial.matriz.manage`.
- Sincronización automática con requisiciones más allá del maestro de clientes existente.
- Historial de cambios de estado por documento.

## Reglas de negocio

1. **Catálogo fijo:** los mismos **10 documentos** y estados que hoy (`CommercialService::documentFields()` / `documentStatuses()`); se extraen a un catálogo compartido (p. ej. `App\Support\CommercialDocumentCatalog` o métodos estáticos en un solo lugar) para cliente, vistas, import y export.
2. **Estado por documento:** cada cliente tiene exactamente **10 filas** (una por `document_key`); valores `ok`, `x`, `pending`, `na`, `incomplete`. Estado `N/A` no exige fecha.
3. **Vencimiento por cliente:** un solo par **`documentation_expires_on`** (fecha, nullable) + **`alert_days_before`** (entero, días de anticipación). No hay fecha ni toggle «tiene vencimiento» por documento en v1.
4. **Alertas UI:** documentación **vencida** si `documentation_expires_on` &lt; hoy; **por vencer** si la fecha está entre hoy y hoy + `alert_days_before` (inclusive). Si `documentation_expires_on` es null, no aplica alerta documental (solo puede aplicar vigencia de **contrato** en servicios/dashboard).
5. **Default anticipación:** si hay fecha y `alert_days_before` vacío al guardar → persistir **30** (alineado a KPI dashboard «por vencer ≤30» actual, sustituyendo umbrales fijos 30/60 sobre `doc_*` de servicio).
6. **Edición:** solo usuarios con `comercial.matriz.manage`; consulta con `comercial.matriz.view` (listado, filtros, export, sin PATCH).
7. **Servicios:** formularios y listados de servicio **no** muestran ni persisten checklist; vigencia operativa del servicio sigue siendo portafolio, fechas de contrato, asesor, etc.
8. **Portafolio inactivos:** servicios en `inactivos` **no** participan en la migración de estados hacia el cliente; KPIs de dashboard siguen excluyendo inactivos como hoy.

### Migración servicio → cliente (supuesto usuario #3)

Por cada `commercial_client_id` y cada `document_key`:

- Considerar solo servicios con `portfolio != inactivos`.
- Tomar el **status** del servicio **activo** (no inactivos) con **`updated_at` más reciente** entre los que tengan valor no null en ese `doc_*`; si todos null, fila checklist con status null → normalizar a `pending` o null según implementación (recomendado: **`pending`** para filas seed post-migración).

Para **`documentation_expires_on`** del cliente (un solo valor):

- Entre todos los servicios activos del cliente, reunir fechas `*_expires_on` donde `*_tracks_expiry = true`.
- Si hay al menos una → asignar la fecha **más próxima** (mínimo cronológico) como vencimiento documental del cliente (criterio conservador para alertas).
- **`alert_days_before`:** **30** por defecto en migración si no existe dato previo.

Tras migración exitosa: **eliminar** de `commercial_services` las columnas `doc_*`, `*_tracks_expiry`, `*_expires_on` (30 columnas). No mantener doble fuente de verdad.

### Import Excel MT-CO-01

- Por fila/hoja (servicio): mapear estados documentales al **cliente** del NIT (misma regla de conflicto: actualizar ítem si el servicio importado tiene `updated_at` más reciente tras save, o reaplicar merge post-save por cliente).
- Columnas de vencimiento documental en Excel: actualizar **`documentation_expires_on`** del cliente con el **mínimo** entre la fecha ya guardada y las fechas parseadas en la fila (si aplica).
- **No** escribir columnas `doc_*` en `commercial_services` (eliminadas).

### Vigencia en listado servicios y dashboard

- **Contrato:** sin cambio semántico (`contract_end` vs fecha de referencia / hoy).
- **Documentación:** usar solo el checklist del **cliente** vinculado (`documentation_expires_on` + `alert_days_before`), no el servicio.
- Un servicio entra en filtro `vigencia=expiring|expired` si cumple contrato **o** su cliente cumple alerta documental (misma ventana: `alert_days_before` del cliente para «expiring»).
- KPIs dashboard `expiring_soon` / `expired`: recalcular sobre colección de servicios no inactivos con la lógica unificada anterior (sustituir `CommercialService::isExpiringSoon` / `isExpired` que leen `doc_*`).

## Permisos (`config/access.php`)

| Permiso | Rol(es) tipicos | Descripcion |
| --- | --- | --- |
| `comercial.matriz.view` | Comercial consulta | Ver listado clientes, checklist dedicado, export checklist, dashboard y servicios. |
| `comercial.matriz.manage` | Comercial gestion | Editar estados checklist, fecha y días de anticipación; CRUD maestro cliente/servicio (sin checklist en servicio). |

**Sin permisos nuevos** ni cambios en `config/access.php` para esta feature (salvo corrección textual opcional en doc de permisos).

Autorización en controlador: mismos helpers/patrón que `CommercialClientController` / `CommercialServiceController` (`authorizeView` / `authorizeManage`).

## Rutas

Registrar en `routes/areas/comercial.php` dentro del grupo `comercial/clientes`, **antes** de `/{client}` para evitar colisión:

| Metodo | URI | Nombre | Archivo | Notas |
| --- | --- | --- | --- | --- |
| GET | `/comercial/clientes/checklist-documental` | `comercial.matriz.clients.checklist.index` | `routes/areas/comercial.php` | Listado checklist; query `q`, `city`, `doc_vigencia=expiring\|expired\|all` (nombre afinable). |
| GET | `/comercial/clientes/checklist-documental/exportar` | `comercial.matriz.clients.checklist.export` | idem | Export Excel; mismos filtros que index. |
| PATCH | `/comercial/clientes/{client}/checklist-documental` | `comercial.matriz.clients.checklist.update` | idem | Body: `documentation_expires_on`, `alert_days_before`, `documents` map `document_key => status`; middleware + `comercial.matriz.manage`. |

**UI listado clientes (`clients/index`):** enlace/botón «Checklist documental» en el header del panel (junto a export / nuevo cliente), **encima** del `<form>` de filtros, apuntando a `comercial.matriz.clients.checklist.index`.

Opcional UX: enlace «Volver a clientes» en la pantalla checklist.

## Base de datos

### Nuevas / alteraciones

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `commercial_clients.documentation_expires_on` | alter | `date`, nullable. |
| `commercial_clients.alert_days_before` | alter | `unsignedSmallInteger`, nullable; default aplicado en app **30** si hay fecha. |
| `commercial_client_document_items` | migracion create | Ver esquema abajo. |
| `commercial_services` — 30 columnas `doc_*` | alter drop | Tras copia de datos en la misma migración o migración encadenada inmediata. |

### Esquema `commercial_client_document_items`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `commercial_client_id` | FK → `commercial_clients`, cascade delete | |
| `document_key` | string(32) | Claves actuales: `doc_economic_proposal`, …, `doc_annex_2`. |
| `status` | string(16) | Nullable o default `pending`; valores del catálogo. |
| `timestamps` | | |

Índice único: `(commercial_client_id, document_key)`.

**Seed post-migración:** para cada cliente existente, asegurar 10 filas (insert faltantes con `pending` si no hubo dato migrado).

### Columnas `doc_*` en `commercial_services`

| Accion | Detalle |
| --- | --- |
| **Migrar** | Status → `commercial_client_document_items`; fechas tracks → agregado a `documentation_expires_on` del cliente (min). |
| **Deprecar / eliminar** | Quitar columnas en la entrega; actualizar `$fillable`, casts y métodos `documentExpiryFields`, `trackedDocumentExpiryDates`, `scopeFilterByVigencia` en `CommercialService` para delegar en cliente o solo contrato. |
| **Modelo servicio** | Conservar solo lógica de **contrato** en métodos de vigencia; extraer catálogo documental fuera del modelo servicio si se eliminan columnas. |

## Capas a implementar

- [x] Migracion(es) — datos + drop columnas servicio
- [x] Modelo(s) — `CommercialClientDocumentItem`; relaciones y helpers de alerta en `CommercialClient`
- [x] Controlador(es) — acciones checklist en `CommercialClientController` o `CommercialClientChecklistController` dedicado (preferido si mantiene archivos &lt; convención del modulo)
- [x] Form Request(s) — `UpdateCommercialClientChecklistRequest` (validación estados, fecha, días)
- [x] Vista(s) Blade — `clients/checklist/index.blade.php`; ajuste `clients/index.blade.php`; retirar checklist de `partials/service-fields.blade.php` y vistas servicio
- [x] JavaScript — mínimo (select estado + submit por cliente o fila); sin framework nuevo
- [x] Export Excel — clase en `app/Exports/` extendiendo `BaseExport`; ruta export checklist
- [x] Servicio opcional — `CommercialClientChecklistSync` solo si justifica import + migración (evitar Repository)
- [x] Tests — `CommercialMatrixTest` + tests checklist

## Componentes reutilizables

- `App\Exports\BaseExport` + `<x-export-excel>` en pantalla checklist.
- Catálogo documental compartido (extracción desde `CommercialService`).
- Badges/pills existentes (`status-pill`) para vigencia documental.
- Estilo navegación: `.module-tab` / botones alineados a [`nav-chrome-ui.mdc`](../../.cursor/rules/nav-chrome-ui.mdc) si se añaden pestañas Clientes ↔ Checklist.

## Documentacion a actualizar

- [ ] `docs/modules/matriz-clientes.md` — checklist por cliente, rutas, migración, import, vigencia.
- [ ] `docs/user/matriz-clientes.md` — procedimiento operativo checklist y alertas UI.
- [ ] `docs/INDEX.md` — solo si falta enlace o descripción del flujo checklist.

## Archivos compartidos (`shared-files`)

Marcar **`shared-files: true`** en Task Cards que toquen:

| Archivo | Motivo |
| --- | --- |
| `routes/areas/comercial.php` | Rutas checklist (orden con `/{client}`) |
| `app/Models/CommercialService.php` | Retiro columnas y refactor vigencia |
| `app/Http/Controllers/Comercial/CommercialDashboardController.php` | KPIs expiring/expired |
| `app/Http/Controllers/Comercial/CommercialServiceController.php` | Listado vigencia, vistas sin checklist |
| `app/Http/Requests/Comercial/StoreCommercialServiceRequest.php` | Quitar reglas `doc_*` |
| `app/Http/Requests/Comercial/UpdateCommercialServiceRequest.php` | Idem |
| `app/Services/Comercial/MtCo01Importer.php` | Escritura checklist cliente |
| `resources/views/areas/comercial/matriz-clientes/partials/service-fields.blade.php` | Eliminar bloque checklist |
| `resources/views/areas/comercial/matriz-clientes/services/create.blade.php` | Sin checklist |
| `resources/views/areas/comercial/matriz-clientes/services/edit.blade.php` | Sin checklist |
| `resources/views/areas/comercial/matriz-clientes/clients/index.blade.php` | Botón checklist |
| `tests/Feature/CommercialMatrixTest.php` | Regresión matriz + checklist |

**Ownership principal (sin flag):** migración checklist, modelo ítems, vistas checklist, export checklist, Form Request checklist, tests nuevos checklist.

## Task cards sugeridas (vertical slices)

Orden recomendado para AgentSj (un agente feature a la vez; respetar `shared-files`):

### FEAT-014-T1 — Esquema, migración y dominio cliente

- Migración: columnas en `commercial_clients`, tabla `commercial_client_document_items`, script de copia desde `commercial_services`, drop columnas `doc_*` en servicios.
- Catálogo documental compartido; modelos y relaciones (`CommercialClient::documentItems()`, helpers `isDocumentationExpired`, `isDocumentationExpiringSoon`).
- Refactor `CommercialService`: vigencia basada en contrato + delegación a cliente para parte documental (métodos usados por scope y dashboard preparados).
- Tests: migración (cliente con 2 servicios distintos → gana `updated_at` reciente); cliente sin servicios activos.

### FEAT-014-T2 — Pantalla checklist + edición + enlace desde clientes

- Rutas `checklist.index`, `checklist.update` (PATCH); vista tabla fila/documento con agrupación por NIT; campos fecha/días en cabecera de grupo.
- Autorización view/manage; mensajes flash; filtros `q`, `city`, `doc_vigencia`.
- Botón en `clients/index` antes de filtros.
- Tests: 403 sin permiso; manage actualiza estado y fecha; view no PATCH.

### FEAT-014-T3 — Servicios, import y retiro UI checklist servicio (`shared-files`)

- Quitar checklist de requests, `service-fields`, create/edit servicio.
- `MtCo01Importer` escribe ítems cliente + `documentation_expires_on` (reglas min / merge).
- Ajustar `CommercialServiceController` index + `scopeFilterByVigencia` sin columnas `doc_*`.
- Actualizar tests existentes de document expiry en servicio → mover escenarios a checklist cliente.

### FEAT-014-T4 — Dashboard, export checklist y cierre (`shared-files`)

- `CommercialDashboardController`: KPIs y filtros con lógica cliente documental.
- Export checklist `BaseExport` + ruta `checklist.export`.
- Smoke tests dashboard vigencia; test export columnas esperadas.
- Handoff Documentador (docs módulo/usuario).

**Dependencia externa (no bloqueante v1):** FEAT-013 para correos; dejar comentario/TODO en servicio de alertas futuro (`module=comercial`, slug propuesto `documentation_expiring`).

## Criterios de aceptacion

1. Desde **Clientes**, el botón «Checklist documental» (antes de filtros) abre la **ruta dedicada** con tabla fila por documento agrupada por cliente.
2. Usuario con **view** ve checklist y export; usuario **manage** puede cambiar estados y el par fecha/días por cliente; **manage** requerido para PATCH.
3. **Crear/editar servicio** no muestra ni guarda checklist documental.
4. Tras migración, estados previos en servicios activos aparecen en el checklist del cliente según regla `updated_at`; no quedan columnas `doc_*` en BD en servicios.
5. Filtros **por vencer / vencido** en checklist y en **Servicios** consideran vencimiento documental del **cliente** (`documentation_expires_on` + `alert_days_before`) además del contrato del servicio.
6. **Dashboard** KPIs «por vencer» y «vencidos» coherentes con la misma regla (no lectura de `doc_*` en servicio).
7. **Import MT-CO-01** actualiza checklist del cliente sin errores y sin reintroducir columnas en servicios.
8. **Export Excel** del checklist respeta filtros activos y usa `BaseExport`.
9. **Sin envío de correo** en esta entrega.
10. Tests en verde para flujos anteriores (`php artisan test --compact` archivos comercial afectados).

## Validacion local

1. `php artisan migrate` en BD con datos de prueba (cliente multi-servicio).
2. Revisar checklist migrado vs servicio legacy (muestra acotada).
3. Editar fecha/días y estados; ver badges y filtros.
4. Listado servicios `?vigencia=expiring` y dashboard con cliente en ventana de anticipación.
5. `php artisan comercial:import-mt-co-01` (archivo local) — checklist cliente actualizado.
6. `php artisan test --compact tests/Feature/CommercialMatrixTest.php` (+ tests checklist).
7. `vendor/bin/pint --dirty` en PHP modificado.

## Tests (minimos)

| Test | Intencion |
| --- | --- |
| `test_checklist_index_forbidden_without_permission` | GET checklist → 403. |
| `test_checklist_index_ok_with_view_permission` | GET → 200, contiene documentos catálogo. |
| `test_checklist_update_requires_manage` | PATCH → 403 con view. |
| `test_checklist_update_persists_status_and_client_expiry` | PATCH → BD ítems + columnas cliente. |
| `test_service_form_does_not_persist_documents` | POST/PATCH servicio sin campos doc → ok, ítems sin cambio accidental. |
| `test_migration_picks_latest_active_service_status` | Feature con RefreshDatabase + migración seed. |
| `test_services_vigencia_filter_uses_client_documentation` | Filtro expiring por fecha cliente. |
| `test_dashboard_expiring_counts_client_documentation` | KPI coherente. |
| `test_checklist_export_returns_spreadsheet` | GET export → 200, content-type excel. |
| Adaptar tests legacy `doc_rut_*` en servicio | Escenarios movidos a checklist. |

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| Pérdida de granularidad: un solo vencimiento por cliente vs Excel con fechas por documento | Regla import/min documentada; comunicar a Comercial; v2 podría no ampliarse sin cambio de negocio. |
| Conflictos multi-servicio distintos estados | Regla `updated_at` en migración e import; documentar en guía usuario. |
| Drop columnas `doc_*` irreversible | Backup BD antes despliegue; migración `down` restaura estructura sin datos si se requiere. |
| Orden rutas `checklist-documental` vs `{client}` | Rutas estáticas primero en `comercial.php`. |
| KPIs dashboard cambian vs histórico | Esperado; nota en control de cambios usuario. |
| FEAT-013 no desplegado | No bloquea v1 UI; definir slug futuro en doc técnica. |

## Supuestos documentados (preguntas analista sin respuesta explícita)

| # | Supuesto en brief |
| --- | --- |
| 3 | Migración por documento: servicio activo, `updated_at` más reciente. |
| 11 | Tabla: fila por documento; fecha/días en cabecera de grupo por NIT. |
| 2 | Misma entrega alinea dashboard y vigencia servicios al checklist cliente. |
| 13 | Export checklist con `BaseExport`. |
| 14 | Edición solo `comercial.matriz.manage`. |
| 12 | UX mínima: formulario PATCH por cliente (estados + fecha/días en bloque); select por fila documento. |
| 7 | Regla vencimiento: sin toggle por documento; fecha opcional a nivel cliente. |
| 9 | Default 30 días si hay fecha sin días. |

## Integracion futura FEAT-013 (fase 2)

- Nuevo tipo en `notification_types`: `module = comercial`, slug `documentation_expiring` (nombre afinable).
- Job diario: clientes con `isDocumentationExpiringSoon()` → correo vía `NotificationConfigService`.
- Fuera de alcance FEAT-014.

## Aprobacion

- [x] Analista — decisiones 4, 6, 8, 10 cerradas; resto supuestos
- [x] Arquitecto — brief final
- [ ] Usuario — confirmacion explicita del brief
- [ ] AgentSj — plan de orquestacion y Task Cards en `docs/TASKS.md`
