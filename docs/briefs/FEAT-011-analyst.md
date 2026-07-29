# Preguntas del Analista — FEAT-011

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

**Feature ID:** FEAT-011  
**Solicitud:** En el tablero de Requisiciones de **Gestión humana** → **Parámetros** → categoría **Encargados de selección**, dejar de mantener un catálogo independiente (`requisition_recruiters`: nombre + activo/inactivo creado a mano). En su lugar:

- Listar **todos los usuarios del sistema cuya área base sea Gestión humana** (`area_key` = `gestion_humana` en `config/access.php`).
- Mediante **activación** (patrón esperado similar a Operaciones → Ajustes → **Capturadores**: toggle sobre usuarios del área), definir quién puede aparecer en el campo **Reclutador** al **editar/gestionar** una requisición (pestaña Gestión).

**Estado técnico hoy (repo):**

| Aspecto | Comportamiento actual |
| --- | --- |
| Parámetros | `recruiters` en `PARAMETER_TYPES` → CRUD genérico nombre + `is_active` sobre `RequisitionRecruiter` |
| Formulario Gestión | Select `recruiter_id` alimentado solo con reclutadores **activos** del catálogo |
| Persistencia | `personal_requisitions.recruiter_id` → FK a `requisition_recruiters.id` |
| Validación | `Rule::exists('requisition_recruiters', 'id')` |
| Legacy | Campo texto `recruiter_name` convive; export usa `recruiter?->name ?? recruiter_name` |
| Alcance GH | Operadores con `requisitions.tab.gestion` gestionan requisiciones de **cualquier área solicitante**; el catálogo de reclutadores es **global** (no filtrado por `{module}` en URL) |
| Referencia UX | `IndicatorCaptureAccessService` + `capturadores.blade.php`: usuarios activos del área + toggle que otorga/revoca permiso Spatie |

**Usuarios impactados (borrador):** personal de GH que administra parámetros (`manage.requisition.parameters`) y quienes gestionan solicitudes (`requisitions.tab.gestion`).

---

## Entendimiento del analista (resumen)

Se busca **alinear el reclutador con identidad real de usuario** (cuenta GH) en lugar de nombres libres duplicados, reduciendo mantenimiento del catálogo y errores de tipeo. La **fuente maestra de candidatos** pasa a ser el padrón de usuarios con área base GH; la **habilitación** para el select **Reclutador** sería configurable en Parámetros (toggle), análogo a capturadores en Operaciones. Implica decisión de **modelo de datos** (reapuntar `recruiter_id` a `users` vs otro mecanismo), **migración** de filas históricas del catálogo y de FKs existentes, y posible **permiso Spatie** o columna dedicada. No está definido si la UI sigue siendo la tarjeta genérica de Parámetros o una subsección dedicada.

---

## Preguntas abiertas

Responde cada punto para cerrar el brief (prioridad negocio):

1. **Datos históricos del catálogo actual**  
   Los encargados que hoy existen solo como **nombre** en `requisition_recruiters`, ¿deben **vincularse automáticamente** a un usuario de GH cuando el **nombre coincida** (total o parcial)? Si **no hay usuario** que coincida, ¿qué debe verse en requisiciones ya guardadas con ese reclutador (dejar referencia antigua, limpiar campo, otro)?

2. **Qué guarda la requisición al elegir Reclutador**  
   ¿El negocio espera que el sistema guarde **directamente al usuario** (equivalente a hoy guardar un ítem del catálogo, pero con `users.id`), o prefiere **no cambiar** lo que se guarda en BD y solo filtrar el select según un “flag” en el usuario? *(Impacta migración de FK y trazabilidad en logs/export.)*

3. **Pantalla en Parámetros**  
   ¿Confirmas que la experiencia deseada es una **tabla de usuarios GH con interruptor Activar/Desactivar** (sin alta manual de nombres), al estilo **Capturadores** en Operaciones? ¿Debe **seguir bajo** “Tablero de Parámetros” (sustituyendo la tarjeta actual) o prefieres una **entrada/sección dedicada** “Encargados de selección” fuera del grid genérico?

4. **Quién entra en la lista de configuración**  
   ¿Solo usuarios con **cuenta activa** (`is_active`) y **área base** Gestión humana? ¿Algún rol debe **aparecer siempre** habilitado (p. ej. administrador de requisiciones) aunque su `area_key` no sea GH, o **excluido** de la lista?

