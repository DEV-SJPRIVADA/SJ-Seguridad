# Control de Acceso

## Base tecnica

El proyecto usa `spatie/laravel-permission` con guard `web`.

## Roles base

- `super-admin` — acceso total; único rol con `manage.users` por defecto
- `administrador` — plataforma/GH (`manage.requisition.parameters`, `requisitions.approve.management`, tablero GH requisiciones); **sin** `manage.users`
- `director` — autoriza solicitudes de compra (`purchase.tab.approval`); **sin** autorizacion de requisiciones cargo nuevo
- `usuario`

Los roles antiguos `coordinador` y `consulta` fueron eliminados; el seeder migra a `usuario` cualquier usuario que aun los tenga.

## Permisos del sistema

Definidos en [`config/access.php`](c:/laragon/www/SJSEGURIDAD/config/access.php):

- `view.dashboard`
- `manage.users`
- `system.view.audit` — auditoria global del sistema (super-admin)
- `manage.requisition.parameters`
- `requisitions.tab.dashboard`
- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento`
- `requisitions.tab.gestion`
- `requisitions.approve.management`
- `requisitions.selection_officer`

`manage.requisitions` permanece en codigo por compatibilidad con asignaciones legacy, pero **no aparece en Admin**. Usar `requisitions.tab.gestion` + tablero visible en alcance.

**Autorizacion gerencia (`requisitions.approve.management`):** pestaña **Autorizacion gerencia** en Requisiciones; aprueba o rechaza solicitudes con motivo **Cargo nuevo** (`pendiente_autorizacion_gerencia` → `solicitada` o `cancelada`). Rol **`administrador`** (gerencia) lo incluye por seeder; el rol **`director`** no lo incluye. Correo de aviso configurable en Parametros → tipos de notificacion.

**Encargado de seleccion (`requisitions.selection_officer`):** define quien puede aparecer en el select **Reclutador** al gestionar requisiciones. No se asigna por defecto a roles base. La via operativa es el toggle en **Requisiciones → Gestion humana → Parametros → Encargados de seleccion** (permiso `manage.requisition.parameters`). El permiso figura en Admin bajo **Requisiciones — Gestion humana** para visibilidad; la asignacion manual alli es excepcional (p. ej. super-admin). Servicio: `RequisitionSelectionOfficerAccessService`. Tras la migracion `2026_07_28_112704_requisition_recruiter_id_references_users_drop_catalog`, ejecutar `php artisan migrate` y reactivar toggles en GH.

Permisos del modulo de suministros:

- `supply.tab.my_requests`
- `supply.tab.quality` (Aprobacion Insumos)
- `supply.tab.catalog`
- `manage.supply.catalog`
- `approve.supply.quality`

Permisos del modulo **Solicitudes de compra**:

- `purchase.tab.create`
- `purchase.tab.my_requests`
- `purchase.tab.approval`
- `purchase.tab.processing`

Tableros: `view.board.{area}.solicitudes_compra`, `view.board.{area}.bandeja_compras`

Permisos del modulo de documentos de Calidad:

- `manage.quality.documents`

Permisos del modulo de indicadores (area `operaciones`, board `indicadores`):

- `operations.view`
- `operations.capture`
- `operations.manage`
- `operations.export`

Estos permisos viven en `config/access.php` bajo `area_indicador_permissions.operaciones`. En **Administracion de usuarios** aparecen en **Activa visualizacion de otras areas → Operaciones → Indicadores (funciones)**.

Permisos de Gestion Clientes (area `comercial`, boards sidebar `dashboard` y `gestion_clientes`):

- `comercial.clients.view` / `comercial.clients.edit` — pestaña **Clientes** (consulta / CRUD + checklist + import)
- `comercial.services.view` / `comercial.services.edit` — pestaña **Servicios** (consulta / CRUD)
- `comercial.parameters.edit` — pestaña **Catalogos**
- `view.board.comercial.dashboard` — tablero **Dashboard** KPI (permiso propio; no se otorga por view/edit de pestañas)
- `view.board.comercial.gestion_clientes` — tablero sidebar **Gestion Clientes** (tambien visible con cualquier permiso funcional de pestaña)

`edit` implica `view` en `CommercialAccessService` (no hace falta marcar ambos).

**Legacy (ocultos en Admin, migrados automaticamente):** `comercial.matriz.view` / `manage`, `manage.commercial.parameters`, `view.board.comercial.matriz_clientes` / `servicios_comerciales`.

Viven en `area_indicador_permissions.comercial`. En Admin usuarios: **Activa visualizacion de otras areas → Comercial** (*Ver tableros* / *Gestion Clientes (funciones)*).

Visibilidad de pestañas: `CommercialAccessService` — cada pestaña exige su `view` o `edit`; Catalogos solo con `parameters.edit`.

## Areas actuales

- `gestion_humana`
- `operaciones`
- `programacion`
- `juridico`
- `comercial`
- `calidad`
- `admin_financiero` (unifica las antiguas `remuneraciones` y `facturacion`)
- `compras`

La migracion `2026_07_10_120000_merge_remuneraciones_facturacion_into_admin_financiero` actualiza `area_key` en usuarios, requisiciones, suministros y documentos; fusiona permisos Spatie de las dos areas legacy hacia `admin_financiero`. Las URLs del modulo quedan como `/requisitions/admin_financiero`, `/supplies/admin_financiero`, etc. El proceso de Calidad `gestion_financiera` no cambia.

## Acciones por area

- `view`
- `manage`

Esto produce permisos como:

- `view.area.gestion_humana`
- `manage.area.gestion_humana`
- `view.area.operaciones`
- `manage.area.operaciones`

## Modelo de tres dimensiones

1. **`users.area_key` (area base):** contexto operativo. Solicitar, Mis requisiciones y Mis solicitudes de suministros operan siempre en esta area.
2. **`view.board.{area}.{board}` (alcance):** solo visualiza el tablero en el sidebar. No otorga acciones.
3. **Permisos funcionales:** habilitan subtabs/acciones.

### Modelo objetivo por pestana (en migracion gradual)

Patron canónico (ya en Comercial / Gestion Clientes): **`{modulo}.{pestana}.view`** (consulta) y **`{modulo}.{pestana}.edit`** (escritura); `edit` implica `view` en el AccessService. Dashboard con permiso de tablero propio. Catalogos con `*.parameters.edit` cuando aplique.

Al anadir pestanas o funcionalidades nuevas: **preguntar al usuario** el mapa de permisos antes de codear. Regla Cursor: [`.cursor/rules/permissions-per-tab.mdc`](../.cursor/rules/permissions-per-tab.mdc).

Otras areas se iran alineando de forma incremental; no asumir packs legacy (`*.matriz.view` / `*.manage`) en codigo nuevo.

### Funcionalidades de area base

Operan en `{area_key}` del usuario (sin exigir `view.board` en el area base):

- `requisitions.tab.solicitar`
- `requisitions.tab.seguimiento` (UI: **Mis requisiciones**)
- `supply.tab.my_requests`

### Funcionalidades por tablero visible

Requieren permiso funcional **y** `view.board.{module}.{board}`:

- `requisitions.tab.gestion`, `requisitions.tab.dashboard`, `manage.requisition.parameters`, `requisitions.selection_officer` (select Reclutador; toggle en Parametros GH)
- `supply.tab.quality`, `supply.tab.catalog`, etc.

`view.area.*` y `manage.area.*` no sustituyen `view.board.*` para requisiciones o suministros. Documentos sigue usando `view.area.*`.

### Migracion manual post-deploy

- Directores: permisos transversales de autorizacion; el menu muestra **Gestion humana → Requisiciones** y **Compras → Solicitudes de compra** (hogares canonicos; no requiere tableros en todas las areas)
- Administradores de personal: funcionalidades de area base + tableros visibles en alcance + tabs por modulo (ej. Gestión en GH)
- Solicitantes insumos: `supply.tab.my_requests` (+ tablero visible si actuan fuera del area base)

## Hogares canonicos del sidebar

El sidebar usa [`SidebarVisibilityService`](../app/Services/Navigation/SidebarVisibilityService.php) y la config `board_canonical_areas` en [`config/access.php`](../config/access.php). Cada tablero transversal aparece **una vez** en su area natural; las demas areas solo muestran tableros propios del solicitante en su `area_key`.

| Tablero | Hogar en menu | Excepcion |
| --- | --- | --- |
| Requisiciones (gestion GH, autorizacion gerencia) | `gestion_humana` | — |
| Requisiciones (solicitar / mis req.) | `users.area_key` | Con permisos de solicitante |
| Suministros (aprobacion Calidad) | `calidad` | — |
| Suministros (catalogo / operacion Compras) | `compras` | — |
| Suministros (mis solicitudes) | `users.area_key` | Con `supply.tab.my_requests` |
| Solicitudes de compra (pendientes / bandeja) | `compras` | — |
| Solicitudes de compra (crear / mis) | `users.area_key` | Con permisos base |
| Documentos (administrar) | `calidad` | — |
| Documentos (consulta) | `users.area_key` o `view.area.*` explicito | Super-admin: solo Calidad + su area base |

**Matriz rol → areas visibles tipicas**

| Rol | Areas en sidebar |
| --- | --- |
| Super-admin | GH, Compras, Calidad, Operaciones, Comercial (+ area base si distinta) |
| Director | Gestion humana, Compras |
| Usuario de area | Solo su `area_key` (+ otras areas con `view.board` explicito sin alcance GH global) |
| Compras (processing) | Compras |

La visibilidad del sidebar **no restringe rutas**: un super-admin puede seguir accediendo por URL directa. Los middleware y policies conservan el bypass operativo con `manage.users`.

**Pruebas:** `tests/Feature/NavigationVisibilityTest.php` (super-admin, director, usuario de area, compras).


Cada area puede tener tableros internos definidos en `config/access.php`. Los tableros base son:

- `dashboard`
- `requisiciones`
- `suministros`
- `documentos`
- `indicadores` (solo en area `operaciones`; acceso por permisos `operations.*`, no por `view.board.*`)
- `gestion_clientes` (etiqueta UI: **Gestion Clientes**; solo en area `comercial`; sidebar por `view.board.comercial.gestion_clientes` o permisos funcionales `comercial.clients.*` / `services.*` / `parameters.edit`; pestañas con `comercial.clients.view|edit`, `comercial.services.view|edit`, `comercial.parameters.edit`)
- En area `comercial`, el board `dashboard` redirige a `comercial/dashboard` (KPIs de matriz); acceso **solo** por `view.board.comercial.dashboard` (o bypass `manage.users`)
- En area `gestion_humana`, tableros de area unica (no transversales de solicitante):
  - `ficha_empleados` — **Ficha empleados** (`view.board.gestion_humana.ficha_empleados` + `ficha_empleados.view` / `manage` / `terminate`)
  - `desvinculaciones` — **Desvinculaciones** (`view.board.gestion_humana.desvinculaciones` + `desvinculaciones.view` / `masivos` / `seguimientos.edit`)
  - `reportes_novedades` — **MT-GH-04 Novedades** (`view.board.gestion_humana.reportes_novedades` + `reportes_novedades.{hoja}.view` / `edit` / `review` × Vacaciones, Incapacidades, Retiros, Permisos)
  - `cursos` — **Cursos** (`view.board.gestion_humana.cursos` + `cursos.view` / `edit`)
  - `formacion` — **Formación** (`view.board.gestion_humana.formacion` + `formacion.view` / `edit`)
  - `seleccion` — **Selección** (`view.board.gestion_humana.seleccion` + `seleccion.view` / `edit`)
  - `cliente_interno` — **Cliente interno** (`view.board.gestion_humana.cliente_interno` + `cliente_interno.solicitudes.view` / `edit` + `cliente_interno.cartas_vacaciones.view` / `edit` + `cliente_interno.parameters.edit`)
  - `acreditaciones` — **Acreditaciones** (`view.board.gestion_humana.acreditaciones` + `acreditaciones.view` / `edit`)
  - `mt_st_04` — **MT-ST-04** (`view.board.gestion_humana.mt_st_04` + `mt_st_04.view` / `edit`)
  - `archivo` — **Archivo** (`view.board.gestion_humana.archivo` + `archivo.view` / `manage`)
  - `plantillas_word` — **Plantillas Word** (`view.board.gestion_humana.plantillas_word` + `plantillas_word.view` / `manage`)
  - `cartas_notificacion` — **Cartas Notificación** (`view.board.gestion_humana.cartas_notificacion` + **solo** `cartas_notificacion.edit`; **sin** `.view`)

### Cartas Notificación (Gestion humana)

Tablero **Cartas Notificación**: grilla directa (sin Dashboard / sin pestañas) para armar lote en memoria y generar Word/ZIP. Asignación **manual** en Admin (no viene por defecto en `administrador` / `usuario`). Independiente de Cliente interno y de Ficha empleados.

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.cartas_notificacion` | Ver tablero **Cartas Notificación** en sidebar GH |
| `cartas_notificacion.edit` | Entrar a la pantalla + lookup + Excel a grilla + generar/descargar (**no** existe `cartas_notificacion.view`) |

