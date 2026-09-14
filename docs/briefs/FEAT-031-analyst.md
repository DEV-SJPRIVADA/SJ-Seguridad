# Preguntas del Analista — FEAT-031

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** muchas ambigüedades de producto abiertas (permisos, campos del flujo individual vs grilla Masivos, tipología carta/causal, fallos parciales, modelo Seguimientos, colores legacy). **Con pausa.** No hay Feature Brief final.

---

## Contexto recibido

**Feature ID:** FEAT-031  
**Origen:** `@agent-sj` (2026-09-14)  
**Módulo tentativo:** Desvinculaciones (área Gestión Humana, junto a Ficha de Empleados)  
**Título:** Tablero Desvinculaciones (Masivos + Seguimientos)

### Solicitud original (resumen)

Crear en Gestión Humana un **nuevo tablero** al lado de Ficha de Empleados, con permiso de acceso, llamado **Desvinculaciones**, con dos pestañas:

1. **Masivos** — grilla editable por fila (cédula → nombre si activo; fecha terminación; tipo carta; firma; botón Desvincular). Al ejecutar: desvincular cada empleado como hoy en individual, registrar en Seguimientos, descargar ZIP con cartas y limpiar la vista.
2. **Seguimientos** — tabla de seguimiento post-retiro con checks de trámite, OK TODO automático, fecha entrega nómina, guardado automático al editar.

Referencias visuales adjuntas: imagen 1 (Masivos: CEDULA | NOMBRE | FECHA TERMINACION | TIPO CARTA | FIRMA) e imagen 2 (Seguimientos: No, filtros, checks, colores en celdas).

### Estado técnico hoy (repo — solo para formular preguntas)

| Aspecto | Comportamiento actual |
| --- | --- |
| Desvinculación individual | `FichaEmpleadosController::terminate` + `EmployeeFichaEmploymentPeriodService::closeActivePeriod` / `syncProfileAfterTermination` |
| Campos obligatorios al desvincular | Causal (`termination_cause_code`), recontratable (`is_rehireable`), último día laboral (`last_work_day`), fecha desvinculación (`termination_date`); notas opcionales |
| Permiso actual | `ficha_empleados.terminate` |
| Cartas Word | `TerminationLetterController`; plantillas tipo `desvinculacion`; modal pide **una o más** plantillas + firmante (`signatory_id`); salida docx o zip **por empleado/periodo** |
| Causal ≠ plantilla | Catálogo `termination_cause` (RENUNCIA, etc.) es distinto de “tipo de carta”/plantilla Word |
| Tableros GH | `config/access.php`: boards `ficha_empleados`, `view.board.gestion_humana.*`, tabs `ficha_empleados_tabs` |
| Empleado activo | `employee_ficha_profiles.employment_status` = activo (+ periodo abierto) |
| Docs | `docs/modules/ficha-empleados.md`, `docs/user/ficha-empleados.md`, `docs/user/plantillas-word.md` |

---

## Entendimiento del analista (resumen)

Gestión Humana necesita un **tablero dedicado** para procesar **varias desvinculaciones a la vez** (ingreso de cédulas en grilla, generar cartas y cerrar vínculos) y, en paralelo, un **tablero de seguimiento** de trámites administrativos posteriores al retiro (exámenes, ARL, cesantías, paz y salvo, etc.), con edición rápida tipo Excel y guardado automático.

Hoy eso solo existe de forma **individual** en Ficha empleados (desvincular + generar cartas) y **no** hay entidad de “seguimiento de desvinculación” en el sistema. La solicitud pide reutilizar el proceso individual en lote y materializar el Excel legacy de seguimientos dentro de la plataforma.

Quedan abiertas decisiones críticas de negocio/UX antes de brief/arquitectura (ver preguntas abajo).

---

## Preguntas abiertas

Responde cada punto para cerrar el brief:

### 1. Objetivo y usuarios

1.1. ¿Quién usará este tablero en el día a día (roles o cargos concretos de GH / nómina / SST)?  
1.2. ¿El resultado esperado del lote Masivos es solo “cerrar vínculo + cartas + fila en Seguimientos”, o también debe disparar algo más (correo, aviso a otra área, cambio en requisición)?  
1.3. ¿Seguimientos lo usan las mismas personas que ejecutan Masivos, u otro perfil (p. ej. solo consulta / solo checks)?

