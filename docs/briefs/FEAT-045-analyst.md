# Preguntas del Analista — FEAT-045

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** **sin preguntas abiertas — decisiones cerradas.** Negocio, permisos, UI, datos, estados, import/export y dashboard KPIs confirmados por el usuario (chat 2026-10-09 + run log). **Listo para Arquitecto.** No repreguntar salvo contradicción grave con el Excel de referencia o con Ficha.

---

## Contexto recibido

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-045 |
| Origen | AgentSj — Tablero GH **MT-ST-04** (2026-10-09) |
| Módulo / área | Gestión Humana — tablero `mt_st_04` |
| Título | Matriz de control de exámenes **psicofísicos** (manejo de armas) y **psicosensométricos** (seguridad vial) |
| Run log | [`docs/runs/FEAT-045-run-log.md`](../runs/FEAT-045-run-log.md) |
| Referencia Excel | `MT-ST-04 CONTROL EXAMENES PSICOFISICOS Y PSICOSENSOMETRICO` — hoja **MATRIZ** |
| Docs cercanos | [`formacion.md`](../modules/formacion.md), [`acreditaciones.md`](../modules/acreditaciones.md), [`ficha-empleados.md`](../modules/ficha-empleados.md), [`cursos.md`](../modules/cursos.md) |

### Solicitud (resumen)

Digitalizar en SJ StatFlow la matriz Excel **MT-ST-04**: una fila por cédula con datos de ficha (solo lectura), captura de dos exámenes (psicofísico / psicosensométrico), vencimientos calculados (+364 días), estados persistidos con recalculo, listado filtrable, import upsert, export, y dashboard de KPIs/gráficos.

### Patrones de referencia (repo)

| Aspecto | Referencia |
| --- | --- |
| Board GH + Dashboard + listado + import/export | Formación (FEAT-041), Acreditaciones (FEAT-036+), Cliente interno (FEAT-042) |
| Permisos board + `view`/`edit` (edit ⇒ view); dashboard con `.view` | Formación / Cursos |
| Lookup ficha por cédula (solo lectura) | Cursos / Acreditaciones ↔ `employee_ficha_profiles` |
| DataTables server-side + chrome UI | Ficha empleados, Formación, Cursos |
| Estados de vigencia + job/cron | Cursos (`cursos:sync-estados`) — análogo conceptual |
| Selectores / Excel / audit | `<x-searchable-select>`; `BaseExport` + `<x-export-excel>`; `SystemAuditService` wrapper |
| Shared-files | `config/access.php`, rutas GH / nav, audit |
| Datos | Solo migraciones aditivas; **sin** `migrate:fresh` / wipe / TRUNCATE operativo |

---

## Resumen de lo entendido

Gestión Humana necesita un **tablero dedicado MT-ST-04** para controlar la vigencia de:

1. **Examen psicofísico** (manejo de armas) — examen 1 / columnas ESTADO, FECHA VENCIMIENTO, etc.
2. **Examen psicosensométrico** (seguridad vial) — examen 2 / ESTADO2, FECHA VENCIMIENTO2; **NO APLICA** si el CARGO (mayúsculas) es exactamente `GUARDA` u `OPERADOR`.

Operadores con `mt_st_04.view` consultan dashboard, filtran, exportan; con `mt_st_04.edit` hacen CRUD e import. Una fila por cédula; nombre/cargo/ciudad/puesto vienen de Ficha al resolver la cédula. Vencimientos y estados se calculan según reglas Excel (+364 días; ventana VENCERA 30 días calendario, TZ `America/Bogota`).

---

## Alcance (V1)

### Incluye

- Tablero sidebar **MT-ST-04** en Gestión Humana (`view.board.gestion_humana.mt_st_04`).
- Permisos funcionales `mt_st_04.view` / `mt_st_04.edit` (`edit` implica `view` en AccessService). Dashboard accesible con `.view` (sin permiso board dashboard aparte).
- Asignación Spatie **manual** (sin migración automática de permisos legacy).
- Pestañas exactas: **Dashboard** | **Matriz**. Sin pestaña Catálogos.
- **Matriz:** DataTables **server-side**; filtros; CRUD fila a fila; plantilla Excel + import **upsert por cédula**; export con estados calculados/persistidos.
- Default listado: solo empleados **activos** en ficha; opción/filtro UI para incluir **desvinculados**.
- Lookup ficha por cédula (solo lectura): NOMBRE COMPLETO, CARGO, CIUDAD, PUESTO (`cost_center_name`).
- Campos editables: ARMA, FECHA DE EXAMEN, APTO, OBSERVACIONES, FECHA EXAMEN (examen 2), OBSERVACIONES2.
- ARMA y APTO: selects fijos **SI/NO** (sin catálogo BD).
- FECHA VENCIMIENTO / FECHA VENCIMIENTO2: calculadas = fecha examen correspondiente + **364** días.
- ESTADO / ESTADO2: **persistidos** + recalculo al guardar y vía **cron/job**.
- Chrome UI calidad módulo desde el primer slice (clonar Formación / Acreditaciones / Cliente interno): `.module-tab`, `req-manage-*`, searchable-select, icon-btn canónicos, tokens branding.
- Audit central del módulo; docs técnica + usuario al cierre (Documentador).
- Tests Feature de acceso, reglas de estado, import/export mínimos.

