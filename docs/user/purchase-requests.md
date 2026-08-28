# Solicitudes de compra — Guia de usuario

## Objetivo

Permitir a colaboradores solicitar compras de bienes o servicios, que un director autorice la solicitud, y que el area de Compras procese el pedido junto con los insumos que Calidad ya haya aprobado.

## Alcance

Aplica al tablero **Solicitudes de compra** y a la **Bandeja compras** (area Compras).

Segun su perfil puede:

- Crear solicitudes con una o varias lineas de productos (con foto opcional por linea)
- Adjuntar, si lo desea, varios documentos de soporte a **toda** la solicitud (cotizacion, orden, evidencia)
- Consultar el estado de sus propias solicitudes y descargar esos adjuntos desde el detalle
- Autorizar o rechazar solicitudes asignadas a usted como director
- Procesar solicitudes aprobadas e insumos aprobados desde la bandeja unificada de Compras
- Descargar el formato FO-AD-44 (PDF y Excel) desde el detalle

El director puede autorizar desde el **correo** (enlace personal firmado, sin login) o desde la **plataforma** (Pendientes de autorizacion o detalle). Los documentos de soporte **no** viajan en el correo ni en el FO-AD-44: para verlos hay que entrar al detalle en la plataforma.

## Definiciones

| Termino | Significado |
| --- | --- |
| Solicitud de compra | Pedido formal de bienes o servicios con lineas de detalle (cantidad, descripcion, referencia, etc.). |
| Folio / N.º solicitud | Numero correlativo de cuatro digitos (ej. 0042) que identifica la solicitud. |
| Director aprobador | Usuario con rol de director seleccionado al crear la solicitud; unico que puede autorizarla. |
| Pendiente (director) | Esperando decision del director asignado. |
| Aprobado / Rechazado (director) | Decision del director; si aprueba, pasa a Compras. |
| Bandeja compras | Listado unificado de solicitudes de compra aprobadas e insumos aprobados por Calidad. |
| Estado Compras | Pendiente, En curso, Completado o Rechazado — lo gestiona el equipo de Compras. |
| Solicitud Interno | Compra para uso interno de la empresa. |
| Solicitud Cliente | Compra asociada a un cliente (requiere razon social y datos adicionales). |
| Urgente | Marca de prioridad visible para director y Compras. |
| FO-AD-44 | Formato estandar de reporte/exportacion de la solicitud. No incluye los documentos de soporte adjuntos. |
| Adjuntos | Archivos opcionales de la solicitud completa (no de cada producto). Se consultan y descargan solo en el detalle de la plataforma. |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Colaborador solicitante | Crear solicitudes en su area, elegir director, adjuntar documentos de soporte si aplica, consultar mis solicitudes. Si una solicitud es rechazada, puede reenviarla: conservar, agregar o quitar adjuntos. |
| Director | Revisar y aprobar o rechazar solo las solicitudes asignadas a el. Los adjuntos se revisan en el detalle de la plataforma (no llegan por correo). |
| Compras | Gestionar bandeja, actualizar estados, procesar insumos aprobados, exportar reportes y descargar adjuntos desde el detalle. |
| Super-admin / Admin usuarios | Asignar permisos (`purchase.tab.*`) y rol director. |

## Donde encontrar el modulo en el menu

| Perfil | Ruta en el menu |
| --- | --- |
| Colaborador solicitante | Su area → Solicitudes de compra |
| Director | Compras → Solicitudes de compra → Pendientes |
| Equipo Compras | Compras → Solicitudes de compra → Bandeja compras (landing por defecto); Dashboard Compras en tablero del area |

## Desarrollo

### Crear una solicitud de compra

1. Entre a **Solicitudes de compra** en su area → pestaña **Nueva solicitud**.
2. Complete **Area**, **Fecha**, **Solicitud para** (Interno o Cliente) y seleccione el **Director aprobador**.
3. Marque **Urgente** si aplica.
4. Si eligio **Cliente**, complete razon social, proyecto nuevo y si asume el cliente.
5. Agregue una o mas lineas de producto: cantidad, descripcion, referencia, utilizacion, ubicacion y foto opcional de esa linea.
6. En **Adjuntos** (despues de los productos) puede, si lo desea, seleccionar **varios archivos a la vez** para toda la solicitud: cotizacion, orden de compra, evidencia u otro soporte. Es opcional: puede enviar la solicitud sin ningun archivo. Hasta 5 archivos, 10 MB cada uno. Tipos permitidos: PDF, Word, Excel, PowerPoint, JPG, PNG y WEBP.
7. Envie el formulario. Recibira confirmacion con el numero de folio.
8. El director recibira un correo con el PDF FO-AD-44 y un enlace para **autorizar por correo** o ingresar a la plataforma. Ese correo **no** incluye los archivos de Adjuntos; el director los ve al abrir el detalle en la plataforma.

### Consultar mis solicitudes

