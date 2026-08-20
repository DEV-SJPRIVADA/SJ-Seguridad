# GUÍA DE USUARIO

## MÓDULO DE REQUISICIONES DE PERSONAL

## Tabla de contenido

[INTRODUCCIÓN [2](#introducción)](#introducción)

[OBJETIVO [2](#objetivo)](#objetivo)

[ALCANCE [2](#alcance)](#alcance)

[DEFINICIONES [3](#definiciones)](#definiciones)

[RESPONSABILIDADES [4](#responsabilidades)](#responsabilidades)

[DESARROLLO [5](#desarrollo)](#desarrollo)

[Solicitar una requisición
[5](#solicitar-una-requisición)](#solicitar-una-requisición)

[Consultar mis requisiciones
[8](#consultar-mis-requisiciones)](#consultar-mis-requisiciones)

[Consultar el dashboard (Gestión Humana)
[8](#consultar-el-dashboard-gestión-humana)](#consultar-el-dashboard-gestión-humana)

[Gestionar requisiciones (Gestión Humana)
[8](#gestionar-requisiciones-gestión-humana)](#gestionar-requisiciones-gestión-humana)

[Administrar parámetros
[10](#administrar-parámetros)](#administrar-parámetros)

[Notificaciones [11](#notificaciones)](#notificaciones)

[Autorizar requisiciones cargo nuevo (Gerencia)
[11](#autorizar-requisiciones-cargo-nuevo-gerencia)](#autorizar-requisiciones-cargo-nuevo-gerencia)

[Activar encargados de selección (solo Gestión humana)
[11](#activar-encargados-de-selección-solo-gestión-humana)](#activar-encargados-de-selección-solo-gestión-humana)

[Control de cambios [12](#control-de-cambios)](#control-de-cambios)

# REQUISICIONES DE PERSONAL — GUÍA DE USUARIO

## INTRODUCCIÓN

Esta guía de usuario describe el funcionamiento del módulo de
Requisiciones de Personal, herramienta diseñada para facilitar la
solicitud, seguimiento, gestión y cierre de procesos de contratación
dentro de la organización. A través de este documento, los usuarios
conocerán las rutas de acceso, responsabilidades, estados del proceso,
campos requeridos y acciones disponibles según su perfil, con el fin de
asegurar un uso adecuado, oportuno y estandarizado del sistema.

## OBJETIVO

Permitir solicitar, dar seguimiento y gestionar la contratación de
personal por área, desde la necesidad inicial hasta el cierre por
Gestión Humana, incluyendo la captura de la Estructura del servicio
(horarios, descansos y condiciones del puesto).

## ALCANCE

Aplica al tablero Requisiciones según su perfil y el área visible en el
menú lateral:

| **Perfil**                                     | **Donde entrar**                                       |
|------------------------------------------------|--------------------------------------------------------|
| Solicitante de área                            | Su área (ej. Operaciones) → Requisiciones              |
| Gestión Humana (gestión, dashboard, Catálogos) | Gestión humana → Requisiciones                         |
| Gerencia / administrador                       | Gestión humana → Requisiciones → Autorización gerencia |

El usuario puede, según su perfil:

- Solicitar nuevas requisiciones en su área, con el campo obligatorio
  Estructura del servicio

- Consultar Mis requisiciones (seguimiento de lo solicitado) y exportar
  a Excel con el detalle completo de la solicitud

- Ver el Dashboard consolidado (todas las áreas) con KPIs: Total,
  Solicitadas, En gestión, Contratadas y Canceladas, y gráficos
  interactivos unificados (ApexCharts)

- Gestionar solicitudes de todas las áreas (solo Gestión Humana),
  incluyendo editar la Estructura del servicio

- Administrar Parámetros (catálogos: cargos, motivos, ciudades, correos
  de notificación, etc.)

- En Gestión humana → Parámetros, activar encargados de selección
  (usuarios del área) para que aparezcan como Reclutador al gestionar
  solicitudes

## DEFINICIONES

| **Término**                         | **Significado**                                                                                                                      |
|-------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------|
| Requisición                         | Solicitud de contratación de una o más personas para un cargo.                                                                       |
| Área solicitante                    | Departamento del usuario que crea la solicitud.                                                                                      |
| Estructura del servicio             | Descripción de horarios, descansos y condiciones del puesto a tener en cuenta al cubrir la vacante.                                  |
| Estado solicitado                   | Requisición recién creada, pendiente de acción de GH.                                                                                |
| Estado Aprobada                     | Directora de Gestión Humana aprueba la requisición.                                                                                  |
| Estado en gestión                   | Coordinadora de Gestión Humana está trabajando la solicitud.                                                                         |
| Estado contratado                   | Proceso cerrado con contratación exitosa.                                                                                            |
| Estado cancelado                    | Solicitud descartada.                                                                                                                |
| Gestión Humana (GH)                 | Equipo que valida, completa datos de compensación y cierra procesos.                                                                 |
| Encargado de selección / Reclutador | Persona de GH responsable del proceso de selección en una requisición; se elige en Gestión desde usuarios habilitados en Parámetros. |
| Cliente                             | Empresa o entidad para la cual se solicita el personal (desde matriz comercial).                                                     |

## RESPONSABILIDADES

- **Director o jefe de área:**  Crear requisiciones de personal y
  realizar seguimiento al proceso.

- **Coordinadora de Gestión Humana:**   Gestionar todas las
  requisiciones, revisar o corregir Estructura del servicio; completar
  compensación; cambia el **estado a “En Gestión”**.

**Director de Gestión Humana:** Revisa y valida la requisición y cambia
de **estado a “Aprobado”** para iniciar proceso de selección. Tiene
permisos para modificar Catálogos

- **Analista o Asistente de selección:** realiza proceso de búsqueda y
  selección, cambia **agrega fecha de contratación** y cambia **estado a
  “Contratado”**.

- **Gerencia:**    Autorizar requisiciones Cargo nuevo desde su correo
  electrónico el cual conecta con Gestión humana → Requisiciones →
  Autorización gerencia.

## DESARROLLO

### Solicitar una requisición

1.  Ingrese al explorador Google Chrome y digite en la barra de
    direcciones 172.16.16.130

2.  Seleccione el botón Iniciar Sesión

3.  Ingrese su correo corporativo y contraseña; estos llegaran a su
    correo electrónico para el inicio por primera vez)

4.  Seleccione su proceso en el panel lateral izquierdo

5.  Entre al tablero Requisiciones de su área.

6.  Abra la pestaña Solicitar.

    ![Captura de pantalla del formulario de solicitud](assets/image1.png)

7.  Complete las secciones del formulario:

- **Motivo**: se selecciona el porque se realiza la requisición

  - Cargo Nuevo: Cuando se crea un cargo que no existe en la compañía,
    este debe ser autorizado por la Gerencia.

  - Movimiento Interno: Cuando se cambia de un puesto a otro o se
    asciende al empleado y se requiere su remplazo

  - Remplazo: se aplica cuando un empleado se desvincula de la empresa

  - Servicio Nuevo: Se aplica cuando se vende un nuevo servicio ya sea
    cliente nuevo o no.

- **Cedula a quien reemplaza: documento de identidad de la persona que
  se desvincula o se traslada.**

- **Nombre completo a quien reemplaza:** Recomendado un estándar Primero
  apellidos, luego nombres.

- **Cargo**: Se selecciona el cargo que se va a cubrir.

- **Género**: Selecciona Masculino, Femenino o indiferente cuando no
  afecta el género en el puesto.

- **Área operativa**: Es el proceso al que va a pertenecer el empleado
  requerido; Gerencia, Gestión humana, operaciones, Plan. y
  Programación, Comercial, Calidad, Admin y Financiero, Jurídico,
  Compras y Tic.

- **Tipo de cliente:**

  - **Administrativos:** Personal de Oficinas

  - **Externo:** Personal Operativo de clientes Externo

  - **Grupo:** Personal Operativo de Clientes Grupo

  - **Operativos Sj:** Personal Operativo que se carga al centro de
    costo de SjSeguridad (Disponibles, Vacaciones, Supervisores)

- **Ciudad:** Seleccione la ciudad donde el empleado va a laborar.

- **Tipo de programación:** Es la jornada ej:(4x2,5x2)

- **Perfil requerido**: Indica especificaciones requeridas por el
  cliente o necesarias para el puesto Ej: ( Edad, Conocimientos,
  Educación, Vivienda, trasporte, Experiencia etc..)

- **Dotación requerida:** Selecciona Administrativo, Bono, Escolta, Gala
  u Overol de a cuerdo a la necesidad del cargo o puesto.

- **Estructura del servicio:** Indica Horarios, cantidad de descansos
  promedio por mes y condiciones especiales del puesto de trabajo.

- **Centro de costo:** hace referencia a los centros de costos asignados
  en el ERP Control Roll. Está compuesto por tres partes separadas por
  guion. Ejemplo: 86-2-4, donde 86 hace referencia a SjSeguridad, 2 a la
  ciudad de Cali y 4 a Gestión Humana. Los ID de cada ciudad y proceso
  se describen a continuación:

  **Ciudades**

- 2 CALI

- 3 PALMIRA

- 5 MANIZALES

- 9 CARTAGENA

- 10 CHOCO

- 15 BOGOTA

- 16 ARMENIA

- 24 MONTERIA

- 98 PEREIRA

  **Procesos**

- 1 OPERACIONES

- 4 PROGRAMACION

- 3 ADMINISTRATIVO Y FINANCIERO

- 4 GESTION HUMANA

- 5 JURIDICO / TIC

- 6 GERENCIA

- 7 COMERCIAL

- **Observaciones del solicitante:** En este campo es opcional para que
  el solicitante en casos especiales pueda describir su necesidad,
  plazos, urgencia, especificar el movimiento de un traslado,
  especificación de bolsas o información que requiera gestión humana que
  no se ocupe en los campos anteriores.

  Todos los campos son obligatorios a excepción de observaciones del
  solicitante

8.  Si el motivo es Cargo nuevo o Servicio nuevo, indique la cantidad de
    personas; en otros motivos el sistema registra una persona por
    solicitud.

9.  Si el motivo es Reemplazo o Movimiento interno, complete cedula y
    nombre completo de la persona involucrada.

10. Seleccione Cliente buscando en la matriz comercial (mínimo 2
    caracteres), salvo tipo de cliente Interno.

11. Revise el checklist lateral y envíe la solicitud.

12. Recibirá notificación por correo cuando GH cambie el estado.

### Consultar mis requisiciones

13. Abra pestaña **Mis requisiciones**

14. Use filtros de búsqueda, estado, cliente o ciudad.

15. Opcional: indique Fecha inicio y Fecha fin (fecha de solicitud) y
    pulse Buscar para acotar la lista.

16. Exporte a Excel si tiene la opción disponible; el archivo trae todos
    los campos de la requisición según los filtros activos.

### Consultar el dashboard (Gestión Humana)

17. Abra la pestaña Dashboard del tablero Requisiciones.

18. Revise los KPIs (Total, Solicitadas, En gestión, Contratadas,
    Canceladas).

19. Use los filtros disponibles; la pantalla se actualiza al cambiarlos.

20. Revise los gráficos de tendencia, estado, ciudad y cliente.

### Gestionar requisiciones (Gestión Humana)

21. Al correo electrónico de los implicados en el proceso de selección
    llegara la notificación con los datos de la requisición.

22. Abra la pestaña Gestión.

23. Por defecto el listado muestra requisiciones En curso (sin
    Contratado ni Cancelada). Use el filtro de estados **Todos** para
    ver todos los estados, los demás para filtrar uno en concreto, o
    búsqueda y rango de Fecha inicio / Fecha fin; pulse Buscar.

    ![Captura de pantalla de gestión de requisiciones](assets/image2.png)

24. Seleccione el botón abrir de la requisición a iniciar proceso
    complete datos de compensación y contrato, cambie el estado a **En
    Gestión** y guarde cambios.

    **La responsable de este paso es el coordinador de Gestión Humana y
    debe notificar al director de Gestión Humana para su aprobación.**

    Datos de Compensación:

- Tipo de contrato:

  - Aprendizaje

  - Fijo

  - Indefinido

  - Obra o Labor

- **Duración del contrato:** define a cuantos meses se contrata el
  empleado.

- **Valor salario base:** define el salario que recibirá mensualmente el
  empleado

- **Auxilio de transporte:** define el auxilio de trasporte legal

- **Auxilio de movilización:** valor definido por la empresa para el
  cargo o función del empleado

- **Bonificación prestacional:** valor definido por la empresa para el
  cargo o función del empleado

- **Bonificación no prestacional:** valor definido por la empresa para
  el cargo o función del empleado

- **Otros valores:** valores de bolsa definido por la empresa de acuerdo
  con su tipo de contrato, se agrega **Horas Extras y Regargos
  Nocturos** (campo tipo texto solicitado por Coordinación de Gestión
  Humana.)

- Contrato de arrendamiento: valor definido para el pago por uso de
  transporte propio del empleado.

25. **El director de Gestión** **Humana** ingresa a la misma ruta busca
    la requisición, la abre, valida y edita Estructura del servicio si
    necesita corregir horarios, descansos o condiciones del puesto; el
    campo es obligatorio al guardar. Cambia el estado a **Aprobada** y
    guarde cambios.

    Los cambios quedan en el Historial de cambios.

26. **El reclutador, Analista o asistente de Selección** inicia el
    proceso de selección, cuando tenga a la persona seleccionada busca
    la requisición cambia el estado a Contratado, complete fecha de
    contratación, valide campos de compensación obligatorios y, en la
    sección Cierre, Cédula persona contratada y Nombre completo persona
    contratada (no confundir con Cédula/Nombre a quien reemplaza del
    motivo Reemplazo).

- Si la cédula ya está registrada en otra requisición, aparece una
  alerta de confirmación con el código de esa requisición; confirme solo
  si es la misma persona (el registro de la lista de espera se resigna a
  la requisición actual).

27. Exporte a Excel; incluye todos los campos de la requisición
    (incluida compensación) según los filtros activos.

28. Imprima la ficha si necesita documento físico.

### Administrar parámetros

29. Acceda a Catálogos (requiere permiso).

30. Mantenga catálogos: cargos, motivos, ciudades, tipos de
    programación, uniformes, Correos de notificación y Tipos de
    notificación (asignar correos por tipo de aviso).

### Notificaciones

31. Si requiere que se notifique a las solicitudes de requisición deberá
    solicitarlo al administrador del sistema.

### Autorizar requisiciones cargo nuevo (Gerencia)

32. Ingrese con usuario que tenga permiso Autorizar cargo nuevo
    (gerencia) (rol administrador).

33. Abra Requisiciones → Gestión humana → Autorización gerencia.

34. Revise la lista (solo pendientes). Abra Revisar, Autorizar o
    Rechazar (comentario obligatorio al rechazar).

35. Tras autorizar, la solicitud pasa a Solicitada y Gestión humana
    puede continuar. Si rechaza, queda Cancelada.

### Activar encargados de selección (solo Gestión humana)

Esta sección aparece únicamente en el tablero Requisiciones del área
Gestión humana, pestaña Catálogos.

36. Entre a Requisiciones → Gestion humana → Catálogos.

37. Abra la tarjeta Encargados de selección.

38. Revise la tabla de usuarios activos del área Gestión humana (nombre
    y correo).

39. Use el interruptor (toggle) en la columna Encargado para activar o
    desactivar a cada persona.

40. Solo los usuarios con toggle activo aparecen en la lista Reclutador
    al editar requisiciones en Gestión (cualquier área solicitante).

Nota: el permiso relacionado puede verse en Administración de usuarios,
pero la forma oficial de habilitar encargados es el toggle en Catálogos
de Gestión humana (salvo ajustes puntuales por superadministrador).

## Control de cambios

| **Versión** | **Fecha de Actualización** | **Razón del Cambio**      |
|-------------|----------------------------|---------------------------|
| 01          |                            | Elaboración del Documento |
