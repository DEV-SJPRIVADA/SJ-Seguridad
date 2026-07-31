# Módulo: Solicitudes de compra

## Objetivo

Gestionar solicitudes de compra libres (multi-línea, fotos), con autorización por director seleccionado y procesamiento unificado en bandeja Compras junto con suministros aprobados por Calidad.

## Rutas

Prefijo autenticado: `/purchase-requests/{module}/`

| Pestaña | Permiso | Rutas |
|---------|---------|-------|
| Nueva solicitud | `purchase.tab.create` | `purchase-requests.create`, `store` |
| Mis solicitudes | `purchase.tab.my_requests` | `purchase-requests.index`, `show` |
| Pendientes autorización | `purchase.tab.approval` | `purchase-requests.approval.*` |
| Bandeja compras | `purchase.tab.processing` | `purchase-requests.processing.*` |

**Autorización:** el director aprueba o rechaza desde **Pendientes autorización** (detalle de la solicitud en la plataforma). El correo solo notifica y enlaza al detalle autenticado.

Enlaces legacy firmados (`/purchase-requests/aprobacion-correo/{id}`) redirigen al detalle en la plataforma; ya no resuelven la solicitud por POST.

## Roles

- **`director`**: autoriza solicitudes (`purchase.tab.approval`); incluye Calidad si se le asigna el rol.
- **`administrador`**: plataforma/GH; ya no incluye `manage.users` (solo `super-admin`).
- **Compras**: `purchase.tab.processing` para bandeja unificada.

## Integración Suministros

Al aprobar Calidad (`aprobada_calidad`), la solicitud aparece en bandeja Compras. Procesamiento supply: costos unitarios → `en_compras` → `completada`.

## FO-AD-44

PDF y Excel por solicitud de compra; PDF adicional para suministros (Excel ya existía).

## Import legacy

`php artisan purchase-requests:import-legacy --dry-run` (requiere `LEGACY_GESTION_COMPRAS_DB_*` en `.env`).

## Pruebas

- `tests/Feature/PurchaseRequestModuleTest.php`
- `tests/Feature/RoleDirectorMigrationTest.php`
