# Feature Brief — FEAT-038

> Brief final del Arquitecto (2026-09-25). Consolida `docs/briefs/FEAT-038-analyst.md` + decisiones previas (chat) + respuestas usuario 1–8 (2026-09-25). **No es borrador.**

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-038 |
| Modulo / area | Gestion humana — tablero **Acreditaciones** (`acreditaciones`) — pestaña **Validaciones** |
| Titulo | Validaciones Acreditaciones (cruces Ficha / Acreditados / Reporte Diario) |
| Solicitante | Usuario / AgentSj (chat 2026-09-25) |
| Fecha | 2026-09-25 |

## Objetivo

Operativizar la pestaña **Validaciones** (hoy placeholder FEAT-036) para que Gestión Humana, con permiso de edición, elija una **fecha de reporte**, verifique que ese día tiene **ambos** orígenes APO cargados, pulse **Ejecutar validaciones** y obtenga **cuatro colas operativas** que cruzan Ficha activa, Acreditados del sistema y el snapshot del Reporte Diario.

Las colas permiten **actuar** (abrir Ficha, editar acreditado, alta con cédula precargada) y **exportar** (por cola y consolidado). Los resultados viven **solo en pantalla** (caché efímera de la corrida); no hay histórico de corridas en BD. El Reporte Diario sigue siendo snapshot independiente; Validaciones es el único lugar del cruce.

## Decisiones de negocio (ley — no reabrir)

| # | Tema | Decision |
| --- | --- | --- |
| P1 | Ficha sin acreditación | Empleado **activo** en Ficha (`employment_status = activo`) cuya cédula **no tiene ninguna** fila en `acreditacion_acreditados`. |
| P2 | Ausentes del reporte | Par **cédula + cargo** en Acreditados que **no** aparece en **ningún origen** del día (ni `PROCESO` ni `ACREDITADO`). |
| P3 | EN_PROCESO vs ACREDITADO APO | Acreditado con `estado = EN_PROCESO` cuyo par cédula+cargo **sí** aparece como **ACREDITADO** en el reporte del día (origen `ACREDITADO`). |
| P4 | UI / cuándo correr | Selector fecha (default **hoy**). Sin carga completa → mensaje + link a Reporte Diario; **sin** ejecutar. Con gate OK → botón **Ejecutar validaciones** (**no** auto al entrar). |
| P5 | Acciones | Sí: Abrir Ficha; Editar acreditado; alta acreditado con cédula precargada (cola sin acreditación). **Sin** marcar revisado / motivo. |
| P6 | Vencidas | Incluye estados **DESACREDITADO** y **POR_VENCER**. |
| 1 | Match cargo | Lado Acreditados = **`cargo_apo`**. Lado reporte = columna **Cargo** del Excel (`acreditacion_reporte_diario_filas.cargo`). Igualdad **normalizada** (ver § Match); **sin** “contiene”. |
| 2 | Persistencia corridas | Solo pantalla (+ export de la corrida). **Sin** tablas de histórico de validaciones. |
| 3 | Permisos | Toda la pestaña operativa exige **`acreditaciones.edit`**. **Sin permiso nuevo.** |
| 4 | Acciones v1 | Ver P5; sin workflow de “revisado”. |
| 5 | Vencidas detalle | DESACREDITADO **+** POR_VENCER (calculados existentes). |
| 6 | Granularidad | **Una fila por par cédula + cargo** (como Acreditados). |
| 7 | Gate carga | Ese día deben estar cargados **ambos** orígenes (`PROCESO` y `ACREDITADO`). Si falta uno → no ejecutar + mensaje claro (cuál falta). |
| 8 | Export | **Sí:** por cola **y** consolidado. |
| D | Datos | Solo lectura/cruzes sobre tablas existentes. **Sin migración** de histórico. Prohibido `migrate:fresh` / wipe / TRUNCATE. |

## Decisiones tecnicas del Arquitecto

