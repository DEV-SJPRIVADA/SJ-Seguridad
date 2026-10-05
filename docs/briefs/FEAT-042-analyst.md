# Preguntas del Analista — FEAT-042

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** negocio base y 8 decisiones cerradas por el usuario (chat previo + run log). **7 preguntas abiertas** (vacíos reales de negocio/UX/datos). **Pendiente respuesta usuario** → AgentSj debe **PAUSAR** antes de Arquitecto. **No** dar por cerrado el brief final.

---

## Contexto recibido

**Feature ID:** FEAT-042  
**Origen:** `@agent-sj` Tablero Cliente interno (Gestión humana) — 2026-10-02  
**Módulo:** Cliente interno (`cliente_interno`) — área Gestión Humana  
**Título:** Tablero Cliente interno GH (Dashboard + Solicitudes + Catálogos estado)  
**Run log:** [`docs/runs/FEAT-042-run-log.md`](../runs/FEAT-042-run-log.md)  
**Doc de referencia cercana:** [`docs/modules/formacion.md`](../modules/formacion.md) (año/mes derivados + import replace), [`docs/modules/seleccion.md`](../modules/seleccion.md) (CRUD + Catálogos + Dashboard)

### Solicitud original (resumen)

Nuevo tablero **Cliente interno** en Gestión humana, con permisos ver/editar, pestañas Dashboard / Solicitudes / Catálogos (porque ESTADO es editable), listado con filtros, DataTables, masivo, export, formulario agregar nuevo, botones icon-only; columnas año y mes derivadas de fecha de solicitud.

### Campos de solicitud (usuario — lista cerrada; no inventar extras)

| Campo UI | Notas ya conocidas |
| --- | --- |
| FECHA DE SOLICITUD | Fuente de **año** y **mes** (manual y masivo) |
| NOMBRE Y APELLIDOS | |
| CEDULA | |
| CORREO ELECTRONICO | |
| SOLICITUD | Tipo (texto vs catálogo) **no** cerrado |
| FECHA DE RESPUESTA | |
| ESTADO | Catálogo editable → pestaña Catálogos |
| NOVEDAD | Tipo (texto vs catálogo) **no** cerrado |
| Días de respuesta | Editable en v1; también se puede precalcular al guardar |
| Año / Mes | Derivados de fecha de solicitud (no campos independientes de captura libre) |

### Decisiones YA CERRADAS (ley — no repreguntar)

| # | Decisión |
| --- | --- |
| 1 | Área: **Gestión humana**. |
| 2 | Permisos **Propuesta A**: `view.board.gestion_humana.cliente_interno` (sidebar); `cliente_interno.solicitudes.view` / `cliente_interno.solicitudes.edit` (`edit` ⇒ `view`); `cliente_interno.parameters.edit` (catálogo estados); Dashboard: acceso con `solicitudes.view` **O** `parameters.edit` (sin permiso KPI aparte). |
| 3 | **ESTADO** = catálogo editable → pestaña **Catálogos**. |
| 4 | **Días de respuesta** = editable en v1 (también se puede precalcular al guardar). |
| 5 | Masivo = **reemplaza** si selecciona año/mes que ya tenga datos (replace por periodo). |
| 6 | Nombre UI tablero: **Cliente interno**. |
| 7 | Año/mes derivados de **fecha de solicitud** (alta manual y masivo). |
| 8 | Estándares: DataTables **server-side**, `<x-searchable-select>`, botones icon-only, `BaseExport` + `<x-export-excel>`, audit central (`SystemAuditService` vía wrapper del módulo). |

### Alcance tentativo (pendiente cierre de preguntas)

- **Incluye (candidato v1):** board GH **Cliente interno**; pestañas Dashboard, Solicitudes, Catálogos; CRUD mínimo de solicitudes (al menos alta); import masivo por año+mes con replace del periodo; export; filtros; catálogo ESTADO; columnas año/mes derivadas.
- **Fuera tentativo (salvo que el usuario diga lo contrario):** bridge/sync con Ficha empleados u otros módulos GH; notificaciones por correo; soft-delete / versionado histórico del dataset; jobs async de import; permiso KPI de dashboard aparte.

### Patrones de referencia (repo)

| Aspecto | Referencia |
| --- | --- |
| Año/mes derivados de fecha + import | Formación (FEAT-041) — replace-all dataset; aquí el replace es **por periodo** año+mes |
| CRUD listado + Catálogos + Dashboard | Selección (FEAT-035) |
| Permisos view/edit por pestaña + `parameters.edit` | Comercial / Propuesta A (cerrada por usuario) |
| Selectores / export / chrome | `<x-searchable-select>`; `BaseExport`; `.module-tab`; `.req-manage-filters__icon-btn` / `.cursos-catalogo-page__icon-btn` |
| Datos | Solo migraciones aditivas; **sin** `migrate:fresh` / wipe / TRUNCATE operativo |

