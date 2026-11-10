# Informe de implementación — Área Gestión Humana

**Plataforma:** SJ StatFlow — SJ Seguridad  
**Área entregada:** Gestión Humana  
**Tipo de documento:** Informe de implementación y entrega formal para el Sistema de Gestión de Calidad  
**Versión:** 1.0  
**Fecha de emisión:** 10 de noviembre de 2026  

---

## 0. Control documental

| Campo | Valor |
| --- | --- |
| Código del documento | INF-GH-001 |
| Versión | 1.0 |
| Estado | Emitido para revisión de Calidad |
| Periodo de implementación | 31/07/2026 – 09/11/2026 |
| Fecha de cierre operativo del área | 04/11/2026 |
| Elaborado por | Equipo de desarrollo SJ StatFlow |
| Revisado por | _(Calidad — diligenciar)_ |
| Aprobado por | _(Dirección / Calidad — diligenciar)_ |
| Clasificación | Uso interno |

**Historial de versiones**

| Versión | Fecha | Descripción |
| --- | --- | --- |
| 1.0 | 2026-11-10 | Emisión inicial para entrega formal del área Gestión Humana. |

---

## 1. Propósito

Dejar constancia formal de la implementación, verificación y puesta en operación del **área Gestión Humana** de la plataforma web SJ StatFlow, presentando:

1. El alcance funcional entregado y su justificación de negocio.
2. El cronograma real de ejecución por funcionalidad (fechas de implementación y última actualización).
3. La evidencia de verificación (pruebas automatizadas, compilación de frontend, revisión de estilo de código y validación funcional en producción).
4. Los controles de seguridad, trazabilidad y gestión del cambio aplicados.
5. Los riesgos residuales, pendientes no bloqueantes y el acto de aceptación.

Este informe se emite para revisión del **Sistema de Gestión de Calidad (SGC)** y soporta la auditoría interna del proceso de desarrollo de software.

---

## 2. Requisitos que cubre este informe para Calidad

Un informe de implementación de un proyecto de desarrollo web, para ser aceptable ante Calidad, debe permitir responder: *qué se entregó, cuándo, con qué evidencia, bajo qué controles y con qué aceptación*. La siguiente matriz relaciona esos requisitos con el contenido de este documento y con los criterios de referencia.

| Requisito del informe | Referencia (ISO 9001:2015 / ISO/IEC 25010) | Sección |
| --- | --- | --- |
| Identificación y control del documento | 7.5 Información documentada | 0, 14 |
| Alcance, límites y exclusiones | 8.1 Planificación y control operacional | 3 |
| Justificación y valor de negocio | 8.2 Requisitos para productos y servicios | 4 |
| Descripción de la solución y arquitectura | 8.3 Diseño y desarrollo | 5 |
| Cronograma real de ejecución por funcionalidad | 8.1 / 8.5.1 | 6 |
| Trazabilidad funcionalidad → permiso → evidencia | 8.5.1 Control de la producción | 7 |
| Verificación y validación (pruebas, build, smoke) | 8.6 Liberación de productos y servicios | 8 |
| Seguridad de la información y control de acceso | 8.5.1 / ISO/IEC 25010 (seguridad) | 9 |
| Integridad y calidad del dato | ISO/IEC 25010 (fiabilidad, integridad) | 10 |
| Gestión del cambio y configuración | 8.5.6 Control de cambios | 11 |
| Riesgos y oportunidades | 6.1 Acciones para abordar riesgos | 12 |
| Salidas no conformes y acciones correctivas | 8.7 / 10.2 | 8.4, 12 |
| Pendientes, exclusiones y recomendaciones | 8.3 / 10.3 Mejora | 13 |
| Aceptación formal y firmas | 8.6 / 7.5.3 | 15 |

**Conclusión de cumplimiento documental:** el informe cubre los 13 requisitos anteriores con evidencia verificable en el repositorio y en el ambiente productivo.

---

## 3. Alcance

### 3.1 Incluido en la entrega