| Tema | Decision | Justificacion |
| --- | --- | --- |
| Persistencia | **Sin migración.** Resultados en **caché Laravel** (TTL 1–2 h) keyed por `user_id` + `fecha_reporte` + `run_token` (UUID). | Usuario eligió solo pantalla; evita tablas/volumen/auditoría de corridas en v1. |
| Gate “ambos orígenes” | Cabecera del día existe **y** `origenHasPriorData(PROCESO)` **y** `origenHasPriorData(ACREDITADO)` (reutilizar helper del modelo `AcreditacionReporteDiarioCarga`). | Alineado FEAT-037: `*_loaded_at` o filas/conteos del origen. |
| Match cédula | `trim` + comparación case-insensitive del `document_number` (sin inventar zero-padding). | Determinista; evita falsos por espacios. |
| Match cargo | Ver § Normalización; igualdad exacta del string normalizado entre `cargo_apo` (Acreditados) y `cargo` (fila reporte). **No** usar `cargo` texto libre del acreditado. **No** “contiene”. | Respuesta usuario 1; reduce ambigüedad. |
| “ACREDITADO en reporte” | Existe ≥1 fila del día con `origen = ACREDITADO` y par cédula+cargo normalizado coincidente. (Opcional defensivo: `estado_apo = ACREDITADO` si está poblado.) | Origen ACREDITADO es la fuente APO de acreditados; no mezclar filas PROCESO. |
| Ausente | Tras normalizar, el par del acreditado **no** está en el set unión de pares (cualquier origen) del día. | Ley P2. |
| Cola vencidas | `acreditacion_acreditados.estado IN ('DESACREDITADO','POR_VENCER')`. **Independiente** del gate/reporte (sigue visible tras ejecutar; no requiere match reporte). | Cola operativa de vigencia; no es cruce APO. |
| Orden UX vencidas | Tras Ejecutar, las 4 colas se muestran juntas; vencidas no bloquean por falta de reporte, pero **Ejecutar** sí exige gate (un solo botón). | UX simple: un flujo; si gate falla no hay corrida. |
| Controlador | Extender `AcreditacionesController` con métodos `validaciones*` (vertical slice). | Consistencia FEAT-036/037. |
| Services | `AcreditacionCargoMatchNormalizer` (o método estático en service), `AcreditacionValidacionesRunnerService` (arma 4 colas), `AcreditacionValidacionesResultStore` (cache get/put), `AcreditacionValidacionesDatatableService`, `AcreditacionValidacionesExportService` (+ clase(s) `BaseExport`). | Sin Repository; responsabilidades claras. |
| Form Requests | `RunAcreditacionValidacionesRequest` (fecha ≤ hoy); query request o reglas mínimas para datatable/export (`fecha`, `run_token`, `cola`). | Validación centralizada. |
| Permisos UI | Subnav: pestaña **Validaciones** visible **solo** con `acreditaciones.edit` (espejo Catálogo). GET shell + todos los endpoints de negocio: `canEdit`. Usuario solo `view`: no ve la pestaña. | Respuesta 3; evita shell vacío confuso. |
| Acceso Ficha | Acción “Abrir Ficha” solo si el usuario tiene permiso Ficha (`ficha_empleados.view` o `manage` según ruta destino) **y** se resuelve entry/profile por cédula; si no → ocultar o deshabilitar con título. | No filtrar filas de cola por permiso Ficha; solo la acción. |
| Editar / Alta | Reutilizar modales existentes (`edit-modal`, `nuevo-modal`) en la vista Validaciones + endpoints ya existentes `acreditados.update` / `acreditados.store` / `acreditados.lookup`. | Sin duplicar CRUD. |
| Listados | DataTables **`serverSide: true`** por cola (4 tablas o 1 con filtro `cola`); length tope 100; sin `-1`. Datos desde cache del `run_token`. | Regla datatables-server-side; volumen Ficha×reporte. |
| Export | `BaseExport` + `<x-export-excel>`: (a) un botón/endpoint **por cola**; (b) **consolidado** = un archivo con **4 hojas** (una por cola). Respetan la corrida (`run_token`). | Respuesta 8. |
| Selectores | Fecha: `input type="date"`. Si hay selector de cola visible: nativo o `<x-searchable-select>`. **Prohibido Select2.** | AGENTS.md. |
| Audit | **No** auditar GET ni la corrida de solo lectura. Mutaciones vía acciones siguen auditando en endpoints Acreditados existentes. | Sin entidad “validacion_run”. |
| CSS | Reusar chrome/filtros/icon-btn; `app.css` solo si faltan estilos mínimos. | shared-files posible menor. |
| Repository | **No.** | Convención. |
| Migrate | **Sin migración.** | Usuario 2 + no hace falta esquema nuevo. |

