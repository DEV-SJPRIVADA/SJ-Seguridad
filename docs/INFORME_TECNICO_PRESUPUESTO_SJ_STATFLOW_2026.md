# Informe técnico y presupuesto de construcción
## Plataforma SJ StatFlow — SJ Seguridad

| Campo | Valor |
|---|---|
| Cliente | SJ Seguridad |
| Producto | SJ StatFlow (`APP_NAME`) |
| Tipo de documento | Auditoría técnica y estimación económica de reconstrucción |
| Alcance | Construcción del proyecto desde cero, con equipo humano y **sin inteligencia artificial** |
| Fecha | 5 de noviembre de 2026 |
| Versión | 1.0 |
| Moneda | Pesos colombianos (COP) |
| TRM de referencia | 1 USD = 4.000 COP |
| Exactitud de la estimación | ±20 % |

> **Nota de método:** este informe se elaboró revisando directamente el código, las rutas, las migraciones, las vistas, las pruebas y el historial Git del repositorio. **No se tomaron cifras de documentos, informes o presupuestos previos.** Las únicas fuentes son el código y el entorno de desarrollo.
>
> **Archivo complementario:** el presupuesto también está disponible en `docs/PRESUPUESTO_SJ_STATFLOW_2026.xlsx`, con hojas de resumen, personal, otros costos, escenarios, módulos, fases y supuestos. Allí puede ajustar salarios, personas-mes y porcentajes, y las fórmulas se recalculan.

---

## Contenido

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Metodología, alcance y límites](#2-metodología-alcance-y-límites)
3. [Radiografía técnica del sistema](#3-radiografía-técnica-del-sistema)
4. [Estimación del esfuerzo](#4-estimación-del-esfuerzo)
5. [Presupuesto de personal](#5-presupuesto-de-personal)
6. [Otros costos del proyecto](#6-otros-costos-del-proyecto)
7. [Presupuesto total y escenarios](#7-presupuesto-total-y-escenarios)
8. [Cronograma propuesto](#8-cronograma-propuesto)
9. [Supuestos, exclusiones y condiciones](#9-supuestos-exclusiones-y-condiciones)
10. [Riesgos presupuestales](#10-riesgos-presupuestales)
11. [Recomendaciones](#11-recomendaciones)
12. [Anexos técnicos](#12-anexos-técnicos)

---

# 1. Resumen ejecutivo

SJ StatFlow es una plataforma web empresarial de operación interna para SJ Seguridad. No es un portal corporativo ni un CRUD aislado: es un **monolito modular Laravel** que cubre, en un solo sistema, procesos de Gestión Humana, requisiciones de personal, suministros, solicitudes y órdenes de compra, indicadores de Operaciones, gestión comercial, documentos de Calidad, solicitudes de desarrollo de TIC, administración de usuarios, permisos, notificaciones y auditoría.

La revisión directa del repositorio arroja las siguientes métricas:

| Indicador | Valor |
|---|---:|
| Áreas de negocio configuradas | 10 |
| Tableros funcionales | 25 |
| Rutas HTTP registradas | 497 |
| Controladores | 61 |
| Modelos Eloquent | 88 |
| Servicios de aplicación | 210 |
| Migraciones | 160 |
| Tablas en base de datos | 104 |
| Vistas Blade | 312 |
| Componentes Blade | 23 |
| Archivos JavaScript | 22 |
| Permisos granulares | 201 |
| Roles | 4 |
| Archivos de prueba | 174 |
| Líneas de código aproximadas | 210.000 |
| Commits | 464 |
| Periodo del historial | 23-abr-2026 a 04-nov-2026 |

El sistema tiene una complejidad **alta**: combina reglas de negocio densas, importaciones masivas desde Excel, generación de documentos Word/PDF/Excel/PPTX, flujos de aprobación por correo con enlaces firmados, tableros con gráficos, auditoría central y un modelo de permisos por área, tablero y pestaña.

### Conclusión económica

Reconstruir este alcance desde cero, con un equipo humano convencional, **sin asistentes de IA** y en Colombia, requiere aproximadamente:

| Concepto | Valor |
|---|---:|
| Esfuerzo | **202 personas-mes** |
| Duración | **18 meses** |
| Equipo promedio | **11,2 personas** |
| Costo de personal | **$ 3.169.750.000 COP** |
| Otros costos | **$ 480.000.000 COP** |
| Costo directo | **$ 3.649.750.000 COP** |
| Overhead administrativo (10 %) | **$ 364.975.000 COP** |
| Contingencia (15 %) | **$ 602.208.750 COP** |
| **Subtotal sin IVA** | **$ 4.616.933.750 COP** |
| IVA 19 % (si aplica) | **$ 877.217.413 COP** |
| **Total con IVA** | **$ 5.494.151.163 COP** |
| Equivalente en USD | **USD 1.154.233 sin IVA / USD 1.373.538 con IVA** |

El 52 % del esfuerzo se concentra en Gestión Humana y cadena de abastecimiento. El 14 % en indicadores, comercial, calidad y TIC. El 34 % restante en núcleo técnico, gestión, seguridad, pruebas, migración, despliegue y capacitación.

### Lectura clave

El repositorio actual se construyó en aproximadamente **6,5 meses** con **464 commits** y **104 tablas**. Esa velocidad no debe asumirse como línea base para un equipo convencional sin asistencia de IA. Por eso la estimación incorpora un **factor explícito de 1,20** por ausencia de IA, además de un factor de ingeniería de 1,47 por análisis, diseño, pruebas, gestión y retrabajo.

La recomendación no es reconstruir todo de una vez. Es **fasar el proyecto** y priorizar el núcleo, requisiciones, suministros y compras, que son los procesos de mayor impacto operativo y los que concentran más reglas de negocio.

---

# 2. Metodología, alcance y límites

## 2.1 Qué se revisó

Se inspeccionaron directamente:

- `composer.json` y `package.json`: stack, dependencias y versiones.
- `config/access.php`: áreas, tableros, permisos, navegación y catálogo de accesos.
- `routes/web.php`, `routes/auth.php`, `routes/console.php`, `routes/areas/*` y `routes/modules/*`: superficie funcional y middleware.
- `app/Http/Controllers`, `app/Services`, `app/Models`, `app/Policies`, `app/Middleware`, `app/Exports`, `app/Mail`, `app/Console/Commands`: lógica y patrones.
- `database/migrations`: 160 migraciones y modelo de datos.
- `resources/views`, `resources/js`, `vite.config.js`: interfaz, componentes y gráficos.
- `tests`, `phpunit.xml`: estrategia de pruebas.
- `bootstrap/app.php`: middleware, scheduler y manejo de excepciones.
- Historial Git: volumen, periodo y velocidad de construcción.
- Consultas de solo lectura al entorno: `php artisan route:list`, conteo de tablas, roles y permisos.

## 2.2 Qué no se revisó

- Documentos funcionales, manuales, actas o presupuestos anteriores.
- Entrevistas con usuarios, dueños de proceso o TI.
- Métricas de producción: usuarios concurrentes, volumen real, tiempos de respuesta, incidentes.
- Contratos, licencias vigentes, costos históricos o nómina real de SJ Seguridad.
- Infraestructura productiva y acuerdos de nivel de servicio.

## 2.3 Límites de la estimación

- La exactitud es **±20 %**. Sin levantamiento funcional detallado, el rango puede ampliarse.
- No se incluyen funcionalidades nuevas que no existan hoy en el código.
- No se incluye una reingeniería de experiencia de usuario; se asume paridad funcional y una capa visual profesional.
- Los salarios son **rangos de mercado 2026** para Bogotá/Medellín, no nómina real de SJ Seguridad.
- Los precios de infraestructura y licencias son referencias de mercado; no son cotizaciones formales.
- La estimación asume un equipo estable, con disponibilidad de usuarios clave y decisiones oportunas.

## 2.4 Definición de “sin inteligencia artificial”

Este presupuesto asume dos cosas:

1. **Sin IA en el proceso de construcción:** no se usan asistentes de código, generadores de pruebas, agentes autónomos ni herramientas de IA para analizar, programar, documentar o probar. El equipo trabaja con IDE, depurador, repositorio, CI/CD y revisión humana.
2. **Sin IA en el producto:** el sistema no incorpora modelos de lenguaje, OCR inteligente, machine learning, chatbots, motores de recomendación ni clasificación automática. Todo el procesamiento es determinista: reglas, validaciones, catálogos, importaciones Excel/CSV, expresiones regulares y generación de documentos por plantilla.

**Consecuencia económica:** se aplica un factor de productividad de **1,20** sobre el esfuerzo de ingeniería. Además, el producto evita costos recurrentes de tokens, OCR o servicios cognitivos, pero exige más trabajo manual en depuración de datos y revisión de documentos no estructurados.

---

# 3. Radiografía técnica del sistema

## 3.1 Stack tecnológico

| Capa | Tecnología | Versión / observación |
|---|---|---|
| Lenguaje | PHP | 8.3 |
| Framework | Laravel | 13 |
| Base de datos | MySQL | 8 en desarrollo y producción |
| Base de datos de pruebas | SQLite | `:memory:` |
| Frontend | Blade + Alpine.js + Tailwind | Alpine 3.4, Tailwind 3.1 |
| Build | Vite | 6 |
| Gráficos | ApexCharts | 6.6 |
| Tablas | DataTables + jQuery | 1.13.7 y 3.7.1, cargados por CDN |
| Iconos | Blade Lucide / Selfhst | Paquetes Composer |
| Autenticación y permisos | Sesión + `spatie/laravel-permission` | 6.25 |
| Excel | `phpoffice/phpspreadsheet` | 5.8 |
| Word | `phpoffice/phpword` | 1.4 |
| PDF | `barryvdh/laravel-dompdf` | 3.1 |
| Colas | Driver `database` | 1 job registrado |
| Caché y sesión | Driver `database` | Sin Redis en la configuración base |
| Correo | SMTP | 19 clases `Mailable` |
| Pruebas | PHPUnit | 12 |
| Servidor | Linux (VPS/dedicado) | Nginx/Apache + PHP-FPM + MySQL + cron + worker de colas |

## 3.2 Arquitectura

El sistema es un **monolito modular**. No usa microservicios, API separada, Livewire, Inertia, React ni Vue. La interfaz se renderiza en servidor con Blade y se hidrata con Alpine.js.

```text
Navegador
  │
  ├─ Blade + Alpine + Tailwind + ApexCharts + DataTables
  │
  ▼
Laravel 13
  ├─ routes/web.php
  │    ├─ routes/modules/*.php      (compartidos: requisiciones, suministros, compras, calidad, TIC)
  │    └─ routes/areas/*.php        (exclusivos: GH, comercial, operaciones, compras)
  │
  ├─ Middleware
  │    ├─ auth
  │    ├─ active                  (usuario inactivo no opera)
  │    ├─ password.changed        (contraseña temporal obligatoria)
  │    ├─ permission / role       (Spatie)
  │    └─ *.tab                   (acceso por pestaña)
  │
  ├─ Controllers (61)
  │    └─ validación + autorización + orquestación
  │
  ├─ Services (210)
  │    └─ reglas de negocio, importaciones, exportaciones, documentos, auditoría
  │
  ├─ Models (88) + Policies (5)
  │
  └─ MySQL 8 (104 tablas)
       ├─ datos de negocio
       ├─ audit_logs
       ├─ notifications / notification_types
       ├─ jobs / failed_jobs
       ├─ sessions / cache
       └─ permisos Spatie
```

### Patrones presentes

- **Separación por dominio:** controladores y servicios agrupados por área o módulo.
- **Capa de servicios:** la lógica compleja no vive solo en el controlador; hay servicios de importación, exportación, generación de documentos, cálculo de estados, auditoría y acceso.
- **Servicios de acceso:** `app/Services/Access/*` resuelven visibilidad por área, tablero y pestaña.
- **Configuración como fuente de verdad:** `config/access.php` define áreas, tableros, pestañas y permisos; la navegación se genera desde ahí.
- **Middleware por pestaña:** cada tablero tiene un middleware que valida el permiso específico.
- **Policies:** para requisiciones, compras, suministros, documentos de calidad y solicitudes de desarrollo.
- **Auditoría central:** `SystemAuditService` registra cambios con valores anteriores/posteriores, IP, usuario, agente y lote de cambio.
- **Transacciones:** 69 usos de `DB::transaction` en la capa de aplicación.
- **Enlaces firmados:** 11 usos de rutas firmadas temporales para aprobaciones por correo.
- **Trabajo asíncrono:** cola `database`, un job de auditoría y seis tareas programadas.

## 3.3 Métricas del repositorio

| Componente | Archivos | Líneas aproximadas |
|---|---:|---:|
| `app/` (PHP) | 622 | 77.143 |
| `resources/` (Blade, JS, CSS) | 334 | 73.758 |
| `database/` (migraciones, seeders) | 203 | 8.842 |
| `tests/` | 174 | 46.101 |
| `routes/` | 14 | 1.164 |
| `config/` | 32 | 3.488 |
| **Total estimado** | **~1.379** | **~210.000** |

### Superficie HTTP

| Método | Rutas |
|---|---:|
| GET/HEAD | 297 |
| POST | 110 |
| PATCH | 58 |
| DELETE | 28 |
| PUT | 3 |
| PUT/PATCH | 1 |
| **Total** | **497** |

### Distribución por prefijo

| Prefijo | Rutas |
|---|---:|
| `gestion-humana` | 263 |
| `purchase-requests` | 66 |
| `comercial` | 28 |
| `operaciones` | 28 |
| `requisitions` | 25 |
| `supplies` | 22 |
| `quality-documents` | 16 |
| `development-requests` | 15 |
| `admin` | 14 |
| Autenticación, perfil y sistema | ~20 |

### Complejidad de código

| Archivo | Líneas | Señal |
|---|---:|---|
| `AcreditacionesController` | 1.940 | Controlador más grande del sistema |
| `FichaEmpleadosController` | 1.063 | Alta densidad funcional |
| `CursosController` | 970 | Importaciones, catálogos y documentos |
| `RequisitionController` | 900 | Flujo completo de requisiciones |
| `IndicatorCaptureService` | 820 | Captura y consolidación de indicadores |
| `GestionHumanaAreaDashboardService` | 703 | Dashboard transversal de GH |
| `NavigationResolver` | 641 | Navegación dinámica por permisos |
| `User` | 517 | Modelo con lógica de acceso por tablero |

## 3.4 Modelo de datos

La base tiene **104 tablas**. Se agrupan así:

| Dominio | Tablas representativas |
|---|---|
| Seguridad y usuarios | `users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `user_managed_areas`, `supply_sites` |
| Requisiciones | `personal_requisitions`, `personal_requisition_status_logs`, `personal_requisition_change_logs`, `personal_requisition_ficha_entries`, `requisition_*` (catálogos), `requisition_notification_*` |
| Suministros | `supply_requests`, `supply_request_items`, `supply_request_comments`, `supply_products`, `supply_sites` |
| Compras | `purchase_requests`, `purchase_request_items`, `purchase_request_attachments`, `purchase_request_comments`, `purchase_orders`, `purchase_order_items`, `purchase_order_evaluations`, `purchase_providers`, `purchase_tax_rates`, `purchase_request_mail_logs` |
| Gestión Humana | `employee_ficha_profiles`, `employee_ficha_employment_periods`, `employee_archive_consultations`, `employee_cursos`, `employee_curso_pending`, `formacion_registros`, `seleccion_ingresos`, `seleccion_examenes_ocupacionales`, `acreditacion_*`, `reportes_novedades_*`, `cliente_interno_*`, `mt_st_04_registros`, `inasistencia_cartas`, `employee_termination_followups` |
| Documentos y plantillas | `quality_documents`, `quality_document_areas`, `quality_document_users`, `word_document_types`, `termination_letter_document_templates` |
| Comercial | `commercial_clients`, `commercial_services`, `commercial_portfolios`, `commercial_sectors`, `commercial_client_types`, `commercial_service_types`, `commercial_client_document_items`, `commercial_*_notification_logs` |
| Indicadores | `indicators`, `indicator_periods`, `indicator_captures`, `indicator_improvements`, `indicator_dashboard_weights`, `indicator_system_documents`, `indicator_audit_logs`, `management_report_drafts` |
| Desarrollo TIC | `development_requests`, `development_request_attachments`, `development_request_status_logs`, `development_request_messages` |
| Notificaciones | `notification_types`, `notification_emails`, pivotes |
| Infraestructura Laravel | `jobs`, `failed_jobs`, `sessions`, `cache`, `cache_locks` |
| Auditoría | `audit_logs` |

### Características del modelo

- 160 migraciones, muchas de ellas evolutivas y aditivas.
- Compatibilidad multi-driver: MySQL en operación, SQLite en pruebas.
- Uso de JSON para valores anteriores/posteriores de auditoría y metadatos.
- Índices compuestos en `audit_logs` por módulo, área, usuario, tipo de evento y fecha.
- Catálogos de nómina y documentos parametrizados en base de datos.
- Historiales de estado y cambios embebidos por dominio.

## 3.5 Módulos funcionales

| Área | Tableros / módulos | Funcionalidad principal | Complejidad | PM estimados |
|---|---|---|---|---:|
| Núcleo y administración | Dashboard, usuarios, notificaciones, auditoría | Login, RBAC por área/tablero/pestaña, navegación dinámica, auditoría, configuración de correos, sedes | Alta | 18 |
| Gestión Humana — Requisiciones | Requisiciones | Solicitud, seguimiento, gestión, autorización de gerencia, parámetros, aprobación por correo, impresión, exportación, sincronización con ficha | Alta | 12 |
| Gestión Humana — Ficha y ciclo laboral | Ficha empleados, Archivo, Plantillas Word, Cartas Notificación, Inasistencias, Desvinculaciones | Ficha, periodos laborales, importación/exportación, cartas de contratación y terminación, plantillas Word, archivo documental, inasistencias, desvinculaciones masivas y seguimiento | Muy alta | 23 |
| Gestión Humana — Formación | Cursos, Formación, Selección, MT-ST-04 | Cursos y escuelas, validaciones, documentos, formación, ingresos, exámenes ocupacionales, matriz MT-ST-04 | Alta | 19 |
| Gestión Humana — Acreditaciones y novedades | Acreditaciones, Reportes Novedades, Cliente Interno, Dashboard GH | Acreditados, cargos, reporte diario, validaciones, export APO, vacaciones, incapacidades, retiros, permisos, cartas, dashboard | Muy alta | 23 |
| Compras y abastecimiento | Suministros, Solicitudes de compra, Bandeja compras, Proveedores, Órdenes de compra | Solicitudes, aprobación de calidad, catálogo, ítems, proveedores, órdenes, evaluaciones, anulaciones, impuestos, correos, PDF/Excel | Muy alta | 27 |
| Operaciones | Dashboard, Indicadores | Captura, periodos, metas, pesos, consolidado, auditoría, PDF, Excel, informe de gestión PPTX | Alta | 13 |
| Comercial | Dashboard, Gestión Clientes | Clientes, servicios, cartera, sectores, checklist documental, notificaciones, importación/exportación | Media | 7 |
| Calidad | Biblioteca de documentos | Biblioteca, mis documentos, administración, tipos, áreas, usuarios, abrir/descargar | Media | 4 |
| TIC | Solicitudes de desarrollo | Creación, mis solicitudes, aprobación de líder, bandeja TIC, mensajes, adjuntos, UAT | Media | 4 |
| Gestión, análisis y arquitectura | Transversal | Descubrimiento, arquitectura, análisis funcional, coordinación | — | 22 |
| QA, seguridad y hardening | Transversal | Pruebas, automatización, pentest, correcciones | — | 20 |
| Migración, UAT, despliegue y capacitación | Transversal | ETL, aceptación, puesta en producción, entrenamiento | — | 10 |
| **Total** | **25 tableros** | | | **202** |

### Detalle funcional relevante

- **Requisiciones:** flujo con solicitante, encargado de selección, gerencia y aprobación por correo. Catálogos de cargos, ciudades, clientes, tipos de contrato, uniformes y motivos. Impresión y exportación. Sincronización con ficha de empleado.
- **Suministros:** solicitud por sede, ítems de catálogo o personalizados, aprobación de Calidad, comentarios, PDF/Excel y generación de orden de compra.
- **Compras:** solicitud con ítems, importación de ítems, adjuntos, comentarios, autorización de director, bandeja de Compras, proveedores, órdenes, evaluación, anulación, tasas de IVA, aprobación por correo y documentos PDF/Excel.
- **Ficha de empleados:** perfil, periodos laborales, catálogos de nómina, importación masiva, cartas de contratación, cartas por tipo, terminación, cursos y consulta de archivo.
- **Acreditaciones:** acreditados, cargos, reporte diario, validaciones, export APO, catálogo, importaciones y ejecuciones.
- **Reportes de novedades:** vacaciones, incapacidades, retiros y permisos, con creación, edición, revisión de nómina, historial, exportación y cartas Word.
- **Indicadores:** captura por indicador, periodos, metas, pesos, consolidado, auditoría, exportación PDF/Excel y generación de informe de gestión en PPTX a partir de plantilla.
- **Comercial:** clientes, servicios, cartera, sectores, checklist documental, notificaciones y digest diario.
- **Calidad:** biblioteca documental con visibilidad por área y usuario.
- **TIC:** solicitudes de desarrollo con aprobación de líder, bandeja de atención, mensajes, adjuntos y UAT.

## 3.6 Seguridad

### Controles presentes

| Control | Implementación |
|---|---|
| Autenticación | Sesión web con `Auth`, login, logout, recuperación y confirmación de contraseña |
| Rate limiting de login | 5 intentos por email + IP, con bloqueo temporal |
| Usuario activo | Middleware `active`; un usuario inactivo es deslogueado |
| Contraseña temporal | `must_change_password` + middleware `password.changed` |
| Roles y permisos | Spatie con 201 permisos granulares y 4 roles |
| Permisos por pestaña | Middleware dedicado por módulo (`requisition.tab`, `purchase.tab`, `supply.tab`, `devreq.tab`, `indicador.tab`) |
| Policies | 5 modelos críticos |
| CSRF | Middleware web de Laravel |
| Validación | Validación de entrada en controladores y Form Requests |
| Enlaces de aprobación | Rutas firmadas temporales, con expiración |
| Archivos | Disco privado local; descargas controladas por controlador |
| Auditoría | `audit_logs` con usuario, IP, user-agent, valores anteriores y posteriores |
| Retención de auditoría | 24 meses; purga mensual programada |
| Registro público | Deshabilitado |
| Seed de administrador | Comando `app:restore-admin` y `app:doctor` |

### Brechas y observaciones

| Brecha | Riesgo | Recomendación |
|---|---|---|
| Sin 2FA/MFA | Medio-alto para usuarios administrativos | Implementar TOTP para `super-admin`, `administrador` y `director` |
| DataTables y jQuery por CDN | Disponibilidad, integridad y dependencia externa | Vendorizar los assets y agregar SRI/CSP |
| Sin CSP ni cabeceras de seguridad explícitas | XSS/clickjacking | Configurar CSP, `X-Frame-Options`, `Referrer-Policy`, HSTS |
| Sesión sin cifrado (`SESSION_ENCRYPT=false`) | Exposición si hay acceso al almacenamiento | Evaluar cifrado de sesión |
| Sin gestión formal de secretos | Rotación y fuga de credenciales | Usar variables de entorno y bóveda de secretos |
| Sin análisis estático ni escaneo de dependencias en CI | Vulnerabilidades no detectadas | Añadir Larastan/PHPStan, `composer audit`, Dependabot |
| Sin política de datos personales en código | Cumplimiento Ley 1581 de 2012 | Definir retención, consentimiento, acceso y borrado |
| `APP_DEBUG=true` en ejemplo local | Riesgo si se replica en producción | Forzar `APP_DEBUG=false` en producción |
| Contraseña semilla en `.env.example` | Riesgo de reutilización | Rotar y eliminar del ejemplo |
| Sin API ni autenticación de API | No aplica hoy, pero limita integraciones | Diseñar API solo si el negocio la requiere |

## 3.7 Documentos, importaciones y reportes

El sistema tiene una carga importante de procesamiento documental:

- **31 clases de exportación** en `app/Exports`, con `BaseExport` como base.
- **19 clases de correo** en `app/Mail`.
- **Importaciones masivas** con plantillas, validación, reporte de fallos y tokens de descarga.
- **Generación de Word** con PHPWord y plantillas `.docx`.
- **Generación de PDF** con DomPDF.
- **Generación de Excel** con PhpSpreadsheet.
- **Generación de PPTX** mediante manipulación de un archivo plantilla y XML interno (no usa una librería de presentaciones).
- **Cartas y plantillas** de contratación, terminación, vacaciones, inasistencias y notificaciones.
- **Firmas** y paquetes ZIP para procesos masivos.
- **Reportes de importación** con errores por fila.

Esta es una de las áreas de mayor esfuerzo de reconstrucción, porque combina reglas de negocio, formato, codificación, nombres de archivo, plantillas y validaciones.

## 3.8 Automatizaciones

El scheduler ejecuta:

| Tarea | Frecuencia | Función |
|---|---|---|
| `comercial:send-documentation-notification-digest` | Diaria 06:00 | Digest de documentación comercial |
| `comercial:send-service-contract-notification-digest` | Diaria 06:00 | Digest de contratos por vencer |
| `cursos:sync-estados` | Diaria 06:15 | Sincroniza estados de cursos |
| `acreditaciones:sync-estados` | Diaria 06:20 | Sincroniza estados de acreditaciones |
| `mt_st_04:sync-estados` | Diaria 06:25 | Sincroniza estados MT-ST-04 |
| `audit:purge --force` | Mensual, día 1, 03:00 | Purga auditoría según retención |

Además hay 14 comandos Artisan, entre ellos importadores, backfills, seeders de catálogos y diagnósticos.

## 3.9 Calidad y pruebas

| Indicador | Valor |
|---|---:|
| Archivos de prueba | 174 |
| Pruebas Feature | 154 |
| Pruebas Unit | 18 |
| Soporte | 1 |
| Líneas de prueba | 46.101 |
| Framework | PHPUnit 12 |
| Base de pruebas | SQLite `:memory:` |
| Aislamiento | `RefreshDatabase` |
| Seeders en pruebas | `RoleAndPermissionSeeder` |

### Concentración de pruebas por dominio

| Dominio | Archivos |
|---|---:|
| Gestión Humana | 81 |
| Purchase Requests | 21 |
| Auth | 5 |
| Admin | 4 |
| Development Requests | 4 |
| Requisitions | 2 |
| Comercial | 1 |
| Quality Documents | 1 |
| Supplies | 1 |

### Brechas de calidad

- Cobertura desigual: Comercial, Calidad y Suministros tienen una sola prueba cada uno.
- No hay pruebas E2E ni de navegador.
- No hay análisis estático (PHPStan/Larastan) en el repositorio.
- No hay pipeline CI/CD versionado.
- Las pruebas corren en SQLite, no en MySQL; pueden escapar diferencias de comportamiento.
- No hay pruebas de carga, estrés o accesibilidad.
- No hay pruebas de regresión visual.
- Este informe no certifica la tasa de éxito de la suite. Esa medición debe hacerse en CI, preferiblemente contra MySQL además de SQLite.
- Ejecución local de referencia: `php artisan test --compact` no completa dentro del límite de 300 segundos del proceso; aborta por timeout de PHP en `Tests\Feature\GestionHumana\CursosImportTest`. Antes del corte se observaron fallos en varios archivos de Gestión Humana. Conclusión operativa: la suite requiere ejecución particionada (`--parallel` o por dominio) y un pipeline dedicado; hoy no es un gate de calidad confiable en un solo comando.

## 3.10 Deuda técnica identificada

| Ítem | Evidencia | Impacto | Esfuerzo de corrección |
|---|---|---|---:|
| DataTables/jQuery por CDN | `layouts/app.blade.php` carga jQuery y DataTables desde `code.jquery.com` y `cdn.datatables.net` | Disponibilidad, integridad, offline | 2-3 semanas |
| Controladores muy grandes | `AcreditacionesController` 1.940 líneas; `FichaEmpleadosController` 1.063 | Mantenibilidad, riesgo de regresión | 4-6 semanas |
| Servicios extensos | Varios servicios superan 600-800 líneas | Complejidad ciclomática | 3-5 semanas |
| JS de gráficos duplicado | 12 archivos de charts con patrones repetidos | Mantenimiento | 2-3 semanas |
| Cache busting con `time()` | `?v={{ time() }}` en CSS | Rompe caché del navegador | 1 semana |
| Sin análisis estático | No hay PHPStan/Larastan | Calidad y tipos | 2-3 semanas |
| Sin CI/CD | No hay workflow de GitHub Actions/GitLab CI | Despliegue manual y riesgo | 3-4 semanas |
| Sin E2E | No hay Playwright/Selenium | Regresiones en flujos críticos | 4-6 semanas |
| Sin 2FA | Solo usuario/contraseña | Seguridad de administradores | 2-3 semanas |
| Permisos legacy mezclados | `admin_hidden_permissions` y permisos antiguos | Confusión y riesgo de sobrepermiso | 3-4 semanas |
| Sin observabilidad central | Solo `Log` y auditoría de negocio | Diagnóstico lento | 2-3 semanas |
| Servidor único | Sin alta disponibilidad | Continuidad | 4-6 semanas |
| Sin política de datos personales | No hay retención/borrado formal | Cumplimiento | 3-4 semanas |

**Esfuerzo estimado de saneamiento:** 25-35 personas-mes. No está incluido en el presupuesto base; se recomienda una fase de hardening de 8-10 semanas después del go-live o antes de escalar.

---

# 4. Estimación del esfuerzo

## 4.1 Modelo de estimación

Se usó un modelo híbrido de tamaño de código y juicio experto, calibrado con la complejidad funcional observada.

### Cálculo base

| Paso | Cálculo | Resultado |
|---|---|---:|
| Tamaño total del sistema | PHP + Blade + JS + migraciones + pruebas + configuración | ~210.000 líneas |
| Productividad conservadora | 100 líneas efectivas por día-persona (incluye pruebas y UI) | 2.100 días-persona |
| Días laborales por año | 220 | 9,55 personas-año |
| Personas-mes | 9,55 × 12 | 114,6 PM |
| Factor de ingeniería | Análisis, diseño, arquitectura, QA, gestión, retrabajo (×1,47) | 168,4 PM |
| Factor sin IA | Ausencia de asistentes de código/pruebas/documentación (×1,20) | **202,0 PM** |

> **Por qué 100 líneas/día:** es un promedio conservador para código de producción con pruebas, validaciones, UI y documentación. No incluye prototipos desechables ni generación automática.

> **Por qué 1,20 por “sin IA”:** el repositorio actual evidencia una velocidad muy alta (104 tablas, 497 rutas, 312 vistas y 174 archivos de prueba en 6,5 meses). Un equipo convencional debe presupuestar más tiempo en codificación, pruebas, documentación y corrección. Si el equipo no usa ninguna herramienta de IA, el factor podría llegar a 1,30; el escenario conservador cubre ese riesgo.

## 4.2 Distribución por fase

| Fase | Duración | PM | Entregables principales |
|---|---:|---:|---|
| F0. Descubrimiento, arquitectura y UX base | 2 meses | 14 | Levantamiento, modelo de datos, arquitectura, design system, CI/CD inicial, plan de seguridad |
| F1. Núcleo de plataforma | 3 meses | 26 | Login, RBAC, usuarios, áreas, tableros, navegación, auditoría, notificaciones, layout |
| F2. Gestión Humana — núcleo | 5 meses | 42 | Requisiciones, ficha de empleados, archivo, plantillas, cartas, inasistencias, desvinculaciones |
| F3. Gestión Humana — avanzado | 5 meses | 38 | Cursos, formación, selección, MT-ST-04, acreditaciones, novedades, cliente interno, dashboard GH |
| F4. Compras y abastecimiento | 5 meses | 36 | Suministros, solicitudes de compra, bandeja, proveedores, órdenes, evaluaciones, impuestos |
| F5. Operaciones, comercial, calidad y TIC | 4 meses | 28 | Indicadores, informe de gestión, comercial, biblioteca de calidad, solicitudes TIC |
| F6. Migración, UAT, seguridad, despliegue y capacitación | 2 meses | 18 | ETL, pruebas de aceptación, pentest, producción, manuales y entrenamiento |
| **Total** | **18 meses** | **202** | |

Las fases se solapan: F1 inicia en el mes 2, F2 en el mes 4, F4 en el mes 8, F5 en el mes 11 y F6 en el mes 16.

## 4.3 Distribución por disciplina

| Disciplina | PM | % |
|---|---:|---:|
| Gestión de proyecto / Scrum | 17 | 8,4 % |
| Análisis funcional / BA | 15 | 7,4 % |
| Arquitectura / Tech Lead | 17 | 8,4 % |
| Desarrollo backend | 62 | 30,7 % |
| Desarrollo frontend | 32 | 15,8 % |
| QA / pruebas | 27 | 13,4 % |
| UX/UI | 7,5 | 3,7 % |
| DBA | 6,5 | 3,2 % |
| DevOps / SRE | 8 | 4,0 % |
| Seguridad | 4 | 2,0 % |
| Documentación / capacitación | 6 | 3,0 % |
| **Total** | **202** | **100 %** |

---

# 5. Presupuesto de personal

## 5.1 Supuestos salariales

- Salarios brutos mensuales de mercado 2026 para Bogotá/Medellín.
- Perfiles senior y semi-senior, con inglés técnico deseable pero no obligatorio.
- Jornada de 160 horas/mes.
- **Factor prestacional de 1,55** sobre el salario bruto. Incluye salud, pensión, ARL, cesantías, intereses de cesantías, prima, vacaciones, parafiscales y auxilio de transporte cuando aplica.
- No incluye overhead administrativo, utilidad, impuestos ni beneficios extralegales.
- No incluye bonos por desempeño ni ajustes por inflación.
- Los valores están redondeados a miles de pesos.

## 5.2 Detalle de roles, costo mensual y costo total

| Rol | Perfil requerido | Salario base mensual | Costo cargado mensual (×1,55) | Costo hora cargado | PM | Costo total |
|---|---|---:|---:|---:|---:|---:|
| Gerente de proyecto / Scrum Master | 5+ años, PMP/Scrum, proyectos empresariales | $ 11.000.000 | $ 17.050.000 | $ 106.562 | 17 | $ 289.850.000 |
| Analista funcional / BA | 4+ años, UML/BPMN, dominio HR/compras | $ 8.000.000 | $ 12.400.000 | $ 77.500 | 15 | $ 186.000.000 |
| Arquitecto de software / Tech Lead | 8+ años, Laravel/PHP, DDD, seguridad, performance | $ 16.000.000 | $ 24.800.000 | $ 155.000 | 17 | $ 421.600.000 |
| Desarrollador backend senior | 5+ años Laravel, MySQL, colas, pruebas | $ 12.000.000 | $ 18.600.000 | $ 116.250 | 33 | $ 613.800.000 |
| Desarrollador backend semi-senior | 3+ años Laravel, Eloquent, APIs internas | $ 8.500.000 | $ 13.175.000 | $ 82.344 | 29 | $ 382.075.000 |
| Desarrollador frontend senior | 5+ años Blade/Alpine/Tailwind/JS, accesibilidad, charts | $ 10.500.000 | $ 16.275.000 | $ 101.719 | 17 | $ 276.675.000 |
| Desarrollador frontend semi-senior | 3+ años Blade/Tailwind/JS | $ 8.000.000 | $ 12.400.000 | $ 77.500 | 15 | $ 186.000.000 |
| QA lead / automatización | 5+ años, PHPUnit, Playwright/Selenium, CI | $ 9.000.000 | $ 13.950.000 | $ 87.188 | 15 | $ 209.250.000 |
| QA manual | 3+ años, casos de prueba, UAT | $ 6.500.000 | $ 10.075.000 | $ 62.969 | 12 | $ 120.900.000 |
| Diseñador UX/UI | 4+ años, design systems, Figma, accesibilidad | $ 8.500.000 | $ 13.175.000 | $ 82.344 | 7,5 | $ 98.812.500 |
| DBA | 5+ años MySQL, tuning, backups, replicación | $ 10.500.000 | $ 16.275.000 | $ 101.719 | 6,5 | $ 105.787.500 |
| DevOps / SRE | 5+ años Linux, Nginx, CI/CD, monitoreo, IaC | $ 12.000.000 | $ 18.600.000 | $ 116.250 | 8 | $ 148.800.000 |
| Especialista en seguridad | 5+ años AppSec, OWASP, pentest, Ley 1581 | $ 12.000.000 | $ 18.600.000 | $ 116.250 | 4 | $ 74.400.000 |
| Documentador / capacitador | 3+ años documentación técnica y funcional | $ 6.000.000 | $ 9.300.000 | $ 58.125 | 6 | $ 55.800.000 |
| **Total** | | | | | **202** | **$ 3.169.750.000** |

### Lectura de la tabla

- El costo promedio cargado por persona-mes es **$ 15.691.832 COP**.
- El costo promedio por hora cargada es **$ 98.074 COP**.
- El equipo promedio es **11,2 personas** durante 18 meses.
- El 46,3 % del costo de personal está en desarrollo backend y frontend.
- El 13,4 % está en QA.
- El 8,4 % está en gestión de proyecto.

## 5.3 Comparación de modalidades de contratación

| Modalidad | Costo relativo | Ventajas | Riesgos |
|---|---:|---|---|
| Nómina directa | 100 % (base) | Control, estabilidad, conocimiento interno | Carga administrativa, prestaciones, desvinculación |
| Fábrica de software | 120-135 % | Equipo completo, garantía, gestión incluida | Menor control diario, dependencia del proveedor |
| Freelance senior | 110-130 % | Flexibilidad, perfiles especializados | Continuidad, disponibilidad, integración |
| Equipo mixto recomendado | 105-115 % | Núcleo interno + escuadra externa | Requiere governance claro |

**Recomendación:** mantener internos los roles de arquitectura, liderazgo técnico, análisis funcional y gestión de proyecto; externalizar capacidad de desarrollo, QA y DevOps por demanda.

## 5.4 Costo de no usar IA

Si se compara con un equipo asistido por IA, el sobrecosto estimado es:

| Concepto | Sin IA | Con IA (referencia) | Diferencia |
|---|---:|---:|---:|
| Esfuerzo | 202 PM | 168 PM | +34 PM |
| Costo de personal | $ 3.169.750.000 | $ 2.636.000.000 | +$ 533.750.000 |
| Duración | 18 meses | 15 meses | +3 meses |
| Costo total con IVA | $ 5.494.151.163 | $ 4.570.000.000 | +$ 924.000.000 |

La diferencia es una referencia, no una recomendación. El uso de IA en el desarrollo exige gobierno, revisión humana, propiedad intelectual y control de calidad; no es gratis ni automático.

## 5.5 Flujo mensual estimado

El costo mensual no es constante: depende de la rampa del equipo.

| Etapa | Meses | Equipo | Costo mensual aproximado |
|---|---:|---:|---:|
| Arranque y arquitectura | 1-2 | 5-7 personas | $ 78.000.000 - $ 110.000.000 |
| Construcción plena | 3-14 | 11-13 personas | $ 173.000.000 - $ 204.000.000 |
| Cierre, UAT y despliegue | 15-18 | 5-10 personas | $ 78.000.000 - $ 157.000.000 |

**Costo mensual promedio:** $ 176.097.000 COP (personal cargado).
**Costo mensual máximo:** ~$ 204.000.000 COP.
**Costo mensual mínimo:** ~$ 78.000.000 COP.

---

# 6. Otros costos del proyecto

| Rubro | Detalle | Costo estimado |
|---|---|---:|
| Herramientas y licencias | IDE, Figma, GitHub/GitLab, SonarQube, monitoreo, ofimática, equipos de prueba | $ 120.000.000 |
| Infraestructura productiva y de staging | Servidor/VPS, backups, SSL, correo, monitoreo, almacenamiento | $ 60.000.000 |
| Equipos de trabajo | 12 laptops + monitores + periféricos (si los provee el cliente) | $ 90.000.000 |
| Pentest y auditoría de seguridad | Prueba externa, informe y acompañamiento de remediación | $ 60.000.000 |
| Migración y depuración de datos | Perfilamiento, ETL, limpieza, validación y reconciliación | $ 80.000.000 |
| Capacitación y gestión del cambio | Materiales, sesiones por área, manuales, soporte de arranque | $ 40.000.000 |
| Documentación, impresión y viáticos | Manuales, actas, desplazamientos | $ 30.000.000 |
| **Total otros costos** | | **$ 480.000.000** |

### Notas

- Los **equipos de trabajo** pueden excluirse si el equipo usa equipos propios o si SJ Seguridad los suministra. En ese caso, el presupuesto baja $ 90.000.000.
- La **infraestructura** puede ser un servidor propio de SJ Seguridad; el costo entonces es de operación, no de alquiler.
- El **pentest** es recomendable antes del go-live, especialmente por el manejo de datos personales de empleados.
- La **migración de datos** es uno de los rubros con mayor variabilidad. Si los datos están limpios y estandarizados, puede bajar; si hay múltiples fuentes y documentos escaneados, puede subir 50-100 %.

### Licencias open source

El stack principal es open source:

| Componente | Licencia | Costo |
|---|---|---:|
| Laravel | MIT | $ 0 |
| Spatie Laravel Permission | MIT | $ 0 |
| PhpSpreadsheet | LGPL | $ 0 |
| PHPWord | LGPL | $ 0 |
| DomPDF | LGPL | $ 0 |
| Alpine.js | MIT | $ 0 |
| Tailwind CSS | MIT | $ 0 |
| Vite | MIT | $ 0 |
| ApexCharts | MIT | $ 0 |
| DataTables | MIT | $ 0 |
| jQuery | MIT | $ 0 |

No hay costo de licenciamiento por usuario. El costo real está en el soporte, la actualización de dependencias y la verificación de compatibilidad.

---

# 7. Presupuesto total y escenarios

## 7.1 Presupuesto base recomendado

| Componente | Valor COP | % sobre subtotal |
|---|---:|---:|
| Personal | $ 3.169.750.000 | 68,7 % |
| Otros costos | $ 480.000.000 | 10,4 % |
| **Costo directo** | **$ 3.649.750.000** | **79,1 %** |
| Overhead administrativo (10 %) | $ 364.975.000 | 7,9 % |
| Contingencia (15 %) | $ 602.208.750 | 13,0 % |
| **Subtotal sin IVA** | **$ 4.616.933.750** | **100 %** |
| IVA 19 % (si aplica) | $ 877.217.413 | — |
| **Total con IVA** | **$ 5.494.151.163** | — |

**Equivalente en USD (TRM 4.000):**

- Sin IVA: **USD 1.154.233**
- Con IVA: **USD 1.373.538**

## 7.2 Escenarios

| Escenario | Supuesto | PM | Duración | Equipo promedio | Sin IVA | Con IVA |
|---|---|---:|---:|---:|---:|---:|
| **Optimizado** | Priorización de alcance, reutilización de componentes open source, menor profundidad en reportes avanzados y migración | 150 | 14 meses | 10,7 | $ 3.521.475.061 | $ 4.190.555.323 |
| **Base recomendado** | Alcance completo, paridad funcional, calidad empresarial, migración y capacitación | 202 | 18 meses | 11,2 | $ 4.616.933.750 | $ 5.494.151.163 |
| **Conservador** | Mayor complejidad de datos, más integraciones, refuerzo de seguridad, 22 meses | 240 | 22 meses | 10,9 | $ 5.472.440.099 | $ 6.512.203.718 |

### Cuándo usar cada escenario

- **Optimizado:** si SJ Seguridad acepta un MVP por fases, reduce reportes avanzados, reutiliza la arquitectura actual y difiere integraciones.
- **Base:** si se requiere paridad funcional completa, migración de datos, capacitación y puesta en producción con garantía.
- **Conservador:** si los datos están dispersos, hay múltiples fuentes, se exige 2FA/SSO, auditoría externa, alta disponibilidad o integraciones con terceros.

## 7.3 Distribución del costo por módulo

El siguiente cálculo es una **proporción del costo de personal** según los PM estimados. No reemplaza una cotización detallada por módulo.

| Módulo / componente | PM | Costo de personal estimado | % |
|---|---:|---:|---:|
| Gestión, análisis y arquitectura | 22 | $ 345.220.000 | 10,9 % |
| Núcleo y administración | 18 | $ 282.453.000 | 8,9 % |
| Requisiciones | 12 | $ 188.302.000 | 5,9 % |
| Ficha, archivo, plantillas, cartas, inasistencias y desvinculaciones | 23 | $ 360.912.000 | 11,4 % |
| Cursos, formación, selección y MT-ST-04 | 19 | $ 298.145.000 | 9,4 % |
| Acreditaciones, novedades, cliente interno y dashboard GH | 23 | $ 360.912.000 | 11,4 % |
| Compras y abastecimiento | 27 | $ 423.679.000 | 13,4 % |
| Operaciones e indicadores | 13 | $ 203.994.000 | 6,4 % |
| Comercial | 7 | $ 109.843.000 | 3,5 % |
| Calidad | 4 | $ 62.767.000 | 2,0 % |
| TIC | 4 | $ 62.767.000 | 2,0 % |
| QA y seguridad | 20 | $ 313.837.000 | 9,9 % |
| Migración, UAT, despliegue y capacitación | 10 | $ 156.919.000 | 5,0 % |
| **Total** | **202** | **$ 3.169.750.000** | **100 %** |

## 7.4 Esquema de pagos sugerido

| Hito | % | Condición |
|---|---:|---|
| Inicio | 10 % | Firma, equipo asignado, plan detallado |
| Arquitectura y núcleo | 20 % | Arquitectura aprobada, login, RBAC, auditoría y layout funcionando |
| Gestión Humana núcleo | 20 % | Requisiciones, ficha, archivo y cartas en UAT |
| Gestión Humana avanzado + Compras | 20 % | Cursos, acreditaciones, novedades, suministros y compras en UAT |
| Operaciones, comercial, calidad y TIC | 15 % | Módulos restantes en UAT |
| UAT y despliegue | 10 % | Aceptación formal, producción y capacitación |
| Cierre y garantía | 5 % | 30 días de estabilización y entrega documental |

## 7.5 Soporte y evolución

La garantía recomendada es de **3 meses** posteriores al go-live, incluida en el presupuesto.

Después de la garantía:

| Paquete | Cobertura | Costo mensual estimado |
|---|---|---:|
| Básico | Horario 8×5, 1 backend + 0,5 QA, correctivos | $ 20.000.000 - $ 25.000.000 |
| Estándar | Horario 8×5, 2 FTE, correctivos + evolutivos menores, monitoreo | $ 35.000.000 - $ 45.000.000 |
| Premium | 24×7, 3 FTE, monitoreo, seguridad, evolución continua | $ 60.000.000 - $ 75.000.000 |

**Presupuesto anual de evolución:** 15-20 % del costo de construcción, es decir, **$ 692.000.000 a $ 923.000.000 COP/año** para el escenario base.

## 7.6 Impuestos y moneda

- **IVA:** 19 % si el servicio está gravado. Si SJ Seguridad es una entidad no responsable o el servicio está excluido, no aplica.
- **Retención en la fuente:** el cliente retiene según el concepto; no es un costo adicional del proyecto.
- **Industria y comercio:** incluido en el overhead.
- **TRM:** se usó 4.000 COP/USD. Una variación del 10 % mueve el equivalente en USD, no el costo en COP.
- **Inflación:** se recomienda indexar el presupuesto si la ejecución supera 12 meses.

---

# 8. Cronograma propuesto

| Mes | Fase | Hito | Equipo aproximado |
|---:|---|---|---:|
| 1 | F0 | Kickoff, levantamiento, arquitectura, plan de seguridad | 5 |
| 2 | F0-F1 | Arquitectura aprobada, design system, CI/CD, modelo de datos | 7 |
| 3 | F1 | Login, RBAC, usuarios, navegación, auditoría | 9 |
| 4 | F1-F2 | Núcleo cerrado; inicio de requisiciones y ficha | 11 |
| 5 | F2 | Requisiciones en UAT; ficha de empleados | 12 |
| 6 | F2 | Archivo, plantillas, cartas, inasistencias | 12 |
| 7 | F2-F3 | Desvinculaciones; inicio de cursos y formación | 12 |
| 8 | F3 | Cursos, formación, selección; inicio de compras | 13 |
| 9 | F3-F4 | MT-ST-04; suministros y solicitudes de compra | 13 |
| 10 | F3-F4 | Acreditaciones; bandeja de compras y proveedores | 13 |
| 11 | F3-F5 | Novedades y cliente interno; inicio de operaciones | 13 |
| 12 | F4-F5 | Compras avanzadas; indicadores | 12 |
| 13 | F4-F5 | Órdenes y evaluaciones; comercial y calidad | 12 |
| 14 | F5 | Operaciones, comercial, calidad y TIC en UAT | 11 |
| 15 | F6 | Integración, migración de datos, pruebas de regresión | 10 |
| 16 | F6 | UAT formal, pentest, correcciones | 9 |
| 17 | F6 | Despliegue, capacitación, manuales | 7 |
| 18 | Cierre | Estabilización, garantía, transferencia | 5 |

### Hitos de aceptación

1. **M2:** arquitectura, modelo de datos y design system aprobados.
2. **M4:** núcleo seguro con RBAC y auditoría.
3. **M6:** requisiciones y ficha de empleados en UAT.
4. **M10:** compras y suministros en UAT.
5. **M14:** funcionalidad completa en UAT.
6. **M16:** UAT formal cerrada y pentest aprobado.
7. **M18:** go-live y transferencia.

---

# 9. Supuestos, exclusiones y condiciones

## 9.1 Supuestos

1. SJ Seguridad designa un **dueño de producto** y **usuarios clave** por área, con disponibilidad mínima de 4 horas semanales.
2. Los procesos, catálogos, reglas y formatos documentales están definidos o se definen durante F0.
3. El cliente entrega acceso a datos, plantillas, documentos y sistemas de origen.
4. El cliente valida y prioriza el alcance por fases.
5. El equipo trabaja en modalidad híbrida/remota, con reuniones en horario Colombia.
6. La infraestructura productiva la provee SJ Seguridad o se contrata con un proveedor local.
7. Se usan las versiones LTS/estables del stack.
8. Se realizan máximo **3 rondas de UAT** por módulo.
9. Los cambios de alcance se gestionan por control de cambios y se cotizan aparte.
10. La garantía cubre defectos, no nuevas funcionalidades.

## 9.2 Exclusiones

- Componentes de IA/ML, OCR inteligente, chatbots, asistentes, recomendaciones o analítica predictiva.
- Aplicaciones móviles nativas.
- API pública para terceros, salvo que se cotice aparte.
- SSO corporativo (Active Directory, Azure AD, Google Workspace).
- 2FA/MFA, salvo que se incluya como mejora.
- Integraciones con nómina, ERP, contabilidad, firma electrónica o facturación electrónica.
- Migración de documentos escaneados sin OCR.
- Digitalización física de archivos.
- Licencias de Microsoft Office para usuarios finales.
- Hardware de usuarios finales, red, conectividad o impresoras.
- Soporte 24×7 durante la construcción.
- Certificaciones ISO, SOC 2 o similares.
- Auditoría legal o tributaria.
- Capacitación fuera de la ciudad base.
- Contenido, textos legales o políticas internas.
- Mantenimiento evolutivo posterior a la garantía.

## 9.3 Condiciones de precio

- Precios en COP, vigentes por 30 días.
- Sujetos a ajuste por inflación si la ejecución supera 12 meses.
- El IVA se factura según la normativa vigente.
- Los pagos se realizan contra hitos aceptados.
- El retraso en validaciones del cliente extiende el cronograma y puede generar costos de espera.
- La garantía no cubre cambios de alcance, datos incorrectos, mal uso o modificaciones de terceros.

---

# 10. Riesgos presupuestales

| Riesgo | Probabilidad | Impacto | Mitigación | Contingencia |
|---|---|---|---|---|
| Reglas de negocio incompletas o contradictorias | Alta | Alto | Descubrimiento profundo, talleres por área, prototipos | +10-15 % esfuerzo |
| Datos de origen sucios o dispersos | Alta | Alto | Perfilamiento, ETL, reconciliación, reglas de calidad | +$ 80-150 M |
| Crecimiento de alcance | Alta | Alto | Control de cambios, MVP, priorización | +15 % contingencia |
| Dependencia de personas clave | Media | Alto | Documentación, pair programming, plan de sucesión | +5-8 % |
| Requisitos de seguridad no previstos (2FA, SSO, cifrado) | Media | Alto | Definir en F0, pentest temprano | +$ 60-120 M |
| Baja adopción o resistencia al cambio | Media | Medio | Gestión del cambio, capacitación, embajadores | +$ 40-80 M |
| Correos que no llegan (SPF/DKIM/DMARC) | Media | Medio | Configurar dominio, monitoreo, proveedor transaccional | +$ 10-20 M |
| Bajo desempeño con volumen alto | Media | Medio | Índices, pruebas de carga, tuning | +$ 30-60 M |
| Dependencia de CDN para DataTables/jQuery | Media | Medio | Vendorizar assets, CSP, SRI | +2-3 semanas |
| Incumplimiento de protección de datos | Media | Alto | Política de datos, 2FA, cifrado, retención | +$ 50-100 M |
| Retraso en decisiones del cliente | Alta | Medio | Gobernanza semanal, actas, plazos | Extensión de cronograma |
| Rotación del equipo | Media | Medio | Contratos estables, documentación, conocimiento distribuido | +5-10 % |

**Contingencia incluida:** 15 % sobre costo directo + overhead. Para el escenario base son **$ 602.208.750 COP**.

---

# 11. Recomendaciones

1. **No reconstruir todo de una vez.** Fasar por valor: núcleo → requisiciones → suministros → compras → GH avanzado → indicadores/comercial/calidad/TIC.
2. **Priorizar los procesos con mayor dolor operativo.** Requisiciones, suministros y compras concentran reglas, usuarios y valor; son el mejor primer entregable.
3. **Reutilizar el stack open source.** Laravel, Blade, Alpine, Tailwind, PhpSpreadsheet, PHPWord y DomPDF son maduros y evitan licencias.
4. **Definir permisos por pestaña antes de programar.** El modelo actual tiene 201 permisos; sin un mapa claro, se generan sobrepermisos y retrabajo.
5. **Formalizar la gobernanza de datos.** Dueños, definiciones, reglas de calidad, retención y tratamiento de datos personales (Ley 1581 de 2012).
6. **Incluir 2FA y hardening desde el inicio.** No como un extra de último momento.
7. **Vendorizar DataTables/jQuery y agregar CSP/SRI.** Elimina dependencia externa y mejora seguridad.
8. **Agregar CI/CD, análisis estático y E2E.** Son la mejor inversión para reducir regresiones en un sistema con 497 rutas.
9. **Definir observabilidad y continuidad.** Logs centralizados, métricas, alertas, backups probados y plan de recuperación.
10. **Contratar por hitos con aceptación formal.** Evita discusiones de alcance y alinea el pago con valor entregado.
11. **Negociar soporte y evolución desde el día uno.** Un sistema de este tamaño requiere 15-20 % anual de evolución.
12. **Mantener la contingencia del 15 %.** No usarla para ampliar alcance; reservarla para riesgos reales.

---

# 12. Anexos técnicos

## Anexo A. Inventario técnico

| Categoría | Cantidad |
|---|---:|
| Rutas | 497 |
| Controladores | 61 |
| Modelos | 88 |
| Servicios | 210 |
| Migraciones | 160 |
| Tablas | 104 |
| Vistas Blade | 312 |
| Componentes Blade | 23 |
| Archivos JS | 22 |
| Mailables | 19 |
| Exportaciones | 31 |
| Comandos Artisan | 14 |
| Jobs | 1 |
| Middleware | 7 |
| Policies | 5 |
| Traits | 17 |
| Permisos | 201 |
| Roles | 4 |
| Archivos de prueba | 174 |
| Commits | 464 |

## Anexo B. Distribución de tablas por dominio

| Dominio | N.º aproximado de tablas |
|---|---:|
| Seguridad, usuarios y permisos | 10 |
| Requisiciones | 15 |
| Suministros | 6 |
| Compras | 12 |
| Gestión Humana | 30 |
| Documentos y plantillas | 6 |
| Comercial | 10 |
| Indicadores | 8 |
| Desarrollo TIC | 4 |
| Notificaciones | 3 |
| Infraestructura Laravel | 5 |
| Auditoría | 1 |

## Anexo C. Roles y permisos

### Roles base

- `super-admin`: acceso total; recibe todos los permisos.
- `administrador`: gestión administrativa y de usuarios.
- `director`: autorizaciones de compras, requisiciones y solicitudes de desarrollo.
- `usuario`: acceso operativo según área y tablero.

### Dimensiones de permisos

- **Sistema:** dashboard, usuarios, notificaciones, auditoría.
- **Área:** gerencia, GH, jurídico, operaciones, programación, comercial, calidad, admin/financiero, compras, TIC.
- **Tablero:** 25 tableros.
- **Pestaña:** permisos `view` / `edit` / `review` según el módulo.
- **Acción:** capturar, aprobar, exportar, administrar catálogos, gestionar parámetros.

## Anexo D. Checklist para iniciar

- [ ] Nombrar dueño de producto y usuarios clave por área.
- [ ] Validar el alcance por fases y el escenario presupuestal.
- [ ] Definir el mapa de permisos por área, tablero y pestaña.
- [ ] Inventariar fuentes de datos, volúmenes y calidad.
- [ ] Definir la política de datos personales y retención.
- [ ] Aprobar la arquitectura de infraestructura (servidor, backups, correo, monitoreo).
- [ ] Definir el plan de migración y reconciliación.
- [ ] Acordar el esquema de pagos y los criterios de aceptación.
- [ ] Definir el plan de capacitación y gestión del cambio.
- [ ] Acordar el SLA de soporte post-garantía.

## Anexo E. Evidencia revisada

| Fuente | Uso |
|---|---|
| `composer.json`, `package.json` | Stack y dependencias |
| `config/access.php` | Áreas, tableros, permisos, navegación |
| `routes/web.php`, `routes/auth.php`, `routes/console.php` | Superficie funcional y scheduler |
| `routes/areas/*`, `routes/modules/*` | Rutas por dominio |
| `app/Http/Controllers` | 61 controladores |
| `app/Services` | 210 servicios |
| `app/Models` | 88 modelos |
| `app/Policies`, `app/Middleware`, `app/Traits` | Seguridad y acceso |
| `app/Exports`, `app/Mail`, `app/Console/Commands` | Documentos, correo y automatización |
| `database/migrations` | 160 migraciones |
| `resources/views`, `resources/js`, `vite.config.js` | UI y assets |
| `tests`, `phpunit.xml` | Estrategia de pruebas |
| `bootstrap/app.php` | Middleware, scheduler y excepciones |
| `php artisan route:list` | Conteo y distribución de rutas |
| Consultas de solo lectura | 104 tablas, 201 permisos, 4 roles |
| Historial Git | 464 commits, periodo abril-noviembre 2026 |

---

## Nota final

Este documento es una **estimación técnica y económica**, no una oferta comercial vinculante. Antes de contratar, se recomienda:

1. Validar el alcance con los dueños de proceso.
2. Hacer un levantamiento detallado de datos y reglas.
3. Cotizar infraestructura y licencias con proveedores locales.
4. Ajustar el presupuesto con el escenario elegido.
5. Convertir los hitos en criterios de aceptación medibles.

La cifra base de **$ 4.616.933.750 COP sin IVA** (aproximadamente **USD 1,15 millones**) es el punto de partida recomendado para una reconstrucción completa, con calidad empresarial, en 18 meses y sin inteligencia artificial. El escenario optimizado de **$ 3.521.475.061 COP sin IVA** es viable si se prioriza el alcance y se reutiliza la arquitectura y los componentes existentes.
