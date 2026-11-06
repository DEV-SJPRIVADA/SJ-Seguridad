# Feature Brief — FEAT-049

> Brief del maestro (2026-11-06). Decisiones numeradas del usuario cerradas en el chat. No reabrir.

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-049 |
| Modulo / area | Calidad — tablero **Servicio al cliente** (`servicio_cliente`) |
| Titulo | Tablero Servicio al cliente (Dashboard + FO-GI-22 + Catálogos) |
| Solicitante | Usuario / AgentSj |
| Fecha | 2026-11-06 |
| Triaje | C — Full |

## Objetivo

Registrar y seguir casos FO-GI-22 (peticiones, quejas, reclamos y sugerencias) dentro de Calidad: CRUD, historial de observaciones, evidencia al cerrar, dashboard y export Excel con los filtros activos.

## Decisiones de negocio (ley)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Ubicación | Tablero nuevo **dentro de Calidad**. Label **Servicio al cliente**. |
| 2 | Pestañas | **Dashboard**, **FO-GI-22**, **Catálogos**. |
| 3 | Dashboard | Lo ve quien tenga `servicio_cliente.fo_gi_22.view` (edit implica view). Sin permiso KPI propio. Estilo del dashboard de área de Gestión Humana. |
| 4 | Catálogos | Pestaña con `servicio_cliente.parameters.edit`: tipología, fuente, responsable y categoría. |
| 5 | Siembra de roles | `super-admin` recibe todo el catálogo. El rol `administrador` también recibe el paquete del tablero (no hay permiso legado que migrar). |
| 6 | Dos responsables | 1) catálogo. 2) texto libre (`responsable_seguimiento`), sin catálogo. |
| 7 | Cliente interno | Nombre fijo **SJSEGURIDAD**. No se elige cliente comercial. |
| 8 | Cliente externo | Select de clientes de Comercial. |
| 9 | Categoría | Catálogo (selector), no texto largo. |
| 10 | Recurrencia | Mismo cliente que repite. Grano: mes, trimestre, semestre y año. |
| 11 | Días | Días hábiles lun–vie, inclusive, desde fecha de recepción hasta fecha de verificación. Sin festivos. Sin verificación, vacío. Si la verificación es anterior a la recepción, vacío. |
| 12 | Fecha de ejecución | Copia de la fecha de recepción. El usuario no la edita. |
| 13 | Plazo de respuesta | 10 días hábiles **posteriores** a la recepción (el día de recepción no cuenta). Sin festivos. |
| 14 | Consecutivo | Autonumérico global 1, 2, 3… Lo asigna el sistema. |
| 15 | Adjuntos | En el caso. PDF, JPG y PNG. Varios por envío, tope como incapacidades. No se puede pasar a **Cerrado** sin al menos un archivo. Se pueden borrar con permiso de edición; queda en el log. No se borra el último archivo de un caso cerrado. |
| 16 | Observaciones | Tabla aparte. El usuario agrega seguimientos. El historial se consulta; no se edita ni se borra en esta versión. |
| 17 | Eficacia | Fijo: Abierto, En proceso, Cerrado. Filtro de botones, no catálogo. |
| 18 | KPI | Total, abiertos, en proceso, cerrados, vencidos (hoy mayor que el plazo y eficacia distinta de Cerrado), promedio de días hábiles. |
| 19 | Gráficos | Cantidad por tipología, por categoría, por eficacia, por fuente y recurrencia. |
| 20 | Borrado del caso | Eliminación real, con modal de aceptación, y snapshot en el log de auditoría. |
| 21 | Export | Excel (`BaseExport`) de los casos filtrados y hoja de observaciones de esos casos. |
| 22 | Fuera de V1 | Import masivo, festivos, alertas de vencimiento, edición del consecutivo. |

## Permisos

| Clave | Uso |
| --- | --- |
| `view.board.calidad.servicio_cliente` | Ver el tablero en Calidad |
| `servicio_cliente.fo_gi_22.view` | Ver FO-GI-22, dashboard, filtrar y exportar |
| `servicio_cliente.fo_gi_22.edit` | Alta, edición, borrado, observaciones y adjuntos (implica view) |
| `servicio_cliente.parameters.edit` | Catálogos |

`manage.users` hace bypass, igual que Cliente interno. El permiso de tablero solo existe en el área `calidad`.

## Datos

- Catálogos: `servicio_cliente_tipologias`, `servicio_cliente_fuentes`, `servicio_cliente_responsables`, `servicio_cliente_categorias`.
- Semilla: tipología (Petición, Queja, Reclamo, Sugerencia) y fuente (Correo electrónico, Encuesta, Página web, Telefónica, WhatsApp). Responsable y categoría quedan vacíos.
- Caso: `servicio_cliente_casos`. Ciudad = `requisition_cities`. Cliente externo = `commercial_clients` + nombre copiado.
- Observaciones: `servicio_cliente_caso_observaciones`.
- Adjuntos: `servicio_cliente_caso_adjuntos` en disco `local`.

Textos libres en mayúsculas al guardar. Auditoría `servicio_cliente` / área `calidad`.
