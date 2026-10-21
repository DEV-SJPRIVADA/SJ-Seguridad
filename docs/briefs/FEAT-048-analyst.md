# Preguntas del Analista — FEAT-048

> Salida del Agente Analista antes del Feature Brief final. Si quedan preguntas abiertas, el AgentSj **pausa** hasta respuesta del usuario.

**Estado de este ciclo:** **Cerrado — listo para Arquitecto.** Todas las preguntas respondidas con decisiones ya aprobadas por el usuario (2026-10-20). No se reabren.

---

## Contexto recibido

| Campo | Valor |
| --- | --- |
| Feature ID | FEAT-048 |
| Origen | AgentSj — plan aprobado por el usuario para iniciar implementación |
| Módulo / área | Compras / `purchase-requests` + Suministros (`supplies`) |
| Título | Compras: FO-AD-07 oficial, estados automáticos y menú en tres tableros |
| Doc módulo | [`docs/modules/purchase-requests.md`](../modules/purchase-requests.md), [`docs/modules/suministros.md`](../modules/suministros.md) |
| Guía usuario | [`docs/user/purchase-requests.md`](../user/purchase-requests.md) (orden de compra y bandeja) |
| Antecedente | [`docs/briefs/FEAT-047.md`](FEAT-047.md) — OC, proveedores, VINE01 y reparto ya existen; T5 evaluación aún sin UI |
| Formato oficial | Imagen FO-AD-07 Enero 2025 Versión 04 (adjunto del usuario) |
| PDF actual | `resources/views/pdf/purchase-order.blade.php` — no es el formato oficial |
| Formulario | `resources/views/modules/purchase-requests/create.blade.php` — Datos del cliente: Proyecto nuevo, Asume el cliente, Reinversión |
| Snapshot | `PurchaseOrderService`: `asume_cliente` = `SI`/`NO` o `null` (suministro e interna quedan `null`) |

### Solicitud (resumen)

Cerrar el ciclo de Compras sobre lo que FEAT-047 ya dejó en producción, en cinco cortes verticales:

1. Formulario Comodato/Activo + snapshot + PDF/Excel FO-AD-07 al formato oficial (una hoja carta + condiciones).
2. `PurchaseFlowStateService` y quitar el selector manual de estado. Reenvío de suministro rechazado por Calidad.
3. Un solo precio (el de la OC). Dejar de completar un suministro pidiendo `unit_cost`.
4. Menú de Compras en tres tableros, folios visibles `SC-`/`SU-`, bandeja distinta del historial, y quitar Insumos aprobados como escritorio paralelo.
5. Recepción (fecha de recibido) + evaluación del proveedor ya definida en FEAT-047 (no se imprime).

---

## Decisiones YA CERRADAS (ley — no repreguntar)

