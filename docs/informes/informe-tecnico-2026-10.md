# Informe Técnico — SJ StatFlow

**Proyecto:** Plataforma web modular SJ Seguridad (`SJ StatFlow`)
**Fecha de corte:** 29 de octubre de 2026
**Commit de referencia:** `a1adff4` — «ajuste en logica para que las colas de ficha pasen a ficha»
**Preparado para:** Dirección técnica / Arquitectura
**Versión del documento:** 1.0

---

## 1. Objetivo y método

Este informe cuantifica el tamaño del sistema por área funcional y su estado de entrega. El conteo es **estático sobre el árbol de trabajo** (no sobre historial) y usa las siguientes reglas:

- Se cuentan **líneas físicas** de cada archivo (incluye líneas en blanco y comentarios).
- **PHP de negocio:** todo `app/**/*.php` asignado por carpeta (controladores, servicios) o por nombre de archivo (modelos, exports, mail, policies, form requests).
- **Vistas:** `resources/views/**/*.blade.php`.
- **Rutas:** `routes/**/*.php`.
- **Pruebas:** `tests/**/*.php`.
- **JS:** `resources/js/**/*.js`.
- La asignación por área es heurística por ruta y nombre de archivo; los archivos transversales (layouts, componentes, middleware, consola, providers, traits, support) se agrupan en **Núcleo / compartido**.
- No se incluyen en «código funcional» el CSS, migraciones, seeders, configuración ni documentación; se reportan aparte.

> **Nota sobre ejecución de pruebas:** `php artisan test` se aborta a los 300 s por `max_execution_time` de PHP y el flag `-d` no se propaga al subproceso de PHPUnit. La corrida directa con `php -d max_execution_time=0 vendor/bin/phpunit` se detuvo por decisión del usuario al 32% (488/1.520 pruebas), por lo que no hay baseline completo. Las cifras de pruebas de este informe son de conteo estático; el baseline debe registrarse en la validación de cierre (ver §5).

---

## 2. Resumen global por capa

| Capa | Archivos | Líneas |
|---|---:|---:|
| PHP backend (`app/`) | 620 | 76.186 |
| Vistas Blade | 310 | 53.324 |
| Pruebas PHP (`tests/`) | 173 | 45.177 |
| CSS (`resources/css/`) | 1 | 15.232 |
| Migraciones | 159 | 6.675 |
| JavaScript (`resources/js/`) | 22 | 4.541 |
| Configuración (`config/`) | 32 | 3.477 |
| Rutas | 14 | 1.162 |
| Seeders | 13 | 905 |
| Documentación (`docs/**/*.md`) | 285 | 26.941 |
| **Código funcional (PHP + Blade + JS + rutas + pruebas)** | **1.136** | **180.390** |
| **Total del repositorio (incluye CSS, BD, config y docs)** | **1.636** | **233.620** |

**Lectura:** el 42% del PHP es backend de negocio, el 30% son vistas y el 25% son pruebas. La relación pruebas/código funcional es de **25 líneas de prueba por cada 100 líneas de producto**, adecuada para un sistema en operación con entregas semanales.

---

## 3. Líneas de código por área y estado

| # | Área | PHP | Blade | JS | Rutas | Pruebas | **Total** | % | Archivos | Estado |
|---:|---|---:|---:|---:|---:|---:|---:|---:|---:|---|
| 1 | Gestión Humana | 36.890 | 29.841 | 1.936 | 373 | 26.712 | **95.752** | 53,1% | 389 | 🟢 Operativo con observaciones |
| 2 | Compras / Solicitudes de compra | 7.402 | 5.718 | 594 | 130 | 6.709 | **20.553** | 11,4% | 131 | 🟡 En evolución |
| 3 | Núcleo / compartido | 13.420 | 4.000 | 445 | 415 | 2.285 | **20.565** | 11,4% | 303 | 🟢 Operativo |
| 4 | Requisiciones | 3.434 | 3.618 | 355 | 53 | 3.222 | **10.682** | 5,9% | 63 | 🟢 Operativo |
| 5 | Operaciones / Indicadores | 4.963 | 2.449 | 794 | 44 | 1.602 | **9.852** | 5,5% | 71 | 🟢 Operativo |
| 6 | Comercial | 3.064 | 2.180 | 151 | 52 | 1.734 | **7.181** | 4,0% | 49 | 🟡 Operativo con pendientes |
| 7 | Admin / Usuarios / Auditoría | 2.779 | 1.952 | 266 | 0 | 655 | **5.652** | 3,1% | 44 | 🟢 Operativo |
| 8 | Suministros | 1.555 | 1.588 | 0 | 35 | 899 | **4.077** | 2,3% | 30 | 🟢 Operativo |
| 9 | Solicitudes de desarrollo (TIC) | 1.591 | 1.271 | 0 | 37 | 590 | **3.489** | 1,9% | 30 | 🟢 Operativo con observaciones |
| 10 | Calidad / Documentos | 747 | 707 | 0 | 23 | 640 | **2.117** | 1,2% | 16 | 🟢 Operativo |
| 11 | Autenticación y perfil | 341 | 0 | 0 | 0 | 129 | **470** | 0,3% | 10 | 🟢 Operativo |
| | **Total** | **76.186** | **53.324** | **4.541** | **1.162** | **45.177** | **180.390** | **100%** | **1.136** | |

