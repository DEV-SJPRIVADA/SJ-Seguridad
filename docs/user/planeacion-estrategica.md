# Planeación estratégica — Guía de usuario

> Documentación operativa para usuarios finales. Ubicación: `docs/user/planeacion-estrategica.md`.

## Objetivo

Construir indicadores de gestión de Calidad (qué se mide y cómo se calcula), publicarlos, compartirlos a las personas que deben diligenciarlos y consultar el resultado del mes y del año en una ficha con gráfico y análisis.

## Alcance

Tablero **Planeación estratégica** en el área **Calidad**, con tres pestañas:

- **Dashboard** — resumen del mes: cuántos indicadores hay, cuántas capturas faltan y cuáles cumplen o no la meta.
- **Indicadores** — crear, editar, publicar, archivar, compartir y abrir la ficha (historial enero–diciembre).
- **Captura** — diligenciar el mes de los indicadores que le asignaron.

**En esta versión:**

- Calidad define los **campos** del formulario (por ejemplo cantidades o porcentajes) y después la **fórmula**, por ejemplo `((campo_1 / campo_2) + campo_3) * 100`.
- El **código** del indicador lo escribe Calidad a mano; no puede repetirse.
- El periodo es **año y mes de calendario**. La frecuencia es **mensual**.
- Varias personas pueden capturar el **mismo** indicador en el **mismo** mes: cada una tiene su fila.
- El consolidado **suma los campos** de todas las capturas del mes y **vuelve a calcular** la fórmula. No promedia los porcentajes individuales.
- Si Calidad publica una versión nueva a mitad de un mes (por ejemplo el 15 de marzo), **ese mes queda cerrado**: no se carga ni se reemplaza. El mes siguiente usa la versión nueva.
- No incluye DOFA, objetivos ni estrategias. La ficha se puede exportar a Excel y PDF (incluye una imagen del gráfico).

## Definiciones

| Término | Significado |
| --- | --- |
| Indicador | Medición que Calidad construye (nombre, meta, campos y fórmula). |
| Campo | Dato numérico que se pide en la captura (clave técnica + etiqueta visible). |
| Fórmula | Cálculo permitido con `+ - * / ( )`, números y los campos. No admite funciones tipo SI o MIN. |
| Versión | Copia congelada de la ficha al publicar. Editar un indicador ya publicado abre un borrador nuevo. |
| Mes cerrado | Mes en el que se publicó una versión 2 o posterior: no admite captura ni reemplazo. |
| Capturador | Persona asignada para diligenciar su fila cada mes. |
| Consulta | Permiso extra en el indicador: ver el listado, la ficha del equipo y el dashboard. |
| Consolidado Todos | Suma de cada campo de todas las personas del mes y recálculo de la fórmula. |
| Cumple | El resultado del mes alcanza la meta según el operador (≥, ≤, >, < o =). |
| Crítico | Cuando no se cumple y el resultado cruza el valor crítico definido en la ficha. |
| Plan de mejora | Texto obligatorio si el mes no cumple la meta. |

## Responsabilidades

| Quién | Qué hace |
| --- | --- |
| Calidad (editar indicadores) | Crea, corrige, publica, archiva, restablece y comparte indicadores. Ve todos en listado, ficha y dashboard. También puede capturar. |
| Consulta de un indicador | Ve la pestaña Indicadores y el Dashboard de **ese** indicador (y el historial del equipo asignado). También captura su fila si está marcado para captura. |
| Captura | Solo ve la pestaña Captura de **sus** indicadores. Diligencia o reemplaza su mes. No ve el listado ni el dashboard. |
| Administrador de usuarios | Puede asignar el tablero y las funciones a mano. El rol administrador los trae de base. Al **compartir** un indicador, el sistema abre solo el acceso mínimo a esa persona. |

## Desarrollo

### Entrar al tablero

1. Entre a **Calidad → Planeación estratégica**.
2. Verá las pestañas que le correspondan (Dashboard, Indicadores y/o Captura).

### Crear y publicar un indicador (Calidad)

1. En **Indicadores**, cree uno nuevo.
2. Escriba el **código** (único) y el **nombre**. Complete objetivo, **insumos para la medición**, **medición**, proceso, unidad, meta, operador y, si aplica, valor crítico. Esos dos textos salen en la ficha.
3. Defina entre 2 y 12 **campos**: clave en minúsculas (ejemplo `campo_1`), etiqueta y si es obligatorio.
4. Escriba la **fórmula** usando esas claves. Use **Probar** con valores de ejemplo. Si hay división entre cero, el sistema no deja guardar.
5. Guarde el **borrador**. Revise y pulse **Publicar** para congelar la versión. Hasta que no esté publicado, nadie puede capturarlo.
6. Si más adelante corrige un indicador ya publicado, el sistema abre una **versión nueva**. Publíquela cuando esté lista. Recuerde: el mes en que publique esa versión nueva queda cerrado.

