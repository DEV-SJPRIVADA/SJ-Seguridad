# Preguntas del Analista — FEAT-047

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** **Pendiente solo Q7 (numeración OC)** — resto de respuestas del usuario registradas 2026-10-16. No repreguntar lo cerrado.

---

## Contexto recibido

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-047 |
| Origen | AgentSj — plan Compras Proveedores + FO-AD-07 + FO-AD-44 (2026-10-16) |
| Módulo / área | Compras / `purchase-requests` (+ catálogo Proveedores) |
| Título | Compras: Proveedores + FO-AD-07 + alinear FO-AD-44 |
| Run log | [`docs/runs/FEAT-047-run-log.md`](../runs/FEAT-047-run-log.md) |
| Plan semilla | `compras_proveedores_oc_14f77b95.plan.md` |
| Doc módulo actual | [`docs/modules/purchase-requests.md`](../modules/purchase-requests.md) |
| Imagen | Bloqueo evaluación FO-AD-07 (criterios, pesos, umbrales) — adjunto chat |

### Solicitud (resumen)

Cerrar el pendiente de Compras tras Fases 0–7 ya hechas:

1. **Alinear FO-AD-44** al formato oficial Marzo 2026 v01.
2. **Catálogo Proveedores** con import desde BASE (~9k), plantilla **VINE01**, solo filas `PROVEEDOR=SI`, columna `dias_pago`, permisos `view`/`edit`.
3. **FO-AD-07 Orden de Compra** desde bandeja (`purchase.tab.processing`), con proveedor + precios.
4. **Evaluación post-entrega** (criterios de la imagen): **no se imprime** en PDF/Excel de la OC; UI + persistencia **en FEAT-047**; permiso `purchase.providers.edit`; ligada a OC emitida; una por OC editable; Calif. 1–5.

Orden sugerido: **A FO-AD-44 → B Proveedores → C FO-AD-07**.

Shared-files: `config/access.php`, rutas/nav Compras.

---

## Decisiones YA CERRADAS (ley — no repreguntar)

| # | Tema | Decisión |
| --- | --- | --- |
| 1 | Pendiente real | Solo: catálogo Proveedores, FO-AD-07 OC, alinear FO-AD-44. Fases 0–7 del plan Compras viejo **ya hechas**. |
| 2 | Permisos Proveedores | `purchase.providers.view` + `purchase.providers.edit`; **edit implica view** en AccessService. Labels Admin: `Proveedores: Ver` / `Proveedores: Editar`. |
| 3 | FO-AD-07 generar/descargar | Solo `purchase.tab.processing` (sin permiso nuevo). |
| 4 | `dias_pago` | Entero nullable en proveedores; no viene en VINE01 por defecto; editable en alta/edición; import acepta `DIAS_PAGO` opcional (si no, null). |
| 5 | Fuente / plantilla import | Plantilla y archivo de carga masiva se llaman **VINE01** (mismo layout de columnas que el Excel de proveedores conocido: `IDENTIFICA`, `NOMBRE`, `TELEFONO`, `CORREO_ELE`, `CLIENTE`, `PROVEEDOR`, `ESTADO` + opcional `DIAS_PAGO`). No usar el nombre “BASE” en UI/docs de producto. |
| 6 | Plantilla masivos | Nombre: **VINE01**. |
| 7 | Filtro import | Cargar **solo** filas que sean proveedores (`PROVEEDOR=SI`). No importar solo-cliente (`CLIENTE=SI` y `PROVEEDOR≠SI`). |
| 8 | Evaluación en documento | Zona evaluación de la imagen **NO se imprime** en PDF/Excel FO-AD-07. |
| 9 | Criterios evaluación (negocio) | Referencias y cantidades **25%**; Oportunidad en la entrega **25%**; Calidad del producto **50%**. |
| 10 | Umbrales | 80–100% Compra satisfactoria; 60–79% Requiere seguimiento; &lt;60% Compra insatisfactoria. |
| 11 | Momento evaluación | Opción en sistema **después** de la entrega de productos (no al generar la OC). |
| 12 | FO-AD-44 | Alinear export al formato oficial **Marzo 2026 Versión 01**. |
| 13 | Orden de fases | A (FO-AD-44) → B (Proveedores) → C (FO-AD-07). |
| 14 | Shared-files | `config/access.php` + rutas/navegación Compras (flag en TASKS). |
| 15 | Migración permisos legacy | No hay legacy de proveedores → asignación Spatie **manual** (sin migración automática). |
| 16 | CRM clientes | Fuera: módulo de clientes (`CLIENTE=SI` y `PROVEEDOR=NO`). |
| 17 | Reabrir Fases 0–7 | Fuera de alcance. |
| 18 | Evaluación v1 | **Sí** en FEAT-047 (UI + BD); no impresa en FO-AD-07. |
| 19 | Permiso evaluación | `purchase.providers.edit`. |
| 20 | Anclaje evaluación | OC emitida; **una por OC** editable. |
| 21 | Escala Calif. | Entero **1–5**. |
| 22 | UI Proveedores | **Pestaña en Compras** (sin board sidebar propio). |
| 23 | FO-AD-07 origen | Solicitudes de compra **y** suministros (bandeja). |
| 24 | Nombre import | Siempre **VINE01** (no “BASE” en producto). |

