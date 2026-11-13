# Firma manuscrita — Guía de usuario

> Documentación operativa para usuarios finales. Ubicación: `docs/user/user-signatures.md`.

## Objetivo

Permitir que cada persona suba la imagen de su firma para que, cuando un documento quede autorizado, esa firma aparezca impresa (por ejemplo en la orden de compra).

## Alcance

- Subir, reemplazar o quitar **su** firma desde **Mi perfil**
- Formato: archivo **PNG** (se recomienda fondo transparente)
- En la orden de compra la firma se imprime **solo después de autorizar** la orden
- En la requisición impresa (FO-GH-22): líder = quien solicitó; Dirección de Gestión Humana = quien la gestiona en GH; Gerencia = el usuario `gerencia@sjsp.com.co` cuando ya autorizó un cargo nuevo

Queda fuera de esta entrega: que un administrador suba la firma de otra persona, y el uso en Excel u otros formatos distintos del PDF de la orden de compra.

## Definiciones

| Término | Significado |
| --- | --- |
| Firma manuscrita | Imagen PNG de la firma escrita a mano, no un certificado digital. |
| Snapshot | Copia de la firma que el sistema guarda en el momento de autorizar el documento. Si usted cambia su firma después, los documentos ya autorizados no cambian. |

## Responsabilidades

| Rol / perfil | Responsabilidad en este módulo |
| --- | --- |
| Cualquier usuario | Subir su PNG en Mi perfil si debe firmar documentos (analista de Compras, directores, gerente). |
| Gerente (correo `gerencia@sjsp.com.co`) | Subir su firma; sale en la casilla **Gerente (cuando aplique)** de la orden autorizada. |
| Administrador | No carga firmas ajenas en esta versión. |

## Desarrollo

### Subir o cambiar su firma

1. Inicie sesión y abra **Mi perfil** (su nombre, al pie del menú).
2. En **Firma manuscrita**, elija un archivo PNG (máximo 2 MB).
3. Pulse el icono de guardar. Verá la vista previa.
4. Para reemplazarla, suba otro PNG y vuelva a guardar.

### Quitar su firma

1. En la misma sección, pulse el icono de papelera y confirme.
2. Las órdenes de compra **ya autorizadas** conservan la firma de ese momento.

### Cómo aparece en la orden de compra

### Cómo aparece en la requisición impresa

Al imprimir desde **Gestión** (FO-GH-22):

1. **Firma autorización Líder:** quien creó la solicitud (si subió PNG).
2. **Aprobación Gerencia:** el usuario con correo `gerencia@sjsp.com.co`, **solo** si gerencia ya autorizó un cargo nuevo. En otros motivos la casilla queda en blanco.
3. **Dirección de Gestión Humana Recibió:** quien guarda o pasa la requisición a En gestión en GH. Si nadie de GH la ha tomado, queda en blanco.

La plantilla vacía no lleva firmas.

### Cómo aparece en la orden de compra

Cuando el director administrativo autoriza la orden, el PDF FO-AD-07 pinta (si hay PNG) las firmas de:

1. Quien elaboró la orden (Analista de Compras)
2. El director del área que autorizó la solicitud (o quien revisó en Calidad, en un suministro)
3. Quien autorizó la orden (Director Admon. y Financiero)
4. El gerente de la empresa (usuario con correo `gerencia@sjsp.com.co`)

Si alguien no ha subido su PNG, esa casilla queda en blanco y solo se ve el nombre (si aplica).

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.2 | 2026-11-13 | Compras / Requisiciones | Firmas un poco más grandes en el PDF de la orden y en la impresión FO-GH-22 |
| 1.1 | 2026-11-13 | Requisiciones | FO-GH-22: firmas de solicitante, GH y gerencia (`gerencia@sjsp.com.co`) |
| 1.0 | 2026-11-13 | Compras / Perfil | Alta: firma PNG en Mi perfil y snapshot en el PDF de la orden de compra al autorizar |