### Normalización de match (cargo y cédula auxiliar)

Función única reutilizable (PHP), aplicada a ambos lados antes de comparar:

1. Convertir a string; si null → `''`.
2. `trim`.
3. Colapsar espacios internos múltiples a uno.
4. Uppercase multibyte (`mb_strtoupper`, locale/app UTF-8).
5. Quitar tildes/diacríticos (NFD + strip combining marks, o mapa equivalente seguro en PHP 8.3).
6. Comparar con `===` sobre el resultado.

**Prohibido** en v1: `str_contains`, levenshtein, fuzzy, “coincide si cualquiera de cargo/cargo_apo”.

Documento de ejemplo: `cargo_apo = "VIGILANTE"` vs Excel `Cargo = "vigilante "` → match; vs `"Vigilante de seguridad"` → **no** match.

### Codigos de cola (API / export / DT)

| Code | Label UI | Fuente principal |
| --- | --- | --- |
| `sin_acreditacion` | Ficha activa sin acreditación | Ficha activos sin filas Acreditados |
| `ausente_reporte` | Acreditado ausente del reporte del día | Acreditados ∉ pares reporte (cualquier origen) |
| `en_proceso_ya_acreditado` | EN PROCESO en sistema / ACREDITADO en APO | Acreditados EN_PROCESO ∩ pares origen ACREDITADO |
| `vencidas` | Vencidas / por vencer | Acreditados DESACREDITADO ∪ POR_VENCER |

## Alcance

### Incluye

- Sustituir placeholder de **Validaciones** por UI operativa.
- Selector de fecha (default hoy, ≤ hoy), evaluación de gate (ambos orígenes), mensajes + link a Reporte Diario.
- Botón **Ejecutar validaciones** → calcula 4 colas → guarda corrida en caché → muestra resultados.
- DataTables server-side por cola (desde `run_token`).
- Acciones v1: Abrir Ficha; Editar acreditado; Nuevo acreditado con cédula precargada (cola `sin_acreditacion`).
- Export Excel por cola + consolidado (4 hojas).
- Visibilidad subnav Validaciones solo con `acreditaciones.edit`.
- Tests PHPUnit (permisos, gate, match, colas, export, acciones de navegación/precarga).
- Docs: actualizar `docs/modules/acreditaciones.md` + `docs/user/acreditaciones.md` (+ INDEX si describe placeholder). Documentador al cierre.

### Fuera de alcance

- Tablas / histórico de corridas; “marcar revisado”; motivo; estados de cola.
- Bridge automático que escriba Acreditados desde el reporte (upsert masivo desde Validaciones).
- Dashboard / Export Apo (siguen placeholder).
- Match fuzzy / “contiene” de cargos.
- Permiso nuevo / cambios de paquetes en `config/access.php`.
- Notificaciones correo.
- Auto-ejecución al entrar o al cambiar fecha (solo botón).
- Select2 / `excelHtml5` / Repository / `migrate:fresh`.

## Reglas de negocio

### Acceso

1. Ver pestaña Validaciones en subnav, shell, gate, ejecutar, datatable, export, acciones de UI: **`acreditaciones.edit`** (o bypass `manage.users` / super-admin vía `AcreditacionesAccessService`).
2. Usuario solo `acreditaciones.view`: **no** ve la pestaña Validaciones (igual patrón Catálogo). Otras pestañas de solo lectura no cambian.
3. Abrir Ficha: además requiere permiso del módulo Ficha para la ruta destino; si no → no mostrar acción.
4. Editar / alta acreditado: ya cubiertos por `acreditaciones.edit` (mismos endpoints).

