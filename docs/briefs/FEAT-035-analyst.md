# Preguntas del Analista — FEAT-035

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** decisiones de UI/datos/campos/catálogos **cerradas** en run log (2026-09-22). Quedan **5 vacíos de negocio** antes del brief Arquitecto.

---

## Contexto recibido

**Feature ID:** FEAT-035  
**Origen:** `@agent-sj` Tablero Selección GH + respuestas 1–11 y dudas 1–6 (chat 2026-09-22)  
**Módulo:** Selección (`seleccion`) — área Gestión Humana  
**Título:** Tablero Selección GH (Dashboard, Ingreso, Examen ocupacional, Catálogos)  
**Run log:** [`docs/runs/FEAT-035-run-log.md`](../runs/FEAT-035-run-log.md)

### Patrones de referencia (repo)

| Aspecto | Referencia |
| --- | --- |
| Tablero GH multi-pestaña + Dashboard KPIs/gráficos + DataTables server-side + export | Cursos (FEAT-032) — `docs/modules/cursos.md` |
| Catálogos nómina `payroll_catalog_items` + `catalog_types` | Ficha empleados — `config/employee_ficha.php` |
| Permisos board + view/edit | `config/access.php` (`cursos.*` como plantilla) |
| Responsable / reclutadores activos | `RequisitionSelectionOfficerAccessService` + permiso `requisitions.selection_officer` |
| Selectores | `<x-searchable-select>` (prohibido Select2) |
| Export Excel | `BaseExport` + `<x-export-excel>` |
| Datos | Solo migraciones aditivas; sin `migrate:fresh` / wipe |

---

## Resumen de lo entendido

Gestión Humana necesita un **tablero Selección** para operar dos flujos independientes (Ingreso y Examen ocupacional), con Dashboard de seguimiento, listados operativos (CRUD, filtros, Excel) y administración de catálogos propios/compartidos. Los registros **no** dependen de Ficha empleados ni de requisiciones; la cédula puede repetirse (varios procesos) pero el sistema debe **avisar** si ya existe.

| Aspecto | Entendido (cerrado) |
| --- | --- |
| UI | Tablero `seleccion` en GH; pestañas **Dashboard**, **Ingreso**, **Examen ocupacional**, **Catálogos** (esta última solo con `seleccion.edit`) |
| Permisos | `view.board.gestion_humana.seleccion`, `seleccion.view`, `seleccion.edit` |
| Listados | CRUD + filtros + DataTables **server-side** + export Excel |
| Dashboard | KPIs + gráficos (detalle concreto **abierto** — pregunta 3) |
| Datos | 2 tablas separadas Ingreso / Examen; independientes de Ficha; varios por cédula OK; borrado **duro**; FECHA ARL = date; SOLICITUD = estado catálogo |
| Catálogos select | city/position/eps/afp → `payroll_catalog_items`; cliente → `commercial_clients`; dotación → `requisition_uniforms`; responsable → reclutadores activos |
| Catálogos nuevos | RH, estado civil, estados SOLICITUD = nuevos `catalog_type` en `payroll_catalog_items`; editables desde Selección Catálogos junto con city/position/eps/afp |
| Texto libre V1 | Servicio/sector; Jefe OPE asignado |
| Estados SOLICITUD (seed) | CONTRATADO, DXENT OPERACIONES, DXENT SELECCIÓN, DESISTE DEL PROCESO, DXPSICOFÍSICO, DxANT, DxEMO Y PSICOFÍSICO, EN PROCESO, DXPOLIGRAFÍA, **DXEMO** (único; sin DxEMO duplicado), EN RESERVA |
| Campos Ingreso | CEDULA, APELLIDOS Y NOMBRE, CORREO, TELEFONO, CIUDAD, CARGO, CLIENTE, TALLA CAMISA/PANTALÓN/ZAPATOS, TIPO DOTACION, FECHA DE INGRESO, RH, REEMPLAZA A, RESPONSABLE, JEFE OPE ASIGNADO |
| Campos Examen | CEDULA, APELLIDOS Y NOMBRES, CARGO, SERVICIO/SECTOR, CLIENTE, EPS, PENSION(AFP), FECHA NACIMIENTO, CIUDAD, DIRECCION, CORREO, CELULAR, ESTADO CIVIL, FECHA DE ARL, SOLICITUD, RESPONSABLE |

### Fuera de alcance (supuesto V1, salvo que el usuario diga lo contrario)

- Bridge / sync con Ficha empleados o Requisiciones.
- Import masivo Excel (solo export del listado).
- Soft-delete / historial versionado.
- Notificaciones por correo.
- Catálogo administrable de Servicio/sector o Jefe OPE (quedan texto libre).

---

## Preguntas abiertas

Responde cada punto para cerrar el brief (máx. 5 — vacíos reales no cubiertos en el run log):

### 1. Campos obligatorios vs opcionales

Para **Ingreso** y para **Examen ocupacional**, ¿cuáles campos son **obligatorios** al crear/editar y cuáles **opcionales**?

Si es más simple: ¿todos obligatorios excepto alguno concreto? Indica excepciones (ej. REEMPLAZA A, tallas, JEFE OPE, CORREO, DIRECCION, etc.).

### 2. Seed de RH y estado civil

Los estados **SOLICITUD** ya están definidos. Faltan los valores exactos de seed para:

- **RH** (grupo sanguíneo): ¿lista exacta? (ej. `O+`, `O-`, `A+`, `A-`, `B+`, `B-`, `AB+`, `AB-`, u otra).
- **Estado civil**: ¿lista exacta? (ej. Soltero/a, Casado/a, Unión libre, Divorciado/a, Viudo/a, u otra).

### 3. Dashboard — KPIs y gráficos concretos

¿Qué debe mostrar el Dashboard en V1?

Ejemplos a confirmar o corregir:

- **KPIs:** totales Ingreso / Examen; conteo por estado SOLICITUD; ingresos del mes; exámenes “en proceso”; etc.
- **Gráficos:** barras/pastel por estado SOLICITUD; tendencia mensual de ingresos; distribución por cliente / responsable / ciudad; etc.
- **Filtros del dashboard:** ¿rango de fechas, cliente, responsable, u otros?

### 4. Notificación de cédula ya registrada

Al crear/editar, si la cédula **ya tiene** registro(s) en la misma tabla (o ¿también en la otra?):

- **A)** Solo **aviso** en pantalla (warning); el usuario puede guardar sin confirmación extra.
- **B)** Aviso + **confirmación** obligatoria (“Sí, continuar”) antes de guardar.
- **C)** Bloqueo (no permitir duplicar) — *contradice* la decisión cerrada de “varios por cédula OK”; solo si el negocio cambió de opinión.

Además: ¿el aviso aplica **solo dentro del mismo flujo** (Ingreso vs Ingreso; Examen vs Examen) o también si la cédula existe en el **otro** flujo?

### 5. Permisos por defecto y auditoría

- ¿Qué **roles** reciben por defecto `view.board.gestion_humana.seleccion`, `seleccion.view` y `seleccion.edit`?  
  (Patrón Cursos/Desvinculaciones: a menudo **ningún** rol base salvo asignación manual en Admin; `super-admin` / `manage.users` con bypass.)
- ¿Confirmamos auditoría de altas/ediciones/borrados/catálogos vía **`SystemAuditService`** (wrapper módulo `seleccion`, área `gestion_humana`), como el resto de tableros GH?

---

## Vacios identificados (checklist interno)

| Área | Estado |
| --- | --- |
| UI / pestañas / CRUD / server-side / Excel | Cerrado |
| Permisos (nombres) | Cerrado; **roles default** abierto (P5) |
| Tablas / independencia Ficha / borrado duro / ARL date | Cerrado |
| Campos Ingreso / Examen (lista) | Cerrado; **required/optional** abierto (P1) |
| Catálogos origen + SOLICITUD seed | Cerrado; **RH / estado civil seed** abierto (P2) |
| Dashboard detalle KPIs/gráficos | Abierto (P3) |
| UX cédula duplicada | Abierto (P4) |
| Audit SystemAuditService | Abierto / a confirmar (P5) |
| Import masivo / bridge Ficha | Fuera V1 (supuesto) |

---

## Supuestos temporales (mientras el usuario no responde)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Tallas camisa/pantalón/zapatos = **texto libre** (no catálogo). | Si esperaban tallas estandarizadas, habrá retrabajo de catálogo. |
| 2 | Sin import masivo ni bridge Ficha en V1. | Si el negocio carga Excel legacy a diario, V1 quedará corta. |
| 3 | Catálogos Selección **no** editan `commercial_clients` ni `requisition_uniforms` (solo consumen); sí editan city/position/eps/afp + RH + estado civil + estados SOLICITUD. | Si querían CRUD de clientes/dotación desde Selección, falta alcance. |
| 4 | Bypass admin: `manage.users` / super-admin igual que Cursos. | Perfiles admin podrían quedar sin acceso o con exceso. |
| 5 | Labels UI: tablero **Selección**; pestañas como en run log (acentos según branding nav). | Renombre cosmética menor. |
| 6 | Duplicado de cédula se evalúa **por tabla** (Ingreso aparte de Examen) hasta respuesta P4. | Podrían querer aviso cruzado entre flujos. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

(Completar tras la pausa del AgentSj.)

| # | Respuesta |
| --- | --- |
| 1 | **Todos** los campos son obligatorios (Ingreso y Examen). |
| 2 | **Sí** a las listas sugeridas: RH = O+, O-, A+, A-, B+, B-, AB+, AB-; Estado civil = Soltero/a, Casado/a, Unión libre, Divorciado/a, Viudo/a. |
| 3 | **Sí** a KPIs/gráficos de ejemplo del Analista: totales Ingreso/Examen; conteo por estado SOLICITUD; ingresos del mes; exámenes en proceso; gráficos por estado SOLICITUD / tendencia mensual / distribución; filtros rango fechas, cliente, responsable. |
| 4 | **B** — aviso + confirmación obligatoria antes de guardar. *(Supuesto AgentSj: aviso solo dentro del mismo flujo Ingreso↔Ingreso / Examen↔Examen, no cruzado, salvo que Arquitecto documente lo contrario.)* |
| 5 | Enfoque en permiso **`seleccion.edit`** (incluye Catálogos). *(Supuesto: `seleccion.view` + board para consultar; sin asignación automática a rol `usuario`; bypass `manage.users` / super-admin como Cursos; audit vía `SystemAuditService`.)* |

## Acción para AgentSj

**Pausar flujo** hasta respuesta del usuario a las preguntas 1–5.  
Tras respuestas: completar esta sección, producir borrador `docs/briefs/FEAT-035.md` (`BORRADOR — pendiente Arquitecto`) y lanzar **Arquitecto**.

**No** se adjunta borrador FEATURE_BRIEF en este ciclo (quedan vacíos reales).