---

## Resumen de lo entendido

Gestión Humana necesita un **tablero dedicado Cliente interno** para registrar y consultar **solicitudes** de personas (nombre, cédula, correo, texto/dato de solicitud, fechas, estado, novedad y días de respuesta), con:

1. **Dashboard** de seguimiento (contenido KPI concreto aún abierto).
2. **Solicitudes:** filtros, DataTables server-side, alta por formulario, import **masivo que reemplaza el año+mes seleccionado**, export Excel, acciones con botones icon-only.
3. **Catálogos:** administración del catálogo **ESTADO** (único catálogo confirmado).

Año y mes **no** se capturan a mano: se calculan siempre desde **fecha de solicitud**. Los días de respuesta pueden editarse y/o precalcularse al guardar. El acceso al dashboard no exige un permiso KPI aparte: basta `solicitudes.view` o `parameters.edit`, además del board en sidebar.

Queda **crítico** definir qué hacer en el masivo cuando una fila del Excel trae `fecha_solicitud` de un **mes/año distinto** al periodo elegido para reemplazar (pregunta 1).

### Riesgos principales (antes de cerrar brief)

| Riesgo | Por qué importa |
| --- | --- |
| Filas Excel fuera del periodo | Sin regla A/B/C, el replace puede borrar un mes y meter datos de otro, o rechazar cargas válidos. |
| SOLICITUD / NOVEDAD sin tipo | Texto libre vs catálogo cambia UI, permisos y migraciones. |
| Seed ESTADO vacío o incorrecto | Operadores no pueden cargar solicitudes el día 1. |
| Dashboard sin KPIs acordados | Implementación genérica que no sirve al negocio. |
| Solo “agregar” sin editar/borrar | Falta operativa diaria si el Excel no es la única fuente. |
| Cálculo de días ambiguo | Usuario edita un valor distinto al que el sistema precalcula. |

---

## Preguntas abiertas

Responde cada punto para cerrar el brief. Lenguaje de negocio. **No** se inventan campos nuevos fuera de la lista ya acordada.

### 1. Masivo: filas con fecha de solicitud fuera del periodo seleccionado

Al importar, el usuario elige **año + mes** y el sistema **reemplaza** los datos de ese periodo. Si una fila del Excel trae `fecha_solicitud` de **otro** mes/año:

- **A)** **Rechazar** esas filas (error o aviso; no se cargan).
- **B)** **Aceptarlas** (se guardan con su año/mes real derivados de la fecha), pero el replace **solo borra** el periodo seleccionado en el filtro.
- **C)** **Forzar** año/mes al periodo del filtro (ignorar el mes/año natural de la fecha, o ¿también reescribir la fecha?).

Indique A, B o C. Si es C, aclare si se fuerza solo año/mes o también se cambia la fecha de solicitud.

### 2. Campos obligatorios vs opcionales

De la lista acordada, ¿cuáles son **obligatorios** al crear (manual y/o masivo) y cuáles **opcionales**?

En particular: ¿pueden ir vacíos **correo**, **fecha de respuesta**, **novedad**, **días de respuesta** y/o **estado** en el alta?

### 3. ¿SOLICITUD y NOVEDAD son texto libre o catálogo?

**ESTADO** ya es catálogo. Para **SOLICITUD** y **NOVEDAD** en v1:

- **A)** Ambos **texto libre**.
- **B)** Uno o ambos son **catálogo** editable en Catálogos (indique cuál/es y, si puede, valores iniciales).
- **C)** Otro (describa).

### 4. Valores iniciales del catálogo ESTADO

¿Qué valores debe tener el catálogo **ESTADO** el día 1 (lista exacta para seed)?  
Ejemplo a confirmar o corregir: Pendiente, En proceso, Respondida, Cerrada — u otra lista suya.

### 5. Dashboard v1 — ¿qué debe mostrar?

¿Qué KPIs / gráficos quiere en la primera versión?

Ejemplos a confirmar, descartar o reemplazar:

- Total de solicitudes del año (o año+mes).
- Conteo por **ESTADO**.
- Promedio o distribución de **días de respuesta**.
- Tendencia por mes.

Si prefiere otro set, descríbalo en una frase.

### 6. Acciones sobre una solicitud ya guardada

