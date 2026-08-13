# Mapeo Plantilla masivos ↔ EMPLEADOS ↔ BD

Referencia técnica para export nómina (`Plantilla masivos.xlsx`) e import SJ (`EMPLEADOS.xlsx`).

## Plantilla masivos (62 columnas, fila 1)

| Col | Código plantilla | Campo EMPLEADOS | Campo BD `employee_ficha_profiles` | Origen automático |
| --- | --- | --- | --- | --- |
| A | TMPCEDULA.C15 | cedula | document_number | entry.hired_document / profile |
| B | CLASEDOC.C1 | tipo_documento | document_type | catalogo `document_type` — codigos **C, CE, N, TI, PT** |
| C | TMPNOMBRE.C40 | nombre | full_name | entry.hired_full_name |
| D | TMPAPELL_1.C60 | — | first_surname | parse nombre |
| E | TMPAPELL_2.C60 | — | second_surname | parse nombre |
| F | TMPNOMB_1.C60 | — | first_name | parse nombre |
| G | TMPNOMB_2.C60 | — | second_name | parse nombre |
| H | TMPDIRECCI.C40 | direccion | address | profile |
| I | TMPTELEFON.C19 | telefono | phone | profile |
| J | TMPTELEF2.C19 | — | phone_secondary | payroll_extra |
| K | TMPEMAIL.C40 | email | email | profile |
| L | TMPCIUDAD.C5 | codigo_lugar_residencia | residence_city_code | profile |
| M | TMPCIUNOM.C30 | lugar_residencia | residence_city_name | profile (catalogo `city`) |
| N | FECNACIDO.C10 | fecha_nac | birth_date | profile |
| O | FECHAING.C10 | fecha_ingreso | hire_date | profile |
| P | FECHAVACA.C10 | — | payroll_extra.vacation_base_date | payroll_extra |
| Q | TIPOVNC.N1 | tipo_vinculacion | linkage_type | catalogo `linkage_type` |
| R | CODCARGO.C10 | cargo | position_code | catalogo `position` |
| S | NOMCARGO.C30 | nombre_cargo | position_name | auto desde `position` |
| T | FORPAGO.C10 | forma_pago | payment_method_code | catalogo `payment_method` |
| U | CODBANCO.C10 | banco | bank_code | catalogo `bank` |
| V | NOMBANCO.C30 | — | bank_name | auto desde `bank` |
| W | BANCUENTA.C20 | cuenta | account_number | profile |
| X | TIPOCUENTA.N1 | tipo_de_cuenta | account_type | catalogo `account_type` |
| Y | CODCENTROTB.C10 | — | payroll_extra.work_center_code | catalogo `work_center` |
| Z | NITCENTROTB.C15 | — | payroll_extra.work_center_nit | **No capturar / no exportar (FEAT-028)** |
| AA | NOMCENTROTB.C30 | nombre_centro_trabajo | work_center_name | auto desde `work_center` |
| AB | SALARIO.N12 | salario | salary | profile |
| AC | CODEPS.C10 | codigo_eps | eps_code | profile |
| AD | NOMEPS.C30 | nombre_eps | eps_name | profile |
| AE | FECINGEPS.C10 | — | payroll_extra.eps_start_date | payroll_extra |
| AF | CODAFP.C10 | codigo_afp | afp_code | profile |
| AG | NOMAFP.C30 | nombre_afp | afp_name | profile |
| AH | FECINGAFP.C10 | — | payroll_extra.afp_start_date | payroll_extra |
| AI | CODARP.C10 | — | payroll_extra.arp_code | payroll_extra |
| AJ | NOMARP.C30 | nombre_arp | arp_name | profile |
| AK | TASAARP.C10 | nivel_riesgo_arp | risk_level | catalogo `risk_level` |
| AL | CODCCF.C10 | — | payroll_extra.ccf_code | catalogo `ccf` |
| AM | NOMCCF.C30 | nombre_caja_compensacion | compensation_fund_name | auto desde `ccf` |
| AN | LIBRETA.C20 | — | payroll_extra.military_book | payroll_extra |
| AO | SEXO.C1 | sexo | sex | profile |
| AP | CODTPSALAR.C10 | tipo_salario | salary_type_code | catalogo `salary_type` |
| AQ | NOMTPSALAR.C50 | — | salary_type_name | auto desde `salary_type` |
| AR | CODTPCONTR.C10 | tipo_contrato | contract_type_code | catalogo `contract_type` |
| AS | NOMTPCONTR.C50 | — | contract_type_name | auto desde `contract_type` |
| AT | FECHAVCTO.C10 | fecha_vencimiento_contrato | contract_end_date | profile |
| AU | JORNADA.N1 | — | payroll_extra.workday | catalogo `workday` |
| AV | TPRTENTE.N1 | — | payroll_extra.withholding_type | catalogo `withholding_type` |
| AW | TPGASTO.N1 | — | payroll_extra.expense_type | catalogo `expense_type` |
| AX | CODADCESAN.C10 | — | payroll_extra.severance_admin_code | payroll_extra |
| AY | NOMADCESAN.C50 | — | payroll_extra.severance_admin_name | payroll_extra |
| AZ | CODSUCURS.C5 | — | payroll_extra.branch_code | payroll_extra |
| BA | NOMSUCURS.C50 | — | payroll_extra.branch_name | payroll_extra |
| BB | CODCCOSTO.C10 | ccosto | cost_center_code | catalogo `cost_center` |
| BC | NOMCCOSTO.C50 | nombre_ccosto | cost_center_name | auto desde `cost_center` |
| BD | CODDESTINO.C10 | — | payroll_extra.destination_code | payroll_extra |
| BE | NOMDESTINO.C50 | — | payroll_extra.destination_name | payroll_extra |
| BF | CODZONA.C5 | — | payroll_extra.zone_code | payroll_extra |
| BG | NOMZONA.C50 | — | payroll_extra.zone_name | payroll_extra |
| BH | CODACTARL.C10 | actividad_economica | economic_activity_code | profile |
| BI | NOMACTARL.C50 | nombre_actividad_economica | economic_activity_name | profile |
| BJ | EXCLAUXTRA.N1 | — | payroll_extra.exclude_overtime | payroll_extra |

