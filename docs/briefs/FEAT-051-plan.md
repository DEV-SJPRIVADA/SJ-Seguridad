# Plan — FEAT-051

## Triaje

C — Full: módulo de negocio nuevo dentro del tablero Planeación estratégica, permisos nuevos y tres entidades encadenadas.

## Slices

| # | Slice | Alcance | Estado |
| --- | --- | --- | --- |
| T1 | Acceso + datos | Permisos `planeacion.estrategia.view/edit`, pestaña en `planeacion_tabs`, migraciones DOFA/ítems/objetivos/estrategias, modelos | Hecho |
| T2 | DOFA | Listado con filtro por estado, alta/edición en modal con cuatro cuadrantes, activar y archivar | Hecho |
| T3 | Objetivos y estrategias | Árbol en el detalle, alta en línea, avance %, borrado en cascada del objetivo | Hecho |
| T4 | Cierre | Tests, CSS por capa, docs módulo/usuario/ACCESS | Hecho |

## Fuera de alcance

- Enlazar objetivos/estrategias con indicadores de FEAT-050.
- Matriz de priorización con pesos, import masivo, exportación PPTX.

## Shared files

- `config/access.php` (permisos, labels, descripciones, pestaña, grupo Admin).
- `database/seeders/RoleAndPermissionSeeder.php` (rol `administrador`).
- `routes/areas/calidad.php`, `app/Traits/HasPlaneacionTabs.php`, `app/Models/User.php` (URL por defecto).
- `resources/css/app.css` (import de la capa 118).