| # | Tema | Decisión del usuario |
| --- | --- | --- |
| 1 | Puertas de entrada | Se conservan las dos: **Pedir insumos** (catálogo + Calidad) y **Pedir una compra** (director). |
| 2 | Autorización | Sigue la del director en plataforma y por correo, y el ajuste de cantidades de Calidad. |
| 3 | Bandeja y reparto | Bandeja única de Compras, catálogo de proveedores, VINE01 y reparto de una solicitud entre varios proveedores con saldo por línea. Ya existen; no se rehacen. |
| 4 | Formatos | **FO-AD-44** sigue siendo el formato del pedido. **FO-AD-07** es la orden. |
| 5 | Permisos | **Sin permisos nuevos.** Reutilizar `purchase.tab.*` y `supply.tab.*`. Evaluación: `purchase.providers.edit` (FEAT-047). No tocar claves nuevas en `config/access.php`. |
| 6 | Datos viejos | No reescribir pedidos ya cerrados. Migraciones solo aditivas. Prohibido `migrate:fresh`, wipe y truncar. |
| 7 | Estados | Los escribe el sistema. Se quita el selector manual. Traducción a columnas actuales **sin renombrar** valores en BD en el primer corte. |
| 8 | Abrir Procesar | **No cambia el estado.** Hoy un suministro `aprobada_calidad` pasa a `en_compras` al abrir Procesar: eso se elimina. |
| 9 | Reenvío | Suministro rechazado por Calidad se puede reenviar, igual que una compra rechazada por el director. |
| 10 | Bandeja vs historial | Bandeja = solo **Por comprar** y **Con orden**. Historial = **cerrados**. |
| 11 | Precio | Vive solo en la OC. Dejar de pedir `unit_cost` para completar un suministro. Si ya había costo, precargar el valor unitario de la OC. |
| 12 | Notas | Hilo de comentarios único para notas nuevas. `comentarios_director` y `comentarios_compras` se siguen mostrando en pedidos viejos y dejan de capturarse. |
| 13 | Folios | Visible `SC-0042` y `SU-0042` **sin cambiar** el número guardado. Número de OC sigue siendo el `id`. |
| 14 | Menú corte 4 | Tres tableros: **Pedidos** (por comprar, con orden, historial), **Órdenes** (FO-AD-07), **Catálogos** (Proveedores e insumos en pestañas). Dejar de mostrar el tablero suelto Bandeja compras y la pestaña Insumos aprobados de Calidad. Calidad solo conserva Por aprobar. |
| 15 | PDF | Reemplazar el layout de `resources/views/pdf/purchase-order.blade.php` por el orden visual del FO-AD-07 (detalle abajo). Una hoja carta. |
| 16 | Condiciones | Texto legal exacto en `config/purchase-orders.php`. También en el Excel. La impresión de una hoja es el PDF. |
| 17 | Campos cliente | En Nueva solicitud, Reenviar y detalle, solo si `solicitud_para=Cliente`: Comodato y Activo, radios Sí/No, default No, al lado de Reinversión. Fila: Proyecto nuevo, Asume el cliente, Reinversión, Comodato, Activo. |
| 18 | Snapshot OC | Al emitir, congelar `proyecto_nuevo`, `reinversion`, `comodato` y `activo` como string nullable `SI`/`NO`, igual que `asume_cliente`. |
| 19 | Interna y suministro en el PDF | Solicitud interna: no mostrar ni guardar esos campos como Sí. Suministro, y OC emitidas antes del cambio: celdas vacías. |
| 20 | Firmas | Elaborado por = quien emitió (`created_by`). Revisado y aprobado por = director que autorizó; en suministro, revisor de Calidad. Director Admon. y Financiero y Gerente (cuando aplique) en blanco, con espacio para firma manuscrita. |
| 21 | Corte 5 | Fecha de recibido en la orden. Si todas las OC del pedido tienen fecha y el saldo es 0 → Cerrado. Hasta ese corte, la OC que deja el saldo en cero ya cierra. Excepción siempre: botón **Cerrar saldo** con motivo obligatorio en el hilo. |
| 22 | Evaluación | Pesos 25/25/50, una por OC, Calif. 1–5, umbrales FEAT-047. **No se imprime** en el FO-AD-07. Permiso `purchase.providers.edit`. |
| 23 | Fuera | Requisiciones de personal, CRM de clientes, imprimir la evaluación, permisos nuevos, `migrate:fresh` / wipe / truncar. |

### Máquina de estados (cerrada)

