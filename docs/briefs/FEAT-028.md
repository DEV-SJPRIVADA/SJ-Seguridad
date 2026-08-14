# Feature Brief — FEAT-028

> Brief consolidado (Arquitecto + decisiones de negocio del usuario, 2026-08-13). Extiende FEAT-020/022: formulario minimo → formulario completo alineado a **Plantilla masivos** (62 columnas).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-028 |
| Modulo / area | `ficha-empleados` — Gestion Humana |
| Titulo | Formulario ficha empleados completo alineado a Plantilla masivos |
| Solicitante | Manuel-E |
| Fecha | 2026-08-13 |

## Objetivo

Hoy el formulario de alta/edición de ficha captura ~15 campos, pero la **Plantilla masivos** exporta 62 columnas. Los huecos se rellenan con datos de la requisición o defaults hardcodeados, lo que produce exportaciones incorrectas respecto a la persona ingresada.

Esta feature unifica **captura = exportación**: el mismo formulario completo se usa en **Gestionar empleado** (desde requisición), **alta manual** y **editar ficha**. Donde la plantilla tiene par código/nombre, el usuario elige **un solo campo** (catálogo) y el sistema persiste ambos valores. La exportación masivos refleja **solo lo guardado en perfil + `payroll_extra`**, sin fallbacks silenciosos.

## Decisiones de negocio (cerradas)

| Tema | Decision |
| --- | --- |
| Campos obligatorios al crear/guardar | Cédula, nombre completo, **cargo**, **salario**, **EPS**, **AFP**, **caja de compensación**, **fecha ingreso**, **sexo**, **datos bancarios** (banco, tipo cuenta, número cuenta; forma de pago recomendada obligatoria salvo indicación contraria) |
| Centro de costo nómina (BB/BC) | Solo **catálogo `cost_center`**; no usar código/texto crudo de requisición |
| Centro de trabajo (Y/AA) | Catálogo dedicado; ver matriz de catálogos |
| **NITCENTROTB.C15** (col Z) | **No se diligencia nunca** — exportar vacío / null |
| **CLASEDOC.C1** | Mostrar y exportar **iniciales**: `C`, `CE`, `N`, `TI`, `PT` |
| Campos numéricos nómina (jornada, retención, tipo gasto, etc.) | **Catálogo**, no defaults inventados (`1`, `4`, …) |
| Alta manual sin requisición | **Mismo formulario completo** |
| Prefill desde requisición | Bloque referencia solo lectura; sugerencias mínimas (salario, fecha ingreso, cargo vía mapa); **no** copiar cliente→centro trabajo ni ciudad requisición→residencia como dato exportable |

## Matriz Plantilla masivos ↔ UI ↔ BD

Referencia completa: [`docs/Contratacion/MAPEO-PLANTILLA-MASIVOS.md`](../Contratacion/MAPEO-PLANTILLA-MASIVOS.md) (actualizar en esta feature).

### Regla UI

| Tipo | Comportamiento |
| --- | --- |
| Par código + nombre | Un selector catálogo → guarda código + nombre derivado |
| Nombre partido (cols D–G) | Derivado de **nombre completo** al guardar (`EmployeeFichaNameParser`) — no editable en UI |
| NIT centro trabajo (col Z) | **Excluido** — siempre vacío en export |
| Campos restantes | Input directo (texto, fecha, número) |

### Catálogos obligatorios en UI (instrucción usuario)

| Col plantilla | Campo plantilla | Campo BD / extra | `catalog_type` | Obligatorio crear |
| --- | --- | --- | --- | --- |
| B | CLASEDOC.C1 | `document_type` | `document_type` | No (default `C`) |
| Q | TIPOVNC.N1 | `linkage_type` | `linkage_type` | No |
| T | FORPAGO.C10 | `payment_method_code` | `payment_method` | Si (datos banco) |
| U | CODBANCO.C10 | `bank_code` → `bank_name` | `bank` | Si |
| X | TIPOCUENTA.N1 | `account_type` | `account_type` | Si |
| Y | CODCENTROTB.C10 | `payroll_extra.work_center_code` | `work_center` | No |
| AA | NOMCENTROTB.C30 | `work_center_name` | `work_center` (nombre auto) | No |
| Z | NITCENTROTB.C15 | `payroll_extra.work_center_nit` | — | **Nunca diligenciar** |
| AK | TASAARP.C10 | `risk_level` | `risk_level` | No |
| AP | CODTPSALAR.C10 | `salary_type_code` → `salary_type_name` | `salary_type` | No |
| AR | CODTPCONTR.C10 | `contract_type_code` → `contract_type_name` | `contract_type` | No |
| AU | JORNADA.N1 | `payroll_extra.workday` | `workday` | No |
| BB | CODCCOSTO.C10 | `cost_center_code` → `cost_center_name` | `cost_center` | **Si** |
| AC/AF | CODEPS / CODAFP | `eps_code` / `afp_code` | `eps` / `afp` | **Si** |
| AM | NOMCCF | `compensation_fund_name` + `payroll_extra.ccf_code` | `ccf` | **Si** |

