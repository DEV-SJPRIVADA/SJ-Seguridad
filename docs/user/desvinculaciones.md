# Desvinculaciones — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/desvinculaciones.md`.

## Objetivo

Dar a Gestion Humana un tablero propio para desvincular varios empleados a la vez (con carta Word y descarga en un solo archivo comprimido) y para completar el seguimiento administrativo despues del retiro (lista de chequeo y fecha entregado a nomina), sin depender del archivo Excel legacy.

## Alcance

Aplica al tablero **Desvinculaciones** en el area **Gestion Humana**, con dos pestanas:

- **Masivos** — buscar empleados activos por cedula, indicar fecha de desvinculacion, tipo de carta y firma; opcionalmente causal, si es recontratable y observaciones; ejecutar **Desvincular** sobre el lote; ver el reporte de exitos y fallos; descargar el ZIP de cartas generadas.
- **Seguimientos** — consultar y marcar los ocho controles post-retiro; filtrar por texto, estado o rango de **fecha entregado nomina**; exportar a Excel lo filtrado; ver si ya hay carta generada y si el caso quedo **OK TODO**; guardar cambios al marcar (sin boton Guardar).

**En esta version no hay:** colores o semaforos del Excel antiguo, ni envio de correo al desvincular. La regeneracion de una carta que fallo se hace desde **Ficha empleados** (no desde Seguimientos).

La desvinculacion de **un solo** empleado desde la ficha sigue existiendo y, al confirmarse, tambien crea la fila de seguimiento.

## Definiciones

| Termino | Significado |
| --- | --- |
| Desvinculaciones | Tablero de Gestion Humana para lotes y seguimiento post-retiro. |
| Masivos | Pestana para procesar varias desvinculaciones en una grilla. |
| Seguimientos | Pestana con el checklist despues de desvincular (Masivos o Ficha). |
| FECHA DESVINCULACION | Fecha unica que cierra el vinculo (ultimo dia / fecha de retiro unificados en Masivos). |
| TIPO CARTA | Plantilla Word de desvinculacion elegida por fila (debe existir cargada en Plantillas Word). |
| FIRMA | Firmante del catalogo de firmas, por fila. |
| Tiene carta generada | Indica Si/No si el sistema logro generar y guardar la carta de ese retiro. |
| OK TODO | Indicador automatico: solo pasa a Si cuando los ocho checks estan marcados. |
| Paquete de permisos | Los cuatro permisos del tablero que se asignan juntos en Admin (ver Desarrollo). |

## Responsabilidades

| Rol / perfil | Responsabilidad en este modulo |
| --- | --- |
| Administrador (usuarios) | Asignar el **paquete completo** de permisos de Desvinculaciones al personal de GH que operara Masivos y Seguimientos. |
| Gestion Humana (operador del tablero) | Ejecutar lotes en Masivos, revisar el reporte, descargar el ZIP, completar checks y fecha de nomina en Seguimientos. |
| Gestion Humana (desvinculacion en Ficha) | Quien tiene permiso de desvincular en Ficha puede cerrar un empleado uno a uno y **Generar cartas**; eso tambien alimenta Seguimientos y marca «tiene carta». |
| Administrador de Plantillas Word | Mantener plantillas de tipo Desvinculacion con archivo cargado; sin ellas Masivos no puede armar cartas. |

## Desarrollo

### Asignar el paquete de permisos (Administrador)

1. Abra **Admin → Usuarios** y edite al usuario de Gestion Humana.
2. En el grupo de **Gestion humana**, marque en conjunto:
   - Ver el tablero **Desvinculaciones** en el menu lateral.
   - Acceder al tablero (Masivos y Seguimientos en lectura).
   - Ejecutar desvinculaciones masivas.
   - Editar checks y fecha entregado nomina en Seguimientos.
3. Guarde. En esta version la misma persona usa Masivos y Seguimientos; no se recomienda asignar solo una parte del paquete.
4. Quien solo tenga Masivos y **no** el permiso de desvincular en Ficha podra desvincular en lote, pero **no** podra regenerar una carta desde la ficha si el lote dejo el caso sin carta.

### Usar Masivos

