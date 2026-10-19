# Feature Brief — FEAT-047

> Brief final del Arquitecto (2026-10-19). Consolida Analista cerrado (`FEAT-047-analyst.md`) + plan semilla + exploración de bandeja Compras, exporters FO-AD-44 y patrón Comercial view/edit. **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-047 |
| Modulo / area | Compras / `purchase-requests` (+ catálogo Proveedores) |
| Titulo | Compras: Proveedores + FO-AD-07 + alinear FO-AD-44 |
| Solicitante | Usuario (plan Compras Proveedores OC) |
| Fecha | 2026-10-19 |
| Run log | [`docs/runs/FEAT-047-run-log.md`](../runs/FEAT-047-run-log.md) |
| Analista | [`docs/briefs/FEAT-047-analyst.md`](FEAT-047-analyst.md) (CERRADO) |
| Plan semilla | `compras_proveedores_oc_14f77b95.plan.md` |
| Doc módulo | [`docs/modules/purchase-requests.md`](../modules/purchase-requests.md) |

## Objetivo

Completar el pendiente de Compras tras Fases 0–7 ya en producción: alinear el export FO-AD-44 al formato oficial Marzo 2026 v01; ofrecer un catálogo de proveedores importable desde plantilla **VINE01**; emitir Orden de Compra FO-AD-07 desde la bandeja unificada (solicitudes de compra **y** suministros); y registrar evaluación post-entrega del proveedor en sistema (sin imprimir ese bloque en el documento).

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decisión |
| --- | --- | --- |
| 1 | Pendiente real | Solo Proveedores + FO-AD-07 + alinear FO-AD-44 (+ evaluación v1). Fases 0–7 **fuera**. |
| 2 | Permisos Proveedores | `purchase.providers.view` + `purchase.providers.edit`; **edit implica view** en AccessService. Labels: `Proveedores: Ver` / `Proveedores: Editar`. |
| 3 | FO-AD-07 auth | Solo `purchase.tab.processing` (sin permiso nuevo). |
| 4 | `dias_pago` | Entero nullable; editable en alta/edición; import acepta `DIAS_PAGO` opcional. |
| 5 | Plantilla import | Nombre **VINE01** en UI/docs/plantilla. **No** llamar “BASE” en producto. |
| 6 | Filtro import | Solo filas `PROVEEDOR=SI`. |
| 7 | Evaluación en documento | Bloque evaluación **NO** se imprime en PDF/Excel FO-AD-07. |
| 8 | Pesos evaluación | Referencias/cantidades 25%; Oportunidad entrega 25%; Calidad producto 50%. |
| 9 | Umbrales | 80–100 Compra satisfactoria; 60–79 Requiere seguimiento; &lt;60 Compra insatisfactoria. |
| 10 | Momento evaluación | Después de la entrega (no al generar la OC). |
| 11 | FO-AD-44 | Alinear a Marzo 2026 Versión 01. |
| 12 | Orden fases | A → B → C (+ evaluación con C). |
| 13 | Shared-files | `config/access.php` + rutas/nav Compras. |
| 14 | Migración permisos legacy | **No** — asignación Spatie manual. |
| 15 | CRM clientes | Fuera (`CLIENTE=SI` y `PROVEEDOR≠SI`). |
| 16 | Evaluación v1 | UI + BD en esta feature; permiso `purchase.providers.edit`; ligada a OC emitida; **una por OC** editable; Calif. 1–5. |
| 17 | UI Proveedores | **Tablero** del área Compras (`boards.proveedores`), ya no pestaña del subnav de Solicitudes. Visible con `purchase.providers.view`. |
| 18 | Origen FO-AD-07 | Compra **y** suministro desde bandeja. |
| 19 | `numero_oc` | Autoincremental sin límite ni prefijos (como `id`); único global. |

## Decisiones tecnicas del Arquitecto