5. **Criterio para el select Reclutador y bajas**  
   Para aparecer en el desplegable al gestionar: ¿basta con toggle encendido en Parámetros, o también exige cuenta activa? Si **desactivan** a alguien que ya tiene requisiciones asignadas, ¿el nombre debe **seguir mostrándose** en detalle, export e historial de cambios, aunque ya no esté disponible para **nuevas** asignaciones?

6. **Alcance por área / URL del módulo**  
   GH gestiona requisiciones de Operaciones, Comercial, etc. ¿Los encargados de selección son **siempre personal de GH** y el **mismo listado** aplica en todas las rutas de requisiciones, sin importar el `{module}` en la barra? ¿La pantalla de configuración debe existir **solo** al entrar por GH (`gestion_humana`) y no en otras áreas que tengan tab Parámetros?

7. **Campo legacy `recruiter_name` (texto libre)**  
   En registros antiguos o sin match en migración, ¿se mantiene solo como **lectura** en export/detalle, se **oculta** en formularios nuevos, o debe haber un proceso manual para **re-asignar** usuario?

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | `recruiter_id` migrará a FK nullable hacia `users.id`; se elimina tabla/modelo `requisition_recruiters` tras migración de datos | Re-trabajo de migración y logs si el negocio prefiere solo permiso Spatie sin cambiar FK |
| 2 | Activación vía **permiso Spatie nuevo** (p. ej. `requisitions.selection_officer`) otorgado/revocado con toggle, siguiendo patrón `operations.capture` | Si prefieren columna `is_selection_officer`, hay que desalinear con convención Spatie del proyecto |
| 3 | Lista de configuración: usuarios `is_active = true` y `area_key = gestion_humana`, orden por nombre | Usuarios inactivos o de otra área quedarían fuera o dentro incorrectamente |
| 4 | Match migración catálogo → usuario: **igualdad estricta** de nombre normalizado (trim, sin acentos opcional); sin match → `recruiter_id` null y conservar `recruiter_name` si existía | Pérdida de trazabilidad o asignaciones erróneas si los nombres no coinciden con cuentas reales |
| 5 | UI: subsección dedicada dentro de Parámetros GH (no CRUD genérico de `PARAMETER_TYPES['recruiters']`) | Retrabajo de navegación si insisten en tarjeta genérica o pantalla fuera de Parámetros |
| 6 | Select Reclutador: solo usuarios **activados** y cuenta activa; desactivados no aparecen para nuevas asignaciones pero relación histórica se muestra por usuario guardado | Confusión en reportes si esperan ocultar nombres al desactivar |
| 7 | Quien configura encargados: mismo permiso actual `manage.requisition.parameters` | Brecha de autorización si el negocio quiere rol distinto |

---

## Borrador preliminar (NO enviar al Arquitecto hasta cerrar preguntas)

> `BORRADOR — pendiente respuestas usuario y Arquitecto`

| Campo | Valor provisional |
| --- | --- |
| ID | FEAT-011 |
| Módulo / área | `requisitions` (tablero GH; impacto global en select Reclutador) |
| Título | Encargados de selección = usuarios GH activables (sin catálogo `requisition_recruiters`) |
| Objetivo | Vincular reclutador a usuarios reales de GH y configurar habilitación con toggles |
| Fuera de alcance (tentativo) | Cambiar reglas de compensación, estados o notificaciones de requisiciones; matriz comercial |
| Patrón de referencia | Operaciones → Capturadores (`IndicatorCaptureAccessService`) |
| Shared-files probable | `config/access.php` (permiso nuevo), rutas requisitions, posible migración `personal_requisitions` |

---

## Estado

- [x] Preguntas 1–6 respondidas
- [x] Pregunta 7 — propuesta aplicada (ver fila 7 en tabla respuestas)

## Respuestas del usuario (2026-07-28)

| # | Respuesta |
| --- | --- |
| 1 | **Dejar vacío** — no emparejar catálogo antiguo; requisiciones quedan sin `recruiter_id` tras migración |
| 2 | **`users.id`** en `recruiter_id` (cambiar FK) |
| 3 | **Sí** — tabla con toggles sustituye CRUD genérico en Parámetros |
| 4 | Solo usuarios **activos** con `area_key = gestion_humana` |
| 5 | **Sí** — historial/detalle/export conservan asignación previa; desactivados no en select para nuevas |
| 6 | **Sí** — pool único GH; configuración solo tablero GH |
| 7 | *Propuesta aceptada para implementación* | Quitar texto editable en gestión; en pantallas solo lectura/export: nombre del usuario si hay reclutador asignado, si no el texto antiguo `recruiter_name`, si no «—» |
