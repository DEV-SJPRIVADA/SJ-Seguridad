# Documentos de Calidad — Guia de usuario

## Objetivo

Consultar documentos del sistema de gestion de calidad, recibir documentos asignados personalmente y — para el equipo de Calidad — publicar y administrar la biblioteca documental.

## Alcance

El tablero **Documentos** aparece al final de los tableros de **su area asignada** para todos los usuarios con area base. Quien administra documentos tambien lo ve en **Calidad**. Dentro del tablero:

- **Biblioteca:** consultar documentos activos asignados a su area (sin permiso adicional).
- **Mis documentos:** visible cuando tiene documentos activos asignados directamente a su usuario.
- **Administrar:** crear, editar, activar/inactivar documentos (solo Calidad; requiere permiso `manage.quality.documents` asignado en Admin usuarios).

Tipos de recurso: archivo (Word/Excel) o enlace externo.

## Definiciones

| Termino | Significado |
| --- | --- |
| Biblioteca | Listado de documentos visibles para su area actual. |
| Mis documentos | Documentos asignados a usted como persona, no a toda un area. |
| Codigo documental | Identificador del documento en el sistema de calidad. |
| Proceso | Clasificacion del documento segun proceso de calidad (SST, gestion documental, etc.). |
| Estado del documento | Elaboracion, revision o aprobado (ciclo documental). |
| Activo / inactivo | Si el documento es visible para consulta en biblioteca. |
| Tipo de almacenamiento | Digital, impreso o ambos (metadata, no el tipo de archivo). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Usuario de cualquier area | Consultar biblioteca de su area; abrir mis documentos si aplica. |
| Directora / equipo Calidad con gestion documental | Publicar documentos, asignar areas y usuarios, mantener metadata. |
| Administrador del sistema | Otorgar permiso de gestion documental en Admin usuarios. |

## Desarrollo

### Consultar la biblioteca de mi area

1. Seleccione su area en el menu principal si aplica.
2. Abra el tablero **Documentos**.
3. Entre a **Biblioteca**.
4. Busque el documento y use **Descargar** o **Abrir enlace** segun el tipo.

### Ver mis documentos asignados

1. En el tablero Documentos, abra **Mis documentos** (visible si tiene asignaciones directas).
2. Descargue o abra el recurso.

### Publicar un documento (Calidad)

1. Entre al area **Calidad** y abra Documentos → **Administrar**.
2. Pulse **Crear documento**.
3. Complete codigo, nombre, proceso, tipo, origen, estados y version.
4. Suba archivo o indique URL externa.
5. Marque las **areas** y/o **usuarios** que pueden verlo.
6. Guarde. El documento activo aparecera en las bibliotecas correspondientes.

### Editar o inactivar

1. En Administrar, localice el documento.
2. Edite metadata o reemplace archivo/enlace.
3. Use **Inactivar** para ocultarlo sin borrar el registro.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
| 1.1 | 2026-08-03 | Navegacion canonica | Documentos en area asignada y Calidad (no repetido en todas las areas) |