| Tema | Decisión | Justificación |
| --- | --- | --- |
| Ownership | Módulo `purchase-requests` (área Compras); controladores bajo `App\Http\Controllers\PurchaseRequests\`; vistas `resources/views/modules/purchase-requests/` (+ subcarpeta `providers/`) | Misma familia que bandeja; **tablero** del área Compras (actualizado 2026-10-19) |
| Rutas | Extender `routes/modules/purchase-requests.php` (evitar tocar `routes/web.php` si el archivo ya está required) | Menos shared-files; coherente con processing; el tablero se activa por el módulo `compras` |
| Permisos | Keys en `system_permissions` junto a `purchase.tab.*`; grupo Admin UI `compras` → subgroup `providers`. La visibilidad del tablero usa `purchase.providers.view` (no requiere `view.board.compras.proveedores`) | Patrón Comercial labels legibles; board con permiso funcional |
| AccessService | Extender `PurchaseAccessService`: `canViewProviders` / `canEditProviders` (**edit ⇒ view**) y `canViewProvidersBoard`; **no** se agrega a `visibleTabsFor` | Espejo `CommercialAccessService`; Proveedores deja de ser pestaña |
| Middleware tablero | Se conserva `purchase.tab:providers` / `purchase.tab:providers.edit` (solo enforcement de rutas), sin mostrarlo en el subnav | Menos cambios en middleware; el chrome del tablero lo resuelve `NavigationResolver` |
| Origen OC | **Polimórfico** `source_type` + `source_id` en `purchase_orders` (morph map) | Ver § Modelo origen OC |
| `numero_oc` | **Usar el `id` autoincremental de `purchase_orders` como número de OC visible** (accessor `numeroOc()` / columna virtual en API; sin secuencia dedicada ni pad) | Cumple “como el id” sin doble contador ni carreras de secuencia |
| Evaluación | Tabla `purchase_order_evaluations` 1:1 con `purchase_orders` (`unique purchase_order_id`) | Una por OC editable; no ensucia cabecera de emisión |
| Pesos/umbrales | Constantes en `config/purchase-orders.php` (o sección en `purchase-requests.php`) | No hardcodear magia en Blade |
| Uppercase | Form Requests + import: `UppercasesValidatedText` / `UppercaseFormText` (excepto email / keys) | Regla proyecto |
| Export | FO-AD-44 y FO-AD-07 Excel vía PhpSpreadsheet / servicios existentes o nuevos; PDF DomPDF; **no** `excelHtml5`; FO-AD-07 Excel puede heredar estilo `BaseExport` donde aporte | AGENTS.md |
| Import ~9k | Comando Artisan + UI import con chunk; `--dry-run` / `--limit` | database-safety |
| Repository | **No** | Convención proyecto |
| Audit | Eventos vía wrapper purchase / `SystemAuditService` (create/update provider, emit OC, evaluate) | audit-log |
| Migrate | Solo migraciones aditivas. **Prohibido** `migrate:fresh` / wipe | Protección de datos |

### Modelo origen OC (polimórfico) — decisión formal

**Elegido: `source_type` + `source_id` (Eloquent `morphTo`).**

| Campo | Valores / notas |
| --- | --- |
| `source_type` | Morph class o alias mapeado: `purchase_request` → `PurchaseRequest`; `supply_request` → `SupplyRequest` |
| `source_id` | PK del origen |
| Índice | `(source_type, source_id)` **no único** (varias OC por misma solicitud/suministro si Compras emite más de una) |
| Ítems | `purchase_order_items` con snapshot de línea; opcional `source_item_type`/`source_item_id` nullable para trazar línea origen |

**Por qué no dual-FK ni tablas puente:** la bandeja ya unifica dos orígenes (`ComprasQueueService` con `tipo` purchase|supply); el morph refleja ese contrato en una sola cabecera OC, evita XOR de FKs nullables y no requiere tablas puente para v1. Integridad referencial se valida en servicio al crear (origen existe y está en bandeja / processable). Morph map explícito en `AppServiceProvider` o en el modelo.

**Flujo emisión:** desde `processing.purchase` o `processing.supply` (o acción dedicada en detalle bandeja) → elegir proveedor activo → capturar precios/IVA por línea → persistir OC `estado=emitida` → descargar PDF/Excel FO-AD-07. Auth: `purchase.tab.processing` + policy `process` del origen.

### `numero_oc` — recomendación (1 frase)

Usar el **`id` de la fila** `purchase_orders` como número de OC visible (sin secuencia dedicada ni prefijos): es autoincremental único global y cumple la regla de negocio “como el id” sin un segundo contador.

### Diagrama de fases

```text
Fase A ──► FO-AD-44 PDF/Excel (compra + suministro) Marzo 2026 v01 + foto embebida
Fase B ──► purchase_providers + permisos + pestaña + CRUD + import VINE01 + export
Fase C ──► purchase_orders (+ items) polimórfico + FO-AD-07 PDF/Excel
           └──► purchase_order_evaluations (UI post-entrega; no impresa)
