# Preguntas del Analista — FEAT-038

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** negocio base y 6 decisiones cerradas por el usuario (chat previo). **8 preguntas abiertas** (vacíos reales). **Pendiente respuesta usuario** → AgentSj debe **PAUSAR** antes de Arquitecto. **No** dar por cerrado el brief final.

---

## Contexto recibido

**Feature ID:** FEAT-038  
**Origen:** `@agent-sj` Validaciones Acreditaciones (cruces Ficha / Acreditados / Reporte Diario) — 2026-09-25  
**Módulo:** Acreditaciones (`acreditaciones`) — área Gestión Humana  
**Pestaña:** **Validaciones** (hoy placeholder FEAT-036; ruta `GET /gestion-humana/acreditaciones/validaciones`)  
**Run log:** [`docs/runs/FEAT-038-run-log.md`](../runs/FEAT-038-run-log.md)  
**Briefs base:** [`FEAT-036.md`](FEAT-036.md) (estados, Ficha, Acreditados), [`FEAT-037.md`](FEAT-037.md) (Reporte Diario snapshot independiente)  
**Doc técnica:** [`docs/modules/acreditaciones.md`](../modules/acreditaciones.md)  
**Doc usuario:** [`docs/user/acreditaciones.md`](../user/acreditaciones.md)

### Fuentes de datos (referencia, sin inventar campos)

| Fuente | Dónde | Uso en Validaciones |
| --- | --- | --- |
| Ficha activa | `employee_ficha_profiles.employment_status = activo` | Cola: activos sin ninguna acreditación |
| Acreditados | `acreditacion_acreditados` (unique cédula + `cargo_apo`; estados calculados; `cargo` texto + `cargo_apo`) | Colas: ausentes del reporte; EN_PROCESO vs ACREDITADO APO; vencidas |
| Reporte Diario | `acreditacion_reporte_diario_cargas` + `…_filas` (`origen` PROCESO\|ACREDITADO; `document_number`; `cargo` texto; `estado_apo`; `vigencia_acr`) | Snapshot del día elegido; **sin** cruce Ficha en FEAT-037 |

### Decisiones YA CERRADAS (ley — no repreguntar)

| # | Decisión |
| --- | --- |
| 1 | **Ficha activa sin acreditación:** empleado activo en Ficha cuya cédula **no tiene ninguna** fila en Acreditados. |
| 2 | **Acreditados ausentes del reporte del día:** match **cédula + cargo**; ausente si ese par **no** aparece en **ningún origen** del día (ni En proceso ni Acreditado APO). |
| 3 | **EN_PROCESO en Acreditados y ya ACREDITADO en reporte del día:** match **cédula + cargo**. |
| 4 | **UI / cuándo correr:** selector de fecha (default **hoy**). Si **no hay carga** esa fecha → mensaje + ir a Reporte Diario; **sin** ejecutar. Si hay carga → botón **Ejecutar validaciones** (**no** auto al entrar). |
| 5 | Incluye **acciones** (no solo listado/export): p. ej. abrir Ficha, editar Acreditado. |
| 6 | Incluye cola de acreditaciones **vencidas** (DESACREDITADO / vigencia). |

### Alcance tentativo (pendiente cierre de preguntas)

- **Incluye (candidato v1):** pestaña Validaciones operativa; selector fecha; gate por existencia de carga; botón Ejecutar; colas de cruce (1–3 + vencidas); acciones; export (si se confirma); reutilizar permisos del módulo salvo que el usuario diga lo contrario.
- **Fuera tentativo:** Dashboard / Export Apo; bridge automático que escriba Acreditados desde el reporte; notificaciones correo; campos nuevos tipo “motivo” / “marcar revisado” **salvo** que el usuario los pida al responder; auto-ejecución al entrar.

### Patrones de referencia (repo)

| Aspecto | Referencia |
| --- | --- |
| Shell + placeholder Validaciones | FEAT-036 — `validaciones` → `placeholder.blade.php` |
| Snapshot APO por fecha | FEAT-037 — cargas + filas por origen |
| Estados Acreditados | `AcreditacionEstadoCalculator` (EN_PROCESO, DESACREDITADO, POR_VENCER, ACREDITADO) |
| DT / export / selectores | server-side + `BaseExport` / `<x-export-excel>`; `<x-searchable-select>`; sin Select2 |
| Permisos actuales | `acreditaciones.view` (consulta/export) · `acreditaciones.edit` (mutaciones) · board GH |
| Datos | Solo migraciones aditivas si hiciera falta persistir; **sin** `migrate:fresh` / wipe |

---

## Resumen de lo entendido

Gestión Humana necesita **operativizar la pestaña Validaciones** del tablero Acreditaciones: elegir una **fecha de reporte**, y solo si ese día ya tiene **carga** en Reporte Diario, pulsar **Ejecutar validaciones** para obtener colas operativas que crucen:

1. Activos en Ficha **sin** ninguna fila en Acreditados.
2. Pares cédula+cargo en Acreditados que **no** aparecen en el snapshot del día (ningún origen).
3. Acreditados en **EN_PROCESO** que en el reporte del día ya figuran como **ACREDITADO** (mismo match cédula+cargo).
4. Cola de acreditaciones **vencidas** (alcance exacto pendiente: solo DESACREDITADO vs también POR_VENCER).

La UI debe permitir **actuar** sobre los hallazgos (abrir Ficha / editar Acreditado como ejemplos), no solo mirar o exportar. El Reporte Diario sigue siendo snapshot histórico independiente; Validaciones es el lugar del cruce.

### Riesgos principales (antes de cerrar brief)

| Riesgo | Por qué importa |
| --- | --- |
| Match de **cargo** ambiguo | Tres textos distintos: `cargo` y `cargo_apo` del Acreditado vs `cargo` del Excel APO. Mal algoritmo → falsos ausentes / falsos “ya acreditado”. |
| “Hay carga” mal definido | FEAT-037 permite cargar **solo un origen**. Si basta cabecera o un origen, las colas 2–3 pueden ser engañosas. |
| Resultados solo en pantalla vs histórico | Sin persistir, no hay auditoría de “quién validó qué día”; con persistir, hay tablas/volumen/permisos nuevos. |
| Acciones que mutan sin permiso claro | Abrir Ficha (lectura) vs editar Acreditado (escritura) mezclados en la misma cola. |
| Cola “vencidas” demasiado ancha | Incluir POR_VENCER mezcla “ya venció” con “vence pronto”. |
| Varios CARGO APO por cédula | Sin regla “una fila por par”, la UI y el export se duplican o se pierden casos. |

---

## Preguntas abiertas

Responde cada punto para cerrar el brief (máx. ~8). Lenguaje de negocio. **No** inventar campos (motivo, marcar revisado, etc.) salvo que usted los pida aquí.

### 1. ¿Cómo se “acercan” los cargos al cruzar?

En las reglas 2 y 3 el match es **cédula + cargo**. En el sistema hay textos distintos:

- en **Acreditados:** campo **CARGO** (texto libre) y **CARGO APO** (tipo del catálogo);
- en **Reporte Diario:** columna **Cargo** del Excel APO (texto libre, sin catálogo).

Para decir que “es el mismo cargo”:

- **A)** Igualdad tras normalizar (quitar espacios extremos, mayúsculas/minúsculas, y sin tildes). ¿Sobre qué campo del Acreditado: solo CARGO, solo CARGO APO, o ambos (coincide si cualquiera iguala al Cargo APO)?
- **B)** Además de A, aceptar si uno **contiene** al otro (ej. “Vigilante” dentro de “Vigilante de seguridad”).
- **C)** Otra regla (describa con un ejemplo real de sus Excel).

### 2. ¿Los resultados solo se ven al ejecutar, o se guarda histórico de corridas?

Tras pulsar **Ejecutar validaciones** para una fecha:

- **A)** Solo en pantalla (y export de esa corrida si aplica). Si se sale o se vuelve a entrar, hay que ejecutar de nuevo. No se guarda historial de corridas.
- **B)** Se **guarda** un histórico por fecha (quién ejecutó, cuándo, resumen / detalle de colas) para consultarlo después.
- **C)** Otro (describa).

### 3. ¿Quién puede ejecutar, exportar y usar acciones?

Hoy: consulta = `acreditaciones.view`; operativo = `acreditaciones.edit`.

Para Validaciones en v1:

| Acción | ¿Quién? (view / edit / otro) |
| --- | --- |
| Ver la pestaña y el mensaje “sin carga” | |
| Pulsar **Ejecutar validaciones** | |
| Exportar resultados (si hay export) | |
| Abrir Ficha / ir a detalle (lectura) | |
| Editar Acreditado u otras acciones que cambien datos | |

¿Se reutilizan los mismos permisos del módulo **sin permiso nuevo**?

### 4. Lista concreta de **acciones v1** por cola

Indique, por cada cola, qué botones/enlaces quiere en la primera versión (links que navegan vs botones que cambian datos). Ejemplos a confirmar o descartar:

| Cola | Acciones deseadas en v1 |
| --- | --- |
| Ficha activa sin acreditación | ¿Abrir Ficha? ¿Ir a “Nuevo acreditado” con cédula precargada? ¿Solo ver? |
| Acreditado ausente del reporte del día | ¿Editar acreditado? ¿Abrir Ficha? ¿Solo ver? |
| EN_PROCESO en sistema y ACREDITADO en reporte | ¿Editar acreditado (quitar solicitud / poner vigencia)? ¿Solo ver + export? |
| Vencidas | ¿Editar acreditado? ¿Abrir Ficha? |

**No** asumir “marcar como revisado”, motivo ni estado de cola salvo que lo pida explícitamente.

### 5. Alcance de la cola “vencidas”

La decisión 6 habla de DESACREDITADO / vigencia. En v1 la cola debe incluir:

- **A)** Solo registros con estado **DESACREDITADO** (vigencia ya vencida, sin solicitud que los deje EN_PROCESO).
- **B)** DESACREDITADO **y** **POR_VENCER** (ventana de 21 días).
- **C)** Otra definición (ej. filtrar por fecha de vigencia ≤ hoy aunque el estado sea EN_PROCESO por solicitud).

### 6. Si una cédula tiene varios CARGO APO, ¿una fila por par?

En las reglas 2 y 3 (y en vencidas si aplica por registro):

- **A)** **Sí:** una fila de resultado por cada par cédula + cargo (como en Acreditados).
- **B)** Agrupar por cédula (una fila con varios cargos).
- **C)** Otro.

### 7. ¿Qué cuenta como “hay carga” ese día?

FEAT-037 permite subir **solo En proceso**, **solo Acreditado APO**, o ambos.

Para habilitar **Ejecutar validaciones**:

- **A)** Basta con que exista la cabecera del día **y al menos un origen** con datos (filas o marca de carga).
- **B)** Exigir **ambos** orígenes cargados ese día; si falta uno → mensaje (¿cuál?) y no ejecutar.
- **C)** Otro.

### 8. ¿Export Excel de los resultados en v1?

- **A)** Sí: exportar las colas de la corrida (¿un Excel con varias hojas, o un export por cola?).
- **B)** No en v1: solo pantalla + acciones.
- **C)** Otro.

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Match cargo = igualdad normalizada (trim + mayúsculas + sin tildes) comparando **CARGO** del Acreditado con **Cargo** del Excel; `cargo_apo` no participa en el cruce de texto. | Falsos negativos si el negocio compara por CARGO APO o usa “contiene”. |
| 2 | Resultados **solo en pantalla** (+ export opcional de la corrida); sin tabla de histórico de validaciones en v1. | No hay rastro de corridas ni reconsulta sin re-ejecutar. |
| 3 | Sin permiso nuevo: ver/ejecutar/export = `view`; acciones que mutan = `edit` (mismo patrón Reporte Diario / Acreditados). | Operativos sin edit no podrían corregir desde la cola; o se bloquea de más. |
| 4 | Acciones v1 mínimas: enlace Abrir Ficha + Editar acreditado (modal/listado existente) donde haya `acreditado_id`; sin “marcar revisado”. | Falta flujo “nuevo acreditado desde Ficha” u otras acciones pedidas. |
| 5 | Cola vencidas = solo **DESACREDITADO**. | Se omiten POR_VENCER que el negocio quería en la misma cola. |
| 6 | Una fila por par cédula+cargo (y por registro de acreditado en vencidas). | UI distinta a lo esperado si querían agregar por persona. |
| 7 | “Hay carga” = cabecera del día con **≥1 origen** cargado. | Ejecutar con un solo origen puede listar “ausentes” engañosos respecto al origen no subido. |
| 8 | Export Excel de resultados **sí** en v1 (una descarga por cola o consolidado — Arquitecto define formato tras respuesta). | Alcance de export incorrecto. |

> Estos supuestos **no** cierran el brief; son red de seguridad para el Arquitecto solo si el usuario autoriza avanzar sin responder algún punto.

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario → PAUSA

## Respuestas del usuario

> Registradas por AgentSj 2026-09-25 tras pausa Analista.

| # | Respuesta | Interpretación para Brief |
| --- | --- | --- |
| 1 | **CARGO APO** | El lado Acreditados del match cédula+cargo usa **`cargo_apo`** (no el CARGO texto libre). Comparación con el **Cargo** del Excel APO del reporte (normalización a definir por Arquitecto: igualdad normalizada recomendada; sin “contiene” salvo que el Brief lo justifique). |
| 2 | **Solo resultados en pantalla** al ejecutar | Opción A: sin histórico de corridas / sin tablas de persistencia de validaciones en v1. Re-ejecutar al volver a entrar. |
| 3 | **edit** | Toda la pestaña operativa (ver gate, ejecutar, exportar, acciones) exige `acreditaciones.edit`. Sin permiso nuevo. Quien solo tiene `view` no opera Validaciones (o solo ve placeholder/mensaje — Arquitecto decide UX mínima). |
| 4 | **Sí** | Confirma acciones v1 de navegación/edición: Abrir Ficha; Editar acreditado; y donde aplique (cola sin acreditación) ir a alta de acreditado con cédula precargada. **Sin** “marcar revisado” ni motivo. |
| 5 | **Las 2** | Cola vencidas = **DESACREDITADO** y **POR_VENCER**. |
| 6 | **Cédula+cargo** | Una fila de resultado por par (como en Acreditados). |
| 7 | **Deben estar ambos** | Gate “hay carga”: ese día deben existir **ambos** orígenes (En proceso **y** Acreditado APO) cargados; si falta uno → no ejecutar + mensaje claro. |
| 8 | **Sí, por cola y consolidado** | Export Excel v1: descarga **por cola** y también **consolidado** (todas las colas). Formato (hojas vs archivos) lo define el Arquitecto. |
