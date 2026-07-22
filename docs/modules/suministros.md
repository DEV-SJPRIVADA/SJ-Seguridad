# Módulo Compartido: Gestión de Suministros

## 1. Objetivo del Módulo
Permitir a los usuarios autorizados realizar solicitudes de suministros (aseo, cafetería, papelería, etc.), estableciendo un flujo de aprobación que involucra validación por parte de Calidad y ejecución de compra y costeo por parte del área de Compras.

## 2. Flujo del Proceso (Estados de Solicitud)
El ciclo de vida de una solicitud de suministros será el siguiente:

1. **`borrador` (Opcional):** El solicitante está armando la lista.
2. **`pendiente_calidad`:** Solicitud enviada. Esperando revisión de la Jefe de Calidad.
3. **`aprobada_calidad`:** Calidad revisó, (opcionalmente) ajustó cantidades y aprobó el pedido. Pasa a la bandeja de Compras.
4. **`rechazada_calidad`:** Calidad denegó la solicitud (se requiere justificación).
5. **`en_compras`:** Compras está cotizando o adquiriendo los productos.
6. **`completada`:** Compras adquirió los productos, ingresó los costos unitarios y cerró la solicitud.

## 3. Estructura de Base de Datos

### Tabla: `supply_products` (Catálogo Maestro)
Catálogo administrable de productos disponibles para pedir.
* `id` (PK)
* `name` (string) - Ej: "ALCOHOL GALON"
* `description` (string, nullable) - Ej: "LIMPIADOR SUPERFICIES"
* `category` (string, nullable) - Ej: "Aseo", "Cafetería" (Para organizar el catálogo)
* `is_active` (boolean) - Default true. Si es false, no aparece para nuevas solicitudes.
* `timestamps`

### Tabla: `supply_requests` (Cabecera del Pedido)
* `id` (PK)
* `user_id` (FK -> users) - Quién solicita
* `area_key` (string) - Área a la que pertenece el solicitante (para centros de costo)
* `status` (enum/string) - Estado actual (ver flujo)
* `observations` (text, nullable) - Notas del solicitante
* `quality_reviewer_id` (FK -> users, nullable) - Quién aprobó/rechazó en Calidad
* `quality_observations` (text, nullable) - Notas de Calidad al aprobar/rechazar
* `purchasing_manager_id` (FK -> users, nullable) - Quién gestionó la compra
* `total_cost` (decimal, nullable) - Costo total de la solicitud (calculado al completar)
* `timestamps`

### Tabla: `supply_request_items` (Detalle del Pedido)
* `id` (PK)
* `supply_request_id` (FK -> supply_requests)
* `supply_product_id` (FK -> supply_products)
* `requested_quantity` (integer) - Cantidad pedida originalmente
* `approved_quantity` (integer, nullable) - Cantidad autorizada por Calidad
* `unit_cost` (decimal, nullable) - Costo ingresado por Compras
* `timestamps`

## 4. Matriz de Permisos (`config/access.php`)
Siguiendo la arquitectura actual, los accesos se controlarán con:

* **Tableros / Vistas:**
  * `view.board.suministros.mis_solicitudes`: Ver y crear solicitudes propias.
  * `view.board.suministros.revision_calidad`: Tablero exclusivo para Calidad (aprobar/editar cantidades).
  * `view.board.suministros.gestion_compras`: Tablero exclusivo para Compras (ingresar costos y completar).
  * `view.board.suministros.catalogo`: Administrar productos (Activar/Inactivar/Crear).

## 5. Requerimiento Específico: Integración de Costos
* El área de **Compras** tendrá un formulario donde, por cada ítem aprobado por calidad, podrán ingresar el **Costo Unitario**.
* El sistema calculará `approved_quantity * unit_cost` para cada línea y un "Gran Total" para la solicitud.
* La **Jefe de Calidad** y **Compras** tendrán acceso visual a estos costos totales y unitarios en la vista de detalle de las solicitudes completadas.

## 6. Plan de Ejecución (Sprints/Pasos)

1. **Paso 1: Migraciones y Modelos.** Crear las 3 tablas y sus relaciones Eloquent.
2. **Paso 2: Seeder Inicial.** Cargar los 49 artículos enviados en la imagen al catálogo inicial `supply_products`.
3. **Paso 3: Rutas, Controladores y Permisos.** Registrar el módulo en la navegación y establecer permisos.
4. **Paso 4: Vistas - Catálogo.** CRUD básico para el administrador del catálogo.
5. **Paso 5: Vistas - Solicitante.** Interfaz para crear un pedido seleccionando productos.
6. **Paso 6: Vistas - Calidad.** Bandeja de entrada para revisar, modificar cantidades y aprobar.
7. **Paso 7: Vistas - Compras.** Bandeja para procesar pedidos, ingresar precios y cerrar.

---

## 7. Estado real implementado (actualizado)

### Permisos vigentes (`config/access.php`)
El modulo tiene **4 pestañas** activas. El control es granular por pestaña mas el tablero por area:

- `supply.tab.my_requests`: ver y crear solicitudes propias (area base del usuario).
- `supply.tab.quality`: bandejas de **Aprobacion Insumos** e **Insumos aprobados**; requiere `view.board.{module}.suministros`.
- `supply.tab.catalog`: administrar el catalogo; requiere `view.board.{module}.suministros`.
- `manage.supply.catalog`, `approve.supply.quality`: variantes "full" (con tablero visible).
- `view.board.{area}.suministros`: solo visualiza el tablero en sidebar; no sustituye permisos funcionales.