| Acción | Etiqueta | Columna que ya existe (no renombrar) |
| --- | --- | --- |
| Solicitante envía | Pendiente de autorización | Compra: `estado=pendiente`. Suministro: `pendiente_calidad`. |
| Director o Calidad rechaza | Devuelta | Compra: `estado=rechazado`. Suministro: `rechazada_calidad`. |
| Solicitante reenvía | Pendiente de autorización | Vuelve a `pendiente` / `pendiente_calidad`. |
| Director o Calidad aprueba | Por comprar | Compra: `estado=aprobado` y `estado_compras=pendiente` y **sin cantidades ya puestas en una OC**. Suministro: `aprobada_calidad` con la misma condición. |
| Primera OC emitida y aún hay saldo | Con orden | Compra: `estado_compras=en_curso`. Suministro: `en_compras`. |
| Hasta el corte 5: la OC que deja el saldo en cero | Cerrado | Compra: `estado_compras=completado`. Suministro: `completada`. |
| Botón Cerrar saldo (motivo obligatorio en el hilo) | Cerrado | Mismos valores `completado` / `completada`, aunque quede saldo. |
| Corte 5: todas las OC con fecha de recibido y saldo 0 | Cerrado | Mismos valores. Si el saldo ya es 0 pero falta una fecha, **sigue Con orden**. |

`PurchaseFlowStateService` recalcula después de aprobar, rechazar, reenviar y emitir OC. En el corte 2 también después de Cerrar saldo. En el corte 5 también después de registrar la fecha de recibido. Compras **ya no elige** el estado a mano y **ya no rechaza** por su cuenta: esa fila no está en la máquina.

Pendiente de autorización y Devuelta **no** entran al tablero Pedidos. Siguen en Mis solicitudes y en la cola de quien autoriza (director o Calidad).

### Orden visual FO-AD-07 (cerrado)

1. Logo `public/images/logoSj.png` (el archivo existe) | título **ORDEN DE COMPRA O SERVICIO** | recuadro **FO-AD-07** / **Enero de 2025** / **Versión 04** / **Página 1 de 1**.
2. Fecha `dd/mm/aaaa` (`purchase_orders.fecha`) y **OC No.** (`id`).
3. Ciudad = ciudad de entrega (`purchase_orders.ciudad`), no la ciudad del proveedor.
4. Información del Asociado de Negocio, con snapshot: Razón social `provider_nombre`, NIT `provider_identificacion`, Contacto, Teléfono, Correo, Dirección, Ciudad del proveedor, Forma de pago (si está vacía y hay `dias_pago`, imprimir `A {n} DÍAS`), Asume el cliente (`SI`/`NO` si hay snapshot; en suministro, vacío).
5. Debajo de Asume el cliente, **siempre** las filas Proyecto nuevo, Reinversión, Comodato, Activo. Imprimir Sí o No cuando el snapshot es `SI`/`NO`. En suministro o en OC emitidas antes del cambio, celdas vacías. Estas cuatro filas no vienen en el papel escaneado: el usuario las pidió explícitamente.
6. Tabla **Sírvase suministrar los servicios y/o artículos descritos a continuación:** Cantidad, Artículo, Descripción, Valor unidad, IVA (**importe**, no %), Valor total. Fila **TOTAL** dentro de la tabla. Valor total de línea = cantidad × valor unidad + IVA (ya es la regla de FEAT-047). Artículo = el campo `articulo` que hoy se llena con la referencia del origen; no es un dato nuevo.
7. **OBSERVACIONES** siempre visible (`purchase_orders.observaciones`; vacío si no hay texto).
8. Firmas, con espacio para firma manuscrita:
   - Elaborado por: **Analista de Compras** = nombre de quien emitió (`created_by`).
   - Revisado y aprobado por: **Director(a) área solicitante** = director que autorizó la compra (`aprobador`); en suministro, el revisor de Calidad (`quality_reviewer_id`).
   - Aprobado 2 por: **Director Admon. y Financiero** en blanco; **Gerente (cuando aplique)** en blanco.
9. Debajo, **siempre**, el texto de condiciones, letra ~6px, para que todo quepa en **una** hoja carta. Si hay muchas líneas, reducir la letra de la tabla antes de pasar a página 2. Meta de prueba: 1 línea y ~12 líneas, ambas en una página.

