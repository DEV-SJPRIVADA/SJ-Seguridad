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
Los accesos NO usan el esquema `view.board.suministros.*` planteado en el diseño inicial. El control real es granular por pestaña mas el tablero por area:

- `supply.tab.my_requests`: ver y crear solicitudes propias.
- `supply.tab.quality`: tablero de revision de Calidad.
- `supply.tab.purchasing`: tablero de gestion de Compras.
- `supply.tab.catalog`: administrar el catalogo.
- `manage.supply.catalog`, `approve.supply.quality`, `manage.supply.purchasing`: variantes "full".
- `view.board.{area}.suministros`: habilita la pestaña de suministros del area en la navegacion.

El acceso a cada pestaña se resuelve en `User::supplyBoardTabsFor()` y `HasSupplyTabs`.

### Rutas reales (`routes/modules/supplies.php`)
Prefijo `supplies/{module}`:

- `GET /mis-solicitudes` (`supplies.index`)
- `GET /solicitud/{supply_request}` (`supplies.show`)
- `GET|POST /solicitar` (`supplies.create` / `supplies.store`)
- `GET /revision-calidad` (`supplies.quality.index`)
- `GET /revision-calidad/{supply_request}/editar` (`supplies.quality.edit`)
- `PATCH /revision-calidad/{supply_request}` (`supplies.quality.update`)
- `GET /gestion-compras` (`supplies.purchasing.index`)
- `GET /gestion-compras/{supply_request}/costear` (`supplies.purchasing.edit`)
- `PATCH /gestion-compras/{supply_request}` (`supplies.purchasing.update`)
- `GET|POST /catalogo` y `PATCH /catalogo/{product}` (catalogo)

### Campos adicionales en base de datos
Ademas de lo descrito en la seccion 3, `supply_request_items` incluye:

- `current_inventory` (integer, default 0): inventario actual reportado por el solicitante.
- `purchasing_observations` (text, nullable): notas de Compras por linea.

### Seeder
`SupplyProductSeeder` carga el catalogo inicial (aseo y cafeteria) de forma idempotente (`firstOrCreate` por `name`) y esta registrado en `DatabaseSeeder`.

### Reglas de autorizacion aplicadas
- `supplies.show`: solo el solicitante duenio o perfiles de revision (Calidad, Compras, `manage.users`, super-admin) pueden ver el detalle. Ver `SupplyRequestController::authorizeSupplyView()`.
- `quality.update`: solo procesa solicitudes en estado `pendiente_calidad`.
- `purchasing.edit` / `purchasing.update`: solo operan sobre estados `aprobada_calidad` o `en_compras`.

### Pruebas
- `tests/Feature/SupplyModuleTest.php` cubre: catalogo sembrado, creacion de solicitud, visibilidad por propiedad, acceso de revisor, aprobacion de calidad, bloqueo de reproceso y cierre de compras con calculo de total.

### Pendientes conocidos
- Estado `borrador` documentado pero no implementado.
- `qualityIndex` lista todas las solicitudes sin filtro por area.
- No hay notificaciones por correo (a diferencia de requisiciones).
- Falta `defaultSupplyBoardUrl()` equivalente al de requisiciones.

---
*Documento vivo. Actualizar si cambian las reglas de negocio durante el desarrollo.*
