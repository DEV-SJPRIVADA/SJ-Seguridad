# Informe de Avance — Plataforma Web SJ Seguridad

**Fecha:** Agosto 2026
**Preparado para:** Gerencia
**Versión del documento:** 2.0

---

## 1. Resumen Ejecutivo

La plataforma web modular de SJ Seguridad se encuentra operativa y en crecimiento continuo. En agosto se ejecutaron **106 commits** enfocados en madurar los módulos existentes, estandarizar la interfaz de usuario y agregar funcionalidades clave como la gestión de archivos de empleados, cartas laborales, indicadores delegados y un panel de compras completo.

### Métricas del Proyecto (Agosto 2026)

| Indicador | Julio 2026 | Agosto 2026 | Variación |
|---|---|---|---|
| Módulos funcionales | 6 | 8 | +2 |
| Pruebas automatizadas | 212 | ~280 | +68 |
| Tablas en BD | 50 | 58 | +8 |
| Modelos | 35 | 42 | +7 |
| Vistas Blade | 114 | 160+ | +46 |
| Commits en el mes | — | 106 | — |
| Controles de formulario estandarizados | — | 100% | — |

---

## 2. Módulos Implementados

### 2.1 Administración de Usuarios
| Aspecto | Estado | Novedad Ago |
|---|---|---|
| CRUD completo de usuarios | ✅ Operativo | |
| Roles: super-admin, administrador, usuario | ✅ Operativo | |
| Permisos granulares por área y funcionalidad | ✅ Operativo | |
| Sede para suministros por usuario | ✅ Operativo | |
| Cambio obligatorio de contraseña temporal | ✅ Operativo | |
| Usuarios inactivos no pueden operar | ✅ Operativo | |
| Número de cédula en creación/edición + correo de bienvenida | ✅ Operativo | **Nuevo Ago** |
| Copiar acceso de otro usuario al crear/editar | ✅ Operativo | **Nuevo Ago** |
| Formulario maestro-detalle de permisos | ✅ Operativo | **Nuevo Ago** |
| Refuerzo `AuthorizesRequests` en controlador base | ✅ Operativo | **Nuevo Ago** |

### 2.2 Requisiciones de Personal (Gestión Humana)
| Aspecto | Estado | Novedad Ago |
|---|---|---|
| Solicitud con campo "Estructura del servicio" | ✅ Operativo | |
| Dashboard con KPIs y ApexCharts | ✅ Operativo | |
| Mis requisiciones (seguimiento) | ✅ Operativo | |
| Gestión de solicitudes (GH) | ✅ Operativo | |
| Autorización de gerencia para cargos nuevos | ✅ Operativo | |
| Encargados de selección (toggles sobre usuarios GH) | ✅ Operativo | |
| Parámetros configurables (ciudades, clientes, tipos, etc.) | ✅ Operativo | |
| Notificaciones por correo al crear y al cambiar estado | ✅ Operativo | |
| Exportación Excel con filtros de fecha | ✅ Operativo | |
| Filtros dinámicos (en curso / contratadas / canceladas) | ✅ Operativo | **Nuevo Ago** |
| Loader en tabla de gestión | ✅ Operativo | **Nuevo Ago** |
| Pestaña "Parámetros" renombrada a "Catálogos" | ✅ Operativo | **Nuevo Ago** |
| Notificaciones y enrutamiento de notificaciones | ✅ Operativo | **Nuevo Ago** |
| Jurídico habilitado para requisiciones | ✅ Operativo | **Nuevo Ago** |

### 2.3 Ficha de Empleados y Gestión Humana (NUEVO)
| Aspecto | Estado |
|---|---|
| Ficha de empleados (alta, baja, reingreso) | ✅ Operativo |
| Desvinculación de empleados (causal, fechas, recontratable) | ✅ Operativo |
| Gestión de empleados con prellenado desde requisición | ✅ Operativo |
| Filtro por estado de empleo (activo / inactivo / todos) | ✅ Operativo |
| Tipo de documento desde catálogo | ✅ Operativo |
| Ciudad de trabajo precargada desde requisición | ✅ Operativo |
| Exportación de plantilla de archivo (archive_shelf / archive_box) | ✅ Operativo |
| Carga masiva de empleados desde plantilla unificada | ✅ Operativo |
| Catálogo de empleados con etiquetas dinámicas | ✅ Operativo |
| Copiar acceso de usuarios | ✅ Operativo |
| Perfil de empleado con diseño optimizado | ✅ Operativo |

