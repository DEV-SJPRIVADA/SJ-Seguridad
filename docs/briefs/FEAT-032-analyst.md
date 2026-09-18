# Preguntas del Analista — FEAT-032

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** respuestas del usuario registradas (2026-09-15). Listo para Arquitecto / Feature Brief.

---

## Contexto recibido

**Feature ID:** FEAT-032  
**Origen:** `@agent-sj` / AgentSj (2026-09-15)  
**Módulo tentativo:** Cursos (área Gestión Humana, tablero nuevo junto a ficha_empleados, desvinculaciones, archivo, plantillas_word)  
**Título:** Tablero Cursos en Gestión Humana

### Solicitud original (resumen)

Crear en Gestión Humana un **nuevo tablero** llamado **Cursos**, con permisos para **visualizar** y **editar**, con estas columnas/campos:

| Campo | Notas de la solicitud |
| --- | --- |
| CEDULA | |
| NOMBRE COMPLETO | |
| TIPO CURSO | |
| FECHA EXPEDICION | |
| No.CURSO | |
| VIGENCIA | Calculada, **no editable**; `ACTUALIZAR` (rojo) / `VIGENTE` (verde) |
| ESTADO | Seleccionable: `SOLICITADO`, `ACTUALIZADO`, `""` (vacío) |
| OBSERVACIONES | |

**Regla de vigencia (tal cual solicitada):**  
`ACTUALIZAR` cuando `FECHA EXPEDICION` sea **menor que** `(fecha de HOY + 30 días) − 365 días`; en caso contrario `VIGENTE`.

**Importación:** descargar **plantilla vacía** + **carga masiva** Excel (patrón existente: fila 1 claves, fila 2 labels, datos desde fila 3 — como ficha empleados / purchase-requests).

### Estado técnico hoy (repo — solo para formular preguntas)

| Aspecto | Comportamiento actual |
| --- | --- |
| Tablero Cursos | **No existe** (ni board, ni permisos `cursos.*`, ni tablas, ni docs) |
| Tableros GH vecinos | `ficha_empleados`, `desvinculaciones`, `archivo`, `plantillas_word` en `config/access.php` + `board_canonical_areas` → `gestion_humana` |
| Permisos patrón | `view.board.gestion_humana.{board}` + permisos de módulo (ej. `desvinculaciones.view` / `.masivos` / `.seguimientos.edit`; `ficha_empleados.view` / `.manage`) |
| Empleados / cédula | Perfiles en ficha (`employee_ficha_profiles` / entradas ficha) con cédula y nombre |
| Import plantilla vacía | `EmployeeFichaImportTemplateExport`, purchase-requests, matriz comercial: xlsx claves/labels/datos desde fila 3 |
| Export Excel app | `App\Exports\BaseExport` + `<x-export-excel>` |
| Selectores | `<x-searchable-select>` (prohibido Select2) |
| Branding | tokens `--brand-*`, `--color-surface` en `resources/css/app.css` |
| Docs | No hay `docs/modules/cursos.md` ni `docs/user/cursos.md` |
| Datos | Sin `migrate:fresh` / wipe sin OK usuario |

---

## Entendimiento del analista (resumen)

Gestión Humana necesita un **tablero operativo** para llevar el control de **cursos** de personas (típicamente empleados), con vigencia automática según antigüedad de la fecha de expedición (ventana ~30 días antes del año) y un estado de trámite seleccionable. La carga diaria se espera apoyada en **Excel** (plantilla vacía + import masivo), además de permisos separados de ver y editar.

Quedan abiertas decisiones de negocio/UX antes de brief/arquitectura (ver preguntas abajo). En particular: si un registro es “un curso por persona”, cómo se obtiene el nombre desde la cédula, qué valores exactos tiene **TIPO CURSO**, y si V1 incluye alta/edición/borrado fila a fila además del import.

---

## Preguntas abiertas

Responde cada punto para cerrar el brief:

### 1. Objetivo y usuarios

1.1. ¿Quién usará este tablero en el día a día (cargos concretos de GH / SST / formación / nómina)?  
1.2. ¿El resultado esperado es solo **consulta + actualización de vigencia/estado**, o también disparar acciones (solicitar curso, avisar a otra área, generar documento)?  
1.3. ¿Habrá usuarios que **solo consulten** (ver vigencia en rojo/verde) sin poder editar ni importar?

### 2. Alcance

2.1. ¿Qué queda **explícitamente fuera de V1**? Por ejemplo: notificaciones por correo al pasar a ACTUALIZAR, historial de renovaciones, adjuntos del certificado, integración con proveedores de cursos, dashboard/KPI de vencidos.  
2.2. Además de plantilla + carga masiva, ¿V1 incluye **CRUD manual** (crear / editar / eliminar fila desde la UI)?  
2.3. ¿Se requiere **exportar** el listado a Excel (además de la plantilla vacía de import)?  
2.4. ¿El tablero tiene **una sola pestaña** “Cursos”, u otras (catálogo de tipos, historial, etc.)?

### 3. Permisos

