# Review — FEAT-020

Fecha: 2026-07-30. Revisor: Bugbot + correcciones AgentSj.

## Veredicto

**Listo para uso** tras correcciones aplicadas en este turno (3 hallazgos).

## Hallazgos y resolucion

| # | Severidad | Hallazgo | Resolucion |
| --- | --- | --- | --- |
| 1 | Alta | `confirm_duplicate_hired=1` omitia validacion en reenvios posteriores | Confirmacion atada a `confirm_duplicate_hired_document` que debe coincidir con `hired_document`; reset al cambiar cedula/estado |
| 2 | Media | Campos contratado ocultos seguian enviandose | `disabled` cuando estado != contratado; servidor persiste `null` fuera de Contratado |
| 3 | Media | Sidebar Ficha empleados visible solo con `ficha_empleados.view` | `canViewFichaEmpleadosBoard` exige `view.board.gestion_humana.ficha_empleados` (o admin) |

## Tests

- `FichaEmpleadosTest`: 20 passed
- Regresion requisiciones contratado: cubierta en `RequisitionModuleTest`

## Notas

- BD local MySQL: posible estado inconsistente si se ejecuto `migrate:fresh` durante T1; restaurar con backup o `php artisan migrate` + seeders.
- Fallo preexistente dashboard GH (`todas las areas`) no relacionado con FEAT-020.