### 2. Alcance

2.1. ¿Qué queda **explícitamente fuera** de V1? Por ejemplo: reimpresión de cartas desde Seguimientos, anular/revertir una desvinculación masiva, importar Excel de seguimientos legacy, editar causal después del cierre, reingreso desde este tablero.  
2.2. ¿La desvinculación **individual** en Ficha empleados se mantiene igual (sin cambios) y Masivos solo la reutiliza?  
2.3. ¿Hay un tope máximo de filas por lote en V1 (p. ej. 20 / 50 / 100)?

### 3. Permisos

3.1. ¿Basta un permiso de **ver tablero** (`view.board.gestion_humana.desvinculaciones`) más reutilizar `ficha_empleados.terminate` para Masivos, o se necesitan permisos nuevos tipo `desvinculaciones.view` / `desvinculaciones.masivos` / `desvinculaciones.seguimientos.edit`?  
3.2. ¿Puede haber usuarios que **solo vean/editen Seguimientos** sin poder ejecutar Masivos (y viceversa)?  
3.3. ¿Quién puede marcar los checks de Seguimientos: cualquiera con acceso al tablero, o un permiso de edición separado?  
3.4. ¿`manage.users` / super-admin hace bypass total (mismo patrón que Ficha empleados)?

### 4. Reglas de negocio — pestaña Masivos

4.1. En la grilla, **“TIPO CARTA”** ¿es…  
   - (A) una sola plantilla Word de tipo desvinculación,  
   - (B) selección múltiple de plantillas (como el modal individual), o  
   - (C) la **causal de desvinculación** del catálogo (`termination_cause`: RENUNCIA, etc.)?  
4.2. Si “tipo carta” = plantilla(s): ¿dónde se elige la **causal**, si es **recontratable**, el **último día de trabajo** y las **observaciones**? Hoy son obligatorios en el flujo individual y no aparecen en la imagen de Masivos.  
4.3. ¿Fecha de terminación, tipo de carta y firma son **por fila** (como sugiere la imagen) o hay valores **globales** aplicables a todo el lote con posibilidad de override por fila?  
4.4. Al escribir la cédula: ¿se busca al salir del campo (blur) / con debounce / al pulsar Tab/Enter? ¿Qué mensaje exacto si no está activo, no existe, o ya está desvinculado?  
4.5. Fuente del lookup: ¿solo perfil activo en ficha (`employment_status = activo` + periodo abierto), o también entradas pendientes / histórico?  
4.6. ¿Se permite la misma cédula dos veces en la grilla del mismo lote?  
4.7. Si en el lote **falla** un empleado a mitad de camino (p. ej. ya no activo, error al generar carta): ¿se **detiene todo** (todo-o-nada), o se **continúa** con el resto y se muestra un reporte de éxitos/fallos?  
4.8. Si falla la generación de carta pero el cierre del vínculo ya ocurrió: ¿se deja desvinculado sin carta, se revierte, o se marca para reintentar solo cartas?  
4.9. Tras éxito: ¿el ZIP contiene **todas las cartas de todos los empleados** en un solo archivo? ¿Carpeta por cédula/nombre dentro del ZIP? ¿Nombre de archivo esperado?  
4.10. ¿Tras Desvincular se limpia solo la grilla Masivos, o también se refresca/navega a Seguimientos?  
4.11. ¿Se pide confirmación (SweetAlert) antes de ejecutar el lote, con resumen de cantidad de filas?

### 5. Datos — pestaña Seguimientos