### Archivar y restablecer

1. En el listado o en la ficha, pulse el ícono de **archivar**. El sistema pide confirmación.
2. Un indicador archivado no se captura ni aparece en el dashboard. El historial no se borra.
3. Para recuperarlo, filtre por **Archivado** (o ábralo desde **Todos**), pulse el ícono de **restablecer** (flechas en círculo) o ábralo y restablézcalo desde la ficha.

### Compartir

1. En la ficha del indicador (o al editarlo), pulse el ícono de **personas** (Compartir indicador).
2. En el modal, asigne **usuarios activos** o **áreas completas** y pulse **Guardar asignaciones**.
3. Toda persona asignada (directa o por área) puede **capturar**. Marque además **consulta** si debe ver el listado, la ficha del equipo y el dashboard.
4. Al guardar, esas personas reciben el acceso mínimo. Si las quita de todos los indicadores, el sistema les retira ese acceso mínimo. Quien ya edita indicadores (Calidad) no pierde sus funciones.
5. Las áreas usan el área configurada en el usuario (`area_key`).
6. Las **suplencias** se registran en el mismo modal: titular, suplente y rango de fechas. El suplente captura y reemplaza a nombre del titular solo dentro del rango; no ve el consolidado del equipo.

### Capturar el mes


1. Abra **Captura** y elija el indicador.
2. Seleccione año y mes. Complete los campos, el análisis y, si no cumple la meta, el **plan de mejora**.
3. Guarde. Si ya había capturado ese mes, el sistema pide confirmación antes de **reemplazar**.
4. Si el mes está cerrado por una versión nueva, no podrá guardar.
5. En la misma pantalla puede descargar **Excel** o **PDF** de su ficha.

### Consultar la ficha

1. Desde **Indicadores**, abra el indicador.
2. Elija el año y, si tiene consulta o es Calidad, **Todos los capturadores** o una persona.
3. Si el indicador ya tiene más de una versión, use el filtro **Versión** o el panel **Historial de versiones** para ver una versión anterior y qué cambió (nombre, fórmula, meta, campos). **Vigente (según el mes)** muestra la versión que rige cada mes.
4. La hoja muestra nombre, objetivo, proceso, meta, crítico, fórmula, una fila por campo de enero a diciembre, el gráfico y el análisis del mes filtrado. Cada mes aparece en su columna (noviembre no se dibuja en diciembre).
5. En **Todos**, los números de cada campo son la **suma** de las personas y el resultado se **recalcula**. No es el promedio de los porcentajes de cada uno.
6. Para descargar, pulse el ícono de **Excel** o de **PDF** en la barra de la ficha (consulta o captura). Llevan el año, el capturador y la versión que esté filtrando. El archivo incluye el gráfico y el análisis a todo el ancho; el PDF sale en una sola hoja.

### Dashboard

1. Elija año y mes.
2. Revise cuántos indicadores hay, qué porcentaje de capturas está hecho y cuáles cumplen, no cumplen o siguen pendientes.
3. Pulse un indicador de la tabla para abrir su ficha.

## Control de cambios

| Versión | Fecha | Autor | Descripción del cambio |
| --- | --- | --- | --- |
| 1.9 | 2026-11-11 | Documentador | Suplencias temporales: el suplente captura a nombre del titular dentro del rango. |
| 1.8 | 2026-11-11 | Documentador | Compartir por área además de usuarios sueltos; suplencias quedan como pendiente definido. |
| 1.7 | 2026-11-11 | Documentador | Análisis visible en consolidado; Excel/PDF también desde captura; etiquetas del gráfico valor arriba y % dentro. |
| 1.6 | 2026-11-11 | Documentador | Excel y PDF incluyen imagen del gráfico; PDF en una hoja; análisis a todo el ancho. |
| 1.5 | 2026-11-11 | Documentador | Exportar la ficha a Excel y PDF desde la consulta. |
| 1.4 | 2026-11-11 | Documentador | Archivar pide confirmación y se puede restablecer desde el listado o la ficha. |
| 1.3 | 2026-11-10 | Documentador | Compartir indicador pasa a un botón de ícono que abre un modal (ficha y edición). |
| 1.2 | 2026-11-10 | Documentador | Insumos para la medición y Medición se escriben en Identidad y se muestran en la ficha. |
| 1.1 | 2026-11-10 | Documentador | Consulta de versiones anteriores, historial de cambios, bordes de ficha/campos y gráfico por mes. |
| 1.0 | 2026-11-10 | Documentador | Versión inicial del tablero (FEAT-050): indicadores construibles, compartir, captura mensual, ficha consolidada y dashboard. |
