# Inasistencias — Guía de usuario

## Objetivo

Generar cartas de inasistencia desde Gestión Humana y consultar el histórico de lo generado (sin guardar el archivo Word).

## Alcance

Tablero **Inasistencias** (Gestión Humana), pestañas:

- **Generar** (permiso Generar): grilla, Excel, descarga Word/ZIP.
- **Histórico** (permiso Ver): listado y export Excel de registros.

## Definiciones

| Término | Significado |
| --- | --- |
| Fecha ini aus | Fecha de inicio de la ausencia (la escribe el usuario). |
| Días | Días inclusivos desde la fecha ini aus hasta el día de generación (ej. falta el 12 y se registra el 14 → 3 días). |
| Histórico | Registro de cada carta generada (cédula, datos, firma, fechas); no incluye el `.docx`. |

## Responsabilidades

| Perfil | Acción |
| --- | --- |
| Ver | Consultar Histórico y exportar. |
| Generar | Todo lo de Ver + armar grilla y generar cartas. |
| Administrador | Asignar permisos del tablero Inasistencias. |

## Desarrollo

### Generar una carta

1. Entre a **Inasistencias → Generar**.
2. Escriba la **cédula** y salga del campo: el sistema completa **nombre**, **cargo** y **ciudad** desde la ficha.
3. Indique **Fecha ini aus**. Los **Días** se calculan solos.
4. Elija la **Firma** (catálogo).
5. Pulse el icono de descargar para generar. Se descarga el Word (o ZIP si hay varias filas) y el registro queda en **Histórico**.

### Requisitos de plantilla

En **Plantillas Word** debe existir **exactamente una** plantilla del tipo **Inasistencias** con archivo. Variables útiles: `${CEDULA}`, `${NOMBRE}`, `${CARGO}`, `${CIUDAD}`, `${FECHA_INI_AUS}`, `${DIAS}`, `${FIRMA}`, `${CARGO_FIRMA}`, `${FECHA}`.

### Histórico

1. Abra **Histórico**.
2. Filtre por cédula, firma o rango de fecha de generación.
3. Exporte a Excel con el icono correspondiente.

## Control de cambios

| Versión | Fecha | Autor | Descripción |
| --- | --- | --- | --- |
| 1.0 | 2026-10-14 | Agent | Tablero Inasistencias: Generar + Histórico, permisos view/edit, variables Word. |