El periodo visible es **Enero de 2025** (con «de»). El config actual dice `Enero 2025` y el título `ORDEN DE COMPRA`: el corte 1 los alinea al formato oficial.

### Texto legal exacto (no corregir tildes ni espacios dobles)

Guardar este texto tal cual en `config/purchase-orders.php`:

```
CONDICIONES GENERALES: AL ACEPTAR ESTA ORDEN DE COMPRA EL ASOCIADO DE NEGOCIO SE COMPROMETE A LAS SIGUIENTES CONDICIONES:

LA FACTURA O CUENTA DE COBRO QUE GENERE ESTA ORDEN  SERA CANCELADA PREVIA PRESENTACIÓN DE LA MISMA, A PARTIR DE SU FECHA DE RADICACION, EN EL PLAZO ACORDADO POR LAS PARTES, SIN QUE SE PRODUZCA INTERES ALGUNO.

EL ASOCIADO DE NEGOCIO DEBE RADICAR FACTURA O CUENTA DE COBRO EN ORIGINAL Y COPIA, RELACIONANDO EL NUMERO DE LA ORDEN  Y ANEXAR COPIA DE LA REMISION O ACTA DE ENTREGA CON SELLO DE RECIBIDO DEL ALMACEN;  ESTA FACTURA O CUENTA DE COBRO SOLO SERÁ ACEPTADA CUANDO EL SERVICIO CORRESPONDA A LA ORDEN EMITIDA.

PARA EFECTOS CONTABLES  SOLO SE RADICARAN FACTURAS HASTA EL DIA VEINTICINCO(25) DEL MES EN CURSO, POR LO TANTO LAS FACTURAS O CUENTAS DE COBRO QUE SE GENEREN A PARTIR DE ESA FECHA DEBERAN RADICARSE CON FECHA DEL MES SIGUIENTE.

EL ASOCIADO DE NEGOCIO SE COMPROMETE A REALIZAR LA REPARACION O PRESTACION DEL SERVICIO EN LOS TERMINOS QUE ESTIPULA ESTA ORDEN, HACIENDOSE RESPONSABLE POR LOS DAÑOS O PERDIDAS QUE SE CAUSAREN AL BIEN O BIENES PROPIEDAD DE ESTA EMPRESA EN EL DESARROLLO DE LA LABOR SOLICITADA.

LA PRESENTE ORDEN NO CONSTITUYE DEPENDENCIA LABORAL ALGUNA.

ESTA ORDEN DE COMPRA ES CONCORDANTE CON LO ESTIPULADO EN LA COTIZACION POR PARTE DEL ASOCIADO DE NEGOCIO.

VER AL RESPALDO LAS CONDICIONES LEGALES DE LA RELACIÓN COMERCIAL
```

---

## Preguntas abiertas

Ninguna.

---

## Cómo quedan respondidas las preguntas de la plantilla

