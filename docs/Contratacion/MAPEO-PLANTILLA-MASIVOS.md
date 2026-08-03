# Mapeo Plantilla masivos ↔ EMPLEADOS ↔ BD

Referencia técnica para export nómina (`Plantilla masivos.xlsx`) e import SJ (`EMPLEADOS.xlsx`).

## Plantilla masivos (62 columnas, fila 1)

| Col | Código plantilla | Campo EMPLEADOS | Campo BD `employee_ficha_profiles` | Origen automático |
| --- | --- | --- | --- | --- |
| A | TMPCEDULA.C15 | cedula | document_number | entry.hired_document / profile |
| B | CLASEDOC.C1 | tipo_documento | document_type | default C |
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
| M | TMPCIUNOM.C30 | lugar_residencia | residence_city_name | profile / requisition.city |
| N | FECNACIDO.C10 | fecha_nac | birth_date | profile |
| O | FECHAING.C10 | fecha_ingreso | hire_date | profile / requisition.hiring_date |
| P | FECHAVACA.C10 | — | payroll_extra.vacation_base_date | payroll_extra |
| Q | TIPOVNC.N1 | tipo_vinculacion | linkage_type | profile |
| R | CODCARGO.C10 | cargo | position_code | profile / payroll map |
| S | NOMCARGO.C30 | nombre_cargo | position_name | profile / requisition.position |
| T | FORPAGO.C10 | forma_pago | payment_method_code | profile |
| U | CODBANCO.C10 | banco | bank_code | profile |
| V | NOMBANCO.C30 | — | bank_name | profile |
| W | BANCUENTA.C20 | cuenta | account_number | profile |
| X | TIPOCUENTA.N1 | tipo_de_cuenta | account_type | profile |
| Y | CODCENTROTB.C10 | — | payroll_extra.work_center_code | payroll_extra |
| Z | NITCENTROTB.C15 | — | payroll_extra.work_center_nit | payroll_extra |
| AA | NOMCENTROTB.C30 | nombre_centro_trabajo | work_center_name | profile |
| AB | SALARIO.N12 | salario | salary | profile / requisition.base_salary |
| AC | CODEPS.C10 | codigo_eps | eps_code | profile |
| AD | NOMEPS.C30 | nombre_eps | eps_name | profile |
| AE | FECINGEPS.C10 | — | payroll_extra.eps_start_date | payroll_extra |
| AF | CODAFP.C10 | codigo_afp | afp_code | profile |
| AG | NOMAFP.C30 | nombre_afp | afp_name | profile |
| AH | FECINGAFP.C10 | — | payroll_extra.afp_start_date | payroll_extra |
| AI | CODARP.C10 | — | payroll_extra.arp_code | payroll_extra |
| AJ | NOMARP.C30 | nombre_arp | arp_name | profile |
| AK | TASAARP.C10 | nivel_riesgo_arp | risk_level | profile |
| AL | CODCCF.C10 | — | payroll_extra.ccf_code | payroll_extra |
| AM | NOMCCF.C30 | nombre_caja_compensacion | compensation_fund_name | profile |
| AN | LIBRETA.C20 | — | payroll_extra.military_book | payroll_extra |
| AO | SEXO.C1 | sexo | sex | profile / requisition.sex |
| AP | CODTPSALAR.C10 | tipo_salario | salary_type_code | profile |
| AQ | NOMTPSALAR.C50 | — | salary_type_name | profile |
| AR | CODTPCONTR.C10 | tipo_contrato | contract_type_code | profile |
| AS | NOMTPCONTR.C50 | — | contract_type_name | profile / requisition.contract_type |
| AT | FECHAVCTO.C10 | fecha_vencimiento_contrato | contract_end_date | profile |
| AU | JORNADA.N1 | — | payroll_extra.workday | payroll_extra |
| AV | TPRTENTE.N1 | — | payroll_extra.withholding_type | payroll_extra |
| AW | TPGASTO.N1 | — | payroll_extra.expense_type | payroll_extra |
| AX | CODADCESAN.C10 | — | payroll_extra.severance_admin_code | payroll_extra |
| AY | NOMADCESAN.C50 | — | payroll_extra.severance_admin_name | payroll_extra |
| AZ | CODSUCURS.C5 | — | payroll_extra.branch_code | payroll_extra |
| BA | NOMSUCURS.C50 | — | payroll_extra.branch_name | payroll_extra |
| BB | CODCCOSTO.C10 | ccosto | cost_center_code | profile / requisition.cost_center |
| BC | NOMCCOSTO.C50 | nombre_ccosto | cost_center_name | profile |
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

| catalog_type | Origen seed |
| --- | --- |
| document_type | EMPLEADOS.tipo_documento |
| city | codigo_lugar_residencia + lugar_residencia |
| position | cargo + nombre_cargo |
| cost_center | ccosto + nombre_ccosto |
| eps | codigo_eps + nombre_eps |
| afp | codigo_afp + nombre_afp |
| arp | nombre_arp |
| bank | banco |
| payment_method | forma_pago |
| contract_type | tipo_contrato |
| salary_type | tipo_salario |
| economic_activity | actividad_economica + nombre_actividad_economica |
