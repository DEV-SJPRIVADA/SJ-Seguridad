# Modulo Branding

## Objetivo

Centralizar la identidad visual base de `SJ StatFlow` (marca corporativa SJ Seguridad) para que cabecera, accesos y futuras vistas reutilicen la misma marca.

## Implementacion actual

- Componente reutilizable: [`resources/views/components/application-logo.blade.php`](c:/laragon/www/SJSEGURIDAD/resources/views/components/application-logo.blade.php)
- Uso en layout de invitados: [`resources/views/layouts/guest.blade.php`](c:/laragon/www/SJSEGURIDAD/resources/views/layouts/guest.blade.php)
- Uso en navegacion autenticada: [`resources/views/layouts/navigation.blade.php`](c:/laragon/www/SJSEGURIDAD/resources/views/layouts/navigation.blade.php)
- Pie de sidebar (desktop): [`resources/views/components/app-sidebar-footer.blade.php`](c:/laragon/www/SJSEGURIDAD/resources/views/components/app-sidebar-footer.blade.php) — nombre, rol, area asignada, cerrar sesion y logo compacto
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

## Controles de formulario (altura compacta)

Inputs, selects y botones secundarios (`btn--sm`) comparten altura con las pills de navegacion:

| Token | Valor | Uso |
| --- | --- | --- |
| `--control-height-chrome` | `2rem` (32px) | `.form-input`, `.form-select`, `.btn--sm`, filtros |
| `--control-padding-x` | `0.65rem` | Padding horizontal de controles |
| `--control-font-size` | `0.8125rem` | Texto en controles compactos |
| `--control-line-height` | `1.35` | Evita recorte de texto en altura fija |
| `--control-radius` | `12px` | Borde de inputs/selects |

Excepciones documentadas:

- `.form-textarea`: altura libre (multilinea).
- `.form-input--auth`: `52px` en login y pantallas de invitado.
- `.btn` (primario): `44px` para acciones principales.

No reintroducir `min-height` fijos (38px, 42px, etc.) en modulos; usar los tokens anteriores.

## Iconos (Blade Icons)

Toda la UI usa [Blade Icons](https://github.com/blade-ui-kit/blade-icons). No agregar SVG embebidos ni un componente local tipo `<x-lucide-icon name="...">`.

| Uso | Paquete / set | Ejemplo |
| --- | --- | --- |
| Iconos de interfaz | `mallardduck/blade-lucide-icons` | `<x-lucide-search width="18" height="18" aria-hidden="true" />` |
| Marca Excel | `robertboes/blade-selfhst-icons` | `<x-selfhst-microsoft-excel-2013 width="16" height="16" aria-hidden="true" />` |
| Iconos puntuales Remix (pendiente / reabrir) | set local `resources/svg/remix` (prefijo `ri`) | `<x-ri-pass-pending-fill width="24" height="24" aria-hidden="true" />` |

El set Remix local se registra en `AppServiceProvider`. Para un icono Lucide nuevo, usar el componente del paquete (`<x-lucide-{nombre} />`); no copiar paths SVG a Blade.

## Regla de mantenimiento

Si cambia el logo oficial de la empresa, la actualizacion debe hacerse primero en el componente `application-logo` para evitar inconsistencias entre pantallas.

## Riesgos

- Cambios directos en vistas individuales pueden duplicar branding y desalinear la identidad visual.
- Si en el futuro se agrega un archivo raster o vector oficial, este documento debe actualizarse para indicar su ubicacion y formato fuente.
