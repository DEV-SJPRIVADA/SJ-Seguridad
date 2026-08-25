# Preguntas del Analista — FEAT-029

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

**Feature ID:** FEAT-029  
**Origen:** `@agent-sj` (2026-08-21)  
**Título:** Tablero plantillas Word (tipo documento) + modal generar cartas (selección 1/N → docx/zip)

### Solicitud del usuario (resumen)

Evolucionar FEAT-027: dejar de amarrar plantillas Word a la **causal de desvinculación** (pack fijo RENUNCIA de 3 documentos siempre en ZIP) y pasar a un **tablero propio** de plantillas clasificadas por **tipo de documento**, con generación flexible desde ficha (modal con selección 1 o N → un `.docx` o un `.zip`).

### Decisiones YA CONFIRMADAS (chat previo — no repreguntar salvo contradicción)

1. Tablero con **entrada propia en el sidebar** (no pestaña dentro de Ficha empleados).
2. Lista **única** de plantillas **sin amarrar a causal**; columna **tipo de documento** (ej. desvinculacion; más adelante contratacion y otros).
3. En el flujo de **desvinculación**, el modal de generación muestra **solo** plantillas de tipo **desvinculacion** (filtro por tipo, **no** por causal Renuncia/etc.).
4. En el tablero admin: **agregar** plantillas nuevas, **reemplazar** el `.docx` del mismo registro y **eliminar**.
5. Quitar el botón aparte **Regenerar**: **Generar cartas** abre el modal y descarga; **Descargar** sirve el último paquete guardado en el período **si existe**.
6. Evoluciona FEAT-027: hoy las plantillas viven en Catálogos → Causal desvinculación y la generación fija el pack RENUNCIA (3 required) siempre en ZIP.

### Estado técnico hoy (repo — FEAT-027)

| Aspecto | Comportamiento actual |
| --- | --- |
| Tabla | `termination_letter_document_templates` amarrada a `termination_cause_code` + `document_key` |
| Pack config | `config/employee_ficha.php` → `termination_letter_packs.RENUNCIA` (3 docs required) |
| Causales soportadas | Solo `RENUNCIA` en `termination_letter_supported_causes` |
| Admin plantillas | Catálogos → Causal desvinculación (`ficha_empleados.manage`) |
| Generación | Siempre ZIP con todos los `is_required`; permiso `ficha_empleados.terminate` |
| UI ficha | Partial `termination-letter-actions`: Generar / Regenerar + Descargar si hay path |
| Persistencia período | `termination_letter_path` + `termination_letter_type` (`zip`) |
| Servicios | `App\Services\GestionHumana\TerminationLetter\*` |

---

## Entendimiento del analista (resumen)

El negocio quiere **gestionar plantillas Word de forma independiente de la causal**, clasificadas por **tipo de documento**, y al desvincular **elegir qué cartas generar** (una → archivo único; varias → ZIP), sin regenerar a ciegas el pack completo de Renuncia.

El tablero admin es un **hogar sidebar** en Gestión humana (mismo patrón que Ficha / Archivo). La generación sigue anclada al **período cerrado** de ficha, pero el filtro del modal pasa de causal → **tipo**.

Quedan por cerrar con el usuario: etiqueta del tablero, modelo de permisos, migración de las 3 plantillas actuales, reglas de persistencia del paquete, alcance de tipos en v1 y edición de metadatos al administrar plantillas.

---

## Hallazgos técnicos relevantes

| Hallazgo | Detalle | Por qué importa |
| --- | --- | --- |
| Modelo amarrado a causal | `TerminationLetterDocumentTemplate` usa `termination_cause_code` | FEAT-029 implica cambio de modelo/datos (tipo documento) |
| Generación gated por causal | Solo períodos con causa en `termination_letter_supported_causes` | Decisión 3 sugiere abrir generación a **cualquier** desvinculación con plantillas de ese tipo |
| Pack fijo required | Generador toma todos `is_required` del causal | Selección 1/N en modal reemplaza el concepto de “pack obligatorio” en runtime |
| UI Regenerar | Confirm + POST generate sobrescribe ZIP | Decisión 5: unificar en Generar (modal) + Descargar último |
| Shared-files | Nuevo board en `config/access.php` + sidebar | Flag `shared-files` ya en `docs/TASKS.md` |
| Placeholder/firmante | Config actual reutilizable | Probablemente sin cambio de negocio en v1 |

---

## Preguntas abiertas

Responde cada punto para cerrar el brief (lenguaje de negocio):

### 1. Nombre visible del tablero

¿Cómo debe llamarse la entrada en el **sidebar** de Gestión humana?

Ejemplos orientativos (elige o propone otro):

- [ ] Plantillas Word
- [ ] Cartas Word
- [ ] Plantillas de documentos
- [ ] Otro: _______________

### 2. Quién administra y quién genera (permisos)

