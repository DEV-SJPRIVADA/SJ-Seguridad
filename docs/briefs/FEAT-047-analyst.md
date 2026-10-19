# Preguntas del Analista — FEAT-047

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** **Cerrado — listo para Arquitecto.** Todas las preguntas respondidas (2026-10-16). AgentSj en pausa hasta continuar.

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
| 25 | Numeración OC | Autoincremental sin límite (como `id`), **sin prefijos**; único global. Ej.: `1`, `2`, `145`. |

### Columnas VINE01 conocidas (plan)

`IDENTIFICA`, `NOMBRE`, `TELEFONO`, `CORREO_ELE`, `CLIENTE`, `PROVEEDOR`, `ESTADO` + opcional `DIAS_PAGO`; app añade opcionales FO-AD-07 (`contacto`, `direccion`, `ciudad`, `forma_pago`) editables.

### Criterios imagen FO-AD-07 (referencia de cálculo, cuando entre evaluación)

- Calif. ejemplo 1–5; Resultado ≈ Calif. × (Peso/100); Total = suma resultados → % sobre máximo 5 (= 100%).
- Campos visibles en plantilla papel: Fecha de recibido, Calif., Peso, Resultado, Observaciones, tabla de umbrales/acciones.
- **En export OC v1: ese bloque no va al PDF/Excel.**

---

## Preguntas abiertas

Ninguna.

---

## Supuestos residuales (no bloquean; Arquitecto decide detalle)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| S10 | Listado operativo proveedores: por defecto `es_proveedor=true` + `estado=activo`; filtro para ver inactivos. | Ruido en listado ~9k. |
| S11 | Import: upsert por `identificacion` (IDENTIFICA); dry-run / limit según database-safety. | Duplicados o sobrescritura no deseada. |
| S12 | Forma de pago en FO-AD-07: texto editable `forma_pago` y/o derivado de `dias_pago`. | Cabecera OC incompleta vs plantilla oficial. |
| S13 | `numero_oc` entero autoincremental único global (puede coincidir con `id` de la fila o ser secuencia dedicada sin pad/prefijo); visible como `1`, `2`, `145`… | Si negocio pedía pad visual, solo cosmético. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

| # | Respuesta (2026-10-16) |
| --- | --- |
| 1 | Evaluación post-entrega **en esta feature** (v1): UI + persistencia. |
| 2 | Quien registra evaluación: **`purchase.providers.edit`**. |
| 3 | Evaluación **ligada a la OC emitida**. |
| 4 | Misma estructura de columnas que el Excel de proveedores; **llamarlo VINE01** (no “BASE”) en plantilla, UI y docs. |
| 5 | **Pestaña en Compras** (no tablero sidebar propio). |
| 6 | FO-AD-07 desde bandeja: **los dos** (solicitudes de compra **y** suministros). |
| 7 | **Autoincremental sin límite**, como el `id`, **sin prefijos** ni pad obligatorio. Ejemplos visibles: `1`, `2`, `145`, `10001`. Único global. |
| 8 | Calif. entero **1–5** (sí). |
| 9 | **Una evaluación por OC** (editable). |

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

Completar Compras con catálogo de proveedores importable (VINE01), emisión de Orden de Compra FO-AD-07 desde bandeja (compras y suministros), FO-AD-44 alineado al formato oficial, y evaluación post-entrega del proveedor en sistema (sin imprimir el bloque en el documento).

### Alcance

#### Incluye (cerrado)

- **Fase A:** Alinear FO-AD-44 PDF/Excel (compra y suministro según exporters actuales) a Marzo 2026 v01: encabezado FO-AD-44 / versión / página; columnas Cantidad \| Foto \| Descripción \| Referencia \| Utilización \| Ubicación; embeber foto de ítem si existe.
- **Fase B:** Tabla/catálogo `purchase_providers`; permisos `purchase.providers.view` / `.edit`; listado DataTables server-side; CRUD; import **VINE01** solo `PROVEEDOR=SI`; export; chrome UI módulo; pestaña en Compras.
- **Fase C:** `purchase_orders` desde bandeja (compra **y** suministro); `numero_oc` autoincremental sin prefijo; proveedor + precios; FO-AD-07 PDF + Excel (`purchase.tab.processing`); **sin** bloque evaluación en el documento.
- **Evaluación post-entrega:** UI + persistencia; `purchase.providers.edit`; ligada a OC emitida; una por OC editable; Calif. 1–5; pesos/umbrales de la imagen; no impresa.

#### Fuera de alcance

- Reabrir Fases 0–7 del plan Compras viejo.
- CRM / catálogo de solo-clientes.
- Migración automática de permisos Spatie legacy (no aplica).
- Tablero sidebar propio de Proveedores.

### Reglas de negocio (cerradas)

- Import VINE01: solo `PROVEEDOR=SI`; `DIAS_PAGO` opcional; upsert por identificación (S11).
- Textos de formularios/import → MAYÚSCULAS UTF-8 (`UppercasesValidatedText` / `UppercaseFormText`), excepto email y keys técnicas.
- FO-AD-07 documento: cabecera asociada de negocio desde proveedor; líneas con valores; firmas según plantilla oficial — **sin** zona evaluación impresa.
- `numero_oc`: entero autoincremental único global, sin prefijos (como `id`).
- Evaluación: pesos 25/25/50; umbrales 80–100 / 60–79 / &lt;60; post-entrega; Calif. 1–5; una por OC.

### Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripción |
| --- | --- | --- |
| `purchase.providers.view` | Asignación manual | Ver, filtrar, export proveedores |
| `purchase.providers.edit` | Asignación manual | CRUD + import + **evaluación post-entrega**; implica view |
| `purchase.tab.processing` | (existente) | Generar/descargar FO-AD-07 |

### Base de datos (borrador)

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `purchase_providers` | migración nueva | Ver plan; `dias_pago` nullable |
| `purchase_orders` (+ ítems) | migración nueva | Ligado a `purchase_request_id` + `provider_id` + snapshot |
| Evaluación OC | tabla / columnas en OC | Ligada a OC emitida; una por OC |

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
2. Usuario con solo `providers.view` consulta/exporta; sin `edit` no importa ni muta ni evalúa (403).
3. Import VINE01: solo filas proveedor; ~9k manejable (server-side listado); dry-run disponible.
4. `dias_pago` editable y opcional en import.
5. Con `purchase.tab.processing`, desde bandeja (compra y suministro) se elige proveedor, captura precios y descarga FO-AD-07 PDF/Excel **sin** bloque evaluación impreso; `numero_oc` autoincremental sin prefijo.
6. Con `purchase.providers.edit`, tras entrega se registra/edita una evaluación por OC (pesos/umbrales de la imagen), no impresa.

### Riesgos y dependencias

- Volumen ~9k en import (timeouts, memoria) — diseño comando/chunk.
- Shared-files: coordinar con AgentSj flag en TASKS.
- Plantilla oficial FO-AD-07 / FO-AD-44 / VINE01 deben estar disponibles al Feature para no inventar layout.
- OC desde suministros: Arquitecto define polimorfismo / tablas.

### Aprobación

- [x] Analista — vacíos cerrados (Q1–Q9)
- [ ] Arquitecto — brief final
- [ ] Usuario — OK para lanzar Arquitecto (AgentSj en pausa)
