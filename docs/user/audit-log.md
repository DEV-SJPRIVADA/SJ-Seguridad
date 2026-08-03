# Auditoria del sistema — Guia de usuario

> Documentacion operativa para super-administradores.

## Objetivo

Permitir al super-administrador consultar el registro central de acciones realizadas en la plataforma, filtrado por modulo, area, usuario y fechas, para soporte e investigacion de incidentes.

## Alcance

- Consulta de eventos registrados en la tabla central de auditoria.
- Filtros combinables y paginacion de resultados.
- No incluye el detalle campo a campo de requisiciones (historial propio del modulo Requisiciones).
- Visible solo para quien tenga el permiso **Ver auditoria global del sistema**.

## Definiciones

| Termino | Significado |
| --- | --- |
| Auditoria central | Registro unico compartido por modulos (`audit_logs`) |
| Modulo | Origen funcional del evento (ej. Indicadores, Administracion) |
| Area | Departamento asociado cuando aplica (ej. Operaciones) |
| Evento / Accion | Tipo y accion concreta registrada por la aplicacion |
| Consultas informativas | Vistas de tablero o consolidado (ruido operativo; ocultas por defecto) |

## Responsabilidades

| Rol / perfil | Responsabilidad en este modulo |
| --- | --- |
| Super-admin | Revisar eventos sensibles, investigar incidentes, validar exportaciones y cambios de configuracion |
| Soporte tecnico | Usar filtros de fecha y usuario para trazabilidad |
| Otros roles | Sin acceso a esta pantalla |

## Desarrollo

### Acceder a la auditoria global

1. Inicie sesion con una cuenta **super-admin**.
2. En el menu lateral **Administracion**, seleccione **Auditoria del sistema**.
3. Se abrira la lista paginada de eventos recientes.

### Filtrar resultados

1. Use la barra de filtros en la parte superior del listado.
2. Seleccione **Modulo**, **Area**, **Evento**, **Accion** o **Usuario** segun necesite acotar.
3. Indique **Desde** y **Hasta** para un rango de fechas.
4. Marque **Info** solo si desea ver consultas informativas (vistas de dashboard).
5. Pulse **Filtrar**. Use **Limpiar** para restablecer todos los filtros.

Las listas desplegables muestran valores detectados en los ultimos 90 dias.

### Interpretar columnas

| Columna | Significado |
| --- | --- |
| Fecha | Momento en que se registro el evento |
| Usuario | Persona que realizo la accion; «Sistema» si no hubo sesion |
| Modulo / Area | Contexto del evento |
| Evento / Accion | Clasificacion del registro |
| Entidad | Registro de negocio afectado, cuando aplica |
| Motivo | Texto descriptivo dejado por la aplicacion |

### Buenas practicas

- Acote siempre por fechas en revisiones extensas.
- No comparta capturas con datos personales fuera de los canales autorizados.
- Ante lentitud reportada por soporte tecnico, indique el rango de fechas y filtros usados.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-08-03 | FEAT-021 | Version inicial — pantalla global super-admin |
| 1.1 | 2026-08-03 | FEAT-021 | Filtros compactos en barra unica |