- Servicio: `CartasNotificacionAccessService` — `canViewBoard` = board **∨** edit **∨** bypass; `canEdit` = edit **∨** bypass.
- Solo board (sin edit): ve el enlace en sidebar; **403** al entrar a index y en POST (mensaje: necesita permiso de generar).
- Bypass: `manage.users`.
- Seed / sync: `super-admin` todos; **sin** migración Spatie de assign a roles/usuarios existentes.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *Cartas Notificación*). Labels: `Cartas Notificación`, `Cartas Notificación: Generar`.
- Plantilla Word: tipo seed `cartas_notificacion`; generación exige exactamente una plantilla con archivo (ver Plantillas Word).
- Doc: [`docs/modules/cartas-notificacion.md`](modules/cartas-notificacion.md), [`docs/user/cartas-notificacion.md`](user/cartas-notificacion.md).

### Formación (Gestion humana)

Tablero **Formación** (Dashboard + Formaciones: listado, export, plantilla e import replace-all). Asignación **manual** en Admin (no viene por defecto en `administrador` / `usuario`). Independiente del tablero **Cursos**.

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.formacion` | Ver tablero **Formación** en sidebar GH |
| `formacion.view` | Dashboard, listado Formaciones, filtros, export Excel |
| `formacion.edit` | Descargar plantilla e importar (replace-all; implica view en servicio de acceso) |

- Pestanas: `dashboard`, `formaciones` (`config/access.php` → `formacion_tabs`). Sin pestaña parámetros.
- Bypass: `manage.users`.
- Seed / sync: `super-admin` todos; `administrador` y `usuario` **sin** paquete por defecto. **Sin** migración automática de permisos legacy.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *Formación*).
- Mutación única: import replace-all (confirma UI + valida headers/filas antes de borrar dataset).
- Doc: [`docs/modules/formacion.md`](modules/formacion.md), [`docs/user/formacion.md`](user/formacion.md).

### Acreditaciones (Gestion humana)

Tablero **Acreditaciones** (Dashboard, Acreditados, Reporte Diario APO, Validaciones, Export Apo SuperVigilancia, Catálogo). Asignación **manual** en Admin (no viene por defecto en `administrador` / `usuario`).

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.acreditaciones` | Ver tablero **Acreditaciones** en sidebar GH |
| `acreditaciones.view` | Shell, **Dashboard** (KPIs/metrics), Acreditados (lectura), Reporte Diario (ver/filtrar/export/cargas), export listados. **No** ve ni opera Validaciones, Export Apo ni edita parámetros Catálogo |
| `acreditaciones.edit` | CRUD Acreditados, import, Catálogo (cargos + params Export Apo), cargar/reemplazar Reporte Diario, **Validaciones**, **Export Apo** (tab, DT, preview, generar `.xls`, modal novedades) (implica view en servicio de acceso) |