### Columnas VINE01 conocidas (plan)

`IDENTIFICA`, `NOMBRE`, `TELEFONO`, `CORREO_ELE`, `CLIENTE`, `PROVEEDOR`, `ESTADO` + opcional `DIAS_PAGO`; app añade opcionales FO-AD-07 (`contacto`, `direccion`, `ciudad`, `forma_pago`) editables.

### Criterios imagen FO-AD-07 (referencia de cálculo, cuando entre evaluación)

- Calif. ejemplo 1–5; Resultado ≈ Calif. × (Peso/100); Total = suma resultados → % sobre máximo 5 (= 100%).
- Campos visibles en plantilla papel: Fecha de recibido, Calif., Peso, Resultado, Observaciones, tabla de umbrales/acciones.
- **En export OC v1: ese bloque no va al PDF/Excel.**

---

## Preguntas abiertas

Solo queda:

7. **Numeración OC:** ¿Cómo se genera `numero_oc`? Ver ejemplos abajo para elegir (A–E u otro).

*(Q1–Q6, Q8–Q9 respondidas — ver sección «Respuestas del usuario».)*

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| S1 | Evaluación post-entrega **sí entra en v1** de FEAT-047 (UI + BD), porque el usuario la describió como opción de sistema; el plan semilla queda **superado** en ese punto. | Retrabajo si era solo “documentar criterios” / fase posterior. |
| S2 | Quien genera/procesa OC (`purchase.tab.processing`) también puede **registrar la evaluación** (sin permiso nuevo). | Usuarios de solo `providers.edit` o solicitantes quedarían sin acceso (o viceversa). |
| S3 | Evaluación se liga a la **OC emitida** (no a la solicitud suelta). | Modelo/datos incorrectos si el negocio evalúa a nivel solicitud o proveedor global. |
| S4 | VINE01 = plantilla vacía con mismas columnas que BASE + `DIAS_PAGO` opcional (+ opcionales FO-AD-07 si se confirman en import). | Rechazo de import si el Excel oficial usa otros encabezados. |
| S5 | Proveedores = **pestaña** (o subnav) bajo área/módulo Compras, con `purchase.providers.view/edit`; tablero sidebar opcional `view.board.compras.proveedores` solo si el patrón del área lo exige — **Arquitecto** propone tras respuesta Q5. | Sidebar/nav incorrecta o doble entrada. |
| S6 | FO-AD-07 **solo** para solicitudes de compra (no suministros en v1). | Compras no podrá emitir OC desde suministros si lo necesitaban. |
| S7 | `numero_oc` correlativo automático con lock (patrón `numero_solicitud`), formato a definir por Arquitecto (p. ej. año + secuencia). | Choque con numeración contable/legacy. |
| S8 | Calif. evaluación = entero **1–5**; resultado y % según pesos fijos de la imagen. | Escala distinta invalidaría umbrales. |
| S9 | Una evaluación por OC (upsert/edición), no historial múltiple en v1. | Pérdida de trazabilidad si el negocio re-evalúa. |
| S10 | Listado operativo proveedores: por defecto `es_proveedor=true` + `estado=activo`; filtro para ver inactivos. | Ruido en listado ~9k. |
| S11 | Import: upsert por `identificacion` (IDENTIFICA); dry-run / limit según database-safety. | Duplicados o sobrescritura no deseada. |
| S12 | Forma de pago en FO-AD-07: texto editable `forma_pago` y/o derivado de `dias_pago` (Arquitecto detalla); no bloquea brief. | Cabecera OC incompleta vs plantilla oficial. |

