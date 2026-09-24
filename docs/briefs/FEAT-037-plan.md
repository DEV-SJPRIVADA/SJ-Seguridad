# Plan de orquestacion — FEAT-037

> Generado por AgentSj tras Feature Brief final. Ver [`FEAT-037.md`](FEAT-037.md).

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-037 |
| Modo | orquestado |
| Rama Git | (rama actual / Manuel-E — no forzar rama nueva salvo pedido) |
| Modulo principal | acreditaciones (GH) — pestaña Reporte Diario |
| Run log | `docs/runs/FEAT-037-run-log.md` |
| Brief | `docs/briefs/FEAT-037.md` |
| shared-files | `routes/areas/gestion_humana.php` (sí); `config/access.php` (**no**); `app.css` solo si estilos mínimos |

## Secuencia de tareas

| # | Agente | Descripcion | Depende de | Estado |
| --- | --- | --- | --- | --- |
| 1 | Analista | Cerrar vacíos / preguntas usuario | — | OK (Pausa resuelta) |
| 2 | Arquitecto | Feature Brief final | 1 | OK |
| 3 | AgentSj | Plan orquestación + pausa post-Brief | 2 | OK / Pausa confirmación |
| 4 | Feature | **T1 — Vertical slice Reporte Diario APO** | 3 (OK usuario) | OK |
| 5 | Revisor | Review del diff completo FEAT-037 | 4 | OK (Aprobado con observaciones) |
| 6 | Documentador | `docs/modules/acreditaciones.md` + `docs/user/acreditaciones.md` | 5 | OK |
| 7 | AgentSj | Checklist cierre → Completadas | 6 | OK |

## Task Card — T1 (Agente Feature)

**ID:** FEAT-037 / T1  
**Titulo:** Vertical slice — Reporte Diario APO (carga + histórico + listado + export)  
**Modulo:** acreditaciones  
**Brief:** `docs/briefs/FEAT-037.md` (ley — no reabrir decisiones 1–10)

### Entregar (una sola Task Card / un solo Agente Feature)

1. **Migraciones aditivas** `acreditacion_reporte_diario_cargas` + `acreditacion_reporte_diario_filas` (esquema exacto del brief). Solo `php artisan migrate` (nunca fresh/wipe).
2. **Models** + factories + relaciones (carga hasMany filas; filas belongTo carga/users).
3. **Services:** `AcreditacionReporteDiarioImportService`, `…ListService`, `…DatatableService`.
4. **Form Request** import (fecha ≤ hoy, ≥1 file, `confirm_replace`, validación previa headers de **todos** los archivos antes de mutar).
5. **Controller** métodos `reporteDiario*` en `AcreditacionesController` (reemplazar placeholder).
6. **Rutas** en `routes/areas/gestion_humana.php` según brief (datatable, export, import, import-report, cargas).
7. **Vista** `reporte-diario` (o sustituir uso de placeholder): filtros fecha/origen/búsqueda, DT server-side, export, modal carga (1–2 files), confirm replace, botón listado cargas, import-result modal.
8. **Audit** resumen import/replace vía `AcreditacionesAuditLogService`.
9. **Tests PHPUnit** mínimos del brief (parcial, replace, fecha futura, headers, Ficha no bloquea, permisos view vs edit).
10. **Pint** dirty + tests afectados en verde.
11. **No** tocar: `config/access.php`, Dashboard/Validaciones/Export Apo, `acreditacion_acreditados`, `AcreditacionEstadoCalculator`, Select2, excelHtml5.

### Criterios de aceptación (smoke)

- View ve listado default hoy + export; no ve cargar.
- Edit carga 1 archivo → solo ese origen; segundo archivo otro día/misma fecha conserva el otro.
- Fecha futura rechazada.
- Replace sin `confirm_replace` rechazado si origen ya tiene datos; con confirm OK.
- Filas sin IdNum → reporte fallos; carga del resto OK.
- Sin Ficha no bloquea.

### shared-files

- `routes/areas/gestion_humana.php` — serializar (único Feature en este módulo).
- `resources/css/app.css` — solo si necesario; no inventar familias CSS por módulo (icon-btn / filtros existentes).

## Paralelismo

Ninguno. Un solo Feature en el módulo acreditaciones.

## Puntos de pausa usuario

- Post-Analista: **resuelto** (respuestas 1–8).
- **Post-Brief: confirmación de alcance** ← **ahora**.
- Post-Revisor: blockers críticos.

## Conflictos detectados

| Archivo | Tarea en conflicto | Resolucion |
| --- | --- | --- |
| `routes/areas/gestion_humana.php` | Solo T1 Feature | Un agente; no paralelo con otras features GH |
| `AcreditacionesController.php` | Solo T1 | Extender métodos; no controller paralelo |

## Riesgos a vigilar en Feature / Revisor

1. Validar **todos** los Excel antes de mutar (abort total si headers rotos en uno).
2. Replace parcial: no pisar metadata/filas del origen no enviado.
3. Volumen ~2000 filas → DT server-side + inserts en chunk.
4. Sin unique de IdNum en filas (duplicados APO se guardan).
5. Prohibido `migrate:fresh`.
