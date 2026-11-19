# Integracion con SJ LegalSuite (guia de usuario)

## Que sincroniza

Solo los **datos basicos del empleado** que necesita el area juridica: documento, nombres y apellidos, sexo, fecha de nacimiento, direccion, ciudad de residencia y de trabajo, telefono, correo, fecha de ingreso, tipo de contrato, cargo, salario basico, estado (activo o desvinculado) y fecha de retiro.

**No** se envia informacion de contacto de emergencia, area/departamento, EPS, AFP, banco, cuentas ni documentos.

## Cada cuanto

- **Cambios:** cada 5 minutos. Si alguien modifica la ficha de un empleado, el cambio llega a LegalSuite en los 5 minutos siguientes.
- **Carga completa:** todos los lunes a las 3:00 a.m. (hora de Colombia) se reenvia todo el catalogo como respaldo.

## Que pasa cuando un empleado se desvincula

Se marca como **inactivo** en LegalSuite (no se borra). El historial de casos disciplinarios se conserva y sigue visible. Los listados para abrir casos nuevos solo muestran empleados activos.

## Que hacer si falla

1. Revise con el area de sistemas el comando `php artisan integracion:push-cambios`.
2. En la base de datos, la tabla `integration_sync_log` guarda cada intento: hora, estado, cuantos registros se enviaron y el mensaje de error.
3. Si el error es de conexion o de credenciales, sistemas debe revisar `INTEGRATION_LEGALSUITE_*` en el `.env`.
4. Cuando se restablece, la sincronizacion se recupera sola: el sistema reintenta desde el ultimo punto seguro sin perder cambios.

## Como pedir un reenvio total

Sistemas puede ejecutar `php artisan integracion:push-empleados --full`. No borra nada: actualiza lo que cambio y crea lo que falte. Antes conviene correr `--dry-run` para ver cuantos registros se enviarian.

Si hay sospecha de que la conexion esta caida, `php artisan integracion:probar` comprueba el canal con LegalSuite sin enviar ni modificar empleados: solo confirma que la direccion, el certificado y las credenciales siguen vigentes.

## A quien contactar

- Datos de empleados (altas, retiros, cargos): Gestion Humana.
- Fallas de sincronizacion: area TIC.