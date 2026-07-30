# Feature Brief — FEAT-017

## Objetivo

En area **Comercial**, unificar sidebar: un solo tablero **Gestion Clientes** con pestañas **Clientes** y **Servicios** (estilo requisiciones `.module-tab`). Sin cambios de estilos, rutas funcionales, controladores ni reglas de negocio.

## Alcance

- Reemplazar tableros sidebar `matriz_clientes` + `servicios_comerciales` por `gestion_clientes` => label **Gestion Clientes**.
- Pestañas: `clientes`, `servicios` (labels Clientes / Servicios).
- Subnav partial reutilizando clases existentes (`module-subnav`, `module-tab`) como [`resources/views/modules/requisitions/partials/subnav.blade.php`](../../resources/views/modules/requisitions/partials/subnav.blade.php).
- Rutas URL sin cambio: `comercial/clientes/*`, `comercial/servicios/*`.
- Permisos pestaña: conservar `view.board.comercial.matriz_clientes` y `view.board.comercial.servicios_comerciales` (+ `comercial.matriz.*`) para visibilidad por pestaña.
- Nuevo permiso sidebar: `view.board.comercial.gestion_clientes`; migracion/data: otorgar a quien tenia cualquiera de los dos tableros viejos.
- [`NavigationResolver`](../../app/Services/Navigation/NavigationResolver.php), [`routes/web.php`](../../routes/web.php) redirects, Admin UI comercial.
- Incluir subnav en vistas listado clientes/servicios (y opcional checklist si aplica al tab clientes).

## Fuera de alcance

- Cambios visuales en paneles/tablas/formularios.
- Cambios en dashboard KPIs links (siguen a mismas rutas).
- Renombrar permisos Spatie existentes de pestaña.

## Criterios aceptacion

- Sidebar Comercial muestra **Gestion Clientes** (no dos tableros separados).
- Usuario con solo permiso clientes ve pestaña Clientes; solo servicios ve Servicios; ambos ve dos pestañas.
- Rutas y acciones CRUD/export igual que antes.
- Tests navegacion actualizados o nuevos en CommercialMatrixTest.
