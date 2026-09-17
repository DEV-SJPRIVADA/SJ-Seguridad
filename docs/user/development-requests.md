# Solicitudes de desarrollo — Guia de usuario

> Documentacion operativa. Ubicacion: `docs/user/development-requests.md`.

## Objetivo

Pedir y hacer seguimiento a desarrollos internos (mejoras, correcciones, reportes, etc.) con el formato FO-TIC-23, aprobacion del lider, atencion de TIC y conversacion por mensajes con aviso por correo.

## Alcance

Tablero **Solicitudes desarrollo**:

- En el area del solicitante: **Nueva solicitud** y **Mis solicitudes**.
- En **TIC** (hogar del proceso): **Aprobacion lider**, **Bandeja TIC** (indicadores, listado, Excel).
- Detalle de cada solicitud: datos FO-TIC-23, anexos, historial, chat, gestion TIC y UAT segun rol/estado.

## Definiciones

| Termino | Significado |
| --- | --- |
| Radicado | Solicitud con codigo `DEV-AAAA-#####` ya aceptada para trabajo TIC. |
| SLA analisis | Plazo para avanzar el analisis segun prioridad: urgente 2 dias, importante/mejora 5, soporte 3 (desde la radicacion). |
| UAT | Prueba de aceptacion del solicitante cuando la solicitud esta **En pruebas**. Tambien TIC puede forzar Entregado o volver a desarrollo si el solicitante no responde. |
| Chat | Mensajes en el detalle; los demas participantes reciben correo (excepto quien escribe). |

## Responsabilidades

| Perfil | Puede |
| --- | --- |
| Solicitante | Crear/editar borrador o devuelta, enviar a lider, ver propias, chatear, registrar UAT. |
| Lider / director | Aprobar o rechazar pendientes; chatear. |
| TIC | Bandeja, filtros, export Excel, cambiar estados, bloque interno, asignar programador, chatear, cerrar. |

## Desarrollo (uso diario)

### Crear y enviar

1. Abra **Nueva solicitud**, complete el formulario y adjunte evidencias si aplica.
2. Guarde como borrador o envie. Si usted es el lider seleccionado, al enviar puede quedar **radicada** de inmediato.
3. Si no, queda en **Pendiente aprobacion lider** hasta que el lider apruebe.

### Seguimiento

1. En **Mis solicitudes** vea el estado y abra el detalle.
2. Use el chat para aclaraciones; revise el historial de estados.

### Bandeja TIC

1. Revise los KPIs: recibidos, en curso, entregados/cerrados y vencidos de SLA.
2. Filtre por estado, area o prioridad y exporte a Excel con el icono correspondiente.
3. En el detalle: actualice estado, complete el bloque TIC y asigne programador.
4. Cuando este en pruebas, el solicitante registra UAT (acepta → entregado; no acepta → vuelve a desarrollo). Si el solicitante no responde, TIC puede forzar esos mismos cambios desde Gestion TIC.

## Control de cambios

| Ver | Fecha | Cambio |
| --- | --- | --- |
| 1.0 | 2026-09-17 | FEAT-033: FO-TIC-23, lider, bandeja TIC, chat+correo, KPIs/SLA y export. |