3.1. ¿Confirmamos el par solicitado:  
   - `view.board.gestion_humana.cursos` (ver tablero en sidebar),  
   - `cursos.view` (consultar listado),  
   - `cursos.edit` (crear/editar/importar / lo que quede en alcance)?  
   ¿O basta board + un solo permiso `cursos.manage`?  
3.2. ¿La **carga masiva** y **descarga de plantilla** exigen `cursos.edit`, o un permiso aparte (`cursos.import`)?  
3.3. ¿Quién recibe estos permisos por defecto en seed/Admin UI (solo administrador GH, o también perfiles `usuario` con área GH)?  
3.4. ¿`manage.users` / super-admin hace bypass total (mismo patrón que Ficha empleados / Desvinculaciones)?

### 4. Reglas de negocio — vigencia y estado

4.1. Confirmación de la fórmula: con `umbral = (HOY + 30 días) − 365 días` (= `HOY − 335 días`), ¿`ACTUALIZAR` es cuando `FECHA EXPEDICION < umbral` (estricto) y `VIGENTE` en caso contrario? ¿Incluye igualdad en un lado?  
4.2. Si `FECHA EXPEDICION` está vacía o es inválida en import: ¿se rechaza la fila, se deja sin vigencia, o se marca ACTUALIZAR?  
4.3. ¿La vigencia se **recalcula siempre al consultar** (no se guarda en BD), o se materializa en columna y se refresca en algún job?  
4.4. **ESTADO** vacío (`""`): ¿es el valor por defecto al crear/importar? ¿Se puede volver a vacío después de haber elegido SOLICITADO/ACTUALIZADO?  
4.5. ¿Hay **flujo obligatorio** entre estados (ej. solo se puede poner ACTUALIZADO si estaba SOLICITADO), o son valores libres sin secuencia?  
4.6. ¿ESTADO y VIGENCIA son independientes? (ej. puede estar VIGENTE + SOLICITADO, o ACTUALIZAR + ACTUALIZADO)  
4.7. Al renovar un curso (nueva fecha de expedición / nuevo No.CURSO): ¿se **edita la misma fila** o se **crea un registro histórico** y se archiva el anterior?

### 5. Datos — cédula, nombre, tipo curso, unicidad

5.1. ¿**CEDULA** debe existir en Ficha empleados (perfil activo), en cualquier empleado (activo o desvinculado), o se permite cédula **libre** (persona externa / aún no en ficha)?  
5.2. ¿**NOMBRE COMPLETO** se **autocompleta** al ingresar cédula (lookup), se toma solo del Excel en import, o ambos? ¿Es editable manualmente si no hay match?  
5.3. ¿**TIPO CURSO** es…  
   - (A) texto libre,  
   - (B) lista fija definida por el negocio (¿cuáles valores exactos?),  
   - (C) catálogo administrable en el sistema (como catálogos de Ficha)?  
5.4. ¿**No.CURSO** es único global, único por persona, o puede repetirse? ¿Obligatorio?  
5.5. ¿Puede una misma cédula tener **varios cursos** (varios tipos / varias filas)? ¿Unicidad sugerida: `(cedula, tipo_curso)` vigente, o sin restricción?  
5.6. ¿Se puede **eliminar** un registro, o solo “ocultar” / dejar histórico?

### 6. Interfaz

6.1. Etiqueta visible del tablero en sidebar: ¿**Cursos** exacto, u otra (**Control de cursos**, **Formación**, etc.)?  
6.2. Listado: ¿tabla con filtros/búsqueda (cédula, nombre, tipo, vigencia, estado)? ¿Filtro rápido “solo ACTUALIZAR”?  
6.3. Colores vigencia: ¿texto rojo/verde, badge, o fondo de celda? ¿Usar tokens de marca (`--brand-*`) o rojo/verde semánticos fijos?  
6.4. ¿Edición **en grilla** (tipo Excel / inline) o formulario/modal por registro?  
6.5. Tras carga masiva: ¿reporte de éxitos/errores por fila (como otros imports del sistema)? ¿Import es **solo altas**, **alta+actualización** por clave, o ¿reemplaza todo?  
6.6. Si import actualiza: ¿cuál es la **clave de match** (cédula+tipo, No.CURSO, otra)?

### 7. Integraciones

7.1. ¿Auditoría de altas/ediciones/imports vía `SystemAuditService` (módulo `cursos`)?  
7.2. ¿Algún otro módulo debe consumir vigencia de cursos (indicadores, comercial, operaciones)?  
7.3. ¿Notificaciones (correo / campana) cuando un curso pasa a ACTUALIZAR? ¿En V1 o fuera?

### 8. Documentación usuario / procedimiento operativo

8.1. ¿Existe un FO / procedimiento o Excel legacy de cursos que deba reflejarse en `docs/user/cursos.md` (nombres de columnas, tipos de curso, responsables)?  
8.2. ¿Los textos de ESTADO (`SOLICITADO`, `ACTUALIZADO`) y de VIGENCIA (`ACTUALIZAR`, `VIGENTE`) son definitivos para la UI y el Excel?

---

## Vacios identificados (checklist interno)