- Pestanas: `dashboard`, `acreditados`, `reporte_diario`, `validaciones`, `export_apo`, `catalogo` (`config/access.php` → `acreditaciones_tabs`). **Catálogo**, **Validaciones** y **Export Apo** solo con `acreditaciones.edit` (`AcreditacionesAccessService::visibleTabsFor`). **Dashboard** con `view`.
- **Cambio FEAT-039 vs placeholder FEAT-036:** Export Apo pasó de gate `view` a **`edit`**. Sin permiso Spatie nuevo; **no** se tocó `config/access.php`.
- Acción **Abrir Ficha** desde Validaciones: además requiere permiso de gestión de Ficha (`ficha_empleados.manage`); no abre la pestaña Validaciones por sí solo.
- Bypass: `manage.users`.
- Seed / sync: `super-admin` todos; `administrador` y `usuario` **sin** paquete por defecto.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *Acreditaciones*).
- Dependencia operativa: cédula en Ficha para Acreditados; Export Apo exige Ficha activa + identidad completa; CodigoCurso APO vive en `curso_tipos.cursos` (módulo Cursos).
- Doc: [`docs/modules/acreditaciones.md`](modules/acreditaciones.md), [`docs/user/acreditaciones.md`](user/acreditaciones.md).

### MT-ST-04 (Gestion humana)