1. **Objetivo y usuarios.** Compras emite la orden con el papel oficial FO-AD-07; el solicitante pide por las dos puertas que ya existen; el director o Calidad autorizan; Compras compra, reparte, recibe y evalúa. Decisión del usuario: plan aprobado el 2026-10-20.
2. **Alcance.** Cinco cortes de arriba. Fuera: requisiciones de personal, CRM, imprimir la evaluación, permisos nuevos, reescribir cerrados, `migrate:fresh` / wipe.
3. **Permisos.** Sin claves nuevas. Emitir OC, Pedidos y Órdenes: `purchase.tab.processing`. Crear / mis solicitudes / pendientes: `purchase.tab.create`, `purchase.tab.my_requests`, `purchase.tab.approval`. Insumos: `supply.tab.my_requests`, `supply.tab.quality` (solo Por aprobar), `supply.tab.catalog` (pestaña Insumos de Catálogos). Proveedores: `purchase.providers.view` / `.edit` (edit implica view). Evaluación y fecha de recibido de esa pantalla: `purchase.providers.edit`.
4. **Reglas de negocio.** Máquina de estados de la tabla de arriba. Abrir Procesar no mueve el estado. Un hilo para notas nuevas. Precio solo en la OC. Cerrar saldo exige motivo en el hilo.
5. **Datos.** Aditivo: `purchase_requests.comodato` y `activo` (boolean, default false). Aditivo en `purchase_orders`: `proyecto_nuevo`, `reinversion`, `comodato`, `activo` (string nullable `SI`/`NO`). Condiciones en config, no en BD. Evaluación: tabla `purchase_order_evaluations` ya creada en FEAT-047; el corte 5 le pone UI. No renombrar `pendiente` / `rechazado` / `aprobado` / `pendiente` / `en_curso` / `completado` ni los status de suministro.
6. **Interfaz.** PDF y Excel de una hoja; radios Comodato y Activo en alta, reenvío y detalle; sin selector de estado; botón Cerrar saldo; tres tableros; folios `SC-`/`SU-`; Calidad sin Insumos aprobados.
7. **Integraciones.** Correo de autorización del director se conserva (plataforma y enlace firmado). FO-AD-44 no se sustituye. La evaluación no viaja en el PDF.
8. **Documentación de usuario.** El procedimiento operativo está en el plan cerrado (bandeja, orden, cerrar saldo, recepción). La guía `docs/user/purchase-requests.md` se actualiza en la implementación, no en este brief.

---

## Supuestos residuales (no bloquean; Arquitecto puede ajustarlos)

| # | Supuesto | Riesgo si es incorrecto |
| --- | --- | --- |
| S1 | Compra **interna**: no se muestran Proyecto nuevo, Asume el cliente, Reinversión, Comodato ni Activo, y no se guardan en verdadero (siguen `null`, como hoy `asume_cliente`). Al emitir la OC el snapshot queda `null` y el PDF deja la celda **vacía**, no «No». «Sí» o «No» se imprime solo cuando el snapshot es `SI`/`NO` (solicitud Cliente). | Si el negocio quería «No» impreso en toda compra interna, el PDF saldría en blanco en esas filas. |
| S2 | Pedidos que Compras marcó antes con `estado_compras=rechazado` no se reescriben, no se renombran y **no** entran a Por comprar, Con orden ni Historial. Siguen visibles para el solicitante en Mis solicitudes. Compras ya no tiene acción de rechazar. | El analista de Compras no los encuentra en el tablero Pedidos. |
| S3 | El suministro hoy no tiene hilo. En el corte 2 se agrega uno aditivo, del mismo papel que `purchase_request_comments`, para notas nuevas y para el motivo de Cerrar saldo. `observations` y `quality_observations` de pedidos viejos se siguen mostrando. El rechazo de Calidad puede seguir usando su comentario actual; el de Cerrar saldo va al hilo. En compras, el motivo de rechazo del director pasa al hilo, porque `comentarios_director` deja de capturarse. | Si no se crea el hilo de suministro, Cerrar saldo no tiene dónde guardar el motivo. |
| S4 | La fecha de recibido es `purchase_order_evaluations.fecha_recibido` (columna ya creada). La registra quien tiene `purchase.providers.edit`, en la pantalla de evaluación del corte 5. Se puede guardar la fecha sin las tres calificaciones. El cierre automático mira fecha + saldo en cero, no el puntaje. | Quien solo tiene `purchase.tab.processing` no marca recibido; el pedido sigue Con orden hasta que alguien con permiso de evaluación registre la fecha, o hasta que usen Cerrar saldo. |
| S5 | **Cerrar saldo** lo usa quien tiene `purchase.tab.processing`, en el pedido Por comprar o Con orden. El motivo y el paso a Cerrado ocurren en la misma acción. Después de Cerrado no se emiten más OC. El botón cierra aunque falten fechas de recibido (es la excepción, también después del corte 5). | Si el cierre debía ser de otro perfil, el botón quedaría en manos de Compras procesador. |
| S6 | El tablero **Dashboard** de Compras no está en la lista de los que se quitan: se conserva. Desde el corte 2 sus conteos de bandeja deben coincidir con Por comprar + Con orden; los cerrados no inflan «en bandeja». | Si no se toca, el KPI sigue mezclando cerrados con lo que ahora es bandeja. |
| S7 | Menú sin claves nuevas. **Pedidos** y **Órdenes** se ven con `purchase.tab.processing`. **Catálogos**: pestaña Proveedores con `purchase.providers.view`; pestaña Insumos con `supply.tab.catalog`. El tablero suelto Proveedores y el tablero suelto Bandeja dejan de mostrarse; su contenido pasa a esos tableros. Solicitudes de compra sigue para crear, Mis solicitudes y Pendientes del director. Suministros sigue para Pedir insumos. Calidad solo muestra Por aprobar. La pestaña Bandeja dentro de Solicitudes de compra deja de ser un segundo escritorio. | Si el negocio quería la bandeja vieja **y** el tablero Pedidos a la vez, quedaría un solo lugar. |
| S8 | Reimprimir una OC ya emitida usa el layout nuevo. No se rellenan snapshots que no existían: esas celdas salen vacías. El número guardado y el `id` de la OC no cambian. | Una OC vieja, al descargarse de nuevo, ya no se ve con el PDF anterior. |
| S9 | Folio visible = `SC-` o `SU-` + el folio que ya rellena a 4 dígitos (`PurchaseRequest::folio()` / `SupplyRequest::folio()`). Por encima de 9999 no se recorta (`SC-10000`). El correlativo guardado no se modifica. | Si pedían un ancho fijo distinto de 4, el cambio sería solo visual. |