### Estado declarado

| Estado | Significado | Áreas |
|---|---|---|
| 🟢 Operativo | En uso, con pruebas y documentación; sin bloqueos conocidos. | Núcleo, Requisiciones, Operaciones, Admin, Suministros, Calidad, Autenticación |
| 🟢 Operativo con observaciones | En uso; observaciones menores o smoke UI pendiente de validación por el usuario. | Gestión Humana, TIC |
| 🟡 En evolución | Funcionalidad en curso con entregas parciales. | Compras |
| 🟡 Operativo con pendientes | En uso, pero con ítems de revisión abiertos en el tablero. | Comercial |

**Evidencia del estado:**
- **Gestión Humana:** 12 tableros activos (Ficha, Archivo, Cursos, Formación, Selección, Acreditaciones, Cliente interno, Desvinculaciones, MT-ST-04, Cartas, Inasistencias, Reportes de Novedades) y 7 features cerradas en octubre (FEAT-041 a FEAT-046 y FEAT-048).
- **Compras:** FEAT-047 «Proveedores + FO-AD-07 + alineación FO-AD-44» sigue en la fase T4; FEAT-048 se cerró con observaciones y smoke UI pendiente.
- **Comercial:** FEAT-014, FEAT-015 y FEAT-016 figuran implementadas y en revisión en `docs/TASKS.md`.
- **TIC:** FEAT-033 aprobada con observaciones.

---

## 4. Detalle de Gestión Humana (53% del código)

Gestión Humana concentra la mayor inversión. Desglose por tablero (incluye controladores, servicios, form requests, vistas y pruebas; no incluye modelos, exports, rutas ni JS, que suman 6.616 líneas adicionales del área):

| Tablero / módulo | PHP | Vistas | Pruebas | **Total** | % del área |
|---|---:|---:|---:|---:|---:|
| Acreditaciones | 6.925 | 5.917 | 5.777 | **18.619** | 19,4% |
| Ficha de empleados | 4.404 | 3.695 | 5.181 | **13.280** | 13,9% |
| Cursos | 3.204 | 2.866 | 2.766 | **8.836** | 9,2% |
| Desvinculaciones | 2.949 | 2.459 | 2.550 | **7.958** | 8,3% |
| Cliente interno | 2.324 | 2.195 | 2.023 | **6.542** | 6,8% |
| Reportes de Novedades | 2.231 | 3.229 | 1.047 | **6.507** | 6,8% |
| Selección | 2.142 | 2.519 | 1.565 | **6.226** | 6,5% |
| MT-ST-04 | 2.180 | 1.676 | 1.518 | **5.374** | 5,6% |
| Formación | 1.540 | 1.044 | 1.495 | **4.079** | 4,3% |
| Dashboard GH | 1.002 | 830 | 465 | **2.297** | 2,4% |
| Inasistencias | 731 | 845 | 498 | **2.074** | 2,2% |
| Cartas de notificación | 620 | 769 | 497 | **1.886** | 2,0% |
| Plantillas Word | 303 | 925 | 559 | **1.787** | 1,9% |
| Archivo | 478 | 858 | 0 | **1.336** | 1,4% |
| Cartas de contratación | 684 | 0 | 635 | **1.319** | 1,4% |
| Otros GH | 866 | 14 | 136 | **1.016** | 1,1% |
| **Subtotal** | **32.583** | **29.841** | **26.712** | **89.136** | **93,1%** |
| Modelos, exports, mail, rutas y JS del área | 4.307 | — | — | **6.616** | 6,9% |
| **Total del área** | **36.890** | **29.841** | **26.712** | **95.752** | **100%** |