### Fecha y gate

5. `fecha_reporte` obligatoria al ejecutar; default UI = hoy (`America/Bogota`); **prohibido futuro** (422).
6. Gate OK solo si existe cabecera del día **y** ambos orígenes tienen datos previos (`origenHasPriorData` PROCESO y ACREDITADO).
7. Si no hay cabecera → mensaje: no hay carga ese día + CTA/link a Reporte Diario (con fecha si se puede).
8. Si hay cabecera pero falta un origen → mensaje explícito (“Falta carga En proceso” / “Falta carga Acreditado APO” / ambos) + link Reporte Diario; **no** ejecutar.
9. **No** auto-ejecutar al entrar ni al cambiar fecha; cambiar fecha limpia resultados en UI hasta nuevo Ejecutar.

### Ejecución y resultados

10. Al Ejecutar (gate OK): calcular las 4 colas; persistir en caché con `run_token`; devolver resumen de conteos + token.
11. Si el usuario sale y vuelve: sin resultados; debe Ejecutar de nuevo (caché puede haber expirado).
12. Una fila por par cédula+cargo en colas 2–3 y en vencidas (un registro acreditado = un par). Cola 1: una fila por cédula de Ficha (sin par cargo porque no hay acreditado).

### Cola `sin_acreditacion`

13. Universo: `employee_ficha_profiles` con `employment_status = activo`.
14. Excluir cédulas que tengan **≥1** fila en `acreditacion_acreditados` (cualquier `cargo_apo`).
15. Columnas mínimas UI: cédula, nombre (Ficha), acciones (Abrir Ficha, Nuevo acreditado).

### Cola `ausente_reporte`

16. Universo: todos los `acreditacion_acreditados`.
17. Set de pares del día = filas del reporte (ambos orígenes) indexadas por `(norm(document_number), norm(cargo))`.
18. Incluir acreditado si su `(norm(document_number), norm(cargo_apo))` **no** está en el set.
19. Columnas: cédula, nombre, cargo (texto), cargo_apo, estado, acciones (Abrir Ficha, Editar).

### Cola `en_proceso_ya_acreditado`

20. Universo: acreditados con `estado = EN_PROCESO`.
21. Set APO acreditado = filas del día con `origen = ACREDITADO` por `(norm(doc), norm(cargo))`.
22. Incluir si el par del acreditado **está** en ese set.
23. Columnas: cédula, nombre, cargo_apo, estado sistema, vigencia/solicitud, (opcional) vigencia APO de la fila match, acciones (Abrir Ficha, Editar).

### Cola `vencidas`

24. Universo: `estado IN (DESACREDITADO, POR_VENCER)` al momento de ejecutar (columna `estado` ya calculada; no recalcular reglas aquí salvo consistencia).
25. No filtra por reporte. Columnas: cédula, nombre, cargo_apo, estado, vigencia_acr, acciones (Abrir Ficha, Editar).

### Acciones

26. **Abrir Ficha:** deep-link a edición/consulta de Ficha resolviendo por `document_number`; nueva pestaña o misma según patrón GH existente.
27. **Editar acreditado:** abrir modal de edición existente precargado con el `acreditacion_acreditados.id` de la fila.
28. **Nuevo acreditado:** solo en `sin_acreditacion`; abrir modal crear con `document_number` precargado (+ lookup nombre); resto de campos a completar por el usuario.
29. Tras create/update exitoso: **no** se exige re-cálculo automático; UX recomendada: toast + botón “Volver a ejecutar” o invalidar cache de la corrida y pedir re-ejecutar (mensaje). No “marcar revisado”.

### Export

