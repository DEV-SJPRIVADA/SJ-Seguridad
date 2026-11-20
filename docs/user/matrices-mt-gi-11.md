# MT-GI-11 Matrices — Guía de usuario

## ¿Para qué sirve?

Para **solicitar la estandarización de un documento** del sistema de gestión (procedimientos, formatos, instructivos, manuales…) usando el formato **MT-GI-11** de Calidad, y hacerle seguimiento hasta que quede estandarizado.

Está en **Calidad → MT-GI-11**, con tres pestañas:

- **Dashboard**: cuántas solicitudes hay y en qué estado.
- **Matriz**: el listado donde trabajas.
- **Catálogos**: procesos, tipos de documento, tipos de solicitud, cargos y estados (solo quien administra).

## ¿Qué veo y qué ve Calidad?

- **Ves tus propias solicitudes**: las que registraste tú.
- **Calidad ve todas** las solicitudes del tablero.
- Nadie más puede abrir, editar ni descargar los adjuntos de una solicitud ajena.

## Registrar una solicitud

1. Entra a **MT-GI-11 → Matriz**.
2. Pulsa el botón **+** (arriba a la derecha del listado).
3. Completa:
   - **Consecutivo**: el número de la solicitud (ej. `MT-GI-11-001`). Puedes repetirlo si después registras otra versión del mismo documento.
   - **Proceso** y **Tipo de documento**.
   - **Nombre del documento**.
   - **Tipo de solicitud** (creación, actualización, eliminación, estandarización…).
   - **Código actual** y **Versión actual** (los que tiene hoy el documento).
   - **Justificación**: explica por qué se solicita. Es obligatoria.
   - **Nombre y cargo del responsable del proceso**.
   - **Código de estandarización**, **fecha de estandarización** y **versión actualizada**: se pueden completar ahora o después.
   - **Estado**: por defecto queda en *Pendiente*.
4. Puedes **adjuntar archivos** (PDF, JPG o PNG, hasta 10 de 10 MB).
5. Pulsa **Guardar solicitud**.

> Todos los textos se guardan en **MAYÚSCULAS** automáticamente; escríbelos como quieras.

> La **fecha de solicitud no se pide**: el sistema la pone con el día en que registras la solicitud. Aparece como dato informativo en la parte superior del formulario.

## Consultar y editar

- En **Matriz** puedes filtrar por **fecha de solicitud, proceso, consecutivo, tipo de documento** y por **estado** (botones de colores).
- El **buscador de la tabla** busca en todos los campos, incluidos los nombres de los catálogos y de quien solicitó.
- Puedes elegir cuántos registros ver (10, 25, 50 o 100) y moverte entre páginas.
- Pulsa el **lápiz** de una fila para ver o editar la solicitud.

## Registrar una nueva versión

Si un documento ya tiene solicitud y necesitas una versión nueva:

1. En la fila de la solicitud original pulsa el botón **Nueva versión** (icono de copiar).
2. El formulario se abre con los datos ya cargados y el mismo consecutivo.
3. La **versión actualizada** viene sumada en uno.
4. Escribe la **justificación** de esta nueva versión y adjunta lo que corresponda.
5. Guarda.

La solicitud original no se toca: queda el historial de ambas.

## Adjuntos

- Se pueden **ver en el navegador** los PDF y las imágenes.
- Todos se pueden **descargar**.
- Los que ya no necesites se pueden eliminar (queda registrado en la auditoría).

## Cargar muchas solicitudes a la vez

En la barra de la tabla, el botón de **subir** abre el panel de *Masivos*:

1. **Descargar plantilla**.
2. Llena el Excel: la fila 3 en adelante son los datos. Los catálogos se escriben por **nombre** (ej. `FORMATO`, `GESTIÓN HUMANA`).
3. **Importar archivo**.

- Puedes repetir el consecutivo si son versiones del mismo documento.
- Si una fila tiene un error (un proceso que no existe, una fecha mal escrita, la justificación vacía), esa fila **no se guarda** y se te informa cuál y por qué; las demás sí se cargan.

## Exportar

El botón de **Excel** exporta exactamente lo que tienes filtrado. Incluye una columna **ID** al inicio: es el identificador único de cada solicitud (útil porque el consecutivo puede repetirse).

## Dashboard

- **Total** de solicitudes y un recuadro por **estado**, cada uno con su color.
- Dos gráficos: solicitudes **por proceso** y **por tipo de documento**.
- Al pulsar un recuadro de estado te lleva al listado ya filtrado.

## Catálogos (solo administradores)

Si administras los catálogos, en la pestaña **Catálogos** puedes crear, renombrar, activar/desactivar y eliminar valores de:

- Procesos
- Tipos de documento
- Tipos de solicitud
- Cargos del responsable
- Estados (aquí también eliges el **color**: ámbar, azul, verde, rojo o gris)

Un valor **inactivo** deja de aparecer en los formularios, pero no se borra. Un valor **en uso** no se puede eliminar: desactívalo.

## Preguntas frecuentes

**¿Por qué puedo repetir el consecutivo?**
Porque un mismo documento puede tener varias solicitudes (versiones). Lo que identifica cada fila es el **ID**, no el consecutivo.

**¿Por qué no puedo cambiar la fecha de solicitud?**
Porque el sistema la registra automáticamente el día en que haces la solicitud y no debe alterarse después. Al editar se conserva la fecha original.

**¿Puedo borrar una solicitud?**
Sí, con el permiso de edición y si es tuya. Se borran también sus archivos. Calidad puede borrar cualquiera.

**¿Quién cambia el estado a «Aprobada»?**
Quien tenga permiso de edición sobre esa solicitud (su autor o Calidad) desde el formulario.

**No veo el tablero MT-GI-11.**
Necesitas el permiso de tablero y al menos el de consulta. Pídelo al administrador.