---

## Estado

- [ ] Todas las preguntas respondidas — listo para Arquitecto
- [x] Pendiente respuesta usuario (solo **Q7 numeración OC**)

## Respuestas del usuario

| # | Respuesta (2026-10-16) |
| --- | --- |
| 1 | Evaluación post-entrega **en esta feature** (v1): UI + persistencia. |
| 2 | Quien registra evaluación: **`purchase.providers.edit`**. |
| 3 | Evaluación **ligada a la OC emitida**. |
| 4 | Misma estructura de columnas que el Excel de proveedores; **llamarlo VINE01** (no “BASE”) en plantilla, UI y docs. |
| 5 | **Pestaña en Compras** (no tablero sidebar propio). |
| 6 | FO-AD-07 desde bandeja: **los dos** (solicitudes de compra **y** suministros). |
| 7 | *Pendiente — usuario pidió ejemplos.* |
| 8 | Calif. entero **1–5** (sí). |
| 9 | **Una evaluación por OC** (editable). |

### Ejemplos para Q7 (numeración `numero_oc`) — elegir uno

Referencia interna: las solicitudes de compra hoy usan correlativo numérico (`numero_solicitud`) y folio visual `0001`, `0002`…

| Opción | Ejemplo visible | Cómo funciona | Notas |
| --- | --- | --- | --- |
| **A** | `0001`, `0002`, `0145` | Correlativo global automático (igual espíritu que solicitudes), pad 4 | Simple; único global |
| **B** | `OC-2026-0001`, `OC-2026-0002` | Prefijo `OC-` + año + secuencia que **reinicia cada año** | Legible; único por año |
| **C** | `OC-0001`, `OC-0002` | Prefijo `OC-` + correlativo global (no reinicia) | Legible; único global |
| **D** | Manual al emitir | Usuario escribe el número (validar único) | Flexible vs legado contable; más error humano |
| **E** | `2026-0001` | Año + secuencia anual **sin** prefijo OC | Compacto |

También indicar si debe ser **único global** (recomendado en A/C) o **único por año** (B/E).

---

## Borrador Feature Brief (parcial — no final)

> No sustituye `docs/briefs/FEAT-047.md` del Arquitecto. Cierra solo lo confirmado; deja huecos donde hay preguntas abiertas.

### Identificación

| Campo | Valor |
| --- | --- |
| ID | FEAT-047 |
| Módulo / área | Compras / purchase-requests (+ Proveedores) |
| Título | Compras: Proveedores + FO-AD-07 + alinear FO-AD-44 |
| Solicitante | Usuario (plan Compras) |
| Fecha | 2026-10-16 |

### Objetivo

Completar Compras con catálogo de proveedores importable, emisión de Orden de Compra FO-AD-07 desde bandeja, y FO-AD-44 alineado al formato oficial; opcionalmente (pendiente Q1) evaluación post-entrega del proveedor en sistema sin imprimir el bloque en el documento.

### Alcance

#### Incluye (cerrado)

- **Fase A:** Alinear FO-AD-44 PDF/Excel (compra y suministro según exporters actuales) a Marzo 2026 v01: encabezado FO-AD-44 / versión / página; columnas Cantidad \| Foto \| Descripción \| Referencia \| Utilización \| Ubicación; embeber foto de ítem si existe.
- **Fase B:** Tabla/catálogo `purchase_providers` (identificación unique, nombre, teléfono, correo, es_cliente/es_proveedor, estado, `dias_pago`, opcionales FO-AD-07); permisos `purchase.providers.view` / `.edit`; listado DataTables server-side; CRUD; import BASE solo `PROVEEDOR=SI`; plantilla **VINE01**; export; chrome UI módulo (`req-manage-*`, searchable-select, icon-btn canónicos).
- **Fase C:** `purchase_orders` ligado a solicitud; elegir proveedor activo; precios/IVA por línea; generar FO-AD-07 PDF + Excel; auth `purchase.tab.processing`. **Sin** bloque evaluación en el documento.

