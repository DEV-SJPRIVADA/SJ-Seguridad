# FEAT-027 — Cartas de desvinculacion Word

## Objetivo

Generar bajo demanda paquetes de cartas Word (.docx) para desvinculacion por causal, empezando por **RENUNCIA** (3 documentos en ZIP), reemplazando placeholders `[VARIABLE]` como la macro Excel actual.

## Alcance v1

- Causal `RENUNCIA`: aceptacion, autorizacion examen retiro, certificado laboral.
- Descarga ZIP; regeneracion sobrescribe archivo en periodo laboral.
- Admin de plantillas en Catalogos (manage).
- Generacion/descarga con permiso `ficha_empleados.terminate`.

## Fuera de alcance

- Otras causales (estructura lista).
- PDF, correo, editor WYSIWYG, conversion Excel→Word automatica.

## Criterios de aceptacion

- [x] 3 plantillas subibles por causal RENUNCIA
- [x] Generar cartas produce ZIP con 3 docx
- [x] Placeholders `[NOMBRE]`, `[CEDULA]`, etc.
- [x] Audit log + tests PHPUnit
