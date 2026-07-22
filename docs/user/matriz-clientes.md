# Matriz comercial — Guia de usuario

## Objetivo

Gestionar la cartera de clientes y servicios comerciales (matriz MT-CO-01), consultar indicadores en el dashboard comercial y mantener vigencia de contratos.

## Alcance

Modulo del area **Comercial**. Incluye:

- **Dashboard:** indicadores de clientes y servicios (altas, vigencias, portafolios).
- **Clientes:** maestro de clientes por NIT.
- **Servicios:** contratos y portafolios vinculados a cada cliente.

Los clientes registrados aqui alimentan el buscador de **Clientes** en Requisiciones de personal.

## Definiciones

| Termino | Significado |
| --- | --- |
| Cliente | Empresa identificada por NIT con datos maestros (nombre, ciudad, sector). |
| Servicio | Contrato o portafolio asociado a un cliente (seguridad fisica, monitoreo, etc.). |
| Portafolio | Linea de negocio: seguridad fisica, monitoreo, ocasionales, inactivos. |
| Vigencia | Fechas de inicio y fin del contrato del servicio. |
| Por vencer | Servicios que vencen en los proximos 30 dias (alerta visual). |
| Checklist documental | Lista de requisitos documentales del servicio (estado, sin adjuntos en V1). |

## Responsabilidades

| Rol / perfil | Responsabilidad |
| --- | --- |
| Comercial — consulta | Ver dashboard, clientes y servicios. |
| Comercial — gestion | Crear/editar clientes y servicios; inactivar servicios. |
| Gestion Humana / solicitantes | Usar clientes de la matriz al crear requisiciones (solo lectura en Requisiciones). |
| Administrador | Permisos comercial.matriz.view / manage en Admin usuarios. |

## Desarrollo

### Consultar el dashboard comercial

1. Entre al area **Comercial**.
2. Abra **Dashboard**.
3. Use filtros de portafolio, ciudad, anio/mes segun necesite.
4. Revise KPIs y graficos de clientes nuevos y vigencias.

### Registrar un cliente

1. Abra **Clientes** → **Crear**.
2. Complete NIT, nombre, ciudad, sector y datos requeridos.
3. Guarde. El NIT debe ser unico.

### Registrar un servicio

1. Abra **Servicios** → **Crear** (o **Agregar servicio** desde la ficha del cliente).
2. Busque y seleccione el **cliente**.
3. Complete portafolio, numero de contrato, fechas de vigencia, tipo de servicio y checklist.
4. Guarde.

### Editar o inactivar

1. Desde listado de clientes o servicios, abra **Editar**.
2. Actualice datos o reasigne cliente en servicios.
3. Use **Inactivar** en servicios que ya no aplican.

### Exportar listados

1. En Clientes o Servicios use **Exportar Excel** si esta disponible en la pantalla.

### Importacion masiva (soporte TI)

La carga desde Excel MT-CO-01 la ejecuta soporte con comando de consola y una copia del archivo **fuera del repositorio**:

```powershell
php artisan comercial:import-mt-co-01 "C:\ruta\MT-CO-01 Matriz de clientes.xlsx"
```

No es una accion de usuario final en pantalla.

## Control de cambios

| Version | Fecha | Autor | Descripcion del cambio |
| --- | --- | --- | --- |
| 1.0 | 2026-07-22 | Alineacion documental | Version inicial guia de usuario |
| 1.1 | 2026-07-22 | Documentacion | Excel MT-CO-01 fuera del repo; import con ruta explicita |
