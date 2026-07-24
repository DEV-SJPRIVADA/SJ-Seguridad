# Feature Brief — FEAT-006

## Objetivo

Export Excel de Gestion y Seguimiento con detalle completo de requisiciones y filtros por rango de fecha de solicitud.

## Decisiones usuario (2026-07-24)

1. Rango sobre `request_date` (a)
2. Fechas opcionales (b)
3. Filtros en panel; filtran tabla y export (b)
4. Nombres legibles en Excel (a)
5. q + estado + fechas (a)
6. Incluye compensacion (a)
7. Gestion + Seguimiento (b)

## Implementacion

- `PersonalRequisitionFilterBag` — filtros compartidos
- `PersonalRequisitionFullExport` — columnas completas
- `BaseExport` — columnas Excel AA+
- Vistas manage + tracking — date_from, date_to