| # | Módulo / tablero | Ruta base |
| --- | --- | --- |
| 1 | Dashboard del área | `/gestion-humana/dashboard` |
| 2 | Ficha de empleados | `/gestion-humana/ficha-empleados` |
| 3 | Archivo (historias laborales) | `/gestion-humana/archivo` |
| 4 | Cursos | `/gestion-humana/cursos` |
| 5 | Formación | `/gestion-humana/formacion` |
| 6 | Selección | `/gestion-humana/seleccion` |
| 7 | Acreditaciones | `/gestion-humana/acreditaciones` |
| 8 | Cliente interno | `/gestion-humana/cliente-interno` |
| 9 | Desvinculaciones | `/gestion-humana/desvinculaciones` |
| 10 | MT-ST-04 (psicofísicos / psicosensométricos) | `/gestion-humana/mt-st-04` |
| 11 | Cartas Notificación | `/gestion-humana/cartas-notificacion` |
| 12 | Inasistencias | `/gestion-humana/inasistencias` |
| 13 | MT-GH-04 Novedades | `/gestion-humana/reportes-novedades` |
| 14 | Plantillas Word | `/gestion-humana/plantillas-word` |
| 15 | Requisiciones de personal (módulo compartido, hogar GH) | `/requisitions/gestion_humana` |

### 3.2 Funcionalidad transversal incluida

- Autenticación obligatoria, usuario activo y cambio de contraseña temporal al primer ingreso.
- Control de acceso por área, tablero y **pestaña** (`view` / `edit`), con permisos administrables.
- Auditoría centralizada de eventos críticos.
- Exportación a Excel estandarizada y generación de documentos Word (individual y por lote en ZIP).
- DataTables del lado del servidor en los listados operativos.

### 3.3 Excluido / diferido (no condiciona la operación)

- Refactorización estructural de controladores de gran tamaño.
- División del archivo global `app.css` por capas.
- Mejoras no bloqueantes detectadas en revisiones de código (nits de revisión, optimizaciones menores de consultas).
- Registro público de usuarios: **no habilitado por política de seguridad**.

---

## 4. Justificación: por qué es necesaria la implementación

Antes de la plataforma, la operación de Gestión Humana se soportaba en libros de Excel independientes, correo y documentos Word elaborados manualmente. Ese esquema generaba reprocesos, información duplicada, pérdida de trazabilidad y riesgo de error en procesos sensibles (acreditaciones, exámenes psicofísicos, cartas laborales, novedades de nómina).

### 4.1 Problemática identificada y respuesta del sistema

| Problemática previa | Bondad implementada | Beneficio verificable |
| --- | --- | --- |
| Múltiples archivos Excel sin fuente única de verdad | Base de datos relacional con ficha única por empleado y periodos laborales (4.154 fichas, 2.673 periodos) | Un solo dato oficial para toda el área; se elimina la duplicidad y la reconciliación manual |
| Vigencias controladas “a mano” (cursos, acreditaciones, psicofísicos) | Cálculo automático de estados y sincronización programada diaria | Se elimina el vencimiento silencioso; el área ve **antes** lo que está por vencer (1.724 registros de cursos, 1.813 acreditados, 1.561 registros MT-ST-04) |
| Cartas y certificados elaborados uno por uno en Word | Generación automática desde plantillas con variables, en lote y en ZIP | Reducción drástica del tiempo de elaboración y eliminación de errores de digitación en documentos oficiales |
| Validaciones cruzadas manuales entre ficha, acreditación y reporte diario | Pestañas de **Validaciones** con cruces automáticos y detección de cédulas duplicadas | Se detectan inconsistencias antes de que lleguen al cliente o a la autoridad de vigilancia |
| Sin rastro de quién cambió qué y cuándo | Auditoría centralizada (1.788 eventos registrados) e historiales por registro | Trazabilidad completa para auditorías internas y requisitos de calidad |
| Acceso indiferenciado a la información del área | 206 permisos y 21 tableros de GH con permisos por pestaña | Principio de mínimo privilegio; cada rol ve y edita solo lo que le corresponde |
| Reportes gerenciales elaborados manualmente | Dashboard con KPIs y gráficos, filtrable y exportable a Excel | Información oportuna para la toma de decisiones y evidencia mensual de gestión |
| Procesos masivos (importaciones, desvinculaciones, novedades) sin control | Importación con simulación/preview, reportes de error por fila y confirmaciones | Se evita cargar datos inválidos y se corrige con precisión antes de afectar la base oficial |