#### Incluye (condicionado a Q1 = sí)

- UI + persistencia de evaluación post-entrega (3 criterios, pesos, umbrales, fecha recibido, observaciones, categoría calculada).

#### Fuera de alcance

- Reabrir Fases 0–7 del plan Compras viejo.
- CRM / catálogo de solo-clientes.
- Workflow de evaluación si Q1 = no / fase posterior.
- Migración automática de permisos Spatie legacy (no aplica).

### Reglas de negocio (cerradas + supuestos)

- Import proveedores: solo `PROVEEDOR=SI`; `DIAS_PAGO` opcional; upsert por identificación (S11).
- Textos de formularios/import → MAYÚSCULAS UTF-8 (`UppercasesValidatedText` / `UppercaseFormText`), excepto email y keys técnicas.
- FO-AD-07 documento: cabecera asociada de negocio desde proveedor (razón social, NIT/identificación, contacto, teléfono, correo, dirección, ciudad, forma de pago / días, asume cliente); líneas con valores; firmas según plantilla oficial Enero 2025 v04 — **sin** zona evaluación impresa.
- Evaluación (si v1): pesos 25/25/50; umbrales 80–100 / 60–79 / &lt;60; post-entrega (S1–S3, S8–S9).

### Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripción |
| --- | --- | --- |
| `purchase.providers.view` | Asignación manual | Ver, filtrar, export proveedores |
| `purchase.providers.edit` | Asignación manual | CRUD + import; implica view |
| `purchase.tab.processing` | (existente) | Generar/descargar FO-AD-07 (+ evaluación si Q2 confirma) |
| `view.board.compras.…` | ¿? | Pendiente Q5 |

### Base de datos (borrador)

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `purchase_providers` | migración nueva | Ver plan; `dias_pago` nullable |
| `purchase_orders` (+ ítems) | migración nueva | Ligado a `purchase_request_id` + `provider_id` + snapshot |
| Evaluación OC | ¿nueva tabla? | Solo si Q1 = sí; anclaje Q3 |

### Capas a implementar

- [ ] Migración(es)
- [ ] Modelo(s)
- [ ] Controlador(es) / AccessService
- [ ] Form Request(s) + Uppercase
- [ ] Vista(s) Blade (chrome módulo)
- [ ] Import VINE01 / BASE + comando dry-run/limit
- [ ] Export FO-AD-44 alineado + FO-AD-07 PDF/Excel (`BaseExport` / DomPDF)
- [ ] Tests Feature acceso + import + export
- [ ] Docs `purchase-requests` + `purchase-providers` (+ user) + ACCESS_CONTROL + INDEX

### Archivos compartidos

- `config/access.php`
- Rutas/nav Compras (`routes/areas/compras.php` y/o `routes/modules/purchase-requests.php`)

### Criterios de aceptación (borrador)

1. FO-AD-44 export refleja metadatos Marzo 2026 v01 y foto embebida cuando exista.
2. Usuario con solo `providers.view` consulta/exporta; sin `edit` no importa ni muta (403).
3. Import BASE / VINE01: solo filas proveedor; ~9k manejable (server-side listado); dry-run disponible.
4. `dias_pago` editable y opcional en import.
5. Con `purchase.tab.processing`, desde bandeja/detalle se elige proveedor, captura precios y descarga FO-AD-07 PDF/Excel **sin** bloque evaluación impreso.
6. (Si Q1=sí) Tras entrega, se puede registrar evaluación con pesos/umbrales de la imagen y queda persistida.

### Riesgos y dependencias

- Conflicto plan semilla vs usuario en evaluación → **bloqueante** hasta Q1.
- Volumen ~9k en import (timeouts, memoria) — diseño comando/chunk.
- Shared-files: coordinar con AgentSj flag en TASKS.
- Plantilla oficial FO-AD-07 / FO-AD-44 / VINE01 deben estar disponibles al Feature para no inventar layout.

### Aprobación

- [ ] Analista — vacíos cerrados (pendiente Q1–Q9)
- [ ] Arquitecto — brief final
- [ ] Usuario — confirmación
