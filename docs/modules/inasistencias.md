# Módulo Inasistencias

> Documentación técnica. Ubicación: `docs/modules/inasistencias.md`.
> Área: Gestión humana.

## Objetivo

Tablero propio de **Gestión Humana** para generar **cartas de inasistencia** en lote (grilla + Word 1→`.docx` / N→`.zip`) y **guardar el histórico** de cada generación (registro en BD, **sin** almacenar el archivo Word).

## Alcance

- Tablero sidebar **Inasistencias** con pestañas **Generar** | **Histórico**.
- Permisos: `view.board.gestion_humana.inasistencias` + `inasistencias.view` + `inasistencias.edit` (`edit` implica `view` en AccessService).
- Lookup ficha por cédula → nombre, cargo, ciudad.
- `DIAS` = días calendario **inclusivos** desde `FECHA_INI_AUS` hasta hoy (ej. 12→14 = 3; mismo día = 1; snapshot al generar).
- Plantilla Word tipo `inasistencias` (exactamente una activa con archivo).
- Excel plantilla/import-preview (solo memoria).
- Audit: `inasistencias_generate` (sin lista de cédulas).

## Permisos

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.inasistencias` | Sidebar GH |
| `inasistencias.view` | Histórico + export |
| `inasistencias.edit` | Generar (lookup, Excel, Word) |

Servicio: `App\Services\Access\InasistenciasAccessService`.

## Rutas

Prefijo `/gestion-humana/inasistencias` · `gestion-humana.inasistencias.*`

| Método | URI | Nombre | Permiso |
| --- | --- | --- | --- |
| GET | `/generar` | `generar` | edit (view-only → redirect histórico) |
| GET | `/historico` | `historico` | view |
| GET | `/historico/export` | `historico.export` | view |
| POST | `/lookup` | `lookup` | edit |
| POST | `/generar` | `generate` | edit |
| GET/POST | import-template / import-preview | … | edit |

## Modelo

Tabla `inasistencia_cartas` (`InasistenciaCarta`): cédula, nombre, cargo, ciudad, fecha_ini_aus, dias, firma_name, cargo_firma, signatory_id, generated_by, generated_at, word_template_id. **Sin** path de archivo.

## Word

- Tipo seed `inasistencias` (`WordDocumentTypeSeeder`).
- `LetterVariableBuilder::buildForInasistencias`.
- Placeholders UI grupo **Inasistencias** en `config/employee_ficha.php`.

Variables de negocio: `CEDULA`, `NOMBRE` / `NOMBRE_COMPLETO`, `CARGO`, `CIUDAD`, `FECHA_INI_AUS`, `DIAS`, `FIRMA`, `CARGO_FIRMA`, `FECHA`.

## Referencias

- Usuario: [`docs/user/inasistencias.md`](../user/inasistencias.md)
- Cartas Notificación (patrón): [`cartas-notificacion.md`](cartas-notificacion.md)
- Plantillas Word: [`plantillas-word.md`](plantillas-word.md)
