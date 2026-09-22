# Task Card — FEAT-035 / T5

| Campo | Valor |
| --- | --- |
| Feature | FEAT-035 |
| Tarea | T5 — Dashboard KPIs + gráficos ApexCharts |
| Modulo | seleccion |
| shared-files | Parcial — Vite entry si aplica |
| Depende de | T4 OK |
| Brief | [`FEAT-035.md`](FEAT-035.md) |

## Objetivo

Dashboard operativo con KPIs y gráficos según Brief § Dashboard; filtros AJAX fechas/cliente/responsable.

## Incluye

- Endpoint `dashboard/metrics` JSON.
- Service metrics (totales ingreso/examen; por SOLICITUD; ingresos del mes; en proceso EN_PROCESO).
- Gráficos ApexCharts: estado solicitud; tendencia mensual ingresos; distribución cliente y/o responsable.
- Filtros: date_from/date_to (ingreso→fecha_ingreso; examen→fecha_arl), commercial_client_id, responsable_user_id.
- Vista dashboard + JS Vite entry (patrón cursos-dashboard-charts / apex-defaults).
- Tests metrics auth + shape JSON.
- pint.

## No incluye

- Docs finales (Documentador).
- Cambios a Ingreso/Examen salvo consumo read-only.

## Validacion

```bash
php artisan test --compact --filter=Seleccion
npm run build   # si agrega entry Vite
vendor/bin/pint --dirty --format agent
```