### 2.4 Cartas Laborales (NUEVO)
| Aspecto | Estado |
|---|---|
| Cartas de terminación / desvinculación (Word) | ✅ Operativo |
| Cartas de contratación (Word) | ✅ Operativo |
| Generación masiva por pack | ✅ Operativo |
| ~90 variables dinámicas desde 4 modelos | ✅ Operativo |
| Formato de fechas "21 de Agosto del 2026" | ✅ Operativo |
| Plantillas Word independientes (tablero dedicado) | ✅ Operativo |
| Catálogo de firmas (cargo + firma) | ✅ Operativo |
| DocxRenderer con manejo de placeholders divididos | ✅ Operativo |

### 2.5 Archivo de Empleados (NUEVO)
| Aspecto | Estado |
|---|---|
| Historias laborales (listado, búsqueda, paginación AJAX) | ✅ Operativo |
| Consulta múltiple de cédulas | ✅ Operativo |
| Historial de consultas con filtros | ✅ Operativo |
| Edición inline de estante y caja | ✅ Operativo |
| Exportación de plantilla de archivo | ✅ Operativo |

### 2.6 Indicadores de Operaciones (KPIs)
| Aspecto | Estado | Novedad Ago |
|---|---|---|
| Captura mensual de 9 indicadores FT-OP | ✅ Operativo | |
| Dashboard ejecutivo con ranking y críticos | ✅ Operativo | |
| Gráficos ApexCharts unificados | ✅ Operativo | |
| Ajustes: periodos, metas, auditoría, capturadores | ✅ Operativo | |
| Consolidado mensual | ✅ Operativo | |
| Exportaciones: PDF, Excel, PPTX (FO-GI-39) | ✅ Operativo | |
| Semáforo y criticidad por indicador | ✅ Operativo | |
| Mejora continua por captura | ✅ Operativo | |
| Vista previa HTML del informe FO-GI-39 | ✅ Operativo | **Nuevo Ago** |
| Captura delegada (suplente registra por titular) | ✅ Operativo | **Nuevo Ago** |
| Indicador FT-OP-03: metadatos, gráfico, valores críticos | ✅ Operativo | **Nuevo Ago** |
| FT-OP-01: vista consolidada con filtrado por capturador | ✅ Operativo | **Nuevo Ago** |
| Exportación a Excel e PDF desde consolidado | ✅ Operativo | **Nuevo Ago** |
| Correo con PDF adjunto y enlaces firmados | ✅ Operativo | **Nuevo Ago** |

### 2.7 Suministros
| Aspecto | Estado | Novedad Ago |
|---|---|---|
| Solicitud de insumos (aseo, cafetería, papelería) | ✅ Operativo | |
| Aprobación / rechazo por Calidad | ✅ Operativo | |
| Insumos aprobados con reporte FO-AD-44 | ✅ Operativo | |
| Catálogo de productos | ✅ Operativo | |
| Sedes físicas con snapshot | ✅ Operativo | |
| Notificación a Calidad al crear solicitud | ✅ Operativo | |
| Exportación PDF y Excel de solicitudes | ✅ Operativo | **Nuevo Ago** |
| Verificación de sede asignada antes de enviar | ✅ Operativo | **Nuevo Ago** |
| Agrupación de productos por categoría | ✅ Operativo | **Nuevo Ago** |
| Flujo compras y costeo | 🔄 Pendiente v2 | |

### 2.8 Documentos de Calidad
| Aspecto | Estado | Novedad Ago |
|---|---|---|
| Biblioteca de documentos por área | ✅ Operativo | |
| Mis documentos (asignación personal) | ✅ Operativo | |
| Administración (publicar, editar, inactivar) | ✅ Operativo | |
| Tipos documentales (14 tipos: procedimiento, formato, etc.) | ✅ Operativo | |
| Visibilidad por área y por usuario | ✅ Operativo | |
| Refactorización de permisos de acceso | ✅ Operativo | **Nuevo Ago** |
| Categorías y notificaciones | 🔄 Pendiente v2 | |

### 2.9 Solicitudes de Compra (NUEVO)
| Aspecto | Estado |
|---|---|
| Creación con fotos de productos | ✅ Operativo |
| Aprobación / rechazo por director | ✅ Operativo |
| Reenvío de solicitudes rechazadas | ✅ Operativo |
| Adjuntos múltiples (hasta 5 archivos) | ✅ Operativo |
| Registro de correos enviados (mail logs) | ✅ Operativo |
| Exportación a PDF | ✅ Operativo |
| Filtros por estado (pendiente / aprobada / rechazada) | ✅ Operativo |
| Panel de control (KPIs, bandeja, paginación) | ✅ Operativo |
| Notificaciones por correo al director | ✅ Operativo |
| Notificación de resultado (aprobación / rechazo) | ✅ Operativo |

### 2.10 Matriz Comercial
| Aspecto | Estado |
|---|---|
| Dashboard con KPIs comerciales | ✅ Operativo |
| Maestro de clientes por NIT | ✅ Operativo |
| Servicios por portafolio | ✅ Operativo |
| Checklist documental con vencimientos | ✅ Operativo |
| Correo diario de documentación por vencer | ✅ Operativo |
| Importación masiva desde Excel MT-CO-01 | ✅ Operativo |
| Notificaciones configurables | ✅ Operativo |
| Diseño compacto de filtros y tablas | ✅ Operativo |

---

## 3. Estado de la Fábrica (Features)

### Completadas (Julio 2026 — 9 features)
| ID | Feature | Fecha cierre |
|---|---|---|
| FEAT-002 | Export informe gestión FO-GI-39 (PPTX) | Julio 2026 |
| FEAT-003 | Capturadores en Ajustes indicadores | Julio 2026 |
| FEAT-004 | Ranking dashboard indicadores operaciones | Julio 2026 |
| FEAT-005 | Campo Estructura del servicio en requisiciones | Julio 2026 |
| FEAT-006 | Export Excel Gestión: todos los campos + rango fechas | Julio 2026 |
| FEAT-007 | Checklist documental: fecha vencimiento por documento | Julio 2026 |
| FEAT-010 | Unificar gráficos ApexCharts (sin Chart.js/ECharts) | Julio 2026 |
| FEAT-011 | Encargados selección: usuarios GH activables | Julio 2026 |
| FEAT-012 | Autorización gerencia cargo nuevo | Julio 2026 |

### Completadas (Agosto 2026 — 12 features)
| ID | Feature | Commits clave |
|---|---|---|
| FEAT-013 | Configuración global de notificaciones | 2c89f2b, 438e618 |
| FEAT-018 | Ficha de empleados: CRUD completo + desvinculación | f1f0401, 8905387, 7dfb48c, 48d8f80 |
| FEAT-019 | Archivo de empleados: historias laborales + consultas | 8e39b1d, 175fa42, 40dbae7, a2a84e2 |
| FEAT-020 | Cartas de terminación / desvinculación (Word) | 132f6f1, be03c33, fd664c9 |
| FEAT-021 | Cartas de contratación (Word) | 72313c3, fd664c9 |
| FEAT-022 | Plantillas Word: tablero independiente + DocxRenderer | 23e78a5, be03c33 |
| FEAT-023 | Panel de control de compras + KPIs | 1b28c88, b9845c6, 685929b |
| FEAT-024 | Solicitudes de compra: reenvío + adjuntos + fotos | c324b69, 98b83f4, 0720fe9 |
| FEAT-025 | Exportación PDF/Excel solicitudes de suministro | 80badf1, a03c1ce |
| FEAT-026 | Captura delegada de indicadores | 03b896a |
| FEAT-027 | Vista previa HTML informe FO-GI-39 | a3056b7 |
| FEAT-028 | Auditoría central del sistema | c19884a, d1f3274, cf28bac |

### En Desarrollo (continúan de Julio)
| ID | Feature | Estado |
|---|---|---|
| FEAT-014 | Checklist documental por cliente + vista seguimiento | Implementado parcial |
| FEAT-015 | Notificación correo documentación comercial por vencer | Pendiente revisión |
| FEAT-016 | Listado servicios: orden columnas y vigencia por contrato | Pendiente revisión |
| FEAT-017 | Comercial: tablero Gestión Clientes + pestañas | En desarrollo |

---

## 4. Arquitectura y Tecnología

### Stack Principal
| Componente | Tecnología |
|---|---|
| Backend | Laravel 13 + PHP 8.3 |
| Base de datos | MySQL 8 |
| Frontend | Blade + Alpine.js + Tailwind CSS 3 |
| Gráficos | ApexCharts 6.6.1 (único estándar) |
| Control de acceso | Spatie Laravel Permission |
| Exportaciones | PhpSpreadsheet (Excel), DomPDF (PDF) |
| Plantillas Word | PhpOffice (PHPWord) vía DocxRenderer |
| Assets | Vite 6 |