30. Export por cola: columnas de esa cola; nombre archivo incluye cola + fecha reporte.
31. Consolidado: 4 hojas con los codes/labels de cola; mismas columnas que cada export individual.
32. Sin `run_token` válido / cache miss → 422 o redirect con mensaje “Ejecute validaciones primero”.

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion (uso en FEAT-038) |
| --- | --- | --- |
| `view.board.gestion_humana.acreditaciones` | Asignación manual (sin paquete default) | Ver tablero Acreditaciones |
| `acreditaciones.view` | Idem | Otras pestañas de consulta; **no** opera Validaciones |
| `acreditaciones.edit` | Idem | Ver/usar Validaciones (gate, ejecutar, DT, export, acciones) |
| `manage.users` | Admin | Bypass runtime (existente) |
| `ficha_empleados.view` / `ficha_empleados.manage` | Asignación Ficha | Solo para habilitar acción Abrir Ficha (no gate de la pestaña) |

**Sin permiso nuevo.** **No** editar `config/access.php` (shared-files: **no** para access). Actualizar textos en docs de módulo/usuario/ACCESS_CONTROL al documentar (Documentador o Feature al cierre): Validaciones deja de ser placeholder y queda acotada a `edit`.

## Rutas

Archivo: `routes/areas/gestion_humana.php`  
Prefijo existente: `/gestion-humana/acreditaciones` · nombre `gestion-humana.acreditaciones.`  
Middleware: `auth`, `active`, `password.changed` (igual bloque actual).

| Metodo | URI | Nombre | Permiso | Notas |
| --- | --- | --- | --- | --- |
| GET | `/validaciones` | `validaciones` | `acreditaciones.edit` | **Reemplaza** placeholder; shell fecha + gate + botón + contenedor resultados |
| GET | `/validaciones/gate` | `validaciones.gate` | `acreditaciones.edit` | JSON estado gate para `fecha` (opcional si el shell lo resuelve server-side al GET) |
| POST | `/validaciones/ejecutar` | `validaciones.run` | `acreditaciones.edit` | Body/query: `fecha_reporte`; responde conteos + `run_token` |
| GET | `/validaciones/datatable` | `validaciones.datatable` | `acreditaciones.edit` | JSON DT; params: `run_token`, `cola`, protocolo DT |
| GET | `/validaciones/exportar` | `validaciones.export` | `acreditaciones.edit` | Query: `run_token`, `cola` ∈ codes |
| GET | `/validaciones/exportar-consolidado` | `validaciones.export-consolidated` | `acreditaciones.edit` | Query: `run_token`; Excel 4 hojas |

**No** tocar rutas Acreditados / Reporte Diario salvo reutilizar endpoints existentes desde la UI.  
**No** editar `routes/web.php` (el require del área ya existe).

### Middleware de permiso

- En controller: `abort_unless($this->acreditacionesAccess->canEdit(...), 403)` en todos los métodos `validaciones*`.
- Actualizar `AcreditacionesAccessService` / tabs: ocultar tab `validaciones` si `!canEdit` (como `catalogo`).

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| — | **Sin migración** | No hay histórico de corridas ni columnas nuevas |

Lectura (sin alter):

| Tabla | Uso |
| --- | --- |
| `employee_ficha_profiles` | Cola sin acreditación (`employment_status = activo`) |
| `acreditacion_acreditados` | Colas ausente / en_proceso / vencidas |
| `acreditacion_reporte_diario_cargas` | Gate ambos orígenes |
| `acreditacion_reporte_diario_filas` | Sets de pares del día |

Caché (no BD): key sugerida `acreditaciones:validaciones:{userId}:{fecha}:{token}` → payload JSON/array de las 4 colas + metadata.

## Capas a implementar

- [ ] Migracion(es) — **no**
- [ ] Modelo(s) — **no nuevos**; helpers opcionales en `AcreditacionReporteDiarioCarga` (`bothOriginsLoaded(): bool`) si no existe
- [ ] Controlador(es) — métodos `validaciones`, `validacionesGate?`, `validacionesRun`, `validacionesDatatable`, `exportValidaciones`, `exportValidacionesConsolidado` en `AcreditacionesController`
- [ ] Form Request(s) — `RunAcreditacionValidacionesRequest` (+ validación query datatable/export)
- [ ] Vista(s) Blade — `validaciones.blade.php` (+ partials resultados/acciones); dejar de usar `placeholder` para esta pestaña; incluir/reusar `nuevo-modal` / `edit-modal`
- [ ] JavaScript — Alpine/DT server-side; botón ejecutar; tabs o secciones por cola; eventos delegados en acciones
- [ ] Services — Normalizer + Runner + ResultStore (cache) + Datatable + Export
- [ ] Export Excel — `BaseExport` por cola + export consolidado multi-hoja + `<x-export-excel>`
- [ ] Access — `AcreditacionesAccessService` / `HasAcreditacionesTabs`: tab Validaciones solo con edit
- [ ] Tests — ver criterios
- [ ] `config/access.php` — **no**

