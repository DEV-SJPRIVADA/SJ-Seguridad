# Auditoria del sistema — Guia de usuario

> Documentacion operativa para super-administradores.

## Objetivo

Permitir al super-administrador consultar el registro central de acciones realizadas en la plataforma — usuarios, requisiciones, comercial, suministros, compras, documentos de calidad, ficha de empleados, configuracion de notificaciones e indicadores — filtrado por modulo, area, usuario y fechas, para soporte e investigacion de incidentes.

Al abrir la pantalla sin filtros previos, el sistema muestra automaticamente los **ultimos 30 dias** para evitar listados demasiado largos.

## Alcance

### Incluye (v1 + fase 2)

- Consulta del registro central compartido por varios modulos.
- Eventos de **Administracion**: altas y cambios de usuarios, activacion/inactivacion, cambios de rol y permisos, restablecimiento de contrasena por un administrador, y correos agregados o quitados en tipos de notificacion.
- Eventos de **Requisiciones** (resumen): creacion (incluso en lote), cambio de estado, aprobacion o rechazo de gerencia, y exportaciones a Excel.
- Eventos de **Comercial**: altas y cambios de clientes y servicios, activacion/inactivacion de servicios, actualizacion de checklist documental, parametros del modulo, importacion de matriz y exportaciones Excel.
- Eventos de **Suministros**: creacion de solicitudes, aprobacion o rechazo de calidad, altas y cambios en el catalogo de productos, y exportaciones Excel (solicitudes y catalogo).
- Eventos de **Compras**: creacion y reenvio de solicitudes, aprobacion o rechazo del director (web o correo), cambios de estado en bandeja de procesamiento, y exportaciones de suministros en compras.
- Eventos de **Documentos calidad**: publicacion, edicion, activacion/inactivacion y eliminacion de documentos, y exportaciones Excel (administracion, biblioteca y mis documentos).
- Eventos de **Ficha empleados**: promocion desde lista de espera, alta manual, cambios de perfil laboral, cambio de estado de vinculacion, importacion masiva y exportaciones Excel.
- Eventos de **Indicadores** (Operaciones): capturas, periodos, exportaciones y demas acciones ya registradas; mezclados en el listado global.
- Filtros combinables, rango de fechas y paginacion de resultados.
- Acceso restringido al permiso **Ver auditoria global del sistema**, asignado al rol super-administrador.

### No incluye

- El **detalle campo a campo** al editar una requisicion (nombre, cargo, fechas, etc.). Ese historial sigue en la pantalla **Editar requisicion** del modulo Requisiciones.
- Consultas al **archivo de empleados** (Gestion Humana) y plantillas de archivo en ficha empleados.
- Catalogos de parametros de ficha empleados (fuera del registro central).
- Inicio o cierre de sesion, cambio de contrasena hecho por el propio usuario, ni bloqueos por usuario inactivo.
- Descarga o apertura de archivos de documentos de calidad (solo se auditan cambios de metadatos y exportaciones).
- Exportacion del listado de auditoria a Excel.
- Eventos anteriores al despliegue de esta funcionalidad (no hay migracion retroactiva de historiales antiguos).

## Definiciones

| Termino | Significado |
| --- | --- |
| Auditoria central | Registro unico compartido (`audit_logs`) donde convergen eventos de varios modulos |
| Modulo | Origen funcional del evento en el filtro **Modulo**: Indicadores, Administracion, Requisiciones, Comercial, Suministros, Compras, Documentos calidad, Ficha empleados |
| Area | Departamento asociado cuando aplica (por ejemplo Operaciones, Comercial, Compras, Calidad o Gestion Humana). En Suministros puede variar segun el area solicitante |
| Evento / Accion | Clasificacion del registro (por ejemplo creacion de usuario, cambio de estado de requisicion) |
| Consultas informativas | Vistas de tablero o consolidado de Indicadores; ocultas por defecto para reducir ruido |
| Resumen vs detalle | La auditoria central guarda hitos importantes; el detalle fino de requisiciones permanece en su propia pantalla de edicion |
| Ultimos 30 dias | Rango de fechas que se aplica solo al abrir la pantalla; puede ampliarse con los filtros Desde/Hasta |

## Responsabilidades

| Rol / perfil | Responsabilidad en este modulo |
| --- | --- |
| Super-administrador | Revisar eventos sensibles, investigar incidentes, validar exportaciones y cambios de configuracion; usar filtros acotados |
| Soporte tecnico | Guiar al super-admin en el uso de fechas y filtros de usuario para trazabilidad |
| Otros roles | Sin acceso a esta pantalla |

Nota: aunque una cuenta sea super-administrador en otros modulos, necesita tener asignado explicitamente el permiso de auditoria global para entrar a esta pantalla.

## Desarrollo

### Acceder a la auditoria global

1. Inicie sesion con una cuenta **super-administrador** que tenga el permiso de auditoria global.
2. En el menu lateral **Administracion**, seleccione **Auditoria del sistema**.
3. Verá el listado de los **ultimos 30 dias** con las fechas Desde y Hasta ya completadas.

