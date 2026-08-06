# Archivo — Guia de usuario

## Objetivo

Registrar donde se guarda fisicamente el expediente de cada empleado (estante y caja) en el area de archivo de Gestion Humana.

## Alcance

- Tablero **Archivo** en Gestion Humana.
- Solo empleados que ya estan **En ficha**.
- No modifica la carga masiva de empleados (nómina).

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Archivo — consulta | Ver listado y ubicaciones registradas |
| Archivo — gestion | Editar estantes y cajas |
| Ficha empleados (view) | Exportar Excel con datos de empleados + columnas de archivo |

## Desarrollo

### Consultar ubicaciones

1. Entre al area **Gestion Humana** → **Archivo**.
2. Use la busqueda por cedula, nombre o codigo de requisicion.
3. Revise columnas **Estantes** y **Cajas**.

### Registrar o corregir ubicacion

1. En el listado, pulse **Editar** en la fila del empleado.
2. Complete **Estantes** y **Cajas** (ej. `A-03`, `Caja 12`).
3. Guarde. El empleado permanece en ficha; solo se actualiza su ubicacion documental.

### Importar ubicaciones masivamente

1. En **Archivo**, pulse **Importar** (o use el modal).
2. Descargue **Exportar archivo** si aun no tiene la plantilla con datos.
3. Edite columnas **Estantes** y **Cajas** (cedula obligatoria por fila).
4. Suba el `.xlsx` y confirme.
5. Revise el resumen; si hubo filas omitidas, descargue el reporte de errores.

La reimportacion **no modifica** datos de nomina ni otros campos del empleado.

### Exportar Excel para archivo (desde Ficha empleados)

1. Abra **Ficha empleados → Empleados** (pestaña En ficha).
2. Aplique filtros si necesita (busqueda, estado, fechas en el modal masivos).
3. Pulse **Exportar archivo**.
4. El archivo incluye todas las columnas de «Exportar datos para actualizar» **mas** **Estantes** y **Cajas** al final.

Use ese Excel para trabajo offline del area de archivo. Complete estantes/cajas y reimporte desde **Archivo → Importar**.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-08-06 | Modulo Archivo | Campos estantes/cajas, tablero Archivo, export dedicado |
| 1.1 | 2026-08-06 | Import archivo | Carga masiva de estantes/cajas separada del masivo de nomina |
