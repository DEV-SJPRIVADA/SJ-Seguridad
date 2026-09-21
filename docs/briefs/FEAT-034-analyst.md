# Preguntas del Analista — FEAT-034

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

**FEAT-034 — Cola Pendientes en Cursos (icono + contador como Ficha)**

Se necesita una cola operativa en el tablero **Cursos** (Gestion Humana) que avise cuántas personas **activas en ficha** aún no tienen ningún registro de curso, con el mismo patrón visual de Ficha empleados: **icono + contador** que abre el listado de esa cola.

**Reglas ya confirmadas (no se repreguntan):**

1. **Reingreso:** si la persona **ya tuvo cursos** en el sistema, **no** vuelve a Pendientes.
2. Botón **«No aplica / omitir»:** sí, para sacar de la cola **sin** crear curso.
3. Solo empleados con `employment_status = activo`.
4. Sale de la cola con el **primer** curso creado (alta individual o import) **o** al omitir.
5. Solo **ingresos nuevos** a la empresa; **no** encolar por cursos vencidos / por renovar (eso sigue en filtros y dashboard de vigencia actuales).
6. UI: icono con contador **igual** al de Pendientes en Ficha empleados.

**Criterio técnico tentativo (para Arquitecto):** cola derivada = activos en ficha cuya cédula no tiene filas en `employee_cursos` **y** no están marcados como omitidos; omitir requiere mecanismo persistente (flag/tabla) para no reaparecer.

---

## Resumen de lo entendido

| Aspecto | Entendido |
| --- | --- |
| Problema | Personas nuevas en ficha sin ningún curso pasan desapercibidas; la vigencia/vencidos no cubre “cero cursos”. |
| Quién | Usuarios del tablero Cursos (GH). |
| Señal UI | Icono + contador (patrón Ficha). |
| Entrada a cola | Activo en ficha, sin filas en `employee_cursos`, y no omitido; enfoque “ingreso nuevo”, no renovaciones. |
| Salida | Primer curso **o** omitir. |
| Reingreso con historial de cursos | No reencola. |
| Fuera | No mezclar con ACTUALIZAR/VENCIDO; no cambiar el dashboard de vigencia. |

---

## Preguntas abiertas

1. **Ubicación del icono + contador**  
   ¿Dónde debe verse y abrirse la cola?  
   - **A)** Solo en la pestaña **Cursos** (listado / Registros), en la barra de acciones/filtros (análogo a Ficha).  
   - **B)** También en **Dashboard**.  
   - **C)** En **todas** las pestañas del tablero Cursos.  

2. **Nombre visible (evitar confusión)**  
   En Cursos ya existe el estado de trámite **PENDIENTE** y un KPI «Pendientes».  
   ¿Cómo debe llamarse esta cola en pantalla? Ejemplos: **«Sin curso»**, **«Pendientes de curso»**, **«Nuevos sin curso»**, u otra etiqueta exacta.

3. **Al omitir («No aplica»)**  
   ¿Qué pide el flujo al usuario?  
   - **A)** Solo confirmación (Sí / No), **sin** motivo.  
   - **B)** Motivo **obligatorio** (texto).  
   - **C)** Motivo **opcional**.  
   Además: ¿el omitir es **definitivo** (no hay botón “volver a cola”), o debe poder **deshacerse** desde la UI?

4. **Quién ve / quién omite**  
   Con los permisos actuales (`cursos.view` / `cursos.edit`):  
   - ¿Quién **solo consulta** puede **ver** el icono, el contador y el listado de la cola?  
   - ¿Quién puede pulsar **omitir**: solo `cursos.edit` (y bypass admin), o también consulta?  
   - ¿Hace falta **permiso nuevo**, o reutilizar los existentes?

5. **Arranque / inventario actual**  
   El día que se estrene la función, ¿los empleados **activos que hoy ya están en ficha y no tienen ningún curso** deben aparecer en la cola (inventario actual), o **solo** quienes entren a ficha **a partir de** la puesta en marcha?

---

## Supuestos temporales

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | El icono vive en **Registros (Cursos)**; Dashboard solo si el usuario pide B/C. | Icono “invisible” si el trabajo diario empieza en Dashboard. |
| 2 | Etiqueta provisional **«Sin curso»** para no chocar con estado `PENDIENTE`. | Confusión operativa si el negocio espera “Pendientes”. |
| 3 | Omitir: confirmación simple **sin** motivo; **sin** UI de deshacer (queda rastro en auditoría si aplica). | Faltará trazabilidad o se pedirá “revertir” después. |
| 4 | Ver cola con `cursos.view`; omitir / crear primer curso con `cursos.edit`; **sin** permiso nuevo. | Perfiles de solo lectura podrían omitir o no ver la cola según expectativa. |
| 5 | Al go-live **sí** entran en cola los activos actuales sin ningún curso (criterio derivado). | Cola saturada el primer día si el negocio quería solo ingresos futuros. |
| 6 | Crear el **primer** curso (UI o import) saca de la cola; borrar el último curso **no** reencola a quien ya tuvo historial / fue omitido (alineado a reingreso). | Casos borde “borré el único curso” podrían querer reaparecer. |
| 7 | Fuera de alcance: notificaciones por correo, export Excel dedicado de la cola, cambios al KPI/gráficos de vigencia. | Si el negocio esperaba alerta o Excel, habrá hueco en V1. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

| # | Respuesta (2026-09-21) |
| --- | --- |
| 1 | **A)** Solo pestaña **Cursos (Registros)**. |
| 2 | Etiqueta **«Nuevos sin curso»**; en UI ideal **solo icono**, texto en **tooltip** al pasar el mouse. |
| 3 | **C)** Motivo **opcional** al omitir. *(Deshacer omitir: no indicado → supuesto: sin UI de deshacer en V1.)* |
| 4 | Solo quien tenga **`cursos.edit`** (ver cola, contador, omitir y agregar curso). Sin permiso nuevo. |
| 5 | **Solo ingresos nuevos al contratar** a partir del go-live; **no** inventariar el stock actual de activos sin curso. |

## Acción para AgentSj

Respuestas recibidas → lanzar **Arquitecto** para `docs/briefs/FEAT-034.md`.