1. Entre a Gestion Humana → tablero **Desvinculaciones** → pestana **Masivos**.
2. Cargue filas de una en una (cedula + Enter/blur) o use el icono de **varias cedulas** para pegar un listado (max. 500). El sistema agrega las que existan activas, omite duplicadas en la grilla y las no encontradas, y muestra un resumen.
3. En cada fila, si cargo cedula manualmente y sale del campo (o pulsa Enter), aparece el **nombre** si el empleado esta activo. Si no existe, esta inactivo o no tiene vinculo abierto, vera un mensaje y esa fila no es procesable.
4. Complete por fila: **FECHA DESVINCULACION**, **TIPO CARTA** y **FIRMA** (obligatorios). Causal, recontratable y observaciones son opcionales.
5. La grilla inicia con **2 filas**; use **+ fila** para agregar mas lineas o elimine filas que no vaya a usar.
6. No puede repetir la misma cedula dos veces en el mismo lote: el sistema lo rechaza antes de procesar (y el pegado masivo tambien omite duplicadas).
7. Pulse **Desvincular** (no pide confirmacion previa ni tiene tope de filas). El sistema procesa todas las filas: si una falla, las demas siguen.
8. Si una fila tiene **vacaciones / incapacidad / permiso** en MT-GH-04 que se solapa con la fecha de retiro, esa fila falla en el reporte (el resto del lote continua). Un **super-admin** puede marcar «Forzar pese a cruce…» en la barra antes de desvincular.
9. Al terminar vera el **reporte** (exitos y fallos). Si al menos una carta se genero, se descarga un **ZIP** con esas cartas y la grilla se limpia.
10. Si un empleado quedo desvinculado pero **sin carta**, aparecera en el reporte como fallo de carta; igual figura en Seguimientos con «tiene carta» en No. Para generar la carta despues, use **Ficha empleados** (permiso de desvincular) → Generar cartas.
11. **Importante sobre el ZIP:** la descarga es de un solo uso. Si necesita el archivo de nuevo y ya consumio el enlace, no reintente el mismo boton; vuelva a generar cartas desde Ficha o archive el ZIP a tiempo.

### Usar Seguimientos

1. Abra la pestana **Seguimientos**.
2. Filtre por texto (cedula o nombre), por estado (incompletos, OK TODO, sin carta) y/o por rango de fechas. Elija el **campo fecha** (registro, desvinculacion o entregado nomina; por defecto entregado nomina) y complete Desde/Hasta. Al usar fechas, los chips de estado se desactivan hasta que elija uno de nuevo. Al abrir la pestana, por defecto se muestran los **incompletos**. El filtro **OK TODO** solo incluye filas con los ocho checks en Si **y** con fecha entregado nomina. Use el icono de limpiar para volver a incompletos y vaciar busqueda/fechas.
3. Revise columnas de solo lectura: tipo de desvinculacion (causal), fechas, cargo, cedula, nombre, recontratable, observaciones y si **tiene carta generada**.
4. Marque los ocho checks operativos (orden examenes, enviado, control roll, retiro ARL, retiro cesantias, recibido, paz y salvo, reporte noved) y, si aplica, la **fecha entregado nomina**. Los cambios se guardan solos al soltar el control (unos instantes despues).
5. **OK TODO** se calcula solo: pasa a Si cuando los ocho checks estan en verdadero; no se puede forzar a mano.
6. Use **Exportar Excel** para descargar el listado con los filtros activos (incluye cedula, nombre, cargo, tipo desvinculacion, fechas, checks, OK TODO y observaciones).
7. Si el caso esta **sin carta**, regenere la carta en **Ficha empleados** del empleado (vinculo cerrado → Generar cartas). Al generar con exito, Seguimientos mostrara que ya tiene carta.
8. No se elimina ni se oculta un seguimiento de forma silenciosa. Para **revertir** la desvinculacion (reactivar al empleado): use el icono de reabrir en la fila, confirme con un **motivo obligatorio**. El sistema deja al empleado activo, quita la fila de Seguimientos, borra las cartas de ese retiro y, si habia una fila automatica en **MT-GH-04 Novedades → Retiros**, esa novedad deja de mostrarse alli.

### Relacion con Ficha empleados

1. Desvincular desde la ficha (un empleado) tambien crea el seguimiento automaticamente y alimenta **MT-GH-04 Novedades → Retiros**.
2. Generar la carta desde la ficha marca «tiene carta generada» en Seguimientos.
3. Las reglas del formulario individual de Ficha (campos obligatorios de causal, fechas y recontratable) no cambian; Masivos es mas flexible en esos opcionales.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.8 | 2026-10-05 | Agent | Seguimientos: listado con DataTables server-side (paginación/tamaño de página); filtros, export, autosave y revertir se mantienen. |
| 1.7 | 2026-10-05 | Agent | Bloqueo por cruce con MT-GH-04 (Vacaciones/Incapacidades/Permisos); Masivos: fila fallida + forzar super-admin. |
| 1.6 | 2026-09-30 | Agent | Seguimientos: default Incompletos; filtro OK TODO exige fecha entregado nomina; selector campo fecha (registro/desvinculacion/nomina) + botones icono filtrar/limpiar. |
| 1.5 | 2026-09-30 | Agent | Label tablero novedades: MT-GH-04 Novedades |
| 1.4 | 2026-09-30 | Documentador FEAT-040 | Nota: desvincular/revertir sincroniza Retiros en Reportes-Novedades |
| 1.3 | 2026-09-30 | Agencia | Masivos: pegado multi-cedula (max. 500) con lookup, omision de duplicadas/no encontradas y resumen |
| 1.2 | 2026-09-15 | Agencia | Filtro rango FECHA ENTREGADO NOMINA + export Excel en Seguimientos |
| 1.1 | 2026-09-14 | Agencia | Revertir desvinculacion por fila en Seguimientos (motivo + icono) |
| 1.0 | 2026-09-14 | Documentador FEAT-031 | Version inicial: Masivos, Seguimientos, paquete de permisos, relacion con Ficha; sin Excel, colores ni correo. |
