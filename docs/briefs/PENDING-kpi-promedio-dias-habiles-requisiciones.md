# Pendiente — KPI promedio días hábiles a contratar (Dashboard Requisiciones GH)

Estado: **guardado para después** (no implementar hasta indicación).

## Objetivo

En `/requisitions/gestion_humana/dashboard`, agregar un KPI con el **promedio de días hábiles** que tarda una requisición en contratarse.

## Definición cerrada

| Pieza | Regla |
| --- | --- |
| Inicio | `request_date` (día de la solicitud) |
| Fin | `hiring_date` (fecha de ingreso del empleado) |
| Días | Solo **lunes a viernes** (sin sábados/domingos) |
| Festivos CO | **No** restar en v1 (no hay calendario en el repo) |
| Universo | `status = contratado` con ambas fechas; mismos filtros del dashboard (año/mes sobre `request_date`, cliente, cargo, ciudad, reclutador, alcance global) |
| Mismo día hábil | **0** días |
| Ingreso &lt; solicitud | Excluir del promedio |
| Formato | 1 decimal + «días»; sin datos → `—` |
| UI | Sexto card: **Promedio días hábiles a contratar**; tooltip con N; enlace a Gestión filtrada en Contratadas |

## Implementación prevista (cuando se reactive)

1. Helper `BusinessDays::between($from, $to)` (testeable).
2. Cálculo en `RequisitionController::dashboard` sobre el mismo set filtrado.
3. Vista `modules/requisitions/dashboard.blade.php` + CSS grilla 6 KPIs.
4. Test Feature + docs `requisitions` (modules/user).

## Fuera de v1

- Festivos Colombia.
- Mediana / serie mensual Apex.
- Desglose por reclutador/cargo.