| Área | Vacío |
| --- | --- |
| Permisos | Nombres exactos view/edit/import; quién los recibe; bypass admin |
| Reglas | Fórmula de vigencia (borde igualdad); ESTADO vacío y transiciones; renovación vs historial |
| Datos | Origen cédula/nombre; catálogo TIPO CURSO; unicidad; tabla nueva vs extensión ficha |
| UI | CRUD vs solo import; filtros; colores; edición inline vs form |
| Import | Clave de upsert; errores parciales; columnas exactas de la plantilla |
| Alcance | Export listado; notificaciones; adjuntos; pestañas |
| Docs | Procedimiento operativo / Excel legacy de referencia |
| Seguridad datos | Migraciones aditivas únicamente; sin fresh/wipe |

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Módulo/área única GH; board `cursos` con label **Cursos**; hogar `gestion_humana`. | Nombre o ubicación distinta en nav; confusión con otro área. |
| 2 | Permisos: `view.board.gestion_humana.cursos` + `cursos.view` + `cursos.edit`; import/plantilla bajo `cursos.edit`; bypass `manage.users`. | Sobredimensiona o deja sin control a quien solo consulta / solo importa. |
| 3 | VIGENCIA **calculada en lectura** (no persistida); umbral `HOY − 335 días`; `ACTUALIZAR` si `fecha_expedicion < umbral`. | Desfase de 1 día en el borde; negocio esperaba vigencia guardada o job nocturno. |
| 4 | Una fila = un curso de una persona; misma cédula puede tener varios tipos; **No.CURSO** no necesariamente único global. | Duplicados o imposibilidad de varios cursos por persona. |
| 5 | TIPO CURSO será lista fija o catálogo (no texto 100 % libre) — **valores por definir**. | Import rechaza tipos reales del Excel legacy. |
| 6 | Cédula preferible con lookup a Ficha empleados, pero import puede traer nombre si no hay match (o se define rechazo). | Datos huérfanos vs bloqueo operativo. |
| 7 | V1 incluye listado + edición básica + plantilla vacía + carga masiva; **sin** notificaciones ni adjuntos. | Negocio espera alertas o certificados en V1. |
| 8 | Import = alta y actualización por clave de negocio (a confirmar); reporte de errores por fila; sin borrar registros ausentes del archivo. | Usuario cree que el Excel “reemplaza el tablero completo”. |
| 9 | Documentación técnica + usuario en `docs/modules/cursos.md` y `docs/user/cursos.md` en el cierre de la feature. | Procedimiento real no alineado si no hay FO. |
| 10 | Sin `migrate:fresh` / wipe; solo migraciones aditivas con OK implícito de feature (no reset de BD). | Pérdida de datos si alguien usa fresh por error. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

Fecha: 2026-09-15 (AgentSj chat)

| # | Pregunta AgentSj | Respuesta |
| --- | --- | --- |
| 1 | Permisos | Solo **`cursos.view`** (ver listado) y **`cursos.edit`** (editar + importar). Quien tenga activo el board de GH cursos + estos 2. Interpretación AgentSj: `view.board.gestion_humana.cursos` (sidebar) + `cursos.view` + `cursos.edit`. Import/plantilla bajo `cursos.edit`. |
| 2 | Edición / CRUD | **Todo**: alta, edición, eliminación (CRUD) además de import. |
| 3 | TIPO CURSO | **Catálogo** desde tabla propia con campos: **TIPO CURSO**, **CARGO CURSO**, **FORMATO PARA CURSOS**, **CURSOS**, **CARGO ACREDIT**. |
| 4 | Unicidad / duplicados | Un empleado puede tener **muchos** cursos. Duplicado / upsert: **cédula + Nº curso**. |
| 5 | ESTADO vacío | **Sí**: opción en blanco en el select. |
| 6 | Nombre | **Precargar** desde Ficha empleados por cédula si existe. |
| 7 | Plantilla / export | **Ambas**: plantilla vacía + import **y** export Excel del listado. |

### Cierres adicionales (derivados / supuestos aceptados para Arquitecto)

| Tema | Decisión |
| --- | --- |
| Pestañas V1 | Listado Cursos + administración del catálogo (necesario por respuesta 3). |
| Vigencia | Calculada en lectura; `ACTUALIZAR` si `fecha_expedicion < (HOY+30d)−365d`; fecha expedición required. |
| ESTADO | `""` \| `SOLICITADO` \| `ACTUALIZADO`; libre; independiente de vigencia. |
| Import | Upsert por cédula + Nº curso; errores por fila; no borrar ausentes. |
| Cédula sin ficha | Nombre manual/Excel si no hay match. |
| Notificaciones / adjuntos / historial | Fuera de V1. |
| Audit | SystemAuditService módulo `cursos`. |
| 9 | Documento del curso | **1 archivo** por registro: cargar/reemplazar (`cursos.edit`), descargar (`cursos.view` o desde Ficha). PDF/JPG/PNG/WEBP ≤10 MB. No va en Excel import. |
| 10 | Ficha empleados | Botón consultar cursos del empleado + descargar si hay documento. Sin subir/editar desde Ficha en V1. |