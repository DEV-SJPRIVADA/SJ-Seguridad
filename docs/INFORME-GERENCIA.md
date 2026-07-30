# Informe de Avance — Plataforma Web SJ Seguridad

**Fecha:** Julio 2026
**Preparado para:** Gerencia
**Versión del documento:** 1.0

---

## 1. Resumen Ejecutivo

La plataforma web modular de SJ Seguridad se encuentra operativa con **6 módulos funcionales**, construidos sobre Laravel 13, PHP 8.3 y MySQL. El proyecto avanza bajo un enfoque incremental con control de acceso por roles, documentación viva y estándares de calidad.

- **212 pruebas automatizadas pasan** (de 214 totales)
- **50 tablas en base de datos**
- **35 modelos**, **20 controladores**, **114 vistas**
- **8 áreas del negocio configuradas** en el sistema

---

## 2. Módulos Implementados

### 2.1 Administración de Usuarios
| Aspecto | Estado |
|---|---|
| CRUD completo de usuarios | ✅ Operativo |
| Roles: super-admin, administrador, usuario | ✅ Operativo |
| Permisos granulares por área y funcionalidad | ✅ Operativo |
| Sede para suministros por usuario | ✅ Operativo |
| Cambio obligatorio de contraseña temporal | ✅ Operativo |
| Usuarios inactivos no pueden operar | ✅ Operativo |

### 2.2 Requisiciones de Personal (Gestión Humana)
| Aspecto | Estado |
|---|---|
| Solicitud con campo "Estructura del servicio" | ✅ Operativo |
| Dashboard con KPIs y ApexCharts | ✅ Operativo |
| Mis requisiciones (seguimiento) | ✅ Operativo |
| Gestión de solicitudes (GH) | ✅ Operativo |
| Autorización de gerencia para cargos nuevos | ✅ Operativo |
| Encargados de selección (toggles sobre usuarios GH) | ✅ Operativo |
| Parámetros configurables (ciudades, clientes, tipos, etc.) | ✅ Operativo |
| Notificaciones por correo al crear y al cambiar estado | ✅ Operativo |
| Exportación Excel con filtros de fecha | ✅ Operativo |

### 2.3 Suministros
| Aspecto | Estado |
|---|---|
| Solicitud de insumos (aseo, cafetería, papelería) | ✅ Operativo |
| Aprobación / rechazo por Calidad | ✅ Operativo |
| Insumos aprobados con reporte FO-AD-44 | ✅ Operativo |
| Catálogo de productos | ✅ Operativo |
| Sedes físicas con snapshot | ✅ Operativo |
| Notificación a Calidad al crear solicitud | ✅ Operativo |
| Flujo compras y costeo | 🔄 Pendiente v2 |

### 2.4 Documentos de Calidad
| Aspecto | Estado |
|---|---|
| Biblioteca de documentos por área | ✅ Operativo |
| Mis documentos (asignación personal) | ✅ Operativo |
| Administración (publicar, editar, inactivar) | ✅ Operativo |
| Tipos documentales (14 tipos: procedimiento, formato, etc.) | ✅ Operativo |
| Visibilidad por área y por usuario | ✅ Operativo |
| Categorías y notificaciones | 🔄 Pendiente v2 |

### 2.5 Indicadores de Operaciones (KPIs)
| Aspecto | Estado |
|---|---|
| Captura mensual de 9 indicadores FT-OP | ✅ Operativo |
| Dashboard ejecutivo con ranking y críticos | ✅ Operativo |
| Gráficos ApexCharts unificados | ✅ Operativo |
| Ajustes: periodos, metas, auditoría, capturadores | ✅ Operativo |
| Consolidado mensual | ✅ Operativo |
| Exportaciones: PDF, Excel, PPTX (FO-GI-39) | ✅ Operativo |
| Semáforo y criticidad por indicador | ✅ Operativo |
| Mejora continua por captura | ✅ Operativo |

### 2.6 Matriz Comercial
| Aspecto | Estado |
|---|---|
| Dashboard con KPIs comerciales | ✅ Operativo |
| Maestro de clientes por NIT | ✅ Operativo |
| Servicios por portafolio | ✅ Operativo |
| Checklist documental con vencimientos | ✅ Operativo |
| Correo diario de documentación por vencer | ✅ Operativo |
| Importación masiva desde Excel MT-CO-01 | ✅ Operativo |
| Notificaciones configurables | ✅ Operativo |

---

## 3. Estado de la Fábrica (Features)

### Completadas (9 features)
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

### En Desarrollo (5 features)
| ID | Feature | Estado |
|---|---|---|
| FEAT-013 | Configuración global de notificaciones (Super Admin) | Implementado parcial |
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
| Assets | Vite 6 |

### Cobertura del Sistema
- **212 pruebas automatizadas** (24 archivos Feature, 3 Unit)
- **50 tablas** distribuidas en 6 dominios de negocio
- **8 áreas del negocio** mapeadas con permisos diferenciados

---

## 5. Seguridad

- Autenticación web con Laravel Breeze
- Control de acceso por roles (3 niveles) y permisos granulares
- Usuarios inactivos no pueden iniciar sesión
- Contraseñas temporales fuerzan cambio al primer ingreso
- Permisos centralizados en `config/access.php`
- Auditoría de cambios en indicadores y periodos
- Sin registro público de usuarios

---

## 6. Próximos Pasos (Roadmap)

### Corto Plazo
| Actividad | Prioridad |
|---|---|
| Finalizar features comerciales en desarrollo (FEAT-013 a 017) | Alta |
| Completar flujo de suministros (compras y costeo) | Media |
| Categorías y notificaciones en Documentos Calidad | Media |

### Mediano Plazo
| Actividad | Prioridad |
|---|---|
| Módulo de Programación de servicios | Media |
| Módulo Jurídico | Baja |
| Módulo Admin y Financiero | Baja |
| Dashboard corporativo multi-área | Baja |

### Continuo
- Mantenimiento de documentación viva
- Pruebas automatizadas por feature
- Revisiones de seguridad periódicas

---

## 7. Métricas del Proyecto

| Indicador | Valor |
|---|---|
| Módulos funcionales | 6 |
| Features completadas | 9 |
| Features en desarrollo | 5 |
| Pruebas automatizadas | 212 pasan / 214 totales |
| Tablas en BD | 50 |
| Modelos | 35 |
| Vistas | 114 |
| Migraciones | 56 |
| Archivos de documentación | 19 |

---

**Fin del informe.**