```

## Alcance

### Incluye

#### Fase A — Alinear FO-AD-44

- Encabezado oficial: código FO-AD-44, versión Marzo 2026 / v01, página (PDF/Excel compra y suministro).
- Columnas: Cantidad \| Foto \| Descripción \| Referencia \| Utilización \| Ubicación (ortografía UI con tildes).
- Embeber foto de ítem si existe en disco `public`; si no, celda vacía o “S/A” (no dejar `N/A` fijo sin intentar embeber).
- Ajustar `PurchaseRequestExcelExporter`, `PurchaseRequestPdfService` (+ Blade PDF), `SupplyPurchaseReportExporter`, `SupplyPurchasePdfExporter` (+ Blade), y metadatos en `config/purchase-requests.php` / `config/supplies.php` (`form_version`, título, mes/año documento si aplica).
- Tests Feature de export + docs módulo.

#### Fase B — Catálogo Proveedores

- Tabla `purchase_providers` (ver esquema).
- Permisos + `PurchaseAccessService` + tablero **Proveedores** del área Compras (`config/access.php` `boards.proveedores`, `.module-tab`).
- Listado DataTables **server-side** (~9k); filtros; default `estado=activo` (filtro para inactivos).
- CRUD (alta/edición); campos FO-AD-07 opcionales: `contacto`, `direccion`, `ciudad`, `forma_pago`, `dias_pago`.
- Import **VINE01** (solo `PROVEEDOR=SI`); upsert por `identificacion`; columna opcional `DIAS_PAGO`; comando `--dry-run` / `--limit`.
- Export Excel del catálogo (`BaseExport` + `<x-export-excel>`).
- Plantilla descarga nombrada **VINE01** (no “BASE”).
- Tests acceso view vs edit; import; uppercase en nombre.

#### Fase C — FO-AD-07 + evaluación

- Tablas `purchase_orders`, `purchase_order_items`, `purchase_order_evaluations`.
- Emisión desde bandeja para **compra y suministro**; proveedor searchable-select; precios por línea; totales.
- Snapshot de datos de proveedor en la OC (razón social, NIT/`identificacion`, contacto, tel, correo, dirección, ciudad, forma_pago / derivado de `dias_pago`, asume_cliente si aplica al origen compra).
- Export FO-AD-07 PDF + Excel **sin** bloque de evaluación.
- UI evaluación post-entrega: Calif. 1–5 por criterio; pesos 25/25/50; cálculo Resultado y %; umbrales; observaciones; fecha recibido; editable; auth `purchase.providers.edit`.
- Tests 403 sin processing / sin edit evaluación; número OC = id; una evaluación por OC.

### Fuera de alcance

- Reabrir Fases 0–7 del plan Compras viejo.
- CRM / catálogo solo-clientes.
- Migración automática de permisos Spatie.
- Imprimir evaluación en FO-AD-07.
- Prefijos / pad en `numero_oc`.
- Workflow de “borrador OC” multi-paso avanzado (v1: emitir y persistir; si se guarda borrador, opcional mínimo `borrador`→`emitida` sin sobre-diseñar).

## Reglas de negocio

1. Import VINE01: columnas `IDENTIFICA`, `NOMBRE`, `TELEFONO`, `CORREO_ELE`, `CLIENTE`, `PROVEEDOR`, `ESTADO` + opcional `DIAS_PAGO`; persistir solo si `PROVEEDOR=SI` (normalizar SI/SÍ/1/true).
2. Upsert por `identificacion` (IDENTIFICA); textos libres → MAYÚSCULAS UTF-8 excepto email.
3. Listado operativo default: proveedores activos; permitir ver inactivos con filtro.
4. FO-AD-07: cabecera asociada de negocio desde proveedor (+ snapshot); líneas con cantidad, artículo, descripción, valor unidad, IVA, valor total; firmas según plantilla oficial Enero 2025 v04 (o versión que entregue el usuario al Feature); **sin** zona evaluación.
5. Evaluación: pesos fijos 25/25/50; Calif. entero 1–5; Resultado por criterio ≈ Calif × (Peso/100); total % = (suma resultados / 5) × 100 (o fórmula equivalente documentada en config y tests); umbrales 80–100 / 60–79 / &lt;60; solo sobre OC `emitida`; una fila evaluación por OC.
6. Generar/descargar FO-AD-07 exige `purchase.tab.processing`; mutar evaluación exige `purchase.providers.edit`.

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripción |
| --- | --- | --- |
| `purchase.providers.view` | Asignación manual (Admin usuarios) | Ver, filtrar, export catálogo Proveedores |
| `purchase.providers.edit` | Asignación manual | CRUD + import VINE01 + **evaluación post-entrega**; implica view en AccessService |
| `purchase.tab.processing` | (existente) | Bandeja + generar/descargar FO-AD-07 |
| `purchase.tab.*` restantes | (existentes) | Sin cambio semántico |

**Admin UI:** labels `Proveedores: Ver` / `Proveedores: Editar` bajo grupo Compras. Sin migración automática de grants.

**Pestaña:** visible si `canViewProviders` (view o edit). Botones mutación / import / evaluación solo con edit.

## Rutas

Prefijo autenticado existente: `/purchase-requests/{module}/` en `routes/modules/purchase-requests.php`.

| Método | URI (relativa al módulo) | Nombre | Auth |
| --- | --- | --- | --- |
| GET | `proveedores` | `purchase-requests.providers.index` | providers.view |
| GET | `proveedores/datatable` | `purchase-requests.providers.datatable` | providers.view |
| GET | `proveedores/create` | `purchase-requests.providers.create` | providers.edit |
| POST | `proveedores` | `purchase-requests.providers.store` | providers.edit |
| GET | `proveedores/{purchase_provider}/edit` | `purchase-requests.providers.edit` | providers.edit (view para solo lectura opcional) |
| PUT/PATCH | `proveedores/{purchase_provider}` | `purchase-requests.providers.update` | providers.edit |
| GET | `proveedores/export` | `purchase-requests.providers.export` | providers.view |
| GET | `proveedores/import/template` | `purchase-requests.providers.import-template` | providers.edit |
| POST | `proveedores/import` | `purchase-requests.providers.import` | providers.edit |
| GET | `bandeja-compras/…/orden-compra/create` (compra o suministro) | `purchase-requests.orders.create` | processing |
| POST | `…/orden-compra` | `purchase-requests.orders.store` | processing |
| GET | `ordenes-compra/{purchase_order}` | `purchase-requests.orders.show` | processing (o policy) |
| GET | `ordenes-compra/{purchase_order}/pdf` | `purchase-requests.orders.pdf` | processing |
| GET | `ordenes-compra/{purchase_order}/excel` | `purchase-requests.orders.excel` | processing |
| GET/PUT | `ordenes-compra/{purchase_order}/evaluacion` | `purchase-requests.orders.evaluation.*` | providers.edit |

Nombres exactos pueden ajustarse en Task Card; mantener prefijo `purchase-requests.*` y `{module}`.

Rutas FO-AD-44 existentes se mantienen; solo cambia implementación de exporters.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `purchase_providers` | migración nueva | Ver columnas abajo |
| `purchase_orders` | migración nueva | Morph origen + snapshot proveedor; `id` = número OC |
| `purchase_order_items` | migración nueva | Líneas con valores |
| `purchase_order_evaluations` | migración nueva | 1:1 con OC |
| Spatie permissions | seeder / `app:sync-permissions` | Crear keys; sin backfill grants |

### `purchase_providers`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | |
| `identificacion` | string(50) unique | ← IDENTIFICA |
| `nombre` | string(255) | |
| `telefono` | string(50) nullable | |
| `correo` | string(255) nullable | no uppercase |
| `estado` | string(20) | `activo` / `inactivo` (o enum multi-driver) |
| `dias_pago` | unsignedSmallInteger nullable | |
| `contacto` | string(255) nullable | FO-AD-07 |
| `direccion` | string(500) nullable | |
| `ciudad` | string(120) nullable | |
| `forma_pago` | string(120) nullable | texto editable |
| `created_by` | FK users nullable | |
| `timestamps` | | |

Índices: `estado`.

> Ajuste 2026-10-19 (pre-produccion): se eliminaron `es_cliente` y `es_proveedor`; la tabla solo almacena proveedores y el import VINE01 ignora `CLIENTE`.

### `purchase_orders`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | bigint PK | **= número OC visible** |
| `source_type` | string | morph |
| `source_id` | unsignedBigInteger | morph |
| `provider_id` | FK `purchase_providers` nullOnDelete? | prefer prefer `restrict` si hay OC |
| Snapshot | `provider_identificacion`, `provider_nombre`, `provider_telefono`, `provider_correo`, `provider_contacto`, `provider_direccion`, `provider_ciudad`, `provider_forma_pago`, `provider_dias_pago` | Congela datos al emitir |
| `fecha` | date | |
| `ciudad` | string nullable | cabecera OC |
| `asume_cliente` | string/bool nullable | desde origen compra si aplica |
| `observaciones` | text nullable | |
| `estado` | string | `borrador` \| `emitida` (v1 puede crear directo `emitida`) |
| `total` | decimal(15,2) | |
| `created_by` | FK users | |
| `timestamps` | | |

Índice compuesto `(source_type, source_id)`.

### `purchase_order_items`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | PK | |
| `purchase_order_id` | FK cascade | |
| `orden` | unsignedSmallInteger | |
| `cantidad` | decimal/int | |
| `articulo` | string nullable | |
| `descripcion` | string/text | |
| `valor_unidad` | decimal(15,2) | |
| `iva` | decimal(15,2) default 0 | |
| `valor_total` | decimal(15,2) | |
| `source_item_type` / `source_item_id` | nullable morph | traza opcional |

### `purchase_order_evaluations`

| Columna | Tipo | Notas |
| --- | --- | --- |
| `id` | PK | |
| `purchase_order_id` | FK unique | una por OC |
| `fecha_recibido` | date nullable | |
| `calif_referencias_cantidades` | tinyint 1–5 | peso 25% |
| `calif_oportunidad_entrega` | tinyint 1–5 | peso 25% |
| `calif_calidad_producto` | tinyint 1–5 | peso 50% |
| `resultado_referencias` / `_oportunidad` / `_calidad` | decimal nullable | cache cálculo |
| `puntaje_porcentaje` | decimal(5,2) | 0–100 |
| `umbral_codigo` | string | `satisfactoria` / `seguimiento` / `insatisfactoria` |
| `observaciones` | text nullable | |
| `evaluated_by` | FK users | |
| `timestamps` | | |

Multi-driver: enums vía string + check en app; evitar `enum()`/`change()` rotos en sqlite.

## Capas a implementar

- [ ] Migración(es) multi-driver
- [ ] Modelos: `PurchaseProvider`, `PurchaseOrder`, `PurchaseOrderItem`, `PurchaseOrderEvaluation`
- [ ] Controladores: `PurchaseProviderController`, `PurchaseOrderController` (o métodos en Processing + Evaluation)
- [ ] Form Requests + `UppercasesValidatedText`
- [ ] `PurchaseAccessService` + config access + sync permissions
- [x] Datatable service proveedores
- [x] Import VINE01 service + comando Artisan + plantilla export
- [ ] Exporters FO-AD-44 alineados (compra + suministro)
- [ ] Exporters FO-AD-07 PDF + Excel (sin evaluación)
- [ ] Vistas Blade (pestaña, listado, forms, modal import, UI OC, UI evaluación)
- [ ] JS mínimo (DataTables server-side; form OC precios)
- [ ] Audit events
- [ ] Tests Feature (acceso, import, export, OC morph supply+purchase, evaluación)
- [ ] Docs modules/user + ACCESS_CONTROL + INDEX

## Componentes reutilizables

- `<x-searchable-select>` (proveedor en OC; filtros)
- `<x-export-excel>` + `App\Exports\BaseExport` (catálogo; FO-AD-07 Excel si encaja)
- Chrome: `req-manage-page`, `req-manage-filters`, `.module-tab`, icon-btn canónicos
- Partial subnav existente `modules.purchase-requests.partials.subnav`
- DomPDF + PhpSpreadsheet (ya en proyecto)
- `UppercaseFormText` / trait Form Request

## Vista referencia UI (`module-ui-quality`)

| Pantalla | Clonar de |
| --- | --- |
| Listado Proveedores | **Cursos registros** / **Ficha empleados** (DataTables server-side + `req-manage-*` + searchable-select filtros) |
| Subnav tablero | Tableros del área Compras (`NavigationResolver`, `.module-tab`) — Proveedores ya no usa el subnav de Solicitudes |
| Modal import VINE01 | Formación / Cliente interno (modal opaco, icon-btn upload) |
| Emisión OC / evaluación | Layout `pur-req-form-layout` / process-purchase (secciones FO + aside) |
| Bandeja (acción OC) | Icon-btn fila canónico `.cursos-catalogo-page__icon-btn` |

Primera entrega visual pulida; no prototipo genérico. Tras CSS/JS: `npm run build`. Smoke Chrome DevTools MCP en tablero Proveedores + flujo OC.

## Documentacion a actualizar

- [ ] `docs/modules/purchase-requests.md` (FO-AD-44, FO-AD-07, bandeja OC)
- [ ] `docs/modules/purchase-providers.md` (nuevo — catálogo + VINE01 + evaluación permiso)
- [ ] `docs/user/purchase-requests.md` / `docs/user/purchase-providers.md`
- [ ] `docs/ACCESS_CONTROL.md` (permisos providers)
- [ ] `docs/INDEX.md`
- [ ] `docs/ARCHITECTURE.md` ownership fila si se documenta submódulo proveedores (opcional)

## Archivos compartidos (`shared-files`)

| Archivo | Motivo |
| --- | --- |
| `config/access.php` | Keys + labels Admin + grupo Compras |
| `routes/modules/purchase-requests.php` | Rutas proveedores + OC + evaluación |
| Subnav / builders de tabs en controladores purchase | Tablero Proveedores (Compras) |
| `database/seeders/RoleAndPermissionSeeder.php` (o sync) | Registrar permisos Spatie |
| `resources/css/app.css` | Solo si hace falta ajuste menor chrome (evitar familia icon-btn nueva) |
| `config/purchase-requests.php` / `config/supplies.php` | Metadatos FO-AD-44; opcional `purchase-orders.php` nuevo |

**No** requiere `routes/web.php` si `purchase-requests.php` ya está en el require group. **Sí** agrega el board `proveedores` en `config/access.php` + resolver/visibilidad (actualizado 2026-10-19: Proveedores es tablero de Compras).

Flag TASKS: `shared-files: Si`.

## Criterios de aceptacion

1. FO-AD-44 (compra y suministro) muestra metadatos Marzo 2026 v01 y embebe foto de ítem cuando el archivo existe.
2. Usuario solo con `purchase.providers.view` lista/filtra/exporta; sin `edit` no crea/edita/importa ni evalúa (403).
3. Con `edit`, CRUD y import VINE01 solo filas `PROVEEDOR=SI`; upsert por identificación; `dias_pago` opcional; plantilla/UI dicen **VINE01** (nunca “BASE”).
4. Listado proveedores soporta ~9k vía DataTables server-side; default activos proveedores.
5. Con `purchase.tab.processing`, desde bandeja (compra **y** suministro) se emite OC con proveedor + precios y se descarga FO-AD-07 PDF/Excel **sin** bloque evaluación; número visible = `id` (ej. 1, 2, 145).
6. Con `purchase.providers.edit`, se registra/edita **una** evaluación por OC emitida (Calif. 1–5, pesos 25/25/50, umbrales); no aparece en el documento.
7. Textos de formulario/import persistidos en MAYÚSCULAS (excepto email).
8. Tests PHPUnit del alcance en verde; smoke UI DevTools en pantallas tocadas.
9. Docs viva actualizada en la misma entrega (Documentador al cierre del flujo).

## Validacion local

1. `php artisan migrate` (aditivo; **no** fresh).
2. `php artisan app:sync-permissions` (o equivalente del proyecto).
3. `vendor/bin/pint --dirty --format agent` tras PHP.
4. `php artisan test --compact` (filtros Feature purchase/providers/orders/FO-AD-44).
5. `npm run build` si hubo CSS/JS Vite.
6. Smoke Chrome DevTools: tablero Proveedores, import dry-run acotado, emitir OC desde compra y desde suministro, evaluación.

## Riesgos y dependencias

| Riesgo | Mitigación |
| --- | --- |
| Import ~9k timeout/memoria | Chunk + comando CLI `--limit` / `--dry-run`; no cargar todo en request HTTP sin tope |
| Shared-files `access.php` | Un agente a la vez; flag TASKS; slices T2 concentran permisos |
| Plantillas oficiales FO-AD-07 / FO-AD-44 / archivo VINE01 de muestra | Usuario/Feature deben tener Excel/PDF de referencia; no inventar layout |
| Morph sin FK DB | Validar existencia origen en servicio; morph map explícito; tests supply + purchase |
| Foto FO-AD-44 path inválido | Try/catch embeber; fallback vacío/S/A |
| Confusión “BASE” vs VINE01 | Copy UI/docs/plantilla solo VINE01 |

## Propuesta de slices Feature (para plan AgentSj)

| Slice | Fase | Alcance | shared-files |
| --- | --- | --- | --- |
| **T1** | A | Alinear FO-AD-44 PDF/Excel compra + suministro + config + tests export | No access.php (solo config report) |
| **T2** | B | Migración `purchase_providers` + permisos access.php + AccessService + pestaña + listado DT + CRUD | **Sí** access + rutas/nav |
| **T3** | B | Import VINE01 + plantilla + comando dry-run/limit + export catálogo + tests import/uppercase | Rutas import |
| **T4** | C | Migraciones `purchase_orders` + items; morph; emisión bandeja compra/suministro; FO-AD-07 PDF/Excel sin eval | Rutas orders |
| **T5** | C+eval | `purchase_order_evaluations` + UI + auth providers.edit + tests cálculo/umbrales | Rutas evaluación |

Orden estricto T1 → T2 → T3 → T4 → T5. T4 depende de T2 (proveedor activo). T5 depende de T4.

## Aprobacion

- [x] Analista — vacíos cerrados (Q1–Q9)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-047.md`)
- [ ] Usuario / AgentSj — plan de orquestación + Task Cards