### Fuera de alcance (V1)

- Columna **RETIRADOS** del Excel.
- Pestaña Catálogos / parámetros editables.
- Acciones **bulk** sobre filas seleccionadas (solo import + CRUD unitario).
- Migración automática de permisos Spatie a usuarios existentes.
- Notificaciones por correo / alertas push por vencimiento.
- Soft-delete / versionado histórico del dataset.
- Sync bidireccional editable Ficha ↔ matriz (solo lookup lectura).
- Bridge con Cursos, Acreditaciones u otros tableros GH más allá del lookup por cédula.
- Edición manual de FECHA VENCIMIENTO / ESTADO (son derivados/recalculados).
- Multi-fila por cédula o historial de exámenes por persona.

---

## Decisiones YA CERRADAS (ley — no repreguntar)

| # | Tema | Decisión |
| --- | --- | --- |
| 1 | Permiso board | `view.board.gestion_humana.mt_st_04` — label Admin **MT-ST-04** |
| 2 | Consulta | `mt_st_04.view` — ver, filtrar, export, dashboard |
| 3 | Edición | `mt_st_04.edit` — CRUD, import; **edit ⇒ view** en AccessService |
| 4 | Dashboard | Usa `.view`; **sin** permiso board dashboard aparte |
| 5 | Spatie | Asignación **manual**; sin migración automática |
| 6 | Pestañas | Dashboard + Matriz; **sin** Catálogos |
| 7 | ARMA / APTO | Selects fijos SI/NO |
| 8 | Unicidad | 1 fila por cédula (`unique`) |
| 9 | Lookup ficha | NOMBRE COMPLETO, CARGO, CIUDAD, PUESTO=`cost_center_name` — solo lectura |
| 10 | Editables | ARMA, FECHA DE EXAMEN, APTO, OBSERVACIONES, FECHA EXAMEN (2), OBSERVACIONES2 |
| 11 | Vencimientos | fecha examen + **364** días (como Excel) |
| 12 | Estados | Persistidos + recalculo al guardar + cron/job |
| 13 | ESTADO1 | Vacío si no hay vencimiento; si vencimiento < hoy → **VENCIDO**; si hoy+30 > vencimiento → **VENCERA**; else **VIGENTE** |
| 14 | ESTADO2 | Si CARGO en mayúsculas es **GUARDA** u **OPERADOR** → **NO APLICA**; else misma lógica ESTADO1 sobre vencimiento2 |
| 15 | VENCERA | 30 días calendario; TZ **America/Bogota** |
| 16 | Default listado | Solo activos ficha; filtro/opción para ver desvinculados |
| 17 | Import | Upsert por cédula; plantilla + export con estados |
| 18 | Bulk filas | No |
| 19 | RETIRADOS | Fuera V1 |
| 20 | Dashboard KPIs | Total / Vigente / Vencera / Vencido (examen 1); mismos examen 2 (excl. NO APLICA); Aptos vs no aptos; gráficos |
| 21 | UI | Calidad módulo (Formación / Acreditaciones / Cliente interno) |

---

## Reglas de negocio (detalle para Arquitecto)

### Identidad y ficha

- Clave de negocio: **cédula** (única en la matriz).
- Al crear/editar/importar: resolver perfil en Ficha por cédula; mostrar/snapshot de lectura: nombre completo, cargo, ciudad, puesto (`cost_center_name`).
- Arquitecto define: si la cédula **debe existir** en Ficha para alta (recomendación analista alineada a Cursos: sí, fallar fila si no hay ficha — **no es pregunta abierta**; es decisión de diseño técnico documentada como supuesto operativo cerrado por patrón del repo, coherente con “desde ficha por cédula”). Si el Excel de carga trae cédulas sin ficha, el import debe reportar error por fila, no crear “huérfanos” sin lookup.

