# Feature Brief — FEAT-033

> Solicitudes de desarrollo TIC (StatFlow). Fuente: plan aprobado `flujo_tic_desarrollos` + PR-TIC-10 / FO-TIC-23 / PR-TIC-09.

## Objetivo

Canal oficial en StatFlow para solicitar, priorizar y seguir desarrollos internos: formulario FO-TIC-23 digital, aprobación de líder, bandeja TIC con estados del procedimiento, hilo tipo chat con correo, dashboard KPIs básicos.

## Modulo / home

- Modulo compartido `development-requests`
- Area home: `tic` (normalizar slug `Tic` → `tic` en `config/access.php`)
- Board: `solicitudes_desarrollo`

## Actores

| Actor | Puede |
| --- | --- |
| Solicitante (directores y usuarios con permiso create) | Crear/editar borrador, radicar, ver propias, chat, UAT |
| Líder / director | Aprobar/rechazar pendientes del área; chat |
| Analista / Programador TIC | Bandeja, estados, bloque TIC, chat, cierre |
| Consulta | Ver según permisos tab |

## Estados

`borrador` → `pendiente_aprobacion_lider` → `radicado` → `en_analisis` → `en_desarrollo` → `en_pruebas` → `entregado` → `cerrado`  
Alternos: `devuelto`, `rechazado` (TIC o líder). Director-líder puede pasar borrador → `radicado` en un paso.

## Campos FO-TIC-23 (V1)

Ver plan: identificación, descripción, proceso/problema, paso a paso, usuarios, permisos JSON, restricciones, alcance, criterio aceptación, reportes, prioridad, anexos, bloque TIC (viabilidad, prioridad confirmada, complejidad, FO-GE-12 flag, análisis extendido, asignado, fecha estimada, riesgos, UAT, cierre).

## Chat

Tabla `development_request_messages`; UI en detalle; correo a participantes (menos autor) vía Mailable + NotificationConfigService; hilo cerrado en `cerrado`/`rechazado`.

## Permisos (tabs)

`devreq.tab.create`, `my_requests`, `leader_approval`, `tic_queue`, `view` + `view.board.{area}.solicitudes_desarrollo`.

## Espejo técnico

Purchase-requests (tabs, comments, notifications, BaseExport).

## Fuera de V1

FO-GE-12/FO-GI-38 como módulos; WebSockets; import Word automático; Git/CI.

## shared-files

`config/access.php`, `routes/web.php`, nav, User board helpers, audit/notification config.