---

## Estado

- [x] Todas las preguntas respondidas — listo para Arquitecto
- [ ] Pendiente respuesta usuario

## Respuestas del usuario

Citadas en la tabla **Decisiones YA CERRADAS**. El usuario aprobó el plan y pidió iniciar implementación con AgentSj (2026-10-20). No hay respuestas nuevas inventadas en este archivo.

---

## Borrador Feature Brief

> **BORRADOR — pendiente Arquitecto.** No sustituye `docs/briefs/FEAT-048.md`.

### Identificación

| Campo | Valor |
| --- | --- |
| ID | FEAT-048 |
| Módulo / área | Compras / purchase-requests + Suministros |
| Título | Compras: FO-AD-07 oficial, estados automáticos y menú en tres tableros |
| Solicitante | Usuario (plan aprobado 2026-10-20) |
| Fecha | 2026-10-20 |

### Objetivo

Que la orden de compra salga en el papel oficial FO-AD-07 (una hoja, con condiciones), que el estado del pedido lo escriba el sistema según lo que ya ocurrió (autorizar, emitir, recibir o cerrar saldo), y que Compras trabaje en tres tableros: Pedidos, Órdenes y Catálogos. Las dos puertas de pedido y la autorización del director o de Calidad se quedan como están.

### Alcance

#### Incluye

**Corte 1 — Formulario + snapshot + FO-AD-07**

- Radios Comodato y Activo (Sí/No, default No) en alta, reenvío y detalle, solo si la solicitud es para Cliente, en la misma fila que Proyecto nuevo, Asume el cliente y Reinversión.
- Columnas boolean `comodato` y `activo` en `purchase_requests`, default false. Solicitud interna: no mostrarlas y no guardarlas en verdadero.
- Al emitir la OC, snapshot `SI`/`NO` nullable de `proyecto_nuevo`, `reinversion`, `comodato` y `activo`. `asume_cliente` ya existe. Suministro: null.
- PDF y Excel FO-AD-07 con el orden visual y el texto legal de este brief. Una hoja carta (1 línea y ~12 líneas). Excel con los mismos bloques, incluidas las condiciones.
- No backfill de OC ya emitidas.