### 4.2 Bondades del sistema (atributos de calidad)

| Atributo (ISO/IEC 25010) | Cómo se materializa en la entrega |
| --- | --- |
| **Adecuación funcional** | 15 módulos que cubren el ciclo del empleado: requisición → selección → contratación → ficha → cursos/formación → acreditaciones → novedades → desvinculación → archivo. |
| **Eficiencia de desempeño** | Listados con DataTables del lado del servidor; se soportan volúmenes de 81.291 registros de formación y 4.154 fichas sin degradar la interfaz. |
| **Compatibilidad** | Exportaciones estándar a Excel (`.xlsx`) y documentos Word (`.docx` / `.zip`) integrables con los formatos ya usados por el área y por terceros. |
| **Usabilidad** | Interfaz unificada con el resto de la plataforma, filtros persistentes, selectores con búsqueda, acciones iconográficas consistentes y navegación por pestañas. |
| **Fiabilidad** | Validaciones de entrada, confirmación ante duplicados, simulación de importaciones, transacciones y manejo de errores con mensajes accionables. |
| **Seguridad** | Autenticación, usuario activo, cambio forzado de contraseña temporal, autorización por permiso y auditoría de eventos. |
| **Mantenibilidad** | Arquitectura modular por área, documentación técnica y de usuario por módulo, pruebas automatizadas y revisión de estilo obligatoria. |
| **Portabilidad / escalabilidad** | Stack estándar (PHP 8.3 / Laravel 13 / MySQL 8) desplegable en servidor propio; diseño preparado para múltiples áreas y clientes (visión SaaS). |

### 4.3 Valor para el SGC

- **Información documentada controlada:** cada módulo cuenta con documentación técnica (`docs/modules/`) y guía de usuario (`docs/user/`).
- **Evidencia objetiva:** pruebas automatizadas, registro de auditoría y actas de cierre operativo.
- **Mejora continua:** los hallazgos de revisión y los hotfixes quedan registrados y verificados antes de cerrar cada entrega.

---

## 5. Descripción de la solución

### 5.1 Arquitectura

Aplicación web monolítica modular (Laravel) con separación por áreas de negocio y módulos compartidos:

- **Rutas:** `routes/web.php` carga `routes/areas/*.php` y `routes/modules/*.php` dentro del grupo protegido `auth` + `active`.
- **Controladores:** `App\Http\Controllers\GestionHumana\` para funcionalidad exclusiva del área; `App\Http\Controllers\{Modulo}\` para módulos compartidos.
- **Vistas:** Blade + Alpine.js + Tailwind 3, con capas CSS separadas por área/tablero.
- **Datos:** MySQL 8 en operación; SQLite en memoria para pruebas automatizadas (migraciones multi-driver).
- **Documentos:** PhpSpreadsheet (Excel) y PHPWord (Word) con plantillas administrables.
- **Autorización:** `spatie/laravel-permission` + `config/access.php` como fuente de verdad de áreas, tableros y pestañas.
- **Auditoría:** `SystemAuditService` con wrappers por módulo.

### 5.2 Stack técnico

| Capa | Tecnología |
| --- | --- |
| Lenguaje | PHP 8.3 |
| Framework | Laravel 13 |
| Base de datos | MySQL 8 (producción/dev) · SQLite en memoria (pruebas) |
| Frontend | Blade, Alpine.js, Tailwind CSS 3, Vite 6 |
| Pruebas | PHPUnit 12 |
| Documentos | PhpSpreadsheet 5.x · PHPWord 1.x |
| Permisos | spatie/laravel-permission 6.x |
| Despliegue | Servidor Linux propio (VPS/dedicado) |

### 5.3 Modelo de acceso

- Roles base: `super-admin`, `administrador`, `director`, `usuario`.
- **206 permisos** registrados, de los cuales **21** corresponden a tableros del área Gestión Humana.
- Permisos por pestaña con convención `view` / `edit`, aplicados en controlador, navegación y vista.
- Usuarios activos al corte: **18**.

---

## 6. Cronograma real de ejecución por funcionalidad

Las fechas corresponden a los registros de control de versiones del repositorio (primer y último commit de cada módulo), lo que constituye evidencia objetiva y auditable. “Días” es el intervalo calendario de trabajo registrado sobre el módulo.

### 6.1 Módulos del área

| # | Módulo | Inicio implementación | Última actualización | Días | Funcionalidades principales entregadas |
| --- | --- | --- | --- | --- | --- |
| 1 | **Ficha de empleados** | 2026-07-31 | 2026-11-06 | 98 | CRUD de ficha, periodos laborales, reingresos, importación masiva con plantilla, exportación, catálogos de nómina, requisitos de curso y acreditación, indicador de recontratabilidad, carta rápida de contratación y cartas de desvinculación, desvinculación individual |
| 2 | **Archivo** | 2026-08-06 | 2026-11-05 | 91 | Historias laborales con DataTable del servidor, edición en línea de caja/estante, consulta múltiple de cédulas con motivo y “entregada a”, historial de consultas y su exportación |
| 3 | **Plantillas Word** | 2026-08-21 | 2026-10-09 | 49 | Administración centralizada de tipos de documento y plantillas, carga y reemplazo de archivos, variables dinámicas, navegación por pestañas |
| 4 | **Desvinculaciones** | 2026-09-14 | 2026-10-22 | 38 | Desvinculación masiva con carta Word y ZIP, reporte de éxitos/fallos, seguimientos post-retiro con autosave y checklist, dashboard mensual |
| 5 | **Cursos** | 2026-09-16 | 2026-11-04 | 49 | Tablero con carga masiva Excel, plantilla, exportación, estados validados por fecha, escuelas y tipos, pestaña Validaciones (sin curso / por actualizar / vencidos), filtro por múltiples cédulas |
| 6 | **Selección** | 2026-09-22 | 2026-11-09 | 48 | Dashboard, Ingreso y Examen ocupacional con CRUD, filtros, DataTables, exportación y catálogos; estados de examen con historial; “pasar a examen” con prellenado; cédula de reemplazo |
| 7 | **Acreditaciones** | 2026-09-23 | 2026-10-22 | 29 | Acreditados (CRUD, importación upsert, exportación), catálogo de cargos, Reporte Diario APO (carga y snapshots), Validaciones cruzadas, Export APO SuperVigilancia (automático y manual), retiros, renovaciones y cola de nuevos sin acreditación |
| 8 | **MT-GH-04 Novedades** | 2026-09-30 | 2026-09-30 | 1 | Pestañas Vacaciones, Incapacidades, Retiros y Permisos con CRUD, revisión de Nómina, exportación por hoja, historial de auditoría y sincronización de retiros desde desvinculaciones; adjuntos PDF/JPG en incapacidades (2026-11-06) |
| 9 | **Formación** | 2026-10-01 | 2026-11-04 | 34 | Dashboard de KPIs con filtros por estado y vista “por persona ciclo”, listado de 81 mil registros con DataTables del servidor, importación replace total o por mes, plantilla y exportación |
| 10 | **Cliente interno** | 2026-10-05 | 2026-11-04 | 30 | Solicitudes con CRUD, filtros, importación por periodo, dashboard, catálogos propios, cartas de vacaciones en lote y autocompletado por cédula desde Ficha |
| 11 | **Cartas Notificación** | 2026-10-08 | 2026-10-29 | 21 | Generación en lote desde grilla o Excel, duración de contrato 6/12, selección de preforma por lote, descarga individual `.docx` o masiva `.zip` |
| 12 | **MT-ST-04** | 2026-10-09 | 2026-11-04 | 26 | Matriz de psicofísicos y psicosensométricos, cálculo de vencimientos, importación upsert, exportación, validaciones, dashboard y sincronización diaria de estados (06:25, America/Bogota) |
| 13 | **Dashboard del área** | 2026-10-13 | 2026-11-09 | 27 | Informe mensual con 10 KPIs y 7 gráficos, filtros por año/mes/ciudad/cargo/cliente, catálogo de conceptos y exportación a Excel con capturas de gráficos y hojas de detalle |
| 14 | **Inasistencias** | 2026-10-14 | 2026-10-14 | 1 | Generación de cartas de inasistencia en lote con cálculo de días, histórico de generaciones y exportación de plantilla |
| 15 | **Requisiciones (compartido)** | Previo al corte | 2026-11-09 | — | Flujo de solicitud, aprobación, seguimiento y contratación; al marcar **CONTRATADO** se crea ficha y perfil automáticamente, habilitando cursos, acreditaciones y psicofísicos sin esperar la carga manual |

### 6.2 Hitos del proyecto

| Fecha | Hito |
| --- | --- |
| 2026-04-23 | Inicio del repositorio del proyecto |
| 2026-07-31 | Primera entrega del módulo Ficha de empleados |
| 2026-08-06 | Habilitación de Archivo e importación unificada |
| 2026-08-21 | Centralización de plantillas Word |
| 2026-09-14 | Entrega de Desvinculaciones |
| 2026-09-16 | Entrega de Cursos |
| 2026-09-22 | Entrega de Selección |
| 2026-09-23 | Entrega de Acreditaciones |
| 2026-09-30 | Entrega de MT-GH-04 Novedades |
| 2026-10-01 | Entrega de Formación |
| 2026-10-05 | Entrega de Cliente interno |
| 2026-10-08 | Entrega de Cartas Notificación |
| 2026-10-09 | Entrega de MT-ST-04 |
| 2026-10-13 | Entrega del Dashboard del área |
| 2026-10-14 | Entrega de Inasistencias y automatización contratación → ficha/perfil |
| 2026-11-04 | **Cierre operativo del área** validado en producción |
| 2026-11-09 | Última actualización funcional del área (exportación del dashboard) |
| 2026-11-10 | Emisión de este informe para Calidad |

**Duración del área:** 101 días calendario (31/07/2026 a 09/11/2026), dentro de un proyecto iniciado el 23/04/2026 con **479 confirmaciones** de cambio registradas.

---

## 7. Trazabilidad funcionalidad → control de acceso → evidencia

| Módulo | Permisos principales | Documentación técnica | Documentación de usuario | Pruebas automatizadas |
| --- | --- | --- | --- | --- |
| Dashboard GH | `view.board.gestion_humana.dashboard` | `docs/modules/gestion-humana-dashboard.md` | `docs/user/gestion-humana-dashboard.md` | ✔ |
| Ficha empleados | `ficha_empleados.view` / `.manage` / `.terminate` | `docs/modules/ficha-empleados.md` | `docs/user/ficha-empleados.md` | ✔ |
| Archivo | `archivo.view` / `.edit` | `docs/modules/archivo.md` | `docs/user/archivo.md` | ✔ |
| Cursos | `cursos.view` / `.edit` | `docs/modules/cursos.md` | `docs/user/cursos.md` | ✔ |
| Formación | `formacion.view` / `.edit` | `docs/modules/formacion.md` | `docs/user/formacion.md` | ✔ |
| Selección | `seleccion.view` / `.edit` | `docs/modules/seleccion.md` | `docs/user/seleccion.md` | ✔ |
| Acreditaciones | `acreditaciones.view` / `.edit` | `docs/modules/acreditaciones.md` | `docs/user/acreditaciones.md` | ✔ |
| Cliente interno | `solicitudes.*`, `cartas_vacaciones.*`, `parameters.edit` | `docs/modules/cliente-interno.md` | `docs/user/cliente-interno.md` | ✔ |
| Desvinculaciones | `desvinculaciones.view` / `.edit` (paquete de 4 claves) | `docs/modules/desvinculaciones.md` | `docs/user/desvinculaciones.md` | ✔ |
| MT-ST-04 | `mt_st_04.view` / `.edit` | `docs/modules/mt_st_04.md` | `docs/user/mt_st_04.md` | ✔ |
| Cartas Notificación | `cartas_notificacion.edit` | `docs/modules/cartas-notificacion.md` | `docs/user/cartas-notificacion.md` | ✔ |
| Inasistencias | `inasistencias.view` / `.edit` | `docs/modules/inasistencias.md` | `docs/user/inasistencias.md` | ✔ |
| MT-GH-04 Novedades | `.view` / `.edit` / `.review` por hoja | `docs/modules/reportes-novedades.md` | `docs/user/reportes-novedades.md` | ✔ |
| Plantillas Word | `plantillas_word.view` / `.edit` | `docs/modules/plantillas-word.md` | `docs/user/plantillas-word.md` | ✔ |
| Requisiciones | `requisiciones.view` / `.edit` / `.approve` | `docs/modules/requisitions.md` | `docs/user/requisitions.md` | ✔ |

**Cobertura documental:** 100% de los módulos entregados cuentan con documento técnico y guía de usuario vigentes al 2026-11-10.

---

## 8. Verificación y validación

### 8.1 Pruebas automatizadas

| Elemento | Resultado |
| --- | --- |
| Framework | PHPUnit 12 |
| Archivos de prueba del proyecto | 184 |
| Archivos de prueba del área Gestión Humana | 83 (45% del total) |
| Estrategia | Pruebas Feature con `RefreshDatabase` sobre SQLite en memoria |
| Cobertura funcional | Rutas protegidas, permisos por pestaña, importaciones, exportaciones, generación de documentos, cálculos de estado y validaciones de negocio |

**Registro de ejecución:** las pruebas se ejecutan al cierre de cada funcionalidad y su resultado queda registrado en `docs/reviews/` y en la bitácora de ejecución (`docs/runs/`). Para esta entrega documental **no se re-ejecutó la suite**, con el fin de no interferir con los procesos activos en el ambiente de desarrollo; la verificación de aceptación del área se sustenta en la validación funcional en producción del 2026-11-04 (sección 8.3).

### 8.2 Compilación y estilo

| Control | Comando | Estado |
| --- | --- | --- |
| Build de frontend | `npm run build` | Ejecutado sin errores en cada entrega con interfaz |
| Revisión de estilo PHP | `vendor/bin/pint --dirty --format agent` | Ejecutado antes del cierre de cada cambio |
| Diagnóstico de entorno | `php artisan app:doctor` | Disponible para verificación operativa |

### 8.3 Validación funcional en producción

| Fecha | Actividad | Resultado |
| --- | --- | --- |
| 2026-11-04 | Pruebas manuales del área en producción (usuario GH) | **Sin errores**; observaciones de entrega cerradas |
| 2026-11-04 | Hotfixes de cierre | Validación de correo en importación de Cliente interno; normalización de cédula en generadores de cartas; rechazo de importación sin filas válidas en Formación |
| 2026-11-04 | Acta de cierre operativo | Área declarada **operativa** |

Acta de referencia: `docs/informes/cierre-operativo-gestion-humana-2026-11-04.md` y `docs/informes/cierre-observaciones-gestion-humana-2026-11-04.md`.

### 8.4 Criterios de aceptación aplicados

1. La funcionalidad opera en producción sin bloqueantes conocidos.
2. Los permisos por pestaña se comportan según `config/access.php` y las pruebas de acceso pasan.
3. Las importaciones validan, informan errores por fila y no alteran datos ante fallos.
4. Las exportaciones respetan filtros activos y usan el componente estándar.
5. La documentación técnica y de usuario queda actualizada en la misma entrega.
6. El build de frontend y la revisión de estilo se ejecutan sin errores.

---

## 9. Seguridad de la información

| Control | Implementación | Evidencia |
| --- | --- | --- |
| Autenticación obligatoria | Todas las rutas del área dentro del grupo `auth` | `routes/web.php` |
| Usuarios inactivos | Middleware `active` bloquea la operación | Middleware + pruebas |
| Contraseña temporal | Middleware `password.changed` fuerza cambio al primer ingreso | Middleware + pruebas |
| Registro público | **Deshabilitado** por política | Configuración de autenticación |
| Autorización granular | 206 permisos; 21 tableros de GH; `view`/`edit` por pestaña | `config/access.php` |
| Mínimo privilegio | Roles base y asignación explícita; `super-admin` separado | Seeder de roles y permisos |
| Auditoría | 1.788 eventos registrados en `audit_logs` | `SystemAuditService` |
| Protección del dato | Sin operaciones destructivas masivas en la entrega; importaciones con confirmación y preview | Procedimientos y revisiones |
| Gestión de secretos | Variables de entorno fuera del repositorio | `.env` no versionado |

---

## 10. Integridad y volumen de información al corte

| Entidad | Registros |
| --- | ---: |
| Fichas de empleados | 4.154 |
| Periodos laborales | 2.673 |
| Registros de cursos por persona | 1.724 |
| Acreditados | 1.813 |
| Registros de formación | 81.291 |
| Registros MT-ST-04 | 1.561 |
| Solicitudes de Cliente interno | 3.409 |
| Desvinculaciones (seguimientos) | 995 |
| Requisiciones de personal | 91 |
| Registros de vacaciones / incapacidades | 3 / 2 |
| Eventos de auditoría | 1.788 |
| Usuarios activos | 18 |

Estos volúmenes confirman que la solución opera con datos reales de producción y no con datos de demostración.

---

## 11. Gestión del cambio y de la configuración

| Práctica | Implementación |
| --- | --- |
| Control de versiones | Git; 479 confirmaciones entre 2026-04-23 y 2026-11-10 |
| Registro de cambios | Mensajes descriptivos por funcionalidad y documentos de feature en `docs/briefs/` |
| Revisión previa a cierre | Revisión técnica por criterios objetivos (pruebas, build, pint, permisos, coherencia) con informe en `docs/reviews/` |
| Documentación viva | Actualización obligatoria de `docs/modules/` y `docs/user/` en la misma entrega |
| Migraciones de base de datos | Versionadas y compatibles con MySQL y SQLite; prohibido el uso de `fresh` ante fallos |
| Cambios con interfaz | Build de Vite y entrega de pasos de prueba manual al usuario responsable |

---

## 12. Riesgos residuales y acciones

| # | Riesgo | Impacto | Control / acción | Responsable |
| --- | --- | --- | --- | --- |
| 1 | Dependencia de una sola instancia de servidor | Indisponibilidad del área | Verificación periódica de respaldos y plan de recuperación documentado en `docs/PROCEDURES.md` | TIC |
| 2 | Importaciones masivas con reemplazo de periodo | Pérdida de datos si se carga un archivo erróneo | Confirmación en interfaz, simulación previa y respaldo operativo antes de cargas masivas | GH + TIC |
| 3 | Cambios de permisos mal configurados | Exposición o bloqueo de información | Permisos por pestaña en `config/access.php`, pruebas de acceso y sincronización `app:sync-permissions` | TIC |
| 4 | Crecimiento del volumen de formación (81 mil registros) | Degradación de consultas | DataTables del servidor, índices y filtros acotados | Desarrollo |
| 5 | Deuda técnica en controladores extensos | Mayor costo de mantenimiento | Refactorización planificada, no bloqueante | Desarrollo |
| 6 | Dependencia de plantillas Word del área | Cartas con formato incorrecto si cambia la plantilla | Tablero Plantillas Word con administración centralizada y validación de variables | GH |

**Acciones correctivas registradas:** los hotfixes del 2026-11-04 (importación de Cliente interno, normalización de cédula en cartas e importación de Formación) fueron verificados y cerrados antes del acto de cierre operativo.

---

## 13. Pendientes y recomendaciones

### 13.1 Pendientes no bloqueantes

1. Refactorización de controladores de mayor tamaño (mejora de mantenibilidad).
2. División del archivo global de estilos por capas.
3. Optimizaciones menores de consultas y pruebas adicionales identificadas en revisiones.

Ninguno de estos puntos afecta la operación ni la aceptación del área.

### 13.2 Recomendaciones

1. Mantener la revisión semanal de logs (`laravel.log`) de rutas `/gestion-humana/*` durante el primer trimestre de operación.
2. Verificar mensualmente la ejecución de las sincronizaciones programadas (estados de MT-ST-04 y acreditaciones).
3. Ejecutar respaldos verificados antes de cada importación masiva con reemplazo de periodo.
4. Programar la revisión anual de permisos por pestaña con el área usuaria.
5. Incorporar este informe como evidencia en la auditoría interna del proceso de desarrollo.

---

## 14. Anexos

### Anexo A — Evidencia de pruebas automatizadas

Las pruebas del área se ejecutaron en cada cierre de funcionalidad con `php artisan test --compact`, con los siguientes registros de referencia:

| Evidencia | Ubicación |
| --- | --- |
| Informes de revisión por funcionalidad | `docs/reviews/FEAT-*.md` |
| Bitácoras de ejecución y verificación | `docs/runs/FEAT-*-run-log.md` |
| Pruebas del área | `tests/Feature/GestionHumana/` · `tests/Unit/` |

> Nota de esta emisión: la suite no se re-ejecutó el 2026-11-10 para no interferir con procesos activos del ambiente; la aceptación se soporta en el acta de cierre operativo y la validación en producción del 2026-11-04.

### Anexo B — Documentación de referencia

| Documento | Ubicación |
| --- | --- |
| Índice de documentación | `docs/INDEX.md` |
| Arquitectura | `docs/ARCHITECTURE.md` |
| Control de acceso | `docs/ACCESS_CONTROL.md` |
| Procedimientos y despliegue | `docs/PROCEDURES.md` · `docs/LOCAL_SETUP.md` |
| Cierre operativo GH | `docs/informes/cierre-operativo-gestion-humana-2026-11-04.md` |
| Cierre de observaciones GH | `docs/informes/cierre-observaciones-gestion-humana-2026-11-04.md` |
| Informe técnico octubre 2026 | `docs/informes/informe-tecnico-2026-10.md` |
| Informe gerencial octubre 2026 | `docs/informes/informe-gerencial-2026-10.md` |
| Documentación por módulo | `docs/modules/` y `docs/user/` |

### Anexo C — Glosario

| Término | Definición |
| --- | --- |
| **Tablero** | Módulo funcional del área, accesible desde el menú lateral. |
| **Pestaña** | Subdivisión de un tablero con permisos propios (`view` / `edit`). |
| **Ficha** | Registro maestro del empleado. |
| **Periodo laboral** | Intervalo de vínculo del empleado (ingreso–retiro); habilita reingresos. |
| **MT-ST-04** | Matriz de control de exámenes psicofísicos y psicosensométricos. |
| **MT-GH-04** | Formato de novedades de nómina (vacaciones, incapacidades, retiros, permisos). |
| **APO** | Reporte de acreditación de personal operativo ante la Superintendencia de Vigilancia. |
| **Export APO** | Archivo `.xls` generado para trámite de acreditación o renovación. |
| **KPI** | Indicador clave de desempeño. |

---

## 15. Aceptación formal

| Rol | Nombre | Cargo | Firma | Fecha |
| --- | --- | --- | --- | --- |
| Elaboró | | Equipo de desarrollo SJ StatFlow | | 2026-11-10 |
| Revisó (Calidad) | | | | |
| Aprobó (Dirección) | | | | |
| Recibió (Gestión Humana) | | | | |

**Declaración:** con la firma de este documento se formaliza la entrega del área **Gestión Humana** de la plataforma SJ StatFlow, en el estado operativo descrito en las secciones anteriores, con la documentación y la evidencia de verificación anexas.

---

**Fin del informe — INF-GH-001 v1.0**