## Componentes reutilizables

| Componente | Uso |
| --- | --- |
| `<x-export-excel>` | Export por cola y consolidado |
| `<x-searchable-select>` | Solo si hay selector searchable (p. ej. cola); fecha = input date |
| `.module-tab` / subnav | Tab Validaciones; visibilidad edit |
| `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn` | Chrome y acciones de fila |
| Partials `nuevo-modal` / `edit-modal` | Alta precargada / editar |
| `AcreditacionesAccessService` / `HasAcreditacionesTabs` | Acceso + tab activa `validaciones` |
| `AcreditacionEstadoCalculator` | **No** invocar en Validaciones para el cruce; leer `estado` ya persistido |
| `BaseExport` / PhpSpreadsheet | Exports |
| Cache Laravel | Corrida efímera |

### Columnas sugeridas por cola (DT / export)

**sin_acreditacion:** Cédula, Nombre, Acciones  
**ausente_reporte:** Cédula, Nombre, CARGO, CARGO APO, Estado, Acciones  
**en_proceso_ya_acreditado:** Cédula, Nombre, CARGO APO, Estado, Vigencia ACR, Fecha solicitud, Acciones  
**vencidas:** Cédula, Nombre, CARGO APO, Estado, Vigencia ACR, Acciones  

(Export omite HTML de acciones; puede incluir `acreditado_id` interno opcional.)

## Documentacion a actualizar

- [ ] `docs/modules/acreditaciones.md` — Validaciones operativa; rutas; gate; colas; sin migración; permisos edit
- [ ] `docs/user/acreditaciones.md` — cómo ejecutar, interpretar colas, acciones, export
- [ ] `docs/INDEX.md` — si lista placeholders
- [ ] `docs/ACCESS_CONTROL.md` — nota Validaciones = edit (Documentador)
- [ ] `README.md` — solo si menciona placeholder Validaciones

## Archivos compartidos (`shared-files`)

| Archivo | ¿Se toca? | Notas |
| --- | --- | --- |
| `routes/areas/gestion_humana.php` | **Sí** | Nuevas rutas `validaciones.*`; cambiar gate de GET `validaciones` a edit |
| `config/access.php` | **No** | Sin permiso nuevo |
| `routes/web.php` | **No** | |
| Layouts globales | **No** (salvo CSS mínimo justificado en `app.css`) | Preferir estilos de módulo existentes |
| Seeders globales / RolePermission | **No** | |

Flag en `docs/TASKS.md`: `shared-files: routes/areas/gestion_humana.php` (y `resources/css/app.css` solo si el Feature lo necesita).

## Criterios de aceptacion

1. Usuario con solo `acreditaciones.view` **no** ve la pestaña Validaciones; con `edit` sí.
2. GET `/validaciones` sin edit → 403.
3. Fecha default = hoy; fecha futura rechazada al ejecutar.
4. Día sin cabecera → mensaje + link Reporte Diario; botón Ejecutar deshabilitado o no corre.
5. Día con solo un origen cargado → mensaje indica el origen faltante; no ejecuta.
6. Día con ambos orígenes → Ejecutar genera `run_token` y conteos de 4 colas.
7. No hay auto-ejecución al cargar la página.
8. Cola `sin_acreditacion`: activo Ficha sin ninguna fila Acreditados; no incluye inactivos/desvinculados.
9. Match cargo usa solo `cargo_apo` vs `cargo` reporte con normalización definida; “contiene” no aplica (caso de prueba: sufijo distinto → no match).
10. `ausente_reporte`: una fila por par; ausente si el par no está en PROCESO ni ACREDITADO.
11. `en_proceso_ya_acreditado`: solo EN_PROCESO cuyo par está en origen ACREDITADO del día.
12. `vencidas`: incluye DESACREDITADO y POR_VENCER; una fila por registro/par.
13. Acciones: Abrir Ficha (si permiso Ficha); Editar abre modal existente; Nuevo en cola 1 precarga cédula.
14. Export por cola y consolidado (4 hojas) respetan `run_token`; sin token/expirado → error claro.
15. **Sin** tablas nuevas de histórico; **sin** cambios en `config/access.php`.
16. Tests Feature cubren permisos, gate, al menos un caso de cada cola, normalización, export 200 con token válido.
17. DataTables server-side; sin `-1`; sin Select2; export vía `BaseExport` / `<x-export-excel>`.