**Concentración de riesgo:** Acreditaciones + Ficha de empleados representan **33% del área** y **18% de todo el código funcional**. Son también los dos controladores más grandes del sistema.

---

## 5. Calidad y pruebas

| Indicador | Valor |
|---|---:|
| Archivos de prueba | 173 |
| Líneas de prueba | 45.177 |
| Métodos de prueba | 1.509 |
| Relación prueba / código funcional | 25,0% |
| Cobertura de pruebas por área (líneas) | GH 59,1% · Compras 14,8% · Requisiciones 7,1% · Núcleo 5,1% · Comercial 3,8% · Operaciones 3,5% · Suministros 2,0% · Admin 1,5% · Calidad 1,4% · TIC 1,3% · Autenticación 0,3% |

**Estado de ejecución:** sin baseline completo al cierre de este informe. Los intentos con `php artisan test --compact` se abortan a los 300 s por `max_execution_time` de PHP; el flag `-d max_execution_time=0` no se propaga porque `artisan test` ejecuta PHPUnit en un subproceso. La corrida directa con PHPUnit se detuvo a solicitud del usuario al 32% (488/1.520 pruebas), con fallos parciales observados. Para obtener el baseline debe invocarse:

```bash
php -d max_execution_time=0 vendor/bin/phpunit
```

y registrar el resumen (`Tests: X passed, Y failed`) en el run log de cierre. Un límite de 300 s resultó insuficiente para esta suite en el entorno local.

**Pruebas más extensas:**

| Archivo | Líneas |
|---|---:|
| `tests/Feature/RequisitionModuleTest.php` | 2.250 |
| `tests/Feature/GestionHumana/AcreditacionesValidacionesTest.php` | 1.355 |
| `tests/Feature/PurchaseRequestModuleTest.php` | 1.309 |
| `tests/Feature/FichaEmpleadosTest.php` | 1.189 |
| `tests/Feature/IndicadorModuleTest.php` | 1.174 |
| `tests/Feature/GestionHumana/AcreditacionesAcreditadosTest.php` | 988 |
| `tests/Feature/CommercialMatrixTest.php` | 856 |
| `tests/Feature/GestionHumana/DesvinculacionesSeguimientosTest.php` | 719 |

---

## 6. Deuda técnica y riesgos

### 6.1 Archivos sobredimensionados

| Archivo | Líneas | Riesgo |
|---|---:|---|
| `resources/css/app.css` | 15.232 | Hoja monolítica: difícil de mantener, sin separación por módulo ni purga efectiva. |
| `app/Http/Controllers/GestionHumana/AcreditacionesController.php` | 1.940 | Controlador con demasiadas responsabilidades (dashboard, acreditados, catálogo, export APO, reporte diario, validaciones). |
| `app/Http/Controllers/GestionHumana/FichaEmpleadosController.php` | 1.063 | CRUD + importación + periodos laborales + cartas en un solo punto. |
| `app/Http/Controllers/GestionHumana/CursosController.php` | 964 | Tablero, registros, catálogo, importación y colas. |
| `app/Http/Controllers/Requisitions/RequisitionController.php` | 900 | Flujo completo de requisición en un controlador. |
| `app/Http/Controllers/Operaciones/IndicadorController.php` | 732 | Captura, dashboard, consolidado y exportaciones. |
| `app/Http/Controllers/GestionHumana/SeleccionController.php` | 728 | Dashboard, ingresos, exámenes y catálogos. |
| `app/Http/Controllers/Supplies/SupplyRequestController.php` | 672 | Solicitud, aprobación y reporte FO-AD-44. |
| `app/Http/Controllers/GestionHumana/MtSt04Controller.php` | 642 | Matriz, importación y validaciones. |
| `app/Http/Controllers/GestionHumana/ClienteInternoController.php` | 577 | Dashboard, solicitudes, catálogos y cartas de vacaciones. |