Hoy: administrar plantillas = `ficha_empleados.manage`; generar/descargar cartas = `ficha_empleados.terminate`.

Para el **tablero nuevo** y el **modal**:

- ¿Quién puede **ver y administrar** el tablero (agregar / reemplazar / eliminar)?
  - [ ] Los mismos que hoy tienen “Agregar a ficha” (`manage`)
  - [ ] Solo super-admin / administrador
  - [ ] Permiso **nuevo** dedicado (indique a qué roles)
- ¿Quién puede **Generar cartas** / **Descargar** desde la ficha del empleado?
  - [ ] Sigue siendo solo quienes desvinculan (`terminate`)
  - [ ] También quienes gestionan ficha (`manage`)
  - [ ] Otro: _______________

### 3. Migrar las 3 plantillas actuales de Renuncia

En producción/local ya pueden existir las 3 plantillas del pack RENUNCIA (aceptación, autorización examen de retiro, certificado laboral).

Al pasar a “tipo de documento”:

- [ ] **Sí:** migrarlas automáticamente a tipo **desvinculacion** (conservar archivos y etiquetas)
- [ ] **No:** dejarlas como estén / cargar de nuevo a mano en el tablero
- [ ] Otro: _______________

### 4. Guardar siempre el resultado para el botón “Descargar”

Tras **Generar cartas** (1 documento → `.docx`, varios → `.zip`):

- ¿Se debe **guardar siempre** ese archivo en el vínculo/período del empleado para que **Descargar** lo recupere después?
  - [ ] Sí, siempre (y **reemplaza** el paquete anterior del mismo período)
  - [ ] Solo cuando se generan **varios** (ZIP); un solo `.docx` no se guarda
  - [ ] No persistir; Descargar solo si ya había algo de antes / otro criterio: _______________

### 5. Tipos de documento en la primera entrega (v1)

- ¿En v1 el tipo **desvinculacion** es el **único** usable (contratación y otros solo como espacio futuro)?
  - [ ] Solo **desvinculacion** en v1
  - [ ] Incluir ya otros tipos (liste cuáles: _______________)
- ¿Las etiquetas de tipo son una **lista fija del sistema** o un **catálogo editable** por el admin?
  - [ ] Lista fija (config/código)
  - [ ] Catálogo editable en pantalla
  - [ ] Fija en v1; editable después

### 6. Al agregar o reemplazar una plantilla: ¿se edita etiqueta y tipo?

Además del archivo `.docx`:

- Al **agregar**: ¿obligatorio capturar **etiqueta** (nombre visible) y **tipo de documento**?
  - [ ] Sí, ambos
  - [ ] Solo archivo; etiqueta/tipo se definen de otra forma: _______________
- Al **reemplazar** el archivo del mismo registro:
  - [ ] Solo cambia el `.docx` (etiqueta y tipo se mantienen)
  - [ ] También se pueden editar etiqueta y/o tipo en ese momento
- Al **eliminar**: ¿pide confirmación (“¿Eliminar esta plantilla?”)?
  - [ ] Sí
  - [ ] No

### 7. Catálogos de Ficha (Causal desvinculación)

Hoy se suben plantillas dentro de **Catálogos → Causal desvinculación**.

¿Confirmamos que en v1 **se quita** esa administración de plantillas del catálogo Causal (pasa solo al tablero nuevo)?

- [ ] Sí, retirar del catálogo Causal
- [ ] Dejar ambas pantallas un tiempo
- [ ] Otro: _______________

### 8. Generación para cualquier causal de desvinculación

Con el filtro por **tipo** (no por causal): ¿el botón **Generar cartas** debe aparecer en **todo vínculo cerrado** (Renuncia, Justa causa, etc.), siempre que existan plantillas de tipo desvinculacion?

- [ ] Sí, para **todas** las causales de desvinculación
- [ ] Solo Renuncia (como hoy), aunque el modal liste por tipo
- [ ] Otro: _______________

### 9. Documentación / procedimiento operativo

¿Existe un procedimiento interno de GH (qué cartas se entregan por tipo de salida) que debamos reflejar en la guía de usuario, o basta documentar el uso de la pantalla?

- [ ] Solo uso de pantallas (tablero + modal)
- [ ] Hay procedimiento escrito a alinear: _______________

---

## Fuera de alcance (propuesta analista — confirmar en respuestas o implícito)