1. Abra **Mis solicitudes**.
2. Revise el listado con estado (Pendiente, Aprobado, Rechazado).
3. Pulse el folio o **Ver detalle** para consultar lineas, comentarios del director o de Compras. Si hay documentos de soporte, aparecen en **Adjuntos** con su nombre y tamano; pulse **Descargar** para guardarlos.
4. Desde el detalle puede descargar **PDF** o **Excel** (FO-AD-44). Esos formatos no llevan los adjuntos.

### Reenviar una solicitud rechazada

1. Desde **Mis solicitudes**, abra la solicitud rechazada y entre a reenviar.
2. Revise o corrija las lineas de producto (la foto por linea sigue siendo opcional).
3. En **Adjuntos** puede **conservar** los archivos actuales, **quitar** alguno o **agregar** otros (el total no puede pasar de 5). Tambien puede dejar la solicitud sin adjuntos.
4. Envie de nuevo al director.

### Autorizar o rechazar (director)

**Opcion A — Por correo (sin login)**

1. Abra el correo de nueva solicitud de compra.
2. Pulse **Autorizar por correo** (enlace personal con vigencia limitada).
3. Revise folio, lineas y datos del solicitante; opcionalmente abra el PDF. Esta pantalla **no** muestra los documentos de Adjuntos; para verlos use **Ver en la plataforma**.
4. Escriba comentarios si rechaza (obligatorio al rechazar).
5. Pulse **Aprobar solicitud** o **Rechazar**.
6. Confirme en la pantalla de resultado; el solicitante recibe correo con el outcome.

**Opcion B — Por plataforma**

1. Ingrese a **Compras → Solicitudes de compra → Pendientes de autorizacion**.
2. Pulse **Autorizar** en la fila deseada (o abra el detalle desde el correo con **Ver en la plataforma**).
3. Revise el detalle, lineas, datos del solicitante y, si existen, los **Adjuntos** (puede descargarlos).
4. Opcionalmente escriba comentarios (obligatorios si rechaza).
5. Pulse **Aprobar solicitud** o **Rechazar**.
6. La solicitud sale del listado de pendientes; el solicitante recibe correo con el resultado.

### Procesar en bandeja Compras

1. Entre a **Compras → Solicitudes de compra → Bandeja compras**.
2. Use **Filtros**: rango de fechas, area solicitante, tipo (Solicitud compra / Suministro) y pills de estado (Pendiente, En curso, Completado, Rechazado). Al elegir area o tipo, el listado se actualiza solo.
3. El listado sale **mas reciente primero**. Sin filtro de fechas muestra hasta 200 registros recientes; con fechas ve el historico completo del periodo. Si no ve un suministro, revise que Tipo no este en "Solicitud compra" y que no haya un rango de fechas que lo excluya.
4. Pulse **Ver detalle** para consultar la solicitud o el suministro sin salir del flujo de lectura.
5. Para **procesar** una solicitud de compra: abra el flujo de procesamiento y actualice estado y comentarios de Compras.
6. Para **procesar suministros**: ingrese costos unitarios y marque como completado cuando corresponda.
7. El solicitante puede recibir correo cuando Compras actualiza la solicitud.

### Dashboard Compras

1. Entre a **Compras → Dashboard** (tablero Dashboard Compras).
2. Filtre por ano, mes (opcional), area y tipo si necesita acotar el periodo.
3. Revise los indicadores: pendientes del director, en bandeja, bandeja pendiente, en curso y completadas en el periodo.
4. Los numeros de bandeja coinciden con la bandeja cuando usa el mismo mes o rango de fechas.
5. Pulse un indicador de bandeja para abrir la bandeja con esos filtros ya aplicados.
6. Consulte los graficos de tendencia, estados y distribucion por area o tipo.

### Suministros en la misma bandeja

Los pedidos de insumos que Calidad aprobo (y los ya en compras o completados) aparecen junto con las solicitudes de compra. Desde **Ver detalle** puede consultar el pedido con el mismo formato que una solicitud de compra y descargar **PDF** o **Excel** (FO-AD-44).

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-31 | Modulo inicial | Flujo solicitante → director → Compras; FO-AD-44 |
| 1.1 | 2026-07-31 | Autorizacion in-app | Director autoriza en plataforma; correo solo notifica |
| 1.2 | 2026-08-03 | Navegacion canonica | Tabla de entrada al menu por perfil; guia ampliada |
| 1.3 | 2026-08-03 | Bandeja y dashboard | Filtros bandeja, dashboard Compras, ver detalle suministro unificado, KPIs alineados |
| 1.5 | 2026-08-20 | Analista TIC | Sin descripcion general ni justificacion de cabecera; el detalle va en las lineas de producto |
| 1.4 | 2026-08-03 | Autorizacion por correo | Director puede aprobar/rechazar via enlace firmado sin login; flujo dual correo + plataforma |
| 1.6 | 2026-08-28 | Bandeja | Listado por fecha mas reciente; filtro tipo/area se aplica al elegir |
| 1.7 | 2026-08-28 | FEAT-030 | Adjuntos opcionales multiples por solicitud (detalle de la plataforma). El correo de autorizacion y el FO-AD-44 no los incluyen. Al reenviar una rechazada se pueden conservar, agregar o quitar |
