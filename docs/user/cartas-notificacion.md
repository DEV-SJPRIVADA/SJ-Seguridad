# Cartas Notificación — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/cartas-notificacion.md`.
> **Orden obligatorio de secciones.**

## Objetivo

Permitir a Gestión Humana armar un lote de personas en una grilla y descargar **cartas de notificación** en Word: un archivo por una persona, o un ZIP cuando hay varias. Los datos del lote **no se guardan** en el sistema; solo se usan para generar y descargar en el momento.

## Alcance

Aplica al tablero **Cartas Notificación** del área **Gestión Humana** (menú lateral). Quien tenga permiso de generación puede:

- Pegar o escribir cédulas, completar nombre, fecha de terminación y firma por fila.
- Cargar un Excel de apoyo (solo llena la grilla; no crea registros permanentes).
- Buscar o filtrar filas en pantalla; vaciar toda la grilla con **Limpiar**.
- Generar y descargar el Word o el ZIP.

**Límites importantes:**

- Hace falta **exactamente una** plantilla activa del tipo **Cartas Notificación** en el tablero **Plantillas Word**. Si no hay ninguna o hay más de una, la generación se bloquea.
- Máximo **500** filas por lote.
- No hay historial ni re-descarga posterior: si necesita el archivo otra vez, debe generar de nuevo.
- No se envían cartas por correo desde esta pantalla.
- Quien solo ve el enlace del tablero pero **no** tiene permiso de generar recibirá un aviso de acceso denegado al entrar.

## Definiciones

| Término | Significado |
| --- | --- |
| Grilla / lote | Lista temporal de personas en pantalla (cédula, nombre, fecha de terminación, firma). Se borra al limpiar o al salir; no es un archivo guardado del sistema. |
| Fecha de terminación | Fecha que usted escribe en la fila; aparece en la carta en formato largo en español y **mayúsculas** (por ejemplo «15 DE JUNIO DEL 2026»). |
| Firma | Persona firmante elegida de la lista de firmas activas; define el nombre y el cargo que salen en la carta. |
| Plantilla Word | Archivo modelo (`.docx`) con variables `${…}` que el sistema rellena al generar. Se administra en **Plantillas Word**. |
| Carta generada | Documento Word ya rellenado que usted descarga; no queda almacenado en el tablero. |
| Aviso de ficha | Mensaje en la fila cuando la cédula no está en Ficha o la ficha no está activa; igual puede generar si completa nombre, fecha y firma. |

## Responsabilidades

| Rol / perfil | Responsabilidad en este módulo |
| --- | --- |
| Operador de Cartas Notificación | Armar el lote, corregir avisos, elegir firma y generar/descargar. |
| Administrador de Plantillas Word | Subir y mantener **una sola** plantilla activa del tipo Cartas Notificación. |
| Administrador de usuarios | Asignar el tablero **y** el permiso de generar a quien corresponda (ambos). |
| Consulta / solo enlace de tablero | Puede ver el enlace en el menú, pero no puede usar la pantalla sin permiso de generar. |

## Desarrollo

### Abrir el tablero

1. En **Gestión Humana**, pulse **Cartas Notificación** en el menú lateral.
2. Entrará directo a la grilla (no hay pestañas ni panel de indicadores).
3. Si el sistema indica que necesita permiso de generar, pida a un administrador que le asigne ese permiso además del tablero.

### Preparar la plantilla (una sola vez o cuando cambie el modelo)

1. En **Plantillas Word**, asegúrese de que exista el tipo **Cartas Notificación**.
2. Suba **una** plantilla con ese tipo y archivo Word. Si hay cero o varias activas con archivo, la generación fallará con un mensaje claro.
3. Use variables como `${CEDULA}`, `${NOMBRE_COMPLETO}`, `${DURACION_CONTRATO}`, `${FECHA_TERMINACION}`, `${FIRMA}`, `${CARGO_FIRMA}` y `${FECHA}` (listado de apoyo en Plantillas Word, categoría **Cartas notificación**).

### Armar el lote a mano o pegando cédulas

1. Al abrir verá **dos filas vacías** para escribir a mano. Use el botón **+ (Agregar fila)** para sumar más, o pegue varias cédulas de una vez.
2. El sistema buscará cada cédula en Ficha: si está activa, puede precargar el nombre (usted puede editarlo).
3. Si no hay ficha o no está activa, verá un aviso; escriba el nombre a mano.
4. Elija la **duración contrato** (**6** o **12**), complete la **fecha de terminación** y elija la **firma** en cada fila.
5. Las cédulas repetidas al pegar se omiten y el sistema muestra un resumen.

### Cargar Excel (opcional)

1. Descargue la plantilla Excel del tablero (columnas: cédula, nombre, duración contrato, fecha de terminación, firma).
2. Complétela y súbala. En **DURACION_CONTRATO** use **6** o **12**. Los datos pasan a la grilla; **no** se guardan como base de datos del módulo.
3. Si la firma o la duración del Excel no coinciden, corríjalas en la grilla.
4. Ajuste nombres, duración, fechas y firmas antes de generar. El total no puede superar 500 filas.

### Filtrar y limpiar

1. Use el filtro de texto para localizar filas en la grilla (solo afecta lo que ve en pantalla).
2. Pulse **Limpiar** cuando quiera reiniciar la grilla a dos filas vacías (no solo el filtro).

### Generar y descargar

1. Revise que cada fila tenga cédula, nombre, duración contrato (6 o 12), fecha de terminación y firma, y que no haya cédulas duplicadas.
2. Pulse **Generar**. Una persona → descarga un Word; varias → un ZIP con un Word por persona.
3. Guarde el archivo en su equipo; el sistema no lo conserva después de la descarga.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.2 | 2026-10-08 | Agent | Campo **Duración contrato** (6|12) en grilla, Excel masivo y variable `${DURACION_CONTRATO}`. |
| 1.1 | 2026-10-08 | Agent | Grilla inicia con 2 filas vacías + agregar filas a mano (paridad Cartas Vacaciones). |
| 1.0 | 2026-10-08 | Documentador | Versión inicial FEAT-044: tablero Cartas Notificación (grilla en memoria, Excel de apoyo, generación Word/ZIP). |