Además de **agregar nuevo**, export y masivo, en el listado v1 ¿se permite:

- **A)** Solo ver + alta + masivo + export (sin editar ni eliminar fila).
- **B)** **Editar** y **eliminar** filas existentes (¿borrado definitivo?).
- **C)** Solo editar (sin eliminar), u otra combinación.

### 7. Cómo se precalculan los “Días de respuesta”

Cuando el sistema sugiere/guarda el valor automáticamente:

- **A)** Días **calendario** entre fecha de solicitud y fecha de respuesta (si falta fecha de respuesta → vacío / 0 — indique cuál).
- **B)** Solo días **hábiles** (lun–vie).
- **C)** Otro (describa).

¿El usuario puede **sobrescribir** ese valor a mano aunque el sistema lo haya calculado? (la decisión 4 dice que es editable; confirmar que el override manual se conserva al re-guardar).

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Masivo: opción **A** — rechazar filas cuya `fecha_solicitud` no caiga en el año+mes seleccionado; el replace del periodo solo procede con filas del periodo (o falla el archivo si hay fuera de periodo — Arquitecto detalla política). | Se pierden filas que el negocio quería aceptar en otros meses (B) o forzar (C). |
| 2 | Obligatorios: fecha solicitud, nombre, cédula, solicitud, estado; opcionales: correo, fecha respuesta, novedad, días de respuesta. | Validación más estricta o más laxa de lo operativo. |
| 3 | **SOLICITUD** y **NOVEDAD** = texto libre en v1 (solo ESTADO es catálogo). | Si debían ser catálogo, falta pestaña/CRUD y seed. |
| 4 | Seed ESTADO mínimo: Pendiente, En proceso, Respondida, Cerrada (labels exactos a confirmar). | Etiquetas distintas a las del Excel/operación real. |
| 5 | Dashboard v1: total + conteo por ESTADO + filtro año (y mes opcional); sin gráficos avanzados. | Negocio esperaba promedio de días u otros KPIs. |
| 6 | Acciones fila: **editar** y **eliminar duro** con `solicitudes.edit` (patrón Selección). | Alcance de mutación incorrecto. |
| 7 | Días = diferencia calendario (fecha_respuesta − fecha_solicitud); si no hay fecha de respuesta → null; el valor manual se respeta si el usuario lo edita. | Cálculo hábil o a cero distinto. |
| 8 | Varios registros por misma cédula **permitidos** (sin bloqueo); sin bridge a Ficha; sin notificaciones correo en v1. | Política de duplicados o integraciones no cubiertas. |
| 9 | Confirmación UI explícita antes del replace del periodo (como Formación), con conteo de filas a borrar del año+mes. | Wipe accidental del mes. |

> Estos supuestos **no** cierran el brief; son red de seguridad solo si el usuario autoriza avanzar sin responder algún punto. La pregunta **1 (A/B/C)** es crítica y no debe asumirse sin OK.

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

| # | Respuesta (2026-10-02) | Interpretación cerrada |
| --- | --- | --- |
| 1 | **B** | Masivo: aceptar filas fuera del periodo (año/mes reales de `fecha_solicitud`); el replace **solo borra** el año+mes seleccionado en el filtro. |
| 2 | «en v1 si» | En v1 pueden ir vacíos: correo, fecha de respuesta, novedad, días de respuesta y estado. Obligatorios mínimos: fecha solicitud, nombre y apellidos, cédula, solicitud (catálogo). |
| 3 | Solicitud catálogo; novedad texto libre | Catálogos v1: **ESTADO** + **SOLICITUD**. NOVEDAD = texto libre. |
| 4 | «si» | Seed ESTADO: Pendiente, En proceso, Respondida, Cerrada. |
| 5 | «si» | Dashboard v1: total; conteo por ESTADO; promedio/distribución días de respuesta; tendencia por mes (filtros año/mes). |
| 6 | «si» | Acciones fila: **editar** + **eliminar** (definitivo) con `solicitudes.edit`, además de alta/masivo/export. |
| 7 | hábiles | Días de respuesta = días **hábiles** (lun–vie) entre fechas; editable; override manual se conserva al re-guardar si el usuario lo cambió. |

### Nota AgentSj

- Pregunta 2/4/5/6 respondidas con «si» → se cierran con los ejemplos/supuestos del Analista según tabla arriba.
- Catálogo **SOLICITUD**: valores iniciales del seed **no** listados por el usuario → Arquitecto debe proponer seed vacío o placeholder y flags en brief; AgentSj puede pedir lista si el Feature lo bloquea.