### Otros catálogos ya existentes (mantener patrón)

| Uso | `catalog_type` |
| --- | --- |
| Ciudad residencia | `city` |
| Cargo | `position` |
| Actividad económica | `economic_activity` |
| Sucursal | `branch` |
| Retención / tipo gasto (cols AV/AW) | `withholding_type`, `expense_type` (nuevos) |

### Tipos documento (`document_type_defaults`)

Actualizar seed/config para incluir **`CE`** (Cédula extranjería) además de `C`, `N`, `TI`, `PT`. En UI y export solo la **inicial/código** de un carácter o sigla corta según catálogo.

## Alcance

### Incluye

1. **Formulario completo** en `ficha-form-fields.blade.php` (secciones: identificación, contacto, contrato/nómina, centro de costo, SS, pagos/banco, nómina avanzada/`payroll_extra`).
2. Mismo formulario en `create-ficha.blade.php`, `edit-ficha.blade.php`.
3. Servicio **`EmployeeFichaProfileCatalogSync`**: centraliza código → nombre (perfil + pares en `payroll_extra`).
4. Validación ampliada en `EmployeeFichaProfileFieldRules` con obligatorios acordados.
5. **`EmployeeFichaProfilePrefill`**: prefill honesto; referencia requisición separada de datos exportables.
6. **`PlantillaMasivosMapper`**: solo perfil + `payroll_extra`; sin fallbacks requisición; sin defaults numéricos; **col Z siempre null**.
7. **`EmployeeFichaImportRowMapper`** / import alineados al mismo modelo de datos.
8. Catálogos nuevos en `config/employee_ficha.php` + seed (`employee-ficha:seed-catalogs` o seeder) + UI admin Catálogos.
9. Sincronización periodo laboral activo con campos laborales ampliados al guardar.
10. Tests: obligatorios, sync catálogo, export = guardado, NIT vacío, CLASEDOC iniciales.

### Fuera de alcance

- Cambios a flujo de requisiciones / Contratado (FEAT-020).
- Cartas desvinculación (FEAT-027).
- Migraciones de esquema salvo ampliar `catalog_type` en seed (tabla `payroll_catalog_items` ya soporta tipos dinámicos).
- Unificar pantalla `/{id}/ficha` pendiente legacy con edit (ya unificado por mismo partial).

## Reglas de exportación (Plantilla masivos)

1. **Fuente de verdad:** `employee_ficha_profiles` + `payroll_extra`.
2. **Prohibido** rellenar desde `$entry->requisition` en export (salvo cédula/nombre si perfil vacío — solo identificación mínima).
3. **Prohibido** defaults `workday=1`, `withholding_type=1`, `expense_type=4`, `exclude_overtime=0` si no fueron guardados.
4. **`NITCENTROTB.C15`:** siempre `null`/celda vacía.
5. **`CLASEDOC.C1`:** código catálogo (`C`, `CE`, `N`, `TI`, `PT`), nunca etiqueta larga.

## Permisos

Sin cambios — `ficha_empleados.manage` para create/store/update.

## Rutas

Sin rutas nuevas.

## Base de datos

Sin migraciones estructurales previstas. Campos adicionales viven en columnas existentes de `employee_ficha_profiles` y JSON `payroll_extra`.

## Criterios de aceptación

1. Usuario completa formulario desde requisición, guarda, exporta Plantilla masivos → fila coincide con lo capturado.
2. Campos obligatorios bloquean guardado si faltan.
3. Seleccionar banco/EPS/centro de costo/etc. completa nombre en BD sin segundo input.
4. Col Z (NIT) siempre vacía en export.
5. Tipo documento exporta iniciales (`C`, `CE`, `N`, `TI`, `PT`).
6. Alta manual usa el mismo formulario.
7. Tests feature pasan.

## Referencias

- [`docs/modules/ficha-empleados.md`](../modules/ficha-empleados.md)
- [`docs/briefs/FEAT-022.md`](FEAT-022.md)
- [`docs/briefs/FEAT-028-plan.md`](FEAT-028-plan.md)