Tablero **MT-ST-04** (Dashboard + Matriz: listado server-side, CRUD, plantilla/import upsert, export). Asignación **manual** en Admin (no viene por defecto en `administrador` / `usuario`). Independiente de Cursos / Acreditaciones más allá del lookup por cédula en Ficha.

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.mt_st_04` | Ver tablero **MT-ST-04** en sidebar GH |
| `mt_st_04.view` | Dashboard, Matriz, filtros, export Excel |
| `mt_st_04.edit` | CRUD, lookup Ficha, plantilla e import (implica view en servicio de acceso) |

- Pestanas: `dashboard`, `matriz` (`config/access.php` → `mt_st_04_tabs`). **Sin** pestaña parámetros / `mt_st_04.parameters.edit`. **Sin** permiso board dashboard aparte.
- Servicio: `MtSt04AccessService` — `canView` = `view` ∨ `edit`; `canEdit` = `edit`; bypass `manage.users`.
- Seed / sync: `super-admin` todos; `administrador` y `usuario` **sin** paquete por defecto. **Sin** migración automática de permisos legacy.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *MT-ST-04*). Labels: `MT-ST-04`, `MT-ST-04: Ver`, `MT-ST-04: Editar`.
- Dependencia operativa: cédula en Ficha empleados para alta/import; estados recalculados al guardar y por comando diario `mt_st_04:sync-estados`.
- Doc: [`docs/modules/mt_st_04.md`](modules/mt_st_04.md), [`docs/user/mt_st_04.md`](user/mt_st_04.md).

### Selección (Gestion humana)

Tablero **Selección** (Dashboard, Ingreso, Examen ocupacional, Catálogos). Asignación **manual** en Admin (no viene por defecto en `administrador` / `usuario`).

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.seleccion` | Ver tablero **Selección** en sidebar GH |
| `seleccion.view` | Dashboard, listados, filtros, export Excel |
| `seleccion.edit` | CRUD Ingreso / Examen ocupacional + Catálogos (implica view en servicio de acceso) |

