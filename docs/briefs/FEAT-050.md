# Feature Brief — FEAT-050

> Brief del maestro (2026-11-10). Decisiones numeradas del usuario cerradas en el chat. No reabrir.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-050 |
| Modulo / area | Calidad — tablero **Planeación estratégica** (`planeacion_estrategica`) |
| Titulo | Planeación estratégica fase 1: indicadores de gestión construibles |
| Solicitante | Usuario / AgentSj |
| Fecha | 2026-11-10 |
| Triaje | C — Full |

## Objetivo

Que Calidad cree indicadores de gestión **desde cero** (campos + fórmula), los publique, los comparta a usuarios sueltos para captura, y que directores/Calidad consulten la **ficha tipo Operaciones** (historial anual, gráfico, análisis) con consolidado **suma de campos + recálculo de fórmula** y filtro por capturador.

## Decisiones de negocio (ley)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Alcance V1 | Indicadores + compartir + captura + consulta. Sin DOFA, objetivos ni estrategias. |
| 2 | Tablero | Uno en Calidad: **Planeación estratégica**. Dashboard es **pestaña**, no segundo ítem del sidebar. No reutilizar `view.board.calidad.dashboard`. |
| 3 | Pestañas | Dashboard, Indicadores, Captura. Consulta = abrir ficha desde el listado (no pestaña extra). |
| 4 | Compartir | Solo usuarios sueltos. Al asignar, el sistema abre el acceso mínimo (no Admin en cada alta). |
| 5 | Capturador | Solo ve pestaña Captura de **sus** indicadores. Diligencia su fila. |
| 6 | Director | También diligencia. Además ve **sus** indicadores (listado/consulta/dashboard) y el historial del equipo de **ese** indicador. Rol Spatie `director` de Compras **no** se usa. |
| 7 | Roles en el indicador | Todo asignado tiene `can_capture`. Opcional `can_consult` (director). Misma persona puede tener ambos. |
| 8 | Constructor | Calidad crea N campos (clave + etiqueta) y luego la fórmula, p. ej. `((campo_1 / campo_2) + campo_3) * 100`. Esos campos son el formulario de captura. |
| 9 | Fórmula | Lista blanca `+ - * / ( )`, números y claves. Sin `eval`, sin `SI()`/`MIN()`. Preview con valores de prueba. División por cero no guarda. |
| 10 | Código | Calidad lo escribe a mano. Unique. |
| 11 | Versionado | Publicar congela la versión. Si hay capturas, editar abre v2. Publicar v2 el 15 de marzo: marzo **cerrado** en v1 (ni alta ni reemplazo); abril es el primer mes de v2. |
| 12 | Captura múltiple | Varios usuarios, mismo indicador, mismo mes: una fila por persona. Unicidad `(version_id, user_id, year, month)`. |
| 13 | Reemplazo | El mismo usuario, mismo mes, segunda vez: modal de confirmación. Sin `replace_confirmed` → 422. Auditoría del reemplazo. |
| 14 | Consolidado | Como Operaciones: **suma cada campo** de las capturas del mes y **recalcula la fórmula**. Filtro Todos / un capturador. No promedio de %. |
| 15 | Ficha visual | Clonar FT-OP-01: cabecera, campos, tarjetas (resultado/semáforo/cumple/mejora), hoja (nombre, objetivo, proceso, meta, crítico, fórmula, ene–dic por campo, gráfico Apex, análisis mensual). Una fila de historial por campo definido. |
| 16 | Proceso | Catálogo `quality-documents.processes`. Sin pestaña de catálogos propia. |
| 17 | Periodo V1 | Año + mes de calendario. Sin Ajustes de periodos tipo Operaciones. |
| 18 | Frecuencia V1 | Mensual. |
| 19 | Textos | MAYÚSCULAS al persistir (`UppercasesValidatedText` / `UppercaseFormText`). |
| 20 | Tablas | Propias. **No** reutilizar `indicators` / `indicator_captures` de Operaciones. |
| 21 | Fuera de V1 | DOFA, objetivos, asignar por área, suplencia, export Excel/PDF de ficha (opcional T3 si cabe; no bloquea), PPTX, funciones de fórmula avanzadas. |
| 22 | Semilla de roles | `super-admin` catálogo completo. `administrador` recibe el paquete del tablero (edit indicadores + dashboard + captura). No hay permiso legado que migrar. |

## Permisos

`edit` implica `view` en `PlaneacionEstrategicaAccessService`. Dashboard **no** se deriva de `indicadores.view`.

| Clave | Label Admin | Uso |
| --- | --- | --- |
| `view.board.calidad.planeacion_estrategica` | Planeación estratégica | Sidebar |
| `planeacion.dashboard.view` | Planeación: Dashboard | Pestaña Dashboard (sin edit) |
| `planeacion.indicadores.view` | Planeación: Ver indicadores | Listar/consultar; sin `edit` = solo indicadores con `can_consult` |
| `planeacion.indicadores.edit` | Planeación: Editar indicadores | CRUD, versionar, publicar, compartir, archivar |
| `planeacion.captura.view` | Planeación: Ver captura | Pestaña Captura |
| `planeacion.captura.edit` | Planeación: Capturar | Guardar/reemplazar captura **propia** |

Auto-otorgamiento al asignar:

| Rol en el indicador | Permisos mínimos |
| --- | --- |
| Solo Captura | tablero + `captura.view` + `captura.edit` |
| Captura + Consulta | lo anterior + `indicadores.view` + `dashboard.view` |

Al quitar el último indicador asignado, revocar esos mínimos si no quedan otros.

## Datos

- `quality_planning_indicators` — `code` unique, `created_by`, `archived_at`, `current_published_version_id`
- `quality_planning_indicator_versions` — ficha + `formula` + `fields` JSON + estado `draft`/`published`/`superseded`
- `quality_planning_indicator_assignees` — `can_capture`, `can_consult` a nivel indicador
- `quality_planning_captures` — `input_data` JSON, `result_value`, `complies`, análisis, mejora

Auditoría: `module=planeacion_estrategica`, `area=calidad`.

## UI

Referencia de listado: Cursos / FO-GI-22. Referencia de ficha: `resources/views/areas/operaciones/indicadores/capture-form.blade.php` (FT-OP-01). Referencia de dashboard: GH área. Chrome: `.module-tab`, `<x-searchable-select>`, icon-btn canónicos.
