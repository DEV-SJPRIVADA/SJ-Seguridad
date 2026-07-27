# Feature Brief — FEAT-005

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-005 |
| Modulo / area | requisitions (compartido) |
| Titulo | Campo Estructura del servicio en requisiciones |
| Solicitante | Usuario (chat AgentSj) |
| Fecha | 2026-07-24 |

## Objetivo

Capturar horarios, descansos y condiciones del puesto al solicitar personal, y permitir a Gestion Humana ver/editar ese dato al gestionar la requisicion, con trazabilidad y exportacion Excel.

## Alcance

### Incluye

- Columna nueva en `personal_requisitions` (texto largo, nullable en BD; validacion `required` al crear/actualizar desde formularios).
- UI textarea **dentro** de seccion **4 Perfil y dotacion**, debajo de perfil y dotacion (sin nueva seccion numerada).
- Pantallas: **Solicitar** (`form-fields-requester`) y **Gestion → editar** (`form-fields`).
- Persistencia en `store` y `update`.
- Columna en export Excel de Gestion y de Mis requisiciones / seguimiento (si aplica el mismo set de columnas).
- Registro en historial de cambios (`PersonalRequisitionChangeLogger`) cuando GH edita el campo.
- Tests Feature + docs tecnica/usuario (Documentador en paso posterior; Feature puede dejar notas).

### Fuera de alcance

- Impresion / PDF (`print.blade.php`)
- Correos de notificacion
- Nuevos permisos o cambios en `config/access.php`
- Dashboard KPI

## Reglas de negocio (confirmadas por usuario)

1. Campo obligatorio al crear (y al guardar en edicion).
2. Ubicacion: dentro de seccion 4, despues de perfil y dotacion (opcion b).
3. Editable por GH en Gestion (opcion a).
4. Visible en formularios Solicitar + Gestion y en Export Excel; no en print ni emails.
5. Cambios de GH quedan en historial de cambios (opcion a).

## Permisos (`config/access.php`)

Sin cambios. Usa permisos existentes de solicitar / gestionar.

## Rutas

Sin rutas nuevas. Usa `requisitions.store` y `requisitions.update` existentes.

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `personal_requisitions.service_structure` | migracion alter | `text` nullable; label UI: **Estructura del servicio**; ayuda: horarios, descansos y condiciones del puesto a tener en cuenta |

Nombre de columna propuesto: `service_structure` (snake_case).

## Capas a implementar

- [x] Migracion
- [x] Modelo (`fillable`)
- [x] Controlador (`store` / `update` + columnas Excel)
- [x] Form Requests (store required, update required)
- [x] Vistas Blade (requester + gestion)
- [ ] JavaScript — no aplica
- [x] Export Excel (`BaseExport` columnas)
- [x] Change logger label
- [x] Tests

## Componentes reutilizables

- Textarea mismo patron que `requester_observation` (`form-textarea`).

## Documentacion a actualizar

- [x] `docs/modules/requisitions.md`
- [x] `docs/user/requisitions.md`

## Archivos compartidos (`shared-files`)

Ninguno.

## Criterios de aceptacion

1. En Solicitar, tras Perfil requerido y Dotacion, aparece textarea **Estructura del servicio** con descripcion de ayuda; no se puede guardar vacio.
2. En Gestion → abrir, el mismo campo es visible y editable; al guardar vacio falla validacion.
3. Export Excel de Gestion (y tracking si lista campos de detalle) incluye la columna.
4. Si GH cambia el texto, aparece en Historial de cambios.
5. Print y emails no se modifican.
6. Tests de requisiciones pasan.

## Validacion local

1. Crear requisicion con campo lleno / vacio.
2. Editar en Gestion.
3. Export Excel.
4. `php artisan test --filter=RequisitionModuleTest`

## Riesgos y dependencias

- Requisiciones existentes quedan con `NULL`; al reabrir en Gestion el campo sera obligatorio al guardar (aceptable por regla de negocio).

## Aprobacion

- [x] Analista — vacios cerrados (respuestas usuario 2026-07-24)
- [x] Arquitecto — brief final
- [x] Usuario — confirmacion respuestas 1a 2b 3a 4 excel+forms 5a
