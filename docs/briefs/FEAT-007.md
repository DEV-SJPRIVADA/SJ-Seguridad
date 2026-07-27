# Feature Brief — FEAT-007

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-007 |
| Modulo | comercial / matriz-clientes (servicios) |
| Titulo | Checklist documental: fecha de vencimiento por documento |
| Fecha | 2026-07-27 |

## Objetivo

Permitir registrar, por cada documento del checklist de un servicio comercial, si aplica renovacion y su fecha de vencimiento; alertar en listados cuando un documento este por vencer o vencido, ademas del fin de contrato.

## Decisiones usuario (2026-07-27)

1. **Fecha obligatoria** solo si estado = **OK** **y** el toggle “Tiene vencimiento” esta activo.
2. Solo en **crear/editar** servicio (no detalle ni listado de columnas nuevas de fechas).
3. Badges/filtros de vigencia: **contrato** + **cualquier documento** con vencimiento activo por vencer/vencido.
4. Si estado vacio o **N/A**: ocultar toggle y fecha.
5. Aplica a los **10** documentos; cada uno tiene opcion de **habilitar/inhabilitar** vencimiento (documento exigible sin renovacion).
6. Importador MT-CO-01: leer fechas si vienen en columnas reconocidas; si no hay columna, no inventar.

## Regla de UI por documento

Para cada campo `doc_*`:

1. Select de estado (existente).
2. Si estado ∈ {OK, X, Pendiente, Incompleto}: mostrar checkbox **Tiene vencimiento**.
3. Si checkbox ON: mostrar `input type=date` Fecha de vencimiento.
4. Si estado = OK y checkbox ON → fecha **required**.
5. Si checkbox OFF o estado vacio/N/A → no persistir fecha (`null`) y `tracks_expiry = false`.

## Base de datos

Por cada uno de los 10 campos documentales, agregar:

- `{field}_tracks_expiry` boolean default false
- `{field}_expires_on` date nullable

Ejemplo: `doc_rut_tracks_expiry`, `doc_rut_expires_on`.

## Capas

- Migracion
- Modelo `CommercialService` (fillable, casts, helpers de vigencia documental)
- Form Request (validacion condicional)
- Vista `service-fields.blade.php` + JS toggle
- Controller store/update (ya usa validated)
- Listados: `isExpired` / `isExpiringSoon` + filtros `vigencia` consideran docs
- Dashboard comercial si usa los mismos helpers
- `MtCo01Importer`: aliases de encabezado para fechas (ej. `vencimiento rut`, `fecha vencimiento contrato` documental vs contrato — cuidado de no chocar con `fecha de terminacion contrato`)
- Tests Feature CommercialMatrix
- Docs `docs/modules/matriz-clientes.md`, `docs/user/matriz-clientes.md`

## Fuera de alcance

- Adjuntos PDF
- Notificaciones por correo
- Pantallas de solo lectura nuevas

## Criterios de aceptacion

1. En create/edit, cada documento del checklist puede activar “Tiene vencimiento” y capturar fecha.
2. Con estado OK + toggle ON sin fecha → error de validacion.
3. Con estado OK + toggle OFF → guarda sin fecha.
4. Estado N/A u vacio → no se muestran toggle/fecha.
5. Listado servicios/cliente: badge vencido/por vencer si contrato o algun documento aplica.
6. Filtros vigencia incluyen documentos.
7. Importador asigna fecha+tracks si hay columna reconocida.
8. Tests pasan.

## Aprobacion

- [x] Usuario — respuestas 1a 2a 3b 4a 5(todos+toggle) 6b
- [x] Arquitecto — brief