### Modulos visibles en Administracion → Auditoria del sistema

El filtro **Modulo** y la columna **Modulo** del listado muestran las etiquetas siguientes (segun `config/audit.php`):

| Etiqueta en pantalla | Que registra (resumen) |
| --- | --- |
| Indicadores | Capturas, periodos, metas, exportaciones Operaciones |
| Administracion | Usuarios, roles, permisos, notificaciones por correo |
| Requisiciones | Creacion, estados, aprobacion gerencia, exports Excel |
| Comercial | Clientes, servicios, checklist, parametros, import/export matriz |
| Suministros | Solicitudes por area, aprobacion calidad, catalogo productos |
| Compras | Solicitudes compra, aprobacion director, bandeja procesamiento |
| Documentos calidad | CRUD documentos, activacion/inactivacion, exports biblioteca |
| Ficha empleados | Alta/promocion, perfil laboral, import/export masivos |

**Nota:** Indicadores tambien tiene lectura dedicada en **Operaciones → Ajustes → Auditoria** (solo eventos de ese modulo). El resto de modulos se consulta unicamente desde esta pantalla global.

### Filtrar resultados

1. Use la barra de filtros en la parte superior del listado.
2. Seleccione **Modulo** para acotar por Indicadores, Administracion, Requisiciones, Comercial, Suministros, Compras, Documentos calidad o Ficha empleados.
3. Seleccione **Area**, **Evento**, **Accion** o **Usuario** segun necesite.
4. Ajuste **Desde** y **Hasta** si necesita un periodo distinto a los 30 dias iniciales.
5. Marque **Info** solo si desea ver consultas informativas (vistas de tablero de Indicadores).
6. Pulse **Filtrar**. Use **Limpiar** para volver al estado inicial (ultimos 30 dias, sin otros filtros).

Las listas desplegables muestran valores detectados en los ultimos 90 dias.

### Interpretar columnas

| Columna | Significado |
| --- | --- |
| Fecha | Momento en que se registro el evento |
| Usuario | Persona que realizo la accion; «Sistema» si no hubo sesion activa |
| Modulo / Area | Contexto del evento |
| Evento / Accion | Clasificacion del registro |
| Entidad | Registro de negocio afectado, cuando aplica |
| Motivo | Texto descriptivo dejado por la aplicacion |

### Donde buscar segun el caso

| Necesidad | Donde consultar |
| --- | --- |
| Quien creo o modifico un usuario, cambio permisos o restablecio contrasena | Auditoria del sistema — modulo Administracion |
| Quien agrego o quito un correo en notificaciones | Auditoria del sistema — modulo Administracion |
| Creacion de requisicion, cambio de estado, decision de gerencia o export Excel | Auditoria del sistema — modulo Requisiciones |
| Que campos exactos cambiaron en una requisicion | Pantalla **Editar requisicion** (historial propio del modulo) |
| Altas o cambios de clientes, servicios, checklist o parametros comerciales | Auditoria del sistema — modulo Comercial |
| Importacion de matriz comercial o exportaciones Excel comerciales | Auditoria del sistema — modulo Comercial |
| Solicitud de suministro, decision de calidad o cambios en catalogo | Auditoria del sistema — modulo Suministros |
| Solicitud de compra, aprobacion director o procesamiento en bandeja | Auditoria del sistema — modulo Compras |
| Publicacion, edicion o retiro de documento de calidad | Auditoria del sistema — modulo Documentos calidad |
| Promocion desde lista de espera, alta manual o cambio de estado laboral | Auditoria del sistema — modulo Ficha empleados |
| Import o export masivo de fichas de empleados | Auditoria del sistema — modulo Ficha empleados |
| Capturas y exportaciones de Indicadores | Auditoria del sistema (global) o **Operaciones → Ajustes → Auditoria** (solo Indicadores) |
| Consultas al archivo de empleados | Modulo Archivo GH (no aparece en auditoria central) |

### Buenas practicas

- Parta del rango de 30 dias; amplie solo si la investigacion lo requiere.
- Combine filtro de **Usuario** y **Fechas** para incidentes concretos.
- No comparta capturas con datos personales fuera de los canales autorizados.
- Recuerde que un restablecimiento de contrasena por administrador queda registrado, pero **no** se guarda la contrasena en el log.
- Ante lentitud reportada por soporte tecnico, indique el rango de fechas y filtros usados.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-08-03 | FEAT-021 | Version inicial — pantalla global super-admin |
| 1.1 | 2026-08-03 | FEAT-021 | Filtros compactos en barra unica |
| 1.2 | 2026-08-11 | FEAT-025 | Modulos v1 (Administracion, Requisiciones resumen, Indicadores mezclados); default 30 dias al abrir; limites vs historial detallado requisiciones y archivo GH; permiso explicito requerido |
| 1.3 | 2026-08-12 | FEAT-026 | Fase 2: Comercial, Suministros, Compras, Documentos calidad, Ficha empleados visibles en filtro Modulo; guia de consulta ampliada; limites vs archivo GH y descarga de archivos calidad |
