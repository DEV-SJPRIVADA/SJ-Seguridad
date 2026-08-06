# Feature Brief — FEAT-024

## Identificacion

| Campo | Valor |
| --- | --- |
| ID | FEAT-024 |
| Modulo | Indicadores / Operaciones |
| Titulo | Preview HTML informe PPTX FO-GI-39 con narrativas editables |
| Fecha | 2026-08-04 |

## Objetivo

Antes de descargar el informe PowerPoint FO-GI-39, permitir revisar en el navegador una **vista previa HTML** (no render PPTX real) con los textos de **narrativa por indicador** editables, guardar borrador por año/mes y exportar PPTX usando esos textos.

## Alcance

### Incluye

- Tabla `indicator_management_report_drafts` (year, month unique, report_title nullable, narratives JSON, updated_by_user_id).
- Modelo `ManagementReportDraft`.
- Rutas bajo `operations.export`:
  - `GET operaciones/indicadores/exportar/informe-gestion` → preview HTML
  - `POST operaciones/indicadores/exportar/informe-gestion/borrador` → guardar borrador
- Vista preview: portada (titulo editable) + 9 tarjetas (FT-OP-01…09) con KPIs readonly + textarea narrativa.
- Botones: Guardar borrador, Descargar PPTX, Regenerar textos (reset a auto-generados).
- `ManagementReportDataBuilder::build()` aplica borrador guardado (narrativas + titulo portada).
- Dashboard: enlace **Preparar informe PPTX** (preview); mantener descarga directa opcional o solo desde preview.
- Tests feature + doc tecnica/usuario.

### Fuera de alcance

- Preview fiel slide-a-slide del .pptx en navegador.
- Edicion de graficos o titulos por slide (titulos vienen de config).
- Resumen ejecutivo PDF en PPTX.

## Permisos

- Preview y borrador: `operations.export` (mismo grupo exportaciones).
- Sin permiso nuevo.

## Rutas

| Metodo | URI | Nombre |
| --- | --- | --- |
| GET | `/operaciones/indicadores/exportar/informe-gestion` | `indicadores.export.management.preview` |
| POST | `/operaciones/indicadores/exportar/informe-gestion/borrador` | `indicadores.export.management.draft.store` |
| GET | `/operaciones/indicadores/exportar/informe-gestion.pptx` | `indicadores.export.management.pptx` (usa borrador si existe) |

## Criterios de aceptacion

1. Usuario con `operations.export` abre preview con year/month; ve 9 narrativas precargadas (analisis captura o auto).
2. Edita narrativas, guarda borrador; recarga y persisten.
3. Descarga PPTX con textos del borrador.
4. Regenerar textos elimina override y vuelve a auto-generados.
5. Tests en verde.
