# Preguntas del Analista — FEAT-036

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** negocio base + respuestas usuario **1–5 cerradas** (2026-09-23). Listo para Arquitecto.

---

## Contexto recibido

**Feature ID:** FEAT-036  
**Origen:** `@agent-sj` Tablero Acreditaciones GH + confirmaciones 1–8 y catálogo cargos (2026-09-23)  
**Módulo tentativo:** Acreditaciones (`acreditaciones`) — área Gestión Humana  
**Título:** Tablero Acreditaciones GH — Fase 1: shell + Acreditados + Catálogo cargos  
**Run log:** [`docs/runs/FEAT-036-run-log.md`](../runs/FEAT-036-run-log.md)

### Patrones de referencia (repo)

| Aspecto | Referencia |
| --- | --- |
| Tablero GH multi-pestaña + DataTables server-side + import/export Excel | Cursos (FEAT-032) — `docs/modules/cursos.md` |
| Shell de pestañas + view/edit + Catálogos | Selección (FEAT-035) — `docs/modules/seleccion.md` |
| Relación operativa por cédula con Ficha | Cursos: `document_number` ↔ `employee_ficha_profiles.document_number` |
| Permisos board + view/edit | `config/access.php` (`cursos.*` / `seleccion.*`) |
| Selectores / Excel | `<x-searchable-select>`; `BaseExport` + `<x-export-excel>` |
| Shared-files esperables | `config/access.php`, `routes/areas/gestion_humana.php`, NavigationResolver/Sidebar, audit |
| Datos | Solo migraciones aditivas; sin `migrate:fresh` / wipe |

---

## Resumen de lo entendido

Gestión Humana necesita un **tablero Acreditaciones** para controlar el personal acreditado (vigencia, solicitud en trámite, estados automáticos) y, más adelante, dashboard, reporte diario, validaciones y export APO. En **fase 1** solo se construye el **shell del tablero** (pestañas) y la pestaña operativa **Acreditados**, más un **Catálogo de cargos** (seed de la tabla de negocio). Las demás pestañas quedan como **placeholders**.

| Aspecto | Entendido (cerrado) |
| --- | --- |
| UI shell | Tablero `acreditaciones` en GH; pestañas **Dashboard**, **Acreditados**, **Reporte Diario**, **Validaciones**, **Export Apo**, **Catálogo** |
| Fase 1 | Shell + **Acreditados** funcional + **Catálogo** (seed cargos); resto = placeholder |
| **VIGEN.ACR** | Fecha de **vencimiento** de la acreditación |
| **CARGO** (Acreditados) | Viene del **Excel de carga**; **no** se deriva del catálogo en fase 1 |
| Campos Acreditados | CEDULA, NOMBRE COMPLETO, CARGO, VIGEN.ACR, ESTADO, OBSERVACIONES, FECHA SOLICITUD |
| Estados (auto) | ACREDITADO, EN PROCESO, POR VENCER, DESACREDITADO |
| Prioridad estados (mayor → menor) | 1) Si hay FECHA SOLICITUD → **EN PROCESO**; 2) Si VIGEN.ACR ≤ hoy → **DESACREDITADO**; 3) Si VIGEN.ACR ≤ hoy+21 días → **POR VENCER**; 4) Else → **ACREDITADO** |
| Relación Ficha | Por **cédula**; un empleado puede tener **varias** acreditaciones de **distinto tipo** |
| Carga | **Carga masiva** inicial de personal acreditado |
| Catálogo cargos (seed) | Columnas: CARGO MANAGER \| CARGO APO \| CARGO INFORME \| CARGO ACREDITACION; 17 filas (códigos 1, 2, 4, 5, 6; sin código 3 en la muestra) |

### Alcance fase 1 (cerrado)

- Incluye: board + permisos + navegación; subnav pestañas; Acreditados (datos + reglas de estado + import masivo); Catálogo cargos con seed.
- Fuera de fase 1 (explícito): lógica de Dashboard, Reporte Diario, Validaciones, Export Apo (solo shell/placeholder).
- Fuera de alcance implícito hasta que el negocio diga lo contrario: notificaciones por correo; soft-delete; bridge editable desde Ficha; sync automático Ficha→Acreditados.

### Seed catálogo cargos (confirmado)

