# Preguntas del Analista — FEAT-014

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

## Contexto recibido

**Feature ID:** FEAT-014  
**Módulo:** matriz-clientes (área **Comercial**)  
**Solicitud del usuario (texto original):**

- En `/comercial/servicios/editar` y al agregar servicio hay un **Checklist documental** amarrado al **servicio**.
- El checklist de seguimiento debe estar amarrado al **cliente**.
- Botón **antes de los filtros** del tablero de **Clientes** que diga «Checklist documental»; al activarlo, **tabla** con lista por cliente con sus documentos y opción de **editar estado** del documento.
- **Por cliente:** campo **fecha** y campo **días** — el usuario ingresa fecha de vencimiento de documentación y días de anticipación con los cuales el sistema alertará el vencimiento.

**Estado técnico hoy (repo):**

| Aspecto | Comportamiento actual |
| --- | --- |
| Modelo cliente | `commercial_clients`: NIT, datos maestros; **sin** columnas de checklist |
| Modelo servicio | `commercial_services`: 10 documentos (`doc_*`), cada uno con **estado**, `*_tracks_expiry`, `*_expires_on` |
| Catálogo documentos | `CommercialService::documentFields()` / `documentStatuses()` (OK, X, Pendiente, N/A, Incompleto) |
| UI checklist | Solo en `resources/views/areas/comercial/matriz-clientes/partials/service-fields.blade.php` (crear/editar servicio) |
| Vigencia / badges | Filtros `vigencia=expiring\|expired` en **servicios** y lógica `isExpiringSoon` / `isExpired` consideran contrato **y** documentos del **servicio** (umbrales fijos 30/60 días en código) |
| Import Excel | `MtCo01Importer` escribe vencimientos documentales en columnas del **servicio** |
| Vista clientes | `clients/index.blade.php`: filtros en `permission-filter-bar`; export Excel; sin modo checklist |
| Permisos | `comercial.matriz.view` (consulta), `comercial.matriz.manage` (crear/editar) |
| Doc módulo | [`docs/modules/matriz-clientes.md`](../modules/matriz-clientes.md): checklist por servicio; **«Notificaciones de vencimiento»** fuera de V1 |
| FEAT-007 | Vencimiento por documento en servicio (toggle + fecha) |
| FEAT-013 | Configuración global de **correos** por tipo de aviso (v1 conecta requisiciones); **no** define aún avisos de matriz comercial |

---

## Entendimiento del analista (resumen)

Comercial quiere que el **seguimiento documental** (estados y vencimientos del MT-CO-01) deje de ser responsabilidad de cada **contrato/servicio** y pase a ser **único por cliente (NIT)**: un mismo cliente con varios portafolios no debería duplicar ni divergir el RUT, cámara de comercio, etc.

La operación diaria propuesta combina:

1. **Tablero Clientes** con un control «Checklist documental» (antes de filtrar) que cambia la vista a una **tabla operativa**: clientes × documentos, con edición de **estado**.
2. **Captura de vencimiento y anticipación:** fecha de vencimiento por documentación y **días de anticipación** para que el sistema **alerte** antes del vencimiento (hoy solo hay badges/listados con ventanas fijas en servicios, sin correos en matriz).

Implica **mover o replicar datos** desde `commercial_services` hacia el cliente, **rediseñar** pantallas de servicio (¿quitar checklist?), y alinear **dashboard**, filtros de vigencia, import Excel y posible **FEAT-013** si las alertas deben ser correo además de indicadores en pantalla.

---

## Preguntas abiertas (priorizadas — negocio)

Responde cada punto para cerrar el brief:

### Objetivo, usuarios y alcance

1. **Objetivo y usuarios:** ¿Quién usará el modo «Checklist documental» día a día (rol Comercial consulta vs gestión) y qué resultado esperan al terminar (ej. «saber qué clientes tienen RUT vencido en 15 días»)?

2. **Alcance explícito:** ¿Esta entrega incluye **solo** la vista en Clientes + edición de estados/vencimientos, o también debe actualizarse el **Dashboard** comercial y el listado/filtros de **Servicios** para que reflejen el checklist del **cliente** (y no el del servicio)?

### Datos existentes y checklist en servicios

3. **Migración servicio → cliente:** Si un cliente tiene **varios servicios** con el mismo documento (ej. RUT) en estados o fechas **distintos**, ¿cuál manda al pasar al cliente (el más reciente editado, el servicio activo, el de un portafolio prioritario, unión manual por Comercial)?

4. **Checklist en crear/editar servicio:** Tras mover el seguimiento al cliente, ¿se **elimina por completo** el bloque «Checklist documental» en servicios, o debe **quedar visible solo lectura** (legacy) un tiempo? ¿El Excel MT-CO-01 seguirá trayendo columnas documentales por **hoja/servicio** y el sistema debe volcar al **cliente**?

5. **Documentos por cliente:** ¿El conjunto de documentos es **siempre los mismos 10** que hoy (P. económica, FO-CO-02, LAFT, RUT, etc.) para **todos** los clientes, o algunos clientes pueden tener documentos «no aplican» sin usar solo el estado N/A?

### Fecha, días y alertas

6. **Fecha y días — granularidad:** Cuando dice «por cliente: campo fecha y campo días», ¿es **por cada documento** del cliente (fecha de vencimiento + días de anticipación **de ese documento**), o **una sola** fecha y **un solo** número de días para **todo** el checklist del cliente?

7. **Relación con «Tiene vencimiento»:** Hoy, si el estado no es OK/X/Pendiente/Incompleto, no se pide vencimiento. ¿Se mantiene la regla «solo documentos con vencimiento relevante llevan fecha» (equivalente al toggle actual) o **toda** fila con estado distinto de N/A debe poder tener fecha + días?

8. **Formato de la alerta en v1:** ¿Basta con **badges / colores / filtros** en el tablero (como «por vencer» hoy), o deben dispararse **correos** usando la plataforma de **FEAT-013** (nuevo tipo de aviso «documento comercial por vencer»)? Si es correo, ¿a quién va (lista fija Comercial, asesor del servicio, configurable en admin)?

9. **Umbral por defecto:** Si no completan «días de anticipación», ¿valor por defecto del negocio (ej. 30 días, 60 días) o obligatorio si hay fecha de vencimiento?

### Interfaz (tablero Clientes)

10. **Comportamiento del botón «Checklist documental»:** ¿Alterna la **misma pantalla** (lista normal ↔ tabla checklist) manteniendo filtros NIT/ciudad, o abre **ruta/pantalla aparte**? ¿El botón es toggle (activo/inactivo) o navegación?

11. **Formato de la tabla:** ¿Prefieren **una fila por cliente** con columnas por documento (matriz ancha), o **una fila por documento** (cliente repetido: NIT, nombre, documento, estado, fecha, días, acciones)? ¿Debe poder **filtrar** solo clientes con documentos vencidos / por vencer?

12. **Edición de estado:** ¿Cambio **en línea** en la tabla (select guardado al instante), **modal** por fila, o enlace a **editar ficha cliente** ampliada? ¿Exige confirmación o comentario al cambiar estado?

13. **Export Excel:** En modo checklist, ¿necesitan **Exportar Excel** con la misma grilla (cliente + documentos + estados + fechas + días)? ¿Misma regla que listados actuales (`BaseExport`)?

### Permisos y procedimiento

14. **Permisos:** ¿Quién puede **editar** estados/fechas/días — solo `comercial.matriz.manage`, o también consulta en casos puntuales? ¿Hace falta permiso **nuevo** (ej. solo checklist) distinto de editar datos maestros del cliente?

15. **Documentación usuario / operación:** ¿Existe procedimiento MT-CO-01 que defina **orden** de revisión, responsable por documento o periodicidad, que debamos reflejar en la guía de usuario?

---