- Pestanas: `dashboard`, `ingresos`, `examenes`, `catalogos` (`config/access.php` → `seleccion_tabs`). Catálogos solo con `seleccion.edit`.
- Bypass: `manage.users`.
- Seed / sync: `super-admin` todos; `administrador` y `usuario` **sin** paquete por defecto.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *Selección*).
- Dependencia operativa: select **Responsable** usa usuarios con `requisitions.selection_officer` (Parametros GH).
- Doc: [`docs/modules/seleccion.md`](modules/seleccion.md), [`docs/user/seleccion.md`](user/seleccion.md).

### Cliente interno (Gestion humana)

Tablero **Cliente interno** (Dashboard, Solicitudes, **Cartas Vacaciones**, Catálogos). Asignación **manual** en Admin (no viene por defecto en `administrador` / `usuario`). Modelo view/edit por pestaña (Solicitudes + Cartas Vacaciones) + `parameters.edit` para Catálogos; Dashboard sin permiso KPI aparte.

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.cliente_interno` | Ver tablero **Cliente interno** en sidebar GH |
| `cliente_interno.solicitudes.view` | Listado Solicitudes, filtros, export Excel; contribuye a Dashboard |
| `cliente_interno.solicitudes.edit` | Alta, editar, eliminar e import masivo (implica view en `ClienteInternoAccessService`) |
| `cliente_interno.cartas_vacaciones.view` | Ver pestaña Cartas Vacaciones (sin generar). **No** contribuye a Dashboard |
| `cliente_interno.cartas_vacaciones.edit` | Lookup + generar/descargar Word/ZIP (implica view en AccessService). **No** contribuye a Dashboard |
| `cliente_interno.parameters.edit` | Catálogos ESTADO y SOLICITUD; contribuye a Dashboard (sin listado Solicitudes si no hay view/edit) |

- Pestanas: `dashboard`, `solicitudes`, `cartas_vacaciones`, `catalogos` (`config/access.php` → `cliente_interno_tabs`).
- Dashboard: `solicitudes.view`∨`edit` **OR** `parameters.edit` (o bypass). **Usuario solo con permisos de Cartas Vacaciones (+ board) no ve Dashboard**; el shell redirige a la primera pestaña visible (Cartas Vacaciones).
- Usuario solo `parameters.edit` (+ board): Dashboard + Catálogos; Solicitudes y Cartas ocultas si no tiene esos permisos.
- Bypass: `manage.users`.
- Seed / sync: `super-admin` todos; `administrador` y `usuario` **sin** paquete por defecto de Cliente interno.
- **Migración Spatie FEAT-043** (`2026_10_07_131244_migrate_cliente_interno_solicitudes_edit_to_cartas_vacaciones_permissions`): idempotente; usuarios y roles con `cliente_interno.solicitudes.edit` reciben `cartas_vacaciones.view` **y** `.edit`. Tras deploy: `migrate` + `app:sync-permissions` + re-login.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *Cliente interno*). Labels: `Cliente interno: Ver solicitudes`, `… Editar solicitudes`, `… Ver Cartas Vacaciones`, `… Generar Cartas Vacaciones`, `… Catálogos`.
- Mutación masiva solicitudes: import replace-por-periodo (opción B). Confirma UI + `confirm_replace`.
- Mutación Cartas Vacaciones: POST generate (stream descarga; sin persistencia). Lookup también exige `.edit`.
- Doc: [`docs/modules/cliente-interno.md`](modules/cliente-interno.md), [`docs/user/cliente-interno.md`](user/cliente-interno.md).

### Desvinculaciones (Gestion humana)

Tablero **Desvinculaciones** (Masivos + Seguimientos). Paquete V1: asignar los **4** permisos juntos en Admin.

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.desvinculaciones` | Ver tablero **Desvinculaciones** en sidebar GH |
| `desvinculaciones.view` | Acceder al tablero (UI Masivos + lectura Seguimientos) |
| `desvinculaciones.masivos` | Lookup, procesar lote, descargar ZIP (no exige `ficha_empleados.terminate`) |
| `desvinculaciones.seguimientos.edit` | Editar checks y fecha entregado nomina |

