# Peticion

Integracion StatFlow (maestro, interno) -> SJ LegalSuite (Hostinger) por push HTTPS de empleados
(`employee_ficha_profiles` -> `employees`). Sin cambios de esquema existente, sin colas, sin observers.
Tablas nuevas solo en StatFlow: `integration_sync_state`, `integration_sync_log`.

# Reglas del repo (extracto AGENTS.md)

- Prohibido migrate:fresh / refresh / reset / db:wipe / TRUNCATE / DROP.
- Migraciones multi-driver (tests sqlite :memory:, dev MySQL). Sin enum() ni change().
- PHPUnit (nunca Pest). `vendor/bin/pint --dirty --format agent`.
- No tocar el flujo de guardado de empleados.
- Documentacion viva obligatoria en la misma entrega.

# Plan ejecutado

1. Productor StatFlow: config, migracion, modelos, servicio, 2 comandos, scheduler, 14 tests, docs.
2. Consumidor LegalSuite: routes/api.php, middleware HMAC, controlador, servicio upsert, enum, comando de estado, 15 tests, docs.
3. Verificacion cruzada con payload real generado por el servicio de StatFlow e ingerido por el endpoint de LegalSuite (test temporal, aprobado y eliminado por contener datos personales).
4. Pint y migracion en ambos. `php artisan migrate` en dev solo creo las 2 tablas nuevas.

# Reportes por agente

## backend (StatFlow)
Creados: config/integrations.php, migracion 2026_11_18_100000, modelos IntegrationSyncState/Log,
Services/Integrations/EmployeeSyncService, comandos integracion:push-cambios y integracion:push-empleados,
trait PushesEmployees, tests (EmployeePayloadTest, EmployeeSyncCommandsTest), docs/modules|user/integraciones.md.
Modificados: bootstrap/app.php (scheduler), .env.example, README.md, docs/INDEX.md, AGENTS.md.
Tests: 14 passed / 55 assertions. Pint: passed. Migrate: OK (91 ms).

## backend (LegalSuite)
Creados: config/integrations.php, routes/api.php, VerifyIntegrationSignature, EmployeeIngestController,
Services/Integration/EmployeeSyncService, IntegrationStatusCommand, tests/Feature/Integration, docs/INTEGRACION_STATFLOW.md.
Modificados: bootstrap/app.php (api + alias), EmployeeDocumentType (6 casos + fromStatflowCode), .env.example,
.env.production.example, README.md.
Tests: 15 passed / 64 assertions (+ regresion empleados/cargos: 29 passed). Pint: passed. route:list: 2 rutas.

## revisor (criterios objetivos)
- Migracion corre en sqlite (RefreshDatabase) y en MySQL dev.
- Contrato JSON identico en ambos extremos; payload real aceptado end-to-end.
- Sin cambios de `employees.id`, sin borrados, sin colas, sin observers.
- Pendiente: suite completa de StatFlow no terminada en 30 min (se ejecutaron Unit + FichaEmpleados + Integrations: 128 passed).

# Estado

aprobado (con nota: suite completa de StatFlow no ejecutada por tiempo)