## Supuestos temporales (si el usuario no responde aún)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| 1 | Checklist vive en **cliente** con los **mismos 10 documentos** y estados actuales | Catálogo distinto o documentos variables por tipo de cliente |
| 2 | Migración: por cada par cliente+documento se toma el valor del servicio **activo más reciente** (`updated_at`) | Datos incorrectos si priorizan otro criterio |
| 3 | Se **quita** el checklist de crear/editar **servicio**; servicios solo contrato/vigencia operativa | Usuarios siguen editando en servicio y desincronizan |
| 4 | **Fecha + días** son **por documento** en el cliente (no un solo par global) | Modelo UI/BD incompatible con expectativa «un vencimiento por cliente» |
| 5 | v1 = **tabla + edición + badges/filtros** en Clientes; **sin** correo (FEAT-013 en fase 2) | Expectativa de alertas por email no cumplida |
| 6 | Días de anticipación por documento; default **30** si vacío | Umbrales distintos al dashboard actual (30/60 fijos en servicios) |
| 7 | Edición en **modal** o fila expandible; permiso **`comercial.matriz.manage`** | UX o segregación de roles incorrecta |
| 8 | Botón checklist = **toggle de vista** en `clients/index` con mismos filtros `q`/`city` | Navegación o URLs distintas a lo esperado |
| 9 | Dashboard y filtros de servicios se **actualizan** en la misma feature para leer vencimiento del **cliente** | Indicadores contradictorios entre tableros |
| 10 | Export Excel del modo checklist **sí** incluido | Reporte operativo incompleto |

---

## Fuera de alcance (propuesta analista — confirmar con usuario)

- Adjuntos PDF / integración Calidad (ya fuera de V1 en matriz).
- Alta de **nuevos tipos de documento** desde pantalla (catálogo fijo por código).
- Notificaciones in-app o SMS (salvo que respondan correo en pregunta 8).
- Sincronización automática con requisiciones más allá del maestro de clientes existente.

---

## Borrador preliminar (NO enviar al Arquitecto hasta cerrar preguntas)

> `BORRADOR — pendiente respuestas usuario y Arquitecto`

| Campo | Valor provisional |
| --- | --- |
| ID | FEAT-014 |
| Módulo / área | Comercial — matriz clientes |
| Título | Checklist documental por cliente + tablero de seguimiento |
| Objetivo | Centralizar estados y vencimientos documentales en el cliente; operar desde tablero Clientes |
| Incluye (tentativo) | Persistencia en cliente; migración desde servicios; UI toggle «Checklist documental»; edición estados/fechas/días; ajuste vigencia/export/import según respuestas |
| Permiso tentativo | `comercial.matriz.view` / `comercial.matriz.manage` (sin permiso nuevo salvo respuesta 14) |
| Integración FEAT-013 | Opcional v2 (tipo de aviso + destinatarios admin) |
| Shared-files | Posible `config/access.php`, rutas comercial, dashboard JS, importador, docs `matriz-clientes` |

---

## Estado

- [x] Decisiones clave cerradas (2026-07-29) — Arquitecto puede redactar brief; preguntas 3, 11–15 con supuestos documentados abajo
- [ ] Brief final aprobado para implementacion

## Respuestas del usuario

| # | Respuesta |
| --- | --- |
| 6 | **Un solo par** fecha de vencimiento + días de anticipación **por cliente** (no por cada documento). |
| 4 | **Quitar** el checklist en crear/editar servicio; gestión solo desde Clientes. |
| 10 | **Pantalla / ruta dedicada** (no toggle en la misma vista del listado). |
| 8 | **UI ahora** (badges/filtros); **correo en entrega posterior** (FEAT-013). |

**Pendiente confirmacion explicita (supuesto en brief si no responde):**

| # | Supuesto provisional |
| --- | --- |
| 3 | Migracion: por documento, valor del servicio **activo** con `updated_at` mas reciente |
| 11 | Tabla: fila por **documento**; fecha/dias del cliente repetidos o agrupados visualmente por NIT |
| 2 | Misma entrega alinea filtros/badges de **Servicios** y **Dashboard** al checklist del **cliente** |
| 13 | Export Excel del modo checklist **si**, con `BaseExport` |
| 14 | Edicion con `comercial.matriz.manage`; consulta con `comercial.matriz.view` |
| 12 | Edicion de estado en tabla (select + guardar por fila o formulario por cliente) — Arquitecto propone UX minima |

### Ajuste a supuestos temporales (post-respuesta)

| # | Supuesto | Riesgo |
| --- | --- | --- |
| 4 | ~~Por documento~~ → **fecha + dias unicos por cliente** | Vencimiento no distingue RUT vs camara; negocio lo acepto asi |
| 5 | v1 UI; correo fase 2 | OK alineado con usuario |
| 8 | ~~Toggle~~ → **ruta dedicada** + boton en index que navega | OK |
| 3 | Quitar checklist servicio | OK alineado |