### 6.2 Hallazgos

1. **Concentración funcional:** el 53% del código está en Gestión Humana. Cualquier cambio transversal (permisos, navegación, auditoría) impacta principalmente esa área.
2. **CSS monolítico:** 15.232 líneas en un solo archivo, superior a toda la capa JS (4.541). Es el mayor archivo del repositorio.
3. **Servicios grandes:** `IndicatorCaptureService` (820), `GestionHumanaAreaDashboardService` (703), `IndicatorCaptureExcelExporter` (675), `NavigationResolver` (641) y `AcreditacionExportApoRowResolver` (609) superan las 600 líneas.
4. **Base de datos extensa:** 104 tablas, 159 migraciones y 88 modelos. El crecimiento ha sido aditivo y ordenado, pero exige revisar índices y consultas de los tableros con más volumen (Acreditaciones, Ficha, Cursos).
5. **Cobertura desigual:** Comercial (3,8%), Operaciones (3,5%), Calidad (1,4%) y TIC (1,3%) tienen poca prueba relativa a su tamaño, aunque sus flujos son más acotados.
6. **Tablero de tareas desactualizado:** `docs/TASKS.md` mantiene en «En progreso» features ya cerradas (FEAT-013, 020, 032, 033, 034), lo que distorsiona el reporte de avance.
7. **Rutas:** 487 declaraciones en 14 archivos; `routes/areas/gestion_humana.php` concentra 373 líneas. La carga modular funciona, pero el archivo de GH es el punto más frágil para conflictos de merge.

---

## 7. Recomendaciones priorizadas

| Prioridad | Acción | Impacto | Esfuerzo |
|---|---|---|---|
| Alta | Cerrar FEAT-047 (proveedores/OC FO-AD-07) y las observaciones de smoke UI de FEAT-042 a FEAT-048. | Reduce riesgo de regresión en producción. | Medio |
| Alta | Depurar `docs/TASKS.md`: mover a «Completadas» las features ya entregadas. | Métricas gerenciales confiables. | Bajo |
| Alta | Dividir `AcreditacionesController` en subcontroladores por tablero (dashboard, acreditados, export APO, validaciones, reporte diario). | Mantenibilidad y pruebas aisladas. | Alto |
| Media | Extraer de `FichaEmpleadosController` los flujos de importación, periodos laborales y cartas a servicios/acciones dedicadas. | Reduce acoplamiento. | Alto |
| Media | Segmentar `resources/css/app.css` por módulo (base, layout, tableros, formularios) y validar el build. | Mantenibilidad de UI. | Medio |
| Media | Elevar cobertura en Comercial, Calidad y TIC con pruebas de flujo crítico (permisos, exportaciones, estados). | Reduce riesgo funcional. | Medio |
| Baja | Automatizar este informe con un comando artisan (`app:code-metrics`) que genere el conteo por área y lo publique en `docs/informes/`. | Repetibilidad y trazabilidad. | Bajo |
| Baja | Revisar índices y consultas de los tableros con DataTables server-side (Acreditaciones, Ficha, Cursos). | Rendimiento con volumen. | Medio |

---

## 8. Anexo — Inventario técnico

| Indicador | Valor |
|---|---:|
| Controladores | 61 |
| Modelos Eloquent | 88 |
| Vistas Blade | 310 |
| Archivos JavaScript | 22 |
| Migraciones | 159 |
| Tablas en base de datos | 104 |
| Seeders | 13 |
| Rutas declaradas | 487 |
| Permisos registrados | 201 |
| Roles | 4 |
| Usuarios activos en BD local | 18 |
| Commits totales | 449 |
| Commits últimos 30 días | 131 |
| Documentos Markdown en `docs/` | 285 |

**Commits por mes:**

| Mes | Commits |
|---|---:|
| Abril 2026 | 10 |
| Mayo 2026 | 12 |
| Julio 2026 | 110 |
| Agosto 2026 | 125 |
| Septiembre 2026 | 79 |
| Octubre 2026 | 113 |

**Migraciones por mes:** abril 12 · mayo 8 · julio 42 · agosto 27 · septiembre 34 · octubre 33.

---

**Fin del informe técnico.**
