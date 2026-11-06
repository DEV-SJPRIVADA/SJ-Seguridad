# Servicio al cliente (FO-GI-22)

Tablero de Calidad para el formato FO-GI-22: casos de petición, queja, reclamo y sugerencia, con seguimiento, evidencia y dashboard.

## Rutas

Prefijo `calidad/servicio-cliente`, nombre `calidad.servicio-cliente.*`, middleware `auth`, `active`, `password.changed`.

| Pestaña | Permiso | Ruta |
| --- | --- | --- |
| Dashboard | `servicio_cliente.fo_gi_22.view` (o `.edit`) | `calidad.servicio-cliente.dashboard` |
| FO-GI-22 | el mismo | `calidad.servicio-cliente.fo-gi-22.index` |

El listado incluye el historial de cambios (`fo-gi-22.historial`) para quien puede ver la pestaña. No usa un permiso nuevo.
| Catálogos | `servicio_cliente.parameters.edit` | `calidad.servicio-cliente.catalogos` |

La pantalla de catálogos reutiliza el tablero de tarjetas de Parámetros de requisiciones. Al abrir un catálogo, el panel completo (alta y tabla) usa el ancho centrado de la bandeja de Compras: el contenido principal en portátil y 1440px en pantallas mayores. La tabla muestra **id** y **nombre** (el texto del selector), más estado y acciones. Código y orden no se editan en la UI; el código se genera al crear y se conserva al editar.

El alta y la edición de FO-GI-22 reutilizan el esquema de Solicitar personal: secciones numeradas, franja de datos y guía lateral. El contenido del formulario mide 1440px en portátil y en pantallas mayores. Las pestañas del tablero no cambian de ancho.

El tablero en el menú usa `view.board.calidad.servicio_cliente`. `manage.users` hace bypass. La clave de tablero solo existe en el área `calidad`.

## Datos

| Tabla | Uso |
| --- | --- |
| `servicio_cliente_tipologias` | Semilla: Petición, Queja, Reclamo, Sugerencia |
| `servicio_cliente_fuentes` | Semilla: Correo electrónico, Encuesta, Página web, Telefónica, WhatsApp |
| `servicio_cliente_responsables` | Vacío al instalar |
| `servicio_cliente_categorias` | Vacío al instalar |
| `servicio_cliente_casos` | Caso. Consecutivo global. Ciudad = `requisition_cities`. Cliente externo = `commercial_clients` |
| `servicio_cliente_caso_observaciones` | Historial de seguimiento. Alta, edición y eliminación quedan en el log. |
| `servicio_cliente_caso_adjuntos` | PDF, JPG y PNG en disco `local` |

## Reglas de cálculo

- **Fecha de ejecución** = fecha de recepción. No es editable.
- **Plazo** = 10 días hábiles posteriores a la recepción (ese día no cuenta). Lunes a viernes, sin festivos.
- **Días** = días hábiles inclusivos desde la recepción hasta la verificación (`DIAS.LAB.INTL`, fin de semana sáb-dom). Sin verificación, o si la verificación es anterior, queda vacío.
- **Cliente interno:** nombre fijo `SJSEGURIDAD`.
- **Cerrado:** exige al menos un adjunto. No se borra el último archivo de un caso cerrado.
- **Borrado del caso:** definitivo, con modal de aceptación y snapshot en auditoría (`module` `servicio_cliente`).
- Textos libres en mayúsculas (`UppercaseFormText`).

## UI

- Listado DataTables `serverSide`. Filtros de tipo, tipología, fuente, responsable y rangos de recepción, verificación y plazo. Eficacia en botones.
- Export `BaseExport`: hoja Casos y hoja Observaciones, con los filtros activos. Tope `servicio_cliente.export.max_rows` (10000).
- Dashboard con el chrome de Gestión Humana (`gh-area-dashboard-page`): filtros en panel, seis KPI en una fila y gráficos en filas de 3 y 2. JS: `resources/js/servicio-cliente-dashboard-charts.js`.
- Los KPI Abiertos, En proceso, Cerrados y Vencidos abren FO-GI-22 con ese recorte y conservan tipo, tipología, fuente, responsable y rangos de fecha del dashboard. Vencido es plazo ya cumplido y caso no cerrado (`vencidos=1`).
- Recurrencia: clientes con 2 o más casos, agrupados por mes, trimestre, semestre o año.

## Roles

`super-admin` recibe el catálogo completo vía `app:sync-permissions`. El seeder también entrega el paquete al rol `administrador`. No hay permiso anterior que migrar.

## Pruebas

`tests/Unit/Calidad/ServicioClienteBusinessDaysServiceTest.php`  
`tests/Feature/Calidad/ServicioClienteBoardAccessTest.php`  
`tests/Feature/Calidad/ServicioClienteFoGi22Test.php`  
`tests/Feature/Calidad/ServicioClienteDashboardTest.php`