| CARGO MANAGER | CARGO APO | CARGO INFORME | CARGO ACREDITACION |
| --- | --- | --- | --- |
| GUARDA | VIGILANTE | GUARDAS | 1 |
| GUARDA MENSUAL | VIGILANTE | GUARDAS | 1 |
| GUARDA SJ | VIGILANTE | GUARDAS | 1 |
| COORDINADOR CARTAGENA | ESCOLTA | COORDINADORES | 2 |
| COORDINADOR DE OPERACIONES | ESCOLTA | COORDINADORES | 2 |
| COORDINADORES | ESCOLTA | COORDINADORES | 2 |
| COORDINADORES MENSUAL | ESCOLTA | COORDINADORES | 2 |
| DIRECTOR NACIONAL OPERACIONES Y GESTION DE RIESGOS | ESCOLTA | DIRECTOR NACIONAL | 2 |
| ESCOLTA | ESCOLTA | ESCOLTA | 2 |
| ESCOLTA MENSUAL | ESCOLTA | ESCOLTA | 2 |
| JEFE DE SEGURIDAD | ESCOLTA | JEFES DE SEGURIDAD | 2 |
| SUPERVISOR SJ | SUPERVISOR | SUPERVISORES | 4 |
| SUPERVISORES | SUPERVISOR | SUPERVISORES | 4 |
| SUPERVISORES MENSUAL | SUPERVISOR | SUPERVISORES | 4 |
| OPERADOR | OPERADOR DE MEDIOS TECNOLOGICOS | OMT(OPERADOR) | 5 |
| OPERADOR SJ | OPERADOR DE MEDIOS TECNOLOGICOS | OMT(OPERADOR) | 5 |
| GUARDA MANEJADOR CANINO | MANEJADOR CANINO | MANEJADOR CANINO | 6 |

---

## Preguntas abiertas

Responde cada punto para cerrar el brief (máx. 5 — vacíos reales no cubiertos arriba):

### 1. “Tipo” de acreditación y unicidad (multi-tipo)

Se confirmó que un empleado puede tener **varias** acreditaciones de **distinto tipo**, pero en los campos de Acreditados **no** aparece una columna **TIPO**.

1.1. ¿Qué es exactamente el **tipo** en el negocio?  
   - **A)** El código **CARGO ACREDITACION** del catálogo (1, 2, 4, 5, 6).  
   - **B)** El texto **CARGO** del Excel (ej. GUARDA vs ESCOLTA).  
   - **C)** Otro valor (indícalo: nombre del campo y lista o catálogo).

1.2. Al cargar/actualizar, ¿qué identifica un registro único?  
   Ejemplo típico: **cédula + tipo** (una fila por tipo por persona). ¿O cédula + CARGO? ¿O se permiten varias filas iguales y se distinguen solo por fechas?

1.3. Si el Excel trae de nuevo la misma cédula+tipo: ¿**actualizar** la fila existente (upsert) o **crear otra** / rechazar?

### 2. Relación con Ficha empleados (cédula y nombre)

2.1. Al crear o importar una acreditación, la cédula **¿debe existir** ya en Ficha (`employee_ficha_profiles`)?  
   - **A)** Sí, obligatoria (como Cursos): si no está en Ficha, la fila falla.  
   - **B)** No: se puede acreditar personal aunque aún no esté en Ficha (solo “enlace” cuando coincida).

2.2. **NOMBRE COMPLETO**:  
   - **A)** Siempre se toma de Ficha (el Excel puede traerlo pero se ignora).  
   - **B)** Se guarda el del Excel (y Ficha solo se usa para consulta/enlace).  
   - **C)** Preferir Ficha si existe; si no, usar el del Excel.

### 3. Qué puede hacer el usuario en Acreditados (fase 1)

Además de la **carga masiva inicial**, ¿fase 1 incluye…?

Marca sí/no (o “solo con permiso editar”):

- Alta / edición / borrado **manual** fila a fila (formulario o modal).  
- **Plantilla vacía** de import + re-import posteriores (no solo la carga inicial).  
- **Export Excel** del listado filtrado.  
- Filtros por estado / cédula / cargo / fechas.  
- ¿**ESTADO** es solo calculado (no editable en pantalla), como la vigencia en Cursos?

### 4. Pestaña Catálogo (fase 1)

El seed de cargos está cerrado. En la pestaña **Catálogo** de fase 1, ¿el usuario puede…?

- **A)** Solo **consultar** el listado seed (sin agregar/editar/borrar).  
- **B)** **Administrar** (CRUD) las filas CARGO MANAGER / APO / INFORME / ACREDITACION, como Catálogo de Cursos/Selección.  
- **C)** Consultar en fase 1; CRUD en una fase posterior.

### 5. Permisos, roles por defecto y auditoría

5.1. ¿Confirmamos el patrón de Cursos/Selección?  
   - `view.board.gestion_humana.acreditaciones` (ver tablero en sidebar)  
   - `acreditaciones.view` (consultar listados / export si aplica)  
   - `acreditaciones.edit` (import, CRUD si aplica, catálogo si B)  
   - Bypass `manage.users` / super-admin; roles `administrador` / `usuario` **sin** paquete por defecto (asignación manual en Admin).

