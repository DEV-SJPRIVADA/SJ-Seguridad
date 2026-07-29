# Modulo Branding

## Objetivo

Centralizar la identidad visual base de `SJ Seguridad` para que cabecera, accesos y futuras vistas reutilicen la misma marca.

## Implementacion actual

- Componente reutilizable: [`resources/views/components/application-logo.blade.php`](c:/laragon/www/SJSEGURIDAD/resources/views/components/application-logo.blade.php)
- Uso en layout de invitados: [`resources/views/layouts/guest.blade.php`](c:/laragon/www/SJSEGURIDAD/resources/views/layouts/guest.blade.php)
- Uso en navegacion autenticada: [`resources/views/layouts/navigation.blade.php`](c:/laragon/www/SJSEGURIDAD/resources/views/layouts/navigation.blade.php)
- Tokens globales de color: [`resources/css/app.css`](c:/laragon/www/SJSEGURIDAD/resources/css/app.css)

## Paleta corporativa base

Extraida visualmente de la imagen corporativa compartida por el usuario y tomada como referencia oficial inicial:

- `--brand-navy`: `#20214f`
- `--brand-blue`: `#1984c7`
- `--brand-blue-soft`: `#dceffc`
- `--brand-blue-pale`: `#eef7fd`
- `--brand-white`: `#ffffff`
- `--brand-silver`: `#d9d9d9`
- `--brand-steel`: `#9ca3af`
- `--brand-ink`: `#10233f`

## Regla obligatoria de uso

- Toda nueva vista, componente o modulo debe usar primero los tokens `--brand-*` o sus alias `--color-*` definidos en `resources/css/app.css`.
- No introducir hexadecimales nuevos en vistas o componentes si el mismo resultado puede lograrse con la paleta corporativa.
- Si una nueva pantalla necesita una variacion adicional, primero debe incorporarse como token reutilizable y documentarse en este archivo.
- La tabla de permisos, navegacion, botones principales y elementos de marca deben priorizar `--brand-navy` y `--brand-blue` como colores base.

## Formato de fechas en tablas

- **Visualizacion** en tablas, listados DataTables y exportaciones Excel orientadas al usuario: **`dd/mm/yy`** (`App\Support\DisplayDate`, componente Blade `<x-date-table />`).
- **Formularios** (`input type="date"`) y persistencia/API siguen en **ISO `Y-m-d`**.
- Con hora en columna de tabla: **`dd/mm/yy HH:mm`** (`<x-date-table datetime />` o `DisplayDate::dateTime()`).

## Estandar de navegacion (areas, tableros, pestanas)

Los tres niveles de navegacion autenticada usan **el mismo look de pill**:

| Nivel | Clase | Contenedor |
| --- | --- | --- |
| Areas / Procesos (sidebar) | `.sidebar-link` | `.app-sidebar__nav` |
| Tableros del area | `.module-tab` | `.module-strip__inner` |
| Pestanas del modulo | `.module-tab` | `.module-subnav__inner` / `.requisition-subtabs__inner` |

- Estilos compartidos en `resources/css/app.css` (selectores `.sidebar-link, .module-tab`).
- Padding entre botones y contenedor de franja: **`0.2rem`**.
- No crear otra familia visual para tableros/subnavs; reutilizar estas clases.
- Regla Cursor: [`.cursor/rules/nav-chrome-ui.mdc`](../../.cursor/rules/nav-chrome-ui.mdc).

## Regla de mantenimiento

Si cambia el logo oficial de la empresa, la actualizacion debe hacerse primero en el componente `application-logo` para evitar inconsistencias entre pantallas.

## Riesgos

- Cambios directos en vistas individuales pueden duplicar branding y desalinear la identidad visual.
- Si en el futuro se agrega un archivo raster o vector oficial, este documento debe actualizarse para indicar su ubicacion y formato fuente.