- Editor WYSIWYG / edición del contenido Word dentro de la app.
- Conversión automática Excel → Word.
- Envío por correo de las cartas.
- Generación masiva de cartas para muchos empleados a la vez.
- Tipos **contratacion** (u otros) operativos, salvo que la pregunta 5 los incluya en v1.
- Cambiar el motor de placeholders / firmante GH (salvo necesidad detectada por Arquitecto).

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Nombre sidebar: **Plantillas Word** | Etiqueta no coincide con lenguaje interno de GH |
| 2 | Admin tablero = `ficha_empleados.manage`; generar/descargar = `ficha_empleados.terminate` (sin permiso nuevo) | Quien debía administrar no ve el tablero, o demasiada gente genera cartas |
| 3 | Migrar las 3 plantillas RENUNCIA a tipo **desvinculacion** conservando archivos | Plantillas “desaparecen” o hay que re-subirlas |
| 4 | Persistencia **siempre** (docx o zip) en el período; Generar sobrescribe el archivo anterior | No hay “Descargar” útil tras un solo docx, o se pierde el ZIP previo sin querer |
| 5 | v1: solo tipo **desvinculacion**; lista de tipos **fija** en sistema | Esperaban catálogo editable o contratación en la misma entrega |
| 6 | Al agregar: etiqueta + tipo + archivo; al reemplazar: **solo archivo**; eliminar con confirmación | No pueden corregir nombre/tipo sin borrar y volver a crear |
| 7 | Se **retira** la UI de plantillas del catálogo Causal | Confusión con dos sitios de administración |
| 8 | Generar disponible en **cualquier** causal de desvinculación (período cerrado) | Cartas generadas donde el negocio no las quería, o bloqueo indebido en Renuncia sola |
| 9 | Selección modal: mínimo **1** plantilla; 1 → `.docx`, 2+ → `.zip` | Expectativa de ZIP siempre o de pack mínimo obligatorio |
| 10 | Sin correo / sin editor Word / sin generación masiva en v1 | Alcance se infla a mitad de implementación |

---

## Borrador preliminar (NO enviar al Arquitecto hasta cerrar preguntas)

> `BORRADOR — pendiente respuestas usuario y Arquitecto`

| Campo | Valor provisional |
| --- | --- |
| ID | FEAT-029 |
| Módulo / área | Gestión humana — tablero nuevo (plantillas Word) + evolución cartas en Ficha empleados |
| Título | Tablero plantillas Word (tipo documento) + modal generar cartas (1/N → docx/zip) |
| Objetivo | Administrar plantillas Word por tipo de documento y generar cartas de desvinculación eligiendo 1 o N plantillas |
| Incluye (tentativo) | Sidebar board; CRUD plantillas (alta/reemplazo/baja); tipo documento; modal selección; descarga docx/zip; persistencia en período; quitar Regenerar; retirar admin de Catálogos Causal; migración datos RENUNCIA→tipo; tests; docs |
| Fuera (tentativo) | Editor Word, correo, generación masiva, tipos distintos de desvinculacion en v1 (salvo respuesta) |
| Permisos (tentativo) | Ver/admin tablero: `manage`; generar/descargar: `terminate` — a confirmar |
| Shared-files | `config/access.php`, navegación sidebar, rutas GH, posible `config/employee_ficha.php` |

### Criterios de aceptación (borrador)

1. Existe entrada de sidebar que abre el tablero de plantillas (lista única con tipo de documento).
2. Se pueden agregar, reemplazar `.docx` y eliminar plantillas según reglas confirmadas.
3. Desde ficha (vínculo cerrado), **Generar cartas** abre modal con solo plantillas tipo desvinculacion; 1 descarga `.docx`, N descarga `.zip`.
4. **Descargar** entrega el último paquete persistido del período cuando existe; no hay botón **Regenerar** separado.
5. Las plantillas dejan de administrarse (o dejan de ser la fuente) en Catálogos → Causal, según decisión 7.
6. Las 3 plantillas históricas de Renuncia quedan operativas bajo tipo desvinculacion si se confirma migración.
7. Audit + pruebas PHPUnit del flujo admin y del flujo generar/descargar.

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

(2026-08-21 — chat AgentSj)

1. **Nombre sidebar:** Plantillas Word.
2. **Permisos:** Admin del tablero = **permiso nuevo**; Generar/Descargar = solo `ficha_empleados.terminate`.
3. **Migración RENUNCIA:** No migrar; **volver a subir a mano** en el tablero nuevo.
4. **Persistencia:** Guardar **siempre** el archivo en el período (docx o zip) y **reemplazar** el anterior para “Descargar”.
5. **Tipos v1:** **Catálogo editable** de tipos de documento (no lista fija solamente en código).
6. **Metadatos:** Al **agregar**: sí (etiqueta + tipo + archivo). Al **reemplazar**: **solo** el `.docx` (etiqueta/tipo se mantienen). Al **eliminar**: **sí** confirmación.
7. **Catálogos Causal:** **Sí**, retirar admin de plantillas; solo tablero nuevo.
8. **Generar:** En **todas** las causales (vínculo cerrado), con plantillas tipo desvinculación.
9. **Doc usuario:** Por el momento **solo uso de pantallas** (tablero + modal); sin procedimiento GH escrito adicional.