5.1. ¿Confirmamos **tabla nueva** de seguimientos (un registro por desvinculación/periodo cerrado), creada automáticamente al ejecutar Masivos (y ¿también al desvincular individual desde Ficha)?  
5.2. Columna **No**: ¿es un consecutivo de negocio propio (como el Excel 285, 284…) o el `id` técnico? ¿Orden por defecto descendente (más reciente primero)?  
5.3. **TIPO DESVINCULACIÓN** en Seguimientos: ¿es la causal del catálogo, el nombre de la plantilla, o un texto libre?  
5.4. **FECHA DE REGISTRO** = ¿fecha/hora en que se ejecutó la desvinculación en el sistema? ¿Editable o solo lectura?  
5.5. **OK TODO**: ¿solo lectura (se calcula solo si los 8 checks anteriores están en verdadero) o el usuario puede forzarlo?  
5.6. ¿Los checks y la fecha “entregado nómina” tienen historial de quién/cuándo los cambió, o basta el valor actual?  
5.7. ¿Se puede **eliminar** o **ocultar** un seguimiento? ¿Filtros por rango de fechas / incompletos / OK TODO?  
5.8. ¿Exportación Excel de Seguimientos en V1?

### 6. Interfaz

6.1. Etiquetas visibles del tablero y pestañas: ¿**Desvinculaciones** / **Masivos** / **Seguimientos** exactas, u otras?  
6.2. En Masivos: ¿cómo se agregan filas (siempre N vacías, botón “+ fila”, pegar desde Excel)? ¿Se puede borrar una fila antes de ejecutar?  
6.3. Nombre del botón: ¿**Desvincular** (singular) aunque sea lote, u otra etiqueta (**Procesar lote**, **Desvincular seleccionados**)?  
6.4. En Seguimientos: ¿filtros en encabezados (como Excel) son requisito de V1 o basta búsqueda/filtros simples del estándar del sistema?  
6.5. **Colores del Excel legacy** (cédula fondo rojo/rosa, tipo desvinculación naranja, fecha retiro verde, icono aviso en fecha nómina): ¿son **requisito de producto** o solo **referencia visual** del archivo viejo? Si son requisito, ¿qué regla dispara cada color (duplicados, causal específica, incompleto, etc.)?  
6.6. Cargo en Seguimientos: ¿se toma del periodo cerrado al momento del retiro (snapshot) o se lee siempre del perfil actual?

### 7. Integraciones

7.1. ¿Notificaciones por correo al desvincular (empleado, jefe, nómina, SST)? Si sí, ¿en V1 o fuera?  
7.2. ¿Auditoría: registrar lote Masivos (quién, cuántos, cédulas, plantillas, firmante) y cambios de checks en Seguimientos?  
7.3. ¿Algún otro módulo debe enterarse del retiro (requisiciones, comercial, indicadores)?

### 8. Documentación usuario / procedimiento operativo

8.1. ¿Existe un FO / procedimiento escrito de desvinculación masiva y de checklist de seguimientos que deba reflejarse en `docs/user/`?  
8.2. ¿Los nombres de los 8 checks (ORDEN EXAMENES, ENVIADO, CONTROL ROLL, RETIRO ARL, RETIRO CESANTIAS, RECIBIDO, PAZ Y SALVO, REPORTE NOVED) son definitivos o hay que alinearlos a un FO?

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Tablero nuevo **Desvinculaciones** en GH, junto a Ficha empleados; 2 pestañas Masivos + Seguimientos. | Querían una pestaña dentro de Ficha empleados, no tablero aparte. |
| 2 | Permisos nuevos de tablero + pestañas; acción Masivos reutiliza o espeja `ficha_empleados.terminate` hasta confirmar modelo final. | Esperaban un solo permiso o herencia distinta; riesgo de over/under-permissioning. |
| 3 | “Tipo carta” en Masivos = plantilla(s) Word tipo `desvinculacion`; la **causal** y **recontratable** / **último día** faltan en la UI y deben definirse (globales o por fila). | El negocio pensaba que “tipo carta” era la causal; se implementaría el dato equivocado. |
| 4 | Fecha, tipo carta y firma son **por fila** (como la grilla de referencia). | Preferían cabecera global del lote. |
| 5 | Lookup de cédula al salir del campo; solo empleados **activos** con periodo abierto; inactivo/inexistente → mensaje y sin nombre. | Timing o fuente distinta; falsos positivos/negativos. |
| 6 | Fallos parciales: **continuar** el lote y reportar éxitos/errores (no todo-o-nada). | Esperaban transacción única; datos a medias inaceptables. |
| 7 | Un **solo ZIP** con todas las cartas de todos los empleados del lote; grilla se limpia tras éxito. | Querían un zip por empleado o descarga secuencial. |
| 8 | Seguimientos = **tabla nueva**; se crea al desvincular (Masivos y, pendiente confirmar, también individual); **OK TODO** solo lectura calculado. | Solo Masivos crea seguimiento; o OK TODO editable manualmente. |
| 9 | Colores Excel legacy = **referencia visual**, no requisito V1, hasta que el usuario diga lo contrario. | Operación diaria depende del semáforo de colores. |
| 10 | Sin correo en V1; sí auditoría de lote y de cambios relevantes en Seguimientos. | Necesitaban avisos automáticos a nómina/SST. |
| 11 | Desvinculación individual en Ficha empleados **no se elimina**; Masivos es un canal adicional. | Querían migrar todo el flujo solo a Desvinculaciones. |
| 12 | Export Excel de Seguimientos: **fuera de V1** hasta confirmar. | El negocio lo usa a diario como el Excel viejo. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

