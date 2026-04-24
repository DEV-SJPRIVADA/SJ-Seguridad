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

## Regla de mantenimiento

Si cambia el logo oficial de la empresa, la actualizacion debe hacerse primero en el componente `application-logo` para evitar inconsistencias entre pantallas.

## Riesgos

- Cambios directos en vistas individuales pueden duplicar branding y desalinear la identidad visual.
- Si en el futuro se agrega un archivo raster o vector oficial, este documento debe actualizarse para indicar su ubicacion y formato fuente.