- Bypass: `manage.users`.
- Seed: `super-admin` todos; rol `administrador` **no** recibe el paquete por defecto (asignacion manual).
- Relacion Ficha: terminate individual y cartas siguen con `ficha_empleados.terminate`; crean/actualizan seguimiento como side-effect.
- Side-effect FEAT-040: al crear followup (Ficha o Masivos) se asegura fila en **MT-GH-04 Novedades → Retiros**; al revertir se soft-deletea. No exige `reportes_novedades.retiros.edit`.
- Doc: [`docs/modules/desvinculaciones.md`](modules/desvinculaciones.md), [`docs/user/desvinculaciones.md`](user/desvinculaciones.md).

### MT-GH-04 Novedades (Gestion humana)

Tablero **MT-GH-04 Novedades** (clave `reportes_novedades`; Vacaciones, Incapacidades, Retiros, Permisos). Modelo por hoja: `view` / `edit` / `review` (tercer verbo para Nómina). Asignación **manual** en Admin (no viene por defecto en `administrador` / `usuario`).

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.reportes_novedades` | Ver tablero **MT-GH-04 Novedades** en sidebar GH |
| `reportes_novedades.vacaciones.view` | Vacaciones: ver / filtrar / export / historial |
| `reportes_novedades.vacaciones.edit` | Vacaciones: CRUD columnas GH |
| `reportes_novedades.vacaciones.review` | Vacaciones: editar columnas Nómina |
| `reportes_novedades.incapacidades.view` | Incapacidades: ver / filtrar / export / historial |
| `reportes_novedades.incapacidades.edit` | Incapacidades: CRUD columnas GH |
| `reportes_novedades.incapacidades.review` | Incapacidades: review Nómina (`observacion_nomina`, `dias_entrega`) |
| `reportes_novedades.retiros.view` | Retiros: ver / filtrar / export / historial |
| `reportes_novedades.retiros.edit` | Retiros: CRUD columnas GH (manual) |
| `reportes_novedades.retiros.review` | Retiros: review Nómina |
| `reportes_novedades.permisos.view` | Permisos: ver / filtrar / export / historial |
| `reportes_novedades.permisos.edit` | Permisos: CRUD columnas GH |
| `reportes_novedades.permisos.review` | Permisos: review Nómina |

**Implicaciones** (`ReportesNovedadesAccessService`):

| Otorgado | Efecto |
| --- | --- |
| `.edit` | Puede `.view` (aunque Spatie no tenga view) |
| `.review` | Puede `.view` + export; **no** implica `.edit` |
| `.edit` | **No** escribe cols Nómina |
| `.review` | **No** create/delete ni muta cols GH |

**Paquetes tipicos:** GH = board + 4× (`view`+`edit`); Nómina = board + 4× `.review`; consulta = `.view` (+ board).

- Pestanas: `vacaciones`, `incapacidades`, `retiros`, `permisos` (`config/access.php` → `reportes_novedades_tabs`).
- Sidebar: board **o** cualquier permiso funcional de hoja.
- Bypass: `manage.users`.
- Seed / sync: `super-admin` todos; `administrador` y `usuario` **sin** paquete por defecto.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *MT-GH-04 Novedades*, incluye labels *Revisar (Nomina)*).
- Lookup cedula → Ficha exige poder **editar** al menos una hoja.
- Doc: [`docs/modules/reportes-novedades.md`](modules/reportes-novedades.md), [`docs/user/reportes-novedades.md`](user/reportes-novedades.md).

### Plantillas Word (Gestion humana)

Permisos funcionales (independientes de Ficha empleados):

| Permiso | Uso |
| --- | --- |
| `view.board.gestion_humana.plantillas_word` | Ver tablero **Plantillas Word** en sidebar GH |
| `plantillas_word.view` | Ver tipos y plantillas; descargar plantilla maestra |
| `plantillas_word.manage` | Crear/editar/eliminar tipos; agregar/reemplazar/eliminar plantillas (implica view en servicio de acceso) |

- Generar y descargar **cartas** desde la ficha del empleado usa **solo** `ficha_empleados.terminate` (no requiere `plantillas_word.*`).
- Generar **Cartas Vacaciones** desde Cliente interno usa `cliente_interno.cartas_vacaciones.edit` (no requiere `plantillas_word.*`); el tipo seed `cartas_vacaciones` vive en este tablero (regla operativa: exactamente una plantilla con archivo).
- Generar **Cartas Notificación** desde su tablero GH usa `cartas_notificacion.edit` (no requiere `plantillas_word.*`); el tipo seed `cartas_notificacion` vive en este tablero (regla operativa: exactamente una plantilla con archivo).
- Bypass: `manage.users`.
- Seed: `super-admin` todos; rol `administrador` recibe board + view + manage de Plantillas Word. Tipos seed: `desvinculacion`, `cartas_vacaciones`, `cartas_notificacion`.
- Admin UI: **Activa visualizacion de otras areas → Gestion humana** (tablero en *Ver tableros*; funciones en subgroup *Plantillas Word*).
- Doc: [`docs/modules/plantillas-word.md`](modules/plantillas-word.md), [`docs/user/plantillas-word.md`](user/plantillas-word.md).

Esto produce permisos como:

- `view.board.gestion_humana.dashboard`
- `view.board.gestion_humana.requisiciones`
- `view.board.gestion_humana.requisiciones`
- `view.board.compras.suministros` (tablero de suministros; area **Compras**, no GH)

El tablero `documentos` **no** usa `view.board.{area}.documentos` ni `view.area.*` para consulta. En el **sidebar**, aparece al final de los tableros del `area_key` del usuario. Super-admin ve Documentos solo en Calidad + su area base (no en las 8 areas). Quien administra documentos (`manage.quality.documents`) tambien ve el tablero en Calidad. La biblioteca filtra por documentos activos asignados al area. La administracion requiere el permiso funcional `manage.quality.documents`.

Adicionalmente, un documento puede asignarse a usuarios especificos mediante la tabla `quality_document_users`. Esos destinatarios lo consultan en la pestaña `Mis documentos` del tablero `Documentos` de su area (`/quality-documents/{module}/mis-documentos`).

## Asignacion base de roles

Sembrada en [`database/seeders/RoleAndPermissionSeeder.php`](c:/laragon/www/SJSEGURIDAD/database/seeders/RoleAndPermissionSeeder.php):

- `super-admin`: todos los permisos (sidebar compacto via `SidebarVisibilityService`)
- `administrador`: `view.dashboard`, `manage.requisition.parameters`, `requisitions.approve.management`, `view.board.gestion_humana.requisiciones`
- `director`: autorizacion compras; menu en Compras → Solicitudes de compra (ver hogares canonicos)
- `usuario`: `view.dashboard`

Los roles antiguos `coordinador` y `consulta` se migran a `usuario` durante el seeder si existen. Los permisos de areas que ya no esten definidos en `config/access.php` se eliminan para evitar accesos obsoletos.

### Sincronizar permisos sin resetear roles

Comando artisan `app:sync-permissions`:

- Crea o actualiza permisos de sistema, areas y tableros segun `config/access.php`
- Excluye `view.board.{area}.documentos` (el tablero Documentos no usa ese permiso)
- Elimina permisos huerfanos de areas/tableros obsoletos
- **Actualiza rol `super-admin`** con el catalogo completo (incluye permisos nuevos como `system.view.audit`)
- **No** modifica otros roles ni permisos directos de usuarios

Util cuando se agregan areas o permisos nuevos sin ejecutar el seeder completo. Tras ejecutarlo, conviene **cerrar sesion y volver a entrar** para refrescar cache de permisos Spatie.

## Configuracion en Admin de usuarios

El formulario en **Administracion → Usuarios** usa tres bloques (`config/access.php` → `admin_ui`):

1. **Solicitar en su area:** solicitar, mis requisiciones, mis suministros, crear/mis solicitudes de compra (operan en `users.area_key`).
2. **Funcionalidades transversales:** requisiciones GH, suministros Calidad/Compras, autorizacion y bandeja de compras, admin documentos (`manage.quality.documents`).
3. **Activa visualizacion de otras areas:** tableros y modulos por area (GH, **Compras**, Operaciones, Comercial, Calidad) con subgrupos *Ver tableros* / *funciones*.

El listado de usuarios muestra permisos **directos** y notas de acceso efectivo por rol (`UserAccessSummary`).

Migracion `2026_07_21_140000_migrate_legacy_requisition_and_supply_board_permissions` reemplaza:
- `manage.requisitions` → `requisitions.tab.gestion`
- `view.board.gestion_humana.suministros` → `view.board.compras.suministros`

Documentacion: [`docs/modules/admin-users.md`](modules/admin-users.md).

## Middleware y enforcement

- `/dashboard` exige `view.dashboard` ademas de autenticacion, usuario activo y contrasena cambiada.
- Rutas de requisiciones usan middleware `requisition.tab:{tab}` alineado con `RequisitionAccessService`.
- Rutas de suministros usan middleware `supply.tab:{tab}` alineado con `SupplyAccessService`.
- Administracion de documentos de Calidad solo responde en `module=calidad`; otras areas devuelven 404 aunque el usuario tenga `manage.quality.documents`.
- `supply_request` en rutas se resuelve acotado al `module` de la URL (proteccion IDOR).

Servicios centrales:

- [`app/Services/Access/RequisitionAccessService.php`](../app/Services/Access/RequisitionAccessService.php)
- [`app/Services/Access/SupplyAccessService.php`](../app/Services/Access/SupplyAccessService.php)

## Sede fisica del usuario (suministros)

En **Administracion de usuarios** (`manage.users`) cada usuario puede tener `sede_id` (catalogo `supply_sites`). Es requerida para crear solicitudes de insumos y define el snapshot Utilizacion/Ubicacion del reporte FO-AD-44. Las sedes se administran desde el modal **Gestionar** en el formulario de usuario (rutas `admin.supply-sites.*`). El permiso `supply.tab.quality` habilita las pestañas **Aprobacion Insumos** e **Insumos aprobados**. Ver [`docs/modules/suministros.md`](modules/suministros.md).

## Reglas obligatorias

- No habilitar registro publico salvo instruccion expresa
- Todo acceso sensible debe exigir autenticacion y permisos
- Los usuarios inactivos no pueden operar
- Las contrasenas temporales deben obligar cambio al primer ingreso
- Una vez el usuario actualiza correctamente su contrasena, `must_change_password` debe pasar a `false`
- `users.area_key` define el modulo de Solicitar y Mis requisiciones; no otorga permisos por si solo
- `view.board.*` en alcance solo visualiza; las acciones requieren permisos funcionales explicitos

## Impacto de cambios

Cuando se agregue una nueva area del negocio, se deben revisar como minimo:

- `config/access.php`
- `database/seeders/RoleAndPermissionSeeder.php`
- rutas
- navegacion
- vistas del modulo
- pruebas relacionadas con permisos
