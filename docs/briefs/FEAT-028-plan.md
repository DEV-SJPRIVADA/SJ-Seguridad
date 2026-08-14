# Plan de orquestacion — FEAT-028

> Formulario ficha empleados completo alineado a Plantilla masivos.

## Resumen

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-028 |
| Modo | orquestado |
| Rama Git | `feat/FEAT-028-ficha-form-masivos` |
| Modulo principal | `ficha-empleados` |
| Run log | `docs/runs/FEAT-028-run-log.md` |
| shared-files | No (solo `config/employee_ficha.php` del modulo) |

## Secuencia de tareas

| # | Agente | Descripcion | Entregables | Depende de | Estado |
| --- | --- | --- | --- | --- | --- |
| 1 | Analista | Validar matriz catálogos vs plantilla real (`storage/templates/plantilla-masivos.xlsx`); confirmar obligatoriedad forma pago | Notas en brief o cierre AD | — | OK (decisiones usuario) |
| 2 | Arquitecto | Brief final FEAT-028 | `docs/briefs/FEAT-028.md` | 1 | OK |
| 3 | Feature **T1** | **Config + catálogos:** nuevos `catalog_type` (`linkage_type`, `account_type`, `work_center`, `risk_level`, `workday`, `ccf`, `withholding_type`, `expense_type`); `document_type` + **CE**; labels en `employee_ficha.php`; seed; doc MAPEO (col Z excluida) | config, seeder, `MAPEO-PLANTILLA-MASIVOS.md` | 2 | OK |
| 4 | Feature **T2** | **`EmployeeFichaProfileCatalogSync`:** mapa código→nombre (perfil + `payroll_extra`); integrar en store/update/import; ampliar `EmployeeFichaProfileFieldRules` (obligatorios + `payroll_extra` nested) | servicio, FormRequest trait, tests unit | 3 | OK |
| 5 | Feature **T3** | **UI formulario completo:** reescribir `ficha-form-fields.blade.php` por secciones; selectores catálogo para campos listados (CLASEDOC, TIPOVNC, FORPAGO, banco, tipo cuenta, centro trabajo, TASAARP, tipo salario/contrato, jornada, CC, EPS, AFP, CCF); inputs directos restantes; mismo partial en create + edit | vistas, CSS existente | 3, 4 | OK |
| 6 | Feature **T4** | **Prefill + controller:** `EmployeeFichaProfilePrefill` honesto; persistir `payroll_extra` en store/update; sync periodo laboral ampliado; bloque referencia requisición sin contaminar exportables | prefill, controller, period service | 4, 5 | OK |
| 7 | Feature **T5** | **Export/import alineados:** `PlantillaMasivosMapper` sin fallbacks ni defaults; **NITCENTROTB siempre null**; CLASEDOC solo código; `EmployeeFichaImportRowMapper` + import service coherente | mappers, tests plantilla | 4 | OK |
| 8 | Feature **T6** | **Tests feature:** obligatorios; round-trip guardar→export; NIT vacío; tipos doc CE; regresion FEAT-022 create/store | `EmployeeFichaMasivosExportFe028Test.php`, ampliar `EmployeeFichaPlantillasTest` | 5, 6, 7 | OK |
| 9 | Revisor | Review diff completo + seguridad validación | `docs/reviews/FEAT-028.md` | 8 | OK |
| 10 | Documentador | `docs/modules/ficha-empleados.md` + `docs/user/ficha-empleados.md` | docs modulo/usuario | 9 | OK |
| 11 | AgentSj | Checklist cierre → Completadas | `docs/TASKS.md` | 10 | Pendiente |

## Detalle tecnico por tarea

### T1 — Catálogos

```text
Nuevos catalog_type en config/employee_ficha.php:
  linkage_type, account_type, work_center, risk_level, workday,
  ccf, withholding_type, expense_type

document_type_defaults:
  C, CE, N, TI, PT  (+ seed payroll_catalog_items)

MAPEO-PLANTILLA-MASIVOS.md:
  - Col Z NITCENTROTB: "No exportar / no capturar"
  - CLASEDOC: códigos C, CE, N, TI, PT
  - Tabla catálogos UI ↔ col plantilla
```

