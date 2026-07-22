# Feature Brief — FEAT-XXX

> Plantilla: Analista (borrador) → Arquitecto (version final en `docs/briefs/`).

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-XXX |
| Modulo / area | |
| Titulo | |
| Solicitante | |
| Fecha | YYYY-MM-DD |

## Objetivo

Que problema resuelve y para quien.

## Alcance

### Incluye

-

### Fuera de alcance

-

## Reglas de negocio

-

## Permisos (`config/access.php`)

| Permiso | Rol(es) | Descripcion |
| --- | --- | --- |
| | | |

## Rutas

| Metodo | URI | Nombre | Archivo de rutas |
| --- | --- | --- | --- |
| | | | `routes/modules/` o `routes/areas/` |

## Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| | migracion / alter | |

## Capas a implementar

- [ ] Migracion(es)
- [ ] Modelo(s)
- [ ] Controlador(es)
- [ ] Form Request(s)
- [ ] Vista(s) Blade
- [ ] JavaScript (si aplica)
- [ ] Export Excel (si aplica — usar `BaseExport` y `<x-export-excel>`)
- [ ] Tests

## Componentes reutilizables

-

## Documentacion a actualizar

- [ ] `docs/modules/{modulo}.md`
- [ ] `docs/user/{modulo}.md`
- [ ] `docs/INDEX.md` (si aplica)
- [ ] `README.md` (si aplica)

## Archivos compartidos (`shared-files`)

Listar si la feature toca: `config/access.php`, `routes/web.php`, layouts, seeders globales.

-

## Criterios de aceptacion

1.
2.

## Validacion local

1.
2. `php artisan test`

## Riesgos y dependencias

-

## Aprobacion

- [ ] Analista — vacios cerrados
- [ ] Arquitecto — brief final
- [ ] Usuario — confirmacion