## EMPLEADOS.xlsx — columnas importación SJ (fila 1)

Mismas claves que `config/employee_ficha.php` → `import_columns`.

## Estado laboral

| Condición | employment_status |
| --- | --- |
| `fecha_retiro` vacía o futura | activo |
| `fecha_retiro` ≤ hoy | desvinculado |

Export plantilla masivos sin rango de fechas: solo `employment_status = activo` y `moved_to_ficha_at` not null.

## Catálogos (`payroll_catalog_items`)

Configuracion: `config/employee_ficha.php` → `catalog_types`, `catalog_static_defaults`, `plantilla_masivos_catalog_columns`.

| catalog_type | Origen seed | UI formulario (FEAT-028) |
| --- | --- | --- |
| document_type | `document_type_defaults` (C, CE, N, TI, PT) | Si — CLASEDOC |
| linkage_type | EMPLEADOS.tipo_vinculacion | Si — TIPOVNC |
| city | codigo_lugar_residencia + lugar_residencia | Si |
| position | cargo + nombre_cargo | Si — CODCARGO |
| cost_center | ccosto + nombre_ccosto | Si — CODCCOSTO (obligatorio) |
| work_center | EMPLEADOS.nombre_centro_trabajo | Si — CODCENTROTB / NOMCENTROTB |
| eps | codigo_eps + nombre_eps | Si — CODEPS (obligatorio) |
| afp | codigo_afp + nombre_afp | Si — CODAFP (obligatorio) |
| ccf | nombre_caja_compensacion | Si — CODCCF (obligatorio) |
| arp | nombre_arp | Opcional |
| bank | banco | Si — CODBANCO (obligatorio) |
| payment_method | forma_pago | Si — FORPAGO |
| account_type | EMPLEADOS.tipo_de_cuenta + `catalog_static_defaults` | Si — TIPOCUENTA |
| contract_type | tipo_contrato | Si — CODTPCONTR |
| salary_type | tipo_salario | Si — CODTPSALAR |
| risk_level | nivel_riesgo_arp + `catalog_static_defaults` | Si — TASAARP |
| workday | `catalog_static_defaults` | Si — JORNADA |
| withholding_type | `catalog_static_defaults` | Si — TPRTFTE |
| expense_type | `catalog_static_defaults` | Si — TPGASTO |
| economic_activity | actividad_economica + nombre_actividad_economica | Si |
| branch | sucursal (import) | Opcional |

### Columnas excluidas de captura y export

| Col | Codigo | Regla |
| --- | --- | --- |
| Z | NITCENTROTB.C15 | No se diligencia; export siempre vacio (`plantilla_masivos_excluded_columns`) |

### Regla codigo + nombre en formulario

El usuario selecciona **un** campo (codigo en catalogo). Al guardar, el sistema completa el nombre homologo en BD. Ver `EmployeeFichaProfileCatalogSync` (FEAT-028 T2).