**Corte 2 — Estados**

- `PurchaseFlowStateService` con la máquina de la tabla. Sin selector manual.
- Abrir Procesar no cambia el estado.
- Reenvío del suministro `rechazada_calidad` por el solicitante, igual que la compra devuelta.
- Botón Cerrar saldo con motivo obligatorio en el hilo.
- Hilo único para notas nuevas. Dejar de capturar `comentarios_director` y `comentarios_compras`; seguir mostrándolos si el pedido viejo los tiene.
- Hilo aditivo del suministro si hace falta para el motivo (supuesto S3).

**Corte 3 — Un solo precio**

- El precio se captura solo al emitir la OC.
- Completar un suministro ya no pide `unit_cost`.
- Si el suministro ya tenía costo, precargar `valor_unidad`. No borrar costos históricos.

**Corte 4 — Menú y folios**

- Tres tableros: Pedidos (Por comprar, Con orden, Historial), Órdenes, Catálogos (Proveedores e insumos).
- Ocultar el tablero suelto Bandeja compras y la pestaña Insumos aprobados. Calidad solo Por aprobar.
- Folio visible `SC-` / `SU-` sin cambiar el número guardado. OC = `id`.
- Dashboard se conserva; sus conteos de bandeja siguen Por comprar + Con orden (supuesto S6).

**Corte 5 — Recepción y evaluación**

- UI de evaluación FEAT-047 T5 (pesos 25/25/50, Calif. 1–5, umbrales, una por OC, editable, `purchase.providers.edit`).
- Fecha de recibido. Cerrado automático solo si todas las OC tienen fecha y el saldo es 0.
- La evaluación no se imprime.

#### Fuera de alcance

- Requisiciones de personal.
- CRM de clientes.
- Imprimir la evaluación en el FO-AD-07.
- Permisos nuevos en `config/access.php`.
- `migrate:fresh`, wipe, truncar, reescribir pedidos cerrados, renombrar valores de estado ya guardados.

### Reglas de negocio

- Ver la máquina de estados y el orden visual de este archivo. Esas tablas son la regla.
- Saldo por línea = cantidad pedida − cantidad ya ordenada en todas las OC del mismo origen (FEAT-047). No se puede ordenar más que el pendiente.
- Por comprar = aprobado (o `aprobada_calidad`) y todavía sin cantidades en OC.
- Con orden = hay OC y queda saldo (`en_curso` / `en_compras`).
- Hasta el corte 5, saldo en cero al emitir → Cerrado. Desde el corte 5, además hacen falta todas las fechas de recibido; si falta una, sigue Con orden.
- Cerrar saldo → Cerrado con motivo en el hilo, aunque quede saldo o falte una fecha.
- Bandeja del tablero Pedidos = Por comprar + Con orden. Historial = Cerrado (`completado` / `completada`).
- Pendiente de autorización y Devuelta no están en Pedidos.

### Permisos (`config/access.php`)

Sin claves nuevas. Reuso:

| Permiso | Uso en FEAT-048 |
| --- | --- |
| `purchase.tab.create` / `purchase.tab.my_requests` | Pedir una compra, ver las propias, reenviar la devuelta |
| `purchase.tab.approval` | Director autoriza o devuelve (plataforma y correo) |
| `purchase.tab.processing` | Pedidos, emitir OC, Órdenes, Cerrar saldo |
| `supply.tab.my_requests` | Pedir insumos y reenviar el rechazado por Calidad |
| `supply.tab.quality` | Solo Por aprobar |
| `supply.tab.catalog` | Pestaña Insumos dentro de Catálogos |
| `purchase.providers.view` | Pestaña Proveedores (edit implica view) |
| `purchase.providers.edit` | Evaluación y fecha de recibido |

### Base de datos