### Examen 1 — psicofísico (armas)

| Campo | Origen |
| --- | --- |
| ARMA | Editable SI/NO |
| FECHA DE EXAMEN | Editable (manual) |
| FECHA VENCIMIENTO | Calculada = FECHA DE EXAMEN + 364 días; null si no hay fecha examen |
| APTO | Editable SI/NO |
| OBSERVACIONES | Editable texto |
| ESTADO | Persistido; reglas abajo |

**ESTADO (examen 1):**

1. Si no hay FECHA VENCIMIENTO → **vacío** (null / string vacío según diseño Arquitecto; UI muestra vacío).
2. Else si `vencimiento < hoy` (Bogotá) → **VENCIDO**.
3. Else si `hoy + 30 días > vencimiento` → **VENCERA**.  
   *(Interpretación cerrada: ventana de alerta cuando faltan **menos de 30 días** o, en borde, cuando `hoy+30 > vencimiento`; Arquitecto formaliza comparación inclusiva/exclusiva en brief técnico alineada al Excel — ver riesgos.)*
4. Else → **VIGENTE**.

### Examen 2 — psicosensométrico (vial)

| Campo | Origen |
| --- | --- |
| FECHA EXAMEN | Editable (examen 2) |
| FECHA VENCIMIENTO2 | Calculada = FECHA EXAMEN + 364 días |
| OBSERVACIONES2 | Editable |
| ESTADO2 | Persistido |

**ESTADO2:**

1. Si `strtoupper(trim(CARGO))` es exactamente `GUARDA` u `OPERADOR` → **NO APLICA** (independiente de fechas).
2. Else: misma lógica que ESTADO1 sobre FECHA VENCIMIENTO2.

> Nota: CARGO proviene de ficha (solo lectura). Si el cargo en ficha cambia después, el job/cron y el próximo guardado deben recalcular ESTADO2 (posible paso de NO APLICA ↔ vigencia).

### Recalculo

- Al **guardar** (CRUD e import por fila): recalcular vencimientos + estados.
- **Job/cron** diario (TZ Bogotá): recalcular ESTADOs de todas las filas (o chunk) para que VIGENTE→VENCERA→VENCIDO avance sin edición manual.
- Export y Dashboard leen estados **persistidos** (tras recalculo), no reinventar fórmula divergente en lectura suelta sin documentarlo.

### Listado y filtros

- Default: `employment_status = activo` (o equivalente ficha).
- Opción/filtro explícito para incluir o ver **desvinculados**.
- Sin selección masiva de filas ni acciones bulk.

### Import / plantilla / export

- Plantilla descargable (headers alineados a matriz operativa).
- Import: **upsert por cédula** (actualiza existentes; inserta nuevas).
- Export: incluye estados y vencimientos calculados/persistidos.
- `BaseExport` + `<x-export-excel>`; no `excelHtml5`.

### Dashboard

KPIs V1 (examen 1 / psicofísicos):

- Total, Vigente, Vencera, Vencido.

KPIs V1 (examen 2 / psicosensométricos):

- Mismos conteos **excluyendo** filas **NO APLICA**.

Adicional:

- Aptos vs no aptos (campo APTO).
- Gráficos (ApexCharts del proyecto, patrón Formación/GH).

Filtros de dashboard (año u otros): Arquitecto propone mínimos coherentes con el dataset (no hay pregunta abierta de negocio; si no hay dimensión temporal de “periodo de carga”, los KPIs son sobre el universo filtrable actual — activos por defecto).