### Estructura Modular
- **Módulos Compartidos:** Requisiciones, Indicadores — controladores, vistas y rutas compartidas entre áreas.
- **Funcionalidades Únicas de Área:** Gestión Humana, Compras, Calidad — lógica exclusiva por departamento.
- **Navegación Dinámica:** Sidebar generado desde `config/access.php` y permisos del usuario.

### Estandar de Componentes
- **Selectores:** `<x-searchable-select>` con Alpine.js (<Select2 eliminado).
- **Controles de formulario:** Variables CSS para altura, padding, radio de borde — 100% unificados.
- **Paginación:** Estilo unificado en todas las vistas.
- **Exportaciones Excel:** Base configurable vía `App\Exports\BaseExport`.

### Cobertura del Sistema (Agosto 2026)
- **~280 pruebas automatizadas** (estimado; 212 julio + pruebas agosto)
- **58 tablas** en base de datos
- **42 modelos**, **30+ controladores**, **160+ vistas**
- **10 módulos funcionales** activos
- **106 commits** en agosto

---

## 5. Seguridad

- Autenticación web con Laravel Breeze
- Control de acceso por roles (3 niveles) y permisos granulares
- Usuarios inactivos no pueden iniciar sesión
- Contraseñas temporales fuerzan cambio al primer ingreso
- Permisos centralizados en `config/access.php`
- Auditoría central del sistema (`SystemAuditService`) — creación, edición, eliminación
- Sin registro público de usuarios
- Excepción 419 manejada con redirección a login

---

## 6. Resumen de Avance por Área (Agosto 2026)

| Área | Commits | Funcionalidades Clave |
|---|---|---|
| Solicitudes de Compra | ~18 | Panel de control, reenvío, adjuntos múltiples, fotos, mail logs, exportación PDF |
| Gestión Humana / Fichas | ~25 | Ficha empleados CRUD, desvinculación, reingreso, ciudad de trabajo, catálogos dinámicos |
| Cartas Laborales | ~8 | Desvinculación Word, contratación Word, DocxRenderer, ~90 variables, plantillas independientes |
| Archivo de Empleados | ~7 | Historias laborales, consulta múltiple, historial, edición inline, exportación |
| Indicadores / Dashboard | ~10 | Vista previa informe, captura delegada, FT-OP-03, consolidado, exportación Excel/PDF |
| Administración / Usuarios | ~8 | Copiar acceso, documento de identidad, permisos maestro-detalle, correo bienvenida |
| Suministros | ~4 | Exportación PDF/Excel, validación sede, agrupación por categoría |
| UI / Navegación | ~18 | Selectores reutilizables, compacto de formularios, paginación, loaders, CSS unificado |
| Auditoría | ~3 | Auditoría central, rango de fechas, sincronización |
| Notificaciones | ~2 | Configuración global, enrutamiento de notificaciones |
| Documentación | ~3 | Técnica y usuario actualizada |

---

## 7. Próximos Pasos (Roadmap)

### Corto Plazo
| Actividad | Prioridad |
|---|---|
| Finalizar features comerciales en desarrollo (FEAT-014 a 017) | Alta |
| Completar flujo de suministros (compras y costeo) | Media |
| Categorías y notificaciones en Documentos Calidad | Media |
| Pruebas automatizadas para módulos nuevos (GH, Compras, Archivo) | Alta |

### Mediano Plazo
| Actividad | Prioridad |
|---|---|
| Módulo de Programación de servicios | Media |
| Módulo Jurídico | Baja |
| Módulo Admin y Financiero | Baja |
| Dashboard corporativo multi-área | Baja |
| Migración de selects jQuery → Alpine.js (progreso ~80%) | Media |

### Continuo
- Mantenimiento de documentación viva
- Pruebas automatizadas por feature
- Revisiones de seguridad periódicas
- Refactorización de UI para consistencia visual

---

## 8. Métricas del Proyecto

| Indicador | Julio 2026 | Agosto 2026 |
|---|---|---|
| Módulos funcionales | 6 | 8 (+2) |
| Features completadas | 9 | 21 (+12) |
| Features en desarrollo | 5 | 4 (-1) |
| Pruebas automatizadas | 212 | ~280 (+68) |
| Tablas en BD | 50 | 58 (+8) |
| Modelos | 35 | 42 (+7) |
| Controladores | 20 | 30+ (+10) |
| Vistas | 114 | 160+ (+46) |
| Migraciones | 56 | 65+ (+9) |
| Commits en el mes | — | 106 |
| Archivos de documentación | 19 | 25+ (+6) |

---

**Fin del informe.**