| Tabla / cambio | Tipo | Notas |
| --- | --- | --- |
| `purchase_requests.comodato` | alter aditivo | boolean, default false |
| `purchase_requests.activo` | alter aditivo | boolean, default false |
| `purchase_orders.proyecto_nuevo` | alter aditivo | string nullable `SI`/`NO` |
| `purchase_orders.reinversion` | alter aditivo | string nullable `SI`/`NO` |
| `purchase_orders.comodato` | alter aditivo | string nullable `SI`/`NO` |
| `purchase_orders.activo` | alter aditivo | string nullable `SI`/`NO` |
| Hilo de suministro | tabla nueva aditiva, solo si el corte 2 la necesita | Motivo de Cerrar saldo y notas nuevas. No reemplaza textos viejos. |
| `purchase_order_evaluations` | ya existe | Corte 5: UI. `fecha_recibido` ya está. |
| Estados | sin alter de valores | Solo etiquetas en pantalla |

Multi-driver (sqlite de tests y MySQL). Sin `enum()` ni `change()` frágiles.

### Cortes (verticales; no separar front y back)

1. Comodato/Activo + snapshot + PDF/Excel FO-AD-07.
2. Servicio de estados, sin selector, reenvío de suministro, Cerrar saldo, hilo.
3. Precio único en la OC.
4. Tres tableros, folios, bandeja vs historial, Calidad sin Insumos aprobados.
5. Fecha de recibido + evaluación FEAT-047. Sin permiso nuevo.

### Archivos compartidos

- `config/purchase-orders.php`: texto legal y metadatos «Enero de 2025» / «ORDEN DE COMPRA O SERVICIO». No es un permiso.
- `config/access.php`: **solo** si el Arquitecto oculta o renombra tableros ya existentes. **Cero claves nuevas.**
- Rutas de compra y suministro: reutilizar nombres actuales; no publicar rutas fuera de `auth`.

### Criterios de aceptación

1. Una solicitud Cliente guarda Comodato y Activo en No por defecto, los muestra en alta, reenvío y detalle, y la OC nueva imprime Sí o No. Una interna no los guarda como Sí. Un suministro y una OC anterior al cambio imprimen esas celdas vacías.
2. El PDF y el Excel copian el FO-AD-07 de este brief, con condiciones exactas, y caben en una hoja carta con 1 línea y con unas 12.
3. Enviar, rechazar, reenviar, aprobar y emitir OC cambian la etiqueta según la tabla, sin selector manual. Abrir Procesar no mueve un suministro a `en_compras`.
4. Un suministro rechazado por Calidad se reenvía y vuelve a Pendiente de autorización.
5. Cerrar saldo exige motivo en el hilo y deja el pedido Cerrado.
6. Completar un suministro no pide costo unitario. El valor unitario de la OC sale precargado si ya había costo.
7. Pedidos muestra solo Por comprar y Con orden en la bandeja, y los cerrados en el historial. El folio se ve `SC-`/`SU-`. El número de OC sigue siendo el id.
8. Calidad ya no tiene Insumos aprobados. Compras ve Pedidos, Órdenes y Catálogos con los permisos de siempre.
9. Con `purchase.providers.edit` se registra la fecha de recibido y la evaluación (25/25/50). Esa evaluación no sale en el PDF. El pedido pasa a Cerrado cuando todas las OC tienen fecha y el saldo es 0.
10. Ningún pedido cerrado se reescribe. Ninguna migración borra datos.

### Riesgos

- Supuestos S1–S9.
- Reimprimir OC viejas cambia el aspecto del archivo, no los datos guardados.
- El corte 5 cambia la regla «saldo cero = cerrado» del corte 2: no adelantar la fecha de recibido en el corte 2.

### Aprobación

- [x] Analista — vacíos cerrados (este archivo)
- [ ] Arquitecto — brief final `docs/briefs/FEAT-048.md`
- [x] Usuario — plan aprobado el 2026-10-20 (ley de este ciclo)