El acceso se resuelve en `SupplyAccessService`, `User::supplyBoardTabsFor()` y middleware `supply.tab:{tab}`.

### Flujo de estados
`pendiente_calidad` → `aprobada_calidad` | `rechazada_calidad`. Los estados `en_compras` y `completada` quedan obsoletos hasta el proceso de compra futuro.

### Rutas reales (`routes/modules/supplies.php`)
Prefijo `supplies/{module}`:

- `GET /mis-solicitudes` (`supplies.index`)
- `GET /solicitud/{supply_request}` (`supplies.show`)
- `GET|POST /solicitar` (`supplies.create` / `supplies.store`) — UI catalogo + carrito
- `GET /aprobacion-insumos` (`supplies.approval.index`)
- `GET /aprobacion-insumos/{supply_request}/editar` (`supplies.approval.edit`)
- `PATCH /aprobacion-insumos/{supply_request}` (`supplies.approval.update`)
- `GET /insumos-aprobados` (`supplies.approved.index`) — tabla de aprobadas con filtros
- `GET /insumos-aprobados/{supply_request}/exportar` (`supplies.approved.export`) — Excel FO-AD-44 por solicitud
- `GET|POST /catalogo` y `PATCH /catalogo/{product}` (catalogo)

### Items fuera de catalogo
En `supply_request_items`:

- `supply_product_id` nullable
- `custom_product_name` (string, nullable)
- `is_not_in_catalog` (boolean): marca productos agregados manualmente por el solicitante

### Campos adicionales en base de datos
- `current_inventory` (integer, default 0): inventario actual reportado por el solicitante.
- `purchasing_observations`, `unit_cost`, `total_cost`: reservados para el proceso de compra futuro.

### Sedes (`supply_sites`) y snapshot en solicitudes
Catálogo de sedes físicas con `name`, `utilization` (columna Utilización del Excel), `city` (columna Ubicación) e `is_active`.

- Seeder inicial: Cali (Sede Principal y central de monitoreo), Cartagena, Manizales.
- Cada usuario puede tener `sede_id` (FK nullable) asignada desde **Admin > Usuarios**.
- Desde el formulario de usuario, botón **Gestionar** abre un modal para crear, editar o eliminar sedes (`admin/supply-sites`).
- Al crear una solicitud (`store`), se copia snapshot: `sede_id`, `site_utilization`, `site_city` desde la sede del usuario. Sin sede asignada no puede solicitar.
- `exported_at` en `supply_requests`: marca solicitudes ya incluidas en un export FO-AD-44.

### Reporte Excel FO-AD-44 (por solicitud)
Desde el tablero **Insumos aprobados** (`supplies.approved.*`):

1. Listado cross-área de solicitudes `aprobada_calidad` con filtros (sede, fechas, estado de exportación, solicitante).
2. Cada fila permite **Descargar FO-AD-44** para esa solicitud.
3. El Excel incluye solo los ítems de la solicitud con `approved_quantity > 0`; une filas duplicadas por Descripción + Referencia dentro de la misma solicitud.
4. Genera `.xlsx` con formato FO-AD-44 (`config/supplies.php`, servicio `SupplyPurchaseReportExporter`).
5. Marca `exported_at` en la primera descarga (filtro “Pendientes”); las descargas posteriores siguen disponibles.

| Columna Excel | Fuente |
|---------------|--------|
| Cantidad | Suma `approved_quantity` tras merge |
| Insertar Foto del Artículo | `N/A` |
| Descripción | `product.name` o `custom_product_name` |
| Referencia | `product.description` o `N/A` si fuera de catálogo |
| Utilización | `site_utilization` (snapshot) |
| Ubicación | `site_city` (snapshot) |

### Reglas de autorizacion aplicadas
- `supplies.show`: solicitante duenio o quien tenga permiso de aprobacion (`supply.tab.quality`, `approve.supply.quality`, `manage.users`).
- `supplies.approval.*`: solo solicitudes `pendiente_calidad` del modulo activo.
- Bandejas filtran por `area_key` del modulo en la URL.

### Navegacion
- `User::defaultSupplyBoardUrl()` redirige a la primera pestaña autorizada (mis solicitudes, aprobacion insumos o catalogo).
- Pestañas internas: `mis_solicitudes`, `aprobacion_insumos`, `insumos_aprobados`, `catalogo`.

### Notificaciones
- Al crear una solicitud (`store`), se envia `SupplyRequestNotification` a usuarios con `supply.tab.quality` o `approve.supply.quality`.

### Pruebas
- `tests/Feature/SupplyModuleTest.php`: catálogo, creación (catálogo y custom), snapshot de sede, bloqueo sin sede, aprobación, tablero Insumos aprobados (filtros, export por solicitud, `exported_at`), rutas purchasing eliminadas, filtros por módulo, redirección y correo.

### Pendientes conocidos
- Proceso de compra con costeo (fase posterior).
- Estado `borrador` documentado pero no implementado.

---
*Documento vivo. Actualizar si cambian las reglas de negocio durante el desarrollo.*

## Referencias

- Guia de usuario: [`docs/user/suministros.md`](../user/suministros.md)
- Guia documentacion: [`docs/DOCUMENTATION.md`](../DOCUMENTATION.md)