**Fecha:** 2026-09-14  
**Fuente:** chat AgentSj (respuestas numeradas 1–14)

| # | Tema | Decision |
| --- | --- | --- |
| 1 | Permisos | **Permisos nuevos** (no solo reusar `ficha_empleados.terminate` como unico modelo). |
| 2 | Quien usa | **La misma persona** maneja Masivos y Seguimientos (no perfiles separados V1). |
| 3 | TIPO CARTA | Select del **listado de plantillas Word de desvinculacion** disponibles (tipo documento desvinculacion). |
| 4 | Campos faltantes | **Ultimo dia = FECHA DE RETIRO**; renombrar a **FECHA DESVINCULACION**. Causal, recontratable y observaciones van en **ambas** vistas: en Masivos **opcionales**; en Seguimientos **solo consulta**. |
| 5 | Fecha / carta / firma | **Por fila**. |
| 6 | Fallos parciales | **Continuar**, terminar el lote y **reportar** los que fallaron. |
| 7 | Fallo de carta | Dejar desvinculado **sin carta**, reportar al usuario para regenerar; en Seguimientos un campo que indique si **tiene carta generada o no**. |
| 8 | ZIP / limpiar | De acuerdo con supuestos: **un ZIP** con todas las cartas del lote; limpiar grilla Masivos. |
| 9 | Modelo Seguimientos | De acuerdo con supuestos: **tabla nueva**; crear al desvincular. *(Arquitecto: crear tambien desde desvinculacion individual en Ficha para no dejar huecos en Seguimientos.)* |
| 10 | OK TODO | De acuerdo con supuestos: **solo lectura**, calculado si los 8 checks estan en verdadero. |
| 11 | Export Excel | De acuerdo con supuestos: **fuera de V1**. |
| 12 | Colores Excel legacy | **Sin colores** (no requisito). |
| 13 | Tope / confirmacion | **No es necesario** tope de filas ni confirmacion previa. |
| 14 | Correo / auditoria | **Correo no** en V1. **Si** registro en logs de auditoria. |

### Estado post-respuestas

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

**Notas para Arquitecto:**
- Permisos nuevos tipicos: ver tablero + capacidad Masivos + ver/editar Seguimientos; misma persona recibe el paquete (sin split de perfiles en V1).
- Plantilla Word: seleccion **unica por fila** del listado disponible (no multi-plantilla en V1 salvo que el brief lo justifique).
- Columna/campo Seguimientos: indicador de carta generada (si/no) + posible reintento desde flujo existente o enlace a Ficha.
- Renombrar UX: FECHA DESVINCULACION (= last_work_day / fecha retiro unificada segun brief).
- Shared-files: `config/access.php`, rutas GH, nav tableros.

---

## Entrega al AgentSj

- **Artefacto:** `docs/briefs/FEAT-031-analyst.md`
- **Acción:** **Pausar flujo** hasta respuesta del usuario a las preguntas abiertas (prioridad: 3.x permisos, 4.1–4.2 tipo carta vs causal/campos faltantes, 4.7–4.9 fallos/ZIP, 5.x modelo Seguimientos, 6.5 colores).
- **No** producir Feature Brief final en este ciclo.
