# Plantillas Word — Guia de usuario

> Documentacion operativa para usuarios finales. Ubicacion: `docs/user/plantillas-word.md`.
> **Orden obligatorio de secciones.** Uso de pantallas (tablero de administracion).

## Objetivo

Permitir a Gestion Humana administrar en un solo lugar los **tipos de documento** y las **plantillas Word** (.docx) que luego se usan al generar cartas de desvinculacion desde la ficha del empleado.

## Alcance

Aplica al tablero **Plantillas Word** del area **Gestion Humana** (menu lateral). Segun su perfil, el usuario puede:

- Ver tipos de documento y la lista de plantillas.
- Crear, editar o desactivar tipos; agregar, reemplazar o eliminar plantillas; descargar la plantilla maestra.

**No incluye:** generar o descargar cartas de un empleado (eso se hace en **Ficha empleados**, con permiso de desvinculacion). Tampoco hay editor de Word dentro de la aplicacion: se sube un archivo ya preparado.

**Importante tras la actualizacion:** las plantillas antiguas del paquete de Renuncia **no** se migraron automaticamente. Hay que **volver a subirlas** en este tablero, asociadas al tipo **Desvinculacion**.

## Definiciones

| Termino | Significado |
| --- | --- |
| Tipo de documento | Categoria del catalogo (por ejemplo **Desvinculacion**) que clasifica las plantillas. |
| Plantilla Word | Archivo `.docx` con variables entre corchetes (ej. `[NOMBRE]`, `[CEDULA]`) que el sistema rellena al generar una carta. |
| Reemplazar plantilla | Cambiar solo el archivo Word; la etiqueta y el tipo se mantienen. |
| Plantilla maestra | El archivo original guardado en el tablero (no la carta ya generada para un empleado). |

## Responsabilidades

| Rol / perfil | Responsabilidad en este modulo |
| --- | --- |
| Administrador de plantillas (permiso de administrar Plantillas Word) | Mantener tipos y subir/reemplazar/eliminar plantillas; re-subir las de desvinculacion tras el cambio de sistema. |
| Consulta de plantillas (solo ver) | Revisar el listado y descargar plantillas maestras si lo necesita. |
| Operador de desvinculacion (Ficha empleados) | No administra este tablero; genera y descarga cartas desde la ficha del empleado. |
| Administrador de usuarios | Asignar el tablero y los permisos de ver/administrar Plantillas Word a quien corresponda. |

## Desarrollo

### Abrir el tablero

1. En el area **Gestion Humana**, pulse **Plantillas Word** en el menu lateral (solo visible si tiene permiso de tablero).
2. Vera las pestanas **Tipos de documento** y **Plantillas** (una tabla por pestana).
3. Use **Tipos de documento** para el catalogo de tipos; use **Plantillas** para subir o reemplazar archivos Word.

### Administrar tipos de documento

1. Para **agregar** un tipo: complete codigo, nombre, orden y estado activo; confirme.
2. Para **editar**: cambie nombre, orden o activo segun necesite. Evite cambiar el codigo del tipo **Desvinculacion**: si se altera, las cartas en ficha pueden dejar de encontrar plantillas.
3. Para **eliminar**: solo si el tipo no tiene plantillas asociadas. Si ya tiene plantillas, **desactívelo** en lugar de borrarlo.

El sistema trae de fabrica el tipo **Desvinculacion**, necesario para las cartas al desvincular.

### Agregar una plantilla

1. En el bloque **Plantillas**, indique la **etiqueta** (nombre visible), elija el **tipo** (activo) y seleccione un archivo **.docx**.
2. Confirme. La plantilla aparece en la lista con su tipo.
3. Para cartas de retiro, use el tipo **Desvinculacion** y variables en corchetes (`[NOMBRE]`, `[CEDULA]`, `[FECHA_TERMINACION]`, etc.; la pantalla muestra la lista de apoyo).

### Reemplazar o eliminar una plantilla

1. **Reemplazar:** elija solo el nuevo archivo `.docx` de esa fila. La etiqueta y el tipo no cambian.
2. **Eliminar:** confirme cuando el sistema lo pida. Se quita la plantilla del listado (ya no aparecera al generar cartas).
3. **Descargar:** obtiene la plantilla maestra guardada (sin datos de un empleado).

### Relacion con Ficha empleados

1. Tras subir al menos una plantilla de tipo **Desvinculacion**, un usuario con permiso de desvinculacion puede, en la ficha de un empleado ya desvinculado, pulsar **Generar cartas**, elegir una o varias plantillas y descargar el resultado.
2. El detalle de ese flujo esta en la guia de usuario de **Ficha empleados**.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-08-21 | FEAT-029 | Version inicial: tablero Plantillas Word (tipos + plantillas); re-subida obligatoria de plantillas de Renuncia; permisos propios del tablero. |
| 1.1 | 2026-08-21 | UI | Pestanas **Tipos de documento** y **Plantillas** (una tabla por pestana). |