### Permisos (mapa cerrado)

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.mt_st_04` | Sidebar / entrada al tablero |
| `mt_st_04.view` | Dashboard, Matriz (ver/filtrar), export |
| `mt_st_04.edit` | CRUD, plantilla, import |

Labels Admin legibles (ej. **MT-ST-04**, **MT-ST-04: Ver**, **MT-ST-04: Editar** — Arquitecto fija wording exacto en `config/access.php`).

---

## Riesgos

| # | Riesgo | Impacto | Mitigación para Arquitecto |
| --- | --- | --- | --- |
| 1 | Borde VENCERA: `hoy+30 > vencimiento` vs `≥` / “faltan ≤30 días” | Conteos KPI y badges distintos al Excel un día de frontera | Formalizar comparación exacta en brief + tests con fechas fijas TZ Bogotá |
| 2 | CARGO parcial (`GUARDA SJ`, `OPERADOR MENSUAL`) no es NO APLICA | Negocio podría esperar match “contiene”; decisión dice **exactamente** GUARDA/OPERADOR | Documentar match exacto; tests; no ampliar sin OK usuario |
| 3 | Snapshot vs live de CARGO/nombre | Si solo se guarda snapshot, ESTADO2 queda stale al cambiar cargo en Ficha | Preferir lectura live de ficha en listado/recalculo; o snapshot + job que refresque cargo |
| 4 | Cédula sin ficha en import | Filas huérfanas sin nombre/cargo | Rechazar fila con error claro; no inventar datos |
| 5 | Unicidad cédula + Excel duplicados | Última gana vs error | Upsert: última fila del archivo gana **o** reportar duplicados — elegir en brief y testear |
| 6 | Job no programado en deploy | Estados quedan desactualizados hasta edición | Documentar comando schedule en `routes/console.php` + PROCEDURES/docs módulo |
| 7 | Permisos sin sync a usuarios | Tablero invisible el día 1 | Doc usuario Admin: asignar board + view/edit manualmente |
| 8 | Volumen + DT client-side | Listado lento | Server-side obligatorio desde V1 |
| 9 | Confusión RETIRADOS Excel | Operadores piden la columna | Fuera V1 explícito; no implementar “por si acaso” |
| 10 | Desvinculados en KPIs | Dashboard inflado si no filtra | Default KPIs alineados a activos (misma regla listado); documentar |

---

## Checklist para Arquitecto

- [ ] Feature Brief final `docs/briefs/FEAT-045.md` + plan `docs/briefs/FEAT-045-plan.md` (Task Card / slices).
- [ ] Claves en `config/access.php` (board + view/edit + labels Admin + grupo GH); **sin** packs multi-pestaña; **sin** permiso dashboard aparte.
- [ ] `MtSt04AccessService` (o nombre canónico): `canViewBoard`, `canView` (view∨edit), `canEdit`; `edit` ⇒ `view`.
- [ ] Nav: `NavigationResolver` / Sidebar / `User` default URL + tabs (Dashboard | Matriz).
- [ ] Migración multi-driver (MySQL + sqlite tests): tabla matriz 1 fila/cédula unique; columnas editables + vencimientos + estados persistidos; FK/lógica a ficha por cédula.
- [ ] Servicio de dominio: cálculo vencimiento (+364), ESTADO1/ESTADO2 (incl. NO APLICA), TZ Bogotá.
- [ ] Job/comando artisan + schedule diario de recalculo de estados.
- [ ] Controllers + Form Requests; authorize view/edit.
- [ ] Matriz: DataTables server-side + filtros (activo/desvinculado + operativos).
- [ ] CRUD modal/form; ARMA/APTO searchable-select SI/NO; lookup ficha readonly.
- [ ] Import upsert por cédula + plantilla; export `BaseExport`.
- [ ] Dashboard metrics JSON + KPIs + gráficos ApexCharts.
- [ ] Audit wrapper módulo + entrada `config/audit.php`.
- [ ] Shared-files flag respetado; no tocar módulos ajenos.
- [ ] Tests Feature: permisos, unicidad, reglas estado (bordes), NO APLICA, import upsert, default activos.
- [ ] Docs vivos en entrega Feature/Documentador: `docs/modules/mt_st_04.md` (o nombre acordado), `docs/user/…`, ACCESS_CONTROL, INDEX.
- [ ] UI: chrome req-manage / module-tab / icon-btn canónicos; `npm run build` si CSS; smoke DevTools al cerrar Feature.

---

## Supuestos operativos (no reabren preguntas)

| # | Supuesto | Riesgo si incorrecto |
| --- | --- | --- |
| 1 | Alta/import exige cédula existente en Ficha (patrón Cursos) | Si el negocio carga externos sin ficha, habrá que relajar en V1.1 |
| 2 | Match NO APLICA = igualdad exacta tras `strtoupper(trim(CARGO))` | Cargos compuestos no excluídos |
| 3 | Dashboard default sobre universo **activos** (como listado) | KPIs distintos si se mezclan desvinculados sin filtro |
| 4 | Labels UI tablero **MT-ST-04**; pestañas **Dashboard** / **Matriz** | Naming distinto en Excel no cambia keys técnicas |
| 5 | Sin soft-delete: eliminar fila = borrado definitivo con `edit` | Pérdida de dato si borran por error |
| 6 | Gráficos = distribución de estados examen 1 / examen 2 + aptos (detalle visual Arquitecto) | Negocio pedirá otro chart después |

---

## Preguntas abiertas

*(Ninguna.)*

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Nota explícita

**Sin preguntas abiertas — decisiones cerradas.** El AgentSj puede avanzar a fase **Arquitecto** sin pausa por Analista.
