# Feature Brief — FEAT-051

> Brief del maestro (2026-11-11). Decisiones del usuario cerradas en el chat. No reabrir.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-051 |
| Modulo / area | Calidad — tablero **Planeación estratégica** (`planeacion_estrategica`) |
| Titulo | DOFA, objetivos y estrategias encadenados |
| Solicitante | Usuario / AgentSj |
| Fecha | 2026-11-11 |
| Triaje | C — Full (módulo nuevo, permisos nuevos, varios cortes de negocio) |

## Objetivo

Que Calidad construya su planeación estratégica en un solo hilo: un **DOFA** con sus cuatro cuadrantes, los **objetivos** que responden a ese análisis y las **estrategias** que ejecutan cada objetivo. Es un módulo nuevo dentro del tablero existente, no una extensión de indicadores/captura de la V1 (FEAT-050).

## Decisiones de negocio (ley)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Ubicacion | Pestaña **Estrategia** dentro del tablero **Planeación estratégica** (Dashboard, Indicadores, Captura, Estrategia). No es un tablero nuevo del sidebar. |
| 2 | Alcance V1 | DOFA (4 cuadrantes) + objetivos + estrategias encadenados. **Sin** enlace a indicadores en esta fase. |
| 3 | Permisos | Una sola pareja para toda la pestaña: `planeacion.estrategia.view` / `planeacion.estrategia.edit`. `edit` implica `view`. |
| 4 | Periodo | **Libre**, lo define el usuario: año, trimestre, varios años, etc. Se modela como `starts_on` / `ends_on` (fechas) + etiqueta. No auto-generar periodos. |
| 5 | Cardinalidad | Varios DOFA a la vez (uno por periodo). Cada DOFA tiene N objetivos; cada objetivo, N estrategias. |
| 6 | Encadenamiento | La estrategia siempre pertenece a un objetivo y hereda su DOFA. No hay estrategias sueltas. |
| 7 | Captura de texto | Libre (fortalezas, oportunidades, debilidades, amenazas, objetivo, estrategia, meta/indicador de logro). MAYÚSCULAS en BD. |
| 8 | Estados | DOFA `borrador` / `vigente` / `archivado`. Solo un `vigente` por rango solapado se avisa, no se bloquea. |
| 9 | UI | Primera entrega ya pulida: shell del tablero (`.module-tab`, panel, icon-btns canónicos). No prototipo. |
| 10 | Auditoria | Cada alta/edición/archivo de DOFA, objetivo y estrategia pasa por `PlaneacionAuditLogService`. |
| 11 | Fuera de V1 | Enlazar objetivos/estrategias a indicadores, matriz de priorización con pesos, PPTX, import masivo. |
| 12 | Responsable | Calidad crea y edita. Quien solo tiene `view` consulta y exporta. |

## Permisos

| Clave | Label Admin | Uso |
| --- | --- | --- |
| `planeacion.estrategia.view` | Planeación: Ver estrategia | Pestaña Estrategia, consulta del DOFA/objetivos/estrategias |
| `planeacion.estrategia.edit` | Planeación: Editar estrategia | Crear, editar y archivar DOFA, objetivos y estrategias |

- `edit` implica `view` en `PlaneacionEstrategicaAccessService`.
- El rol `administrador` recibe la pareja en el seeder; `super-admin` la obtiene del catálogo.
- No se otorga automáticamente a capturadores.

## Modelo de datos (propuesto)

- `quality_planning_dofas`: `title`, `period_label`, `starts_on`, `ends_on`, `status`, `summary`, `created_by_user_id`, timestamps.
- `quality_planning_dofa_items`: `dofa_id`, `quadrant` (`fortaleza|oportunidad|debilidad|amenaza`), `description`, `sort`, timestamps.
- `quality_planning_objectives`: `dofa_id`, `title`, `description`, `starts_on`, `ends_on`, `sort`, timestamps.
- `quality_planning_strategies`: `objective_id`, `title`, `description`, `responsible`, `starts_on`, `ends_on`, `sort`, timestamps.

## Criterios de aceptación

1. La pestaña Estrategia solo aparece con `planeacion.estrategia.view` o `edit`.
2. Sin `edit`, la pantalla es de consulta: no muestra botones de alta/edición/archivo.
3. Un DOFA se crea con título, etiqueta de periodo y rango de fechas libres; acepta periodos de un mes, un trimestre o varios años.
4. Cada cuadrante (F/O/D/A) admite varias líneas y se puede ordenar.
5. Los objetivos se crean dentro de un DOFA; las estrategias dentro de un objetivo.
6. Archivar un DOFA no borra objetivos ni estrategias, y lo saca del listado vigente.
7. Todo texto libre se persiste en MAYÚSCULAS.
8. Auditoría registrada en alta/edición/archivo.
9. Tests Feature: acceso por permiso, CRUD mínimo de las tres entidades y persistencia en mayúsculas.