### T2 — Catalog sync

```text
EmployeeFichaProfileCatalogSync::sync(EmployeeFichaProfile $profile): void

Pares perfil:
  document_type (solo code)
  eps_code → eps_name
  afp_code → afp_name
  position_code → position_name
  cost_center_code → cost_center_name
  bank_code → bank_name
  salary_type_code → salary_type_name
  contract_type_code → contract_type_name
  economic_activity_code → economic_activity_name
  residence_city_code → residence_city_name
  compensation_fund: ccf catalog → ccf_code (extra) + compensation_fund_name

Pares payroll_extra:
  work_center_code → work_center_name (profile.work_center_name)
  branch_code → branch_name
  destination_code → destination_name
  zone_code → zone_name
  severance_admin_code → severance_admin_name

Validación obligatorios (store + update):
  hired_document, hired_full_name (create)
  sex, hire_date, position_code, salary, cost_center_code
  eps_code, afp_code, ccf (catalog)
  bank_code, account_type, account_number, payment_method_code
```

### T3 — UI

```text
Secciones:
  1. Identificación (cedula, nombre, CLASEDOC, sexo, fechas nac/exp)
  2. Contacto (email, teléfonos, ciudad, dirección)
  3. Contrato y nómina (cargo, salario, tipo salario/contrato, fechas, TIPOVNC, escala)
  4. Centros (cost_center obligatorio; work_center catálogo → CODCENTROTB/NOMCENTROTB)
  5. Seguridad social (EPS, AFP, ARP/TASAARP, caja CCF obligatoria, fechas ingreso EPS/AFP)
  6. Pagos (FORPAGO, banco, tipo cuenta, cuenta)
  7. Nómina avanzada (jornada, retención, tipo gasto, sucursal, destino, zona, actividad económica, etc.)

UX: Select2 existente; mostrar código — nombre en option; no input duplicado para nombres.
```

### T4 — Prefill

```text
Quitar de prefill automático a exportables:
  work_center_name ← client.name  (ELIMINAR)
  residence_city_name ← requisition.city  (ELIMINAR)
  cost_center_code ← requisition.cost_center texto  (ELIMINAR o solo hint readonly)

Mantener sugerencias opcionales en bloque referencia:
  salario, fecha ingreso, cargo requisición, cliente (solo lectura)
```

### T5 — Export

```text
PlantillaMasivosMapper cambios:
  - Eliminar ?: $requisition fallbacks (excepto cedula/nombre mínimo)
  - Col index Z (NITCENTROTB): null fijo
  - Col B: documentTypeCode() soporta CE
  - Sin data_get(..., default) para workday/withholding/expense/exclude_overtime
  - work_center_code desde payroll_extra; work_center_name desde profile
```

## Paralelismo

- T5 puede iniciar en paralelo con T3 una vez T2 esté definido (mappers no dependen de Blade).
- T3 y T4 deben serializarse (form + persistencia).

## Puntos de pausa usuario

- ~~Campos obligatorios~~ — cerrado
- ~~Catálogos vs inputs~~ — cerrado (lista usuario + CE)
- ~~NIT no diligenciar~~ — cerrado
- Post-T3: demo visual GH (opcional) antes de cerrar

## Conflictos detectados

| Archivo | Riesgo | Resolucion |
| --- | --- | --- |
| `ficha-form-fields.blade.php` | Alto — touch FEAT-022/027 | Un solo agente Feature T3 |
| `PlantillaMasivosMapper.php` | Medio — export producción | Tests regresion T6 |
| `config/employee_ficha.php` | Bajo | T1 único |

## Checklist cierre

- [ ] Formulario create/edit/manual idéntico y completo
- [ ] Obligatorios validados server-side
- [ ] Catálogos nuevos sembrados
- [ ] Export masivos = BD sin fallbacks
- [ ] NITCENTROTB vacío
- [ ] CLASEDOC C/CE/N/TI/PT
- [ ] Tests verdes
- [ ] docs/modules + docs/user actualizados