5.2. ¿Auditoría de altas/ediciones/borrados/import vía **`SystemAuditService`** (wrapper módulo `acreditaciones`, área `gestion_humana`), solo mutaciones?

---

## Vacios identificados (checklist interno)

| Área | Estado |
| --- | --- |
| Shell pestañas + placeholders fase 1 | Cerrado |
| Campos Acreditados (lista) | Cerrado; tipo = **CARGO APO** (P1) |
| Regla prioridad estados + VIGEN.ACR = vencimiento | Cerrado |
| CARGO desde Excel (no derivado catálogo) | Cerrado |
| Multi-tipo por empleado | Cerrado: unicidad **cédula + CARGO APO**; upsert |
| Relación Ficha por cédula | Cerrado: cédula obligatoria en Ficha; nombre desde Ficha |
| Carga masiva + CRUD/plantilla/export/filtros | Cerrado (sí a todo); ESTADO solo calculado |
| Seed + CRUD catálogo cargos | Cerrado (opción B) |
| Permisos / roles default / audit | Cerrado (patrón Cursos/Selección) |
| Dashboard / Reporte Diario / Validaciones / Export Apo | Fuera fase 1 (placeholders) |
| Fechas nulas (sin VIGEN.ACR ni FECHA SOLICITUD) | **Arquitecto** debe fijar en brief |

---

## Supuestos temporales (mientras el usuario no responde)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | **ESTADO** se calcula (no se edita a mano); se reevalúa al guardar/importar y, si aplica, con job/comando diario (patrón Cursos). | Si esperaban override manual del estado, falta campo y UX. |
| 2 | Si hay **FECHA SOLICITUD**, gana **EN PROCESO** aunque VIGEN.ACR ya esté vencida (prioridad confirmada). | Ninguno si es correcto; documentar en ayuda UI. |
| 3 | Si **no** hay FECHA SOLICITUD y **VIGEN.ACR está vacía**: tratar como dato incompleto / rechazar en import o estado indefinido — **Arquitecto debe fijar** tras P2/P3; no inventar estado. | Filas legacy sin fecha podrían quedar fuera o mal clasificadas. |
| 4 | Ventana “por vencer” = **21 días calendario** desde hoy (inclusive en el ≤ hoy+21). Zona horaria app America/Bogota. | Desfase de un día vs negocio. |
| 5 | Pestañas placeholder: mismo chrome `.module-tab`, mensaje “Próximamente” o vacío controlado; sin endpoints de negocio. | Si querían mock con datos ficticios, retrabajo menor. |
| 6 | Labels UI: tablero **Acreditaciones**; pestaña **Export Apo** tal cual; Catálogo con acento según nav. | Renombre cosmética. |
| 7 | Listado Acreditados: DataTables **server-side** (tabla operativa que crecerá). | Si el volumen es pequeño y prefieren client-side, cambio menor. |
| 8 | Selectores (filtros/catálogo): `<x-searchable-select>`; export con `BaseExport`. | Alineado a AGENTS.md. |
| 9 | Shared-files: `config/access.php`, rutas `gestion_humana`, nav, `config/audit.php` — flag en TASKS ya previsto. | Coordinación AgentSj. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

| # | Respuesta |
| --- | --- |
| 1.1 | El **tipo** de acreditación es **CARGO APO** del catálogo de cargos (tabla nueva). |
| 1.2 | Unicidad: **cédula + tipo** (cédula + CARGO APO). |
| 1.3 | Si el Excel trae misma cédula+tipo → **actualizar** (upsert). |
| 2.1 | **A** — la cédula **debe existir** en Ficha; si no, la fila falla. |
| 2.2 | **A** — **NOMBRE COMPLETO** siempre desde Ficha (Excel lo puede traer pero se ignora). |
| 3 | **Sí** a todo: CRUD manual fila a fila; plantilla vacía + re-import; export Excel filtrado; filtros (estado/cédula/cargo/fechas); **ESTADO** solo calculado (no editable). |
| 4 | **B** — Catálogo con **CRUD** (CARGO MANAGER / APO / INFORME / ACREDITACION). |
| 5 | **Sí** — permisos `view.board…acreditaciones` + `acreditaciones.view` / `acreditaciones.edit`; sin paquete default a roles; auditoría mutaciones vía `SystemAuditService` (módulo `acreditaciones`, área `gestion_humana`). |

---

## Acción para AgentSj

Respuestas cerradas → lanzar **Arquitecto** para Feature Brief final `docs/briefs/FEAT-036.md`.