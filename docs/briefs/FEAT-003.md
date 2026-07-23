# FEAT-003 — Capturadores en Ajustes (Indicadores Operaciones)

## Objetivo

Pestana en **Ajustes → Indicadores** para listar usuarios activos del area **Operaciones** (`area_key = operaciones`) y activar/desactivar su permiso de captura de indicadores.

## Alcance

- Nueva seccion `?section=capturadores` junto a Periodos, Metas y Logs.
- Tabla: nombre, email, rol/resumen, estado captura (toggle).
- Activar: otorga `operations.capture` (+ `operations.view` y board indicadores si faltan).
- Desactivar: revoca solo `operations.capture` (no toca `operations.manage`).
- Usuarios con `operations.manage` aparecen como administradores de captura (toggle bloqueado).
- Consolidado/dashboard usan solo capturadores habilitados del area operaciones.

## Fuera de alcance

- Crear usuarios o cambiar `area_key` (sigue en Admin usuarios).
- Gestion de otros permisos `operations.*` desde esta pantalla.

## Criterios de aceptacion

1. Solo `operations.manage` accede a la pestana.
2. Listado filtra `is_active = true` y `area_key = operaciones`.
3. Toggle persiste permiso y audita el cambio.
4. Usuario desactivado no ve tab Captura ni puede guardar fichas.
5. Tests feature cubren listado y toggle.