## Validacion local

1. Cargar ambos Excel APO para una fecha de prueba (Reporte Diario).
2. Abrir Validaciones → verificar gate OK → Ejecutar → revisar 4 colas con datos conocidos.
3. Quitar un origen (reemplazar vacío o usar día parcial) → gate falla con mensaje correcto.
4. Probar acciones: abrir Ficha, editar acreditado, alta con cédula precargada.
5. Export cola + consolidado; abrir en Excel.
6. Usuario solo view: no ve tab.
7. `php artisan test --compact` (filtros Feature Acreditaciones / Validaciones).
8. `vendor/bin/pint --dirty --format agent` tras PHP.

## Riesgos y dependencias

| Riesgo | Mitigacion |
| --- | --- |
| `cargo_apo` (catálogo) ≠ texto **Cargo** del Excel APO | Normalización estricta; documentar en doc usuario que deben alinearse; falsos “ausentes” si APO usa redacción distinta — **sin** fuzzy en v1. |
| Volumen Ficha activos × filas reporte (~cientos–miles) | Sets en memoria / queries `whereNotExists` / chunks; DT desde cache; tope length 100; medir en Runner. |
| Caché expirada o multi-servidor | TTL claro + mensaje “vuelva a ejecutar”; cache store default del entorno (file/redis). |
| Acción Abrir Ficha sin permiso Ficha | Ocultar acción; no romper la cola. |
| Duplicados IdNum en filas reporte | Un par en el set basta (existencia); no explode filas de resultado por duplicados APO. |
| Dependencia FEAT-037 | Reporte Diario y modelos carga/filas deben estar desplegados. |
| Subnav shared | Cambio de visibilidad Validaciones afecta `AcreditacionesAccessService` (módulo propio, no access.php). |

## Task Cards sugeridas (vertical slices — plan AgentSj)

> Un Agente Feature por slice; orden recomendado. Marcar `shared-files` en la TC que toque `gestion_humana.php`.

| ID sugerido | Slice | Entrega |
| --- | --- | --- |
| FEAT-038-TC1 | Shell + permisos + gate | Vista `validaciones.blade.php`; tab solo edit; fecha; mensajes gate + link Reporte Diario; rutas GET shell (+ gate opcional); sin colas aún |
| FEAT-038-TC2 | Motor + ejecución + listados | Normalizer; Runner (4 colas); ResultStore cache; POST ejecutar; DT server-side por cola; UI resultados |
| FEAT-038-TC3 | Acciones + export | Modales reuso; Abrir Ficha; exports por cola + consolidado `BaseExport` |
| FEAT-038-TC4 | Tests + polish | Feature tests criterios 1–16; pint; ajustes UX mensajes |

Documentador (post-Revisor): `docs/modules/acreditaciones.md` + `docs/user/acreditaciones.md`.

## Aprobacion

- [x] Analista — vacíos cerrados (respuestas 1–8 + decisiones previas)
- [x] Arquitecto — brief final (`docs/briefs/FEAT-038.md`)
- [ ] Usuario — confirmacion (si AgentSj la solicita antes de Feature)
- [ ] AgentSj — plan `docs/briefs/FEAT-038-plan.md` + Task Cards en `docs/TASKS.md`
