# Procedimientos de Trabajo

## Regla general

Todo cambio funcional debe cerrar con codigo, validacion y documentacion sincronizada.

## Procedimiento para nuevas funcionalidades

1. Revisar impacto en autenticacion, permisos, navegacion, base de datos, pruebas y despliegue.
2. Implementar el cambio de forma desacoplada por modulo.
3. Actualizar documentacion tecnica afectada.
4. Registrar rutas, permisos, tablas y validaciones nuevas.
5. Verificar que no se rompan rutas protegidas ni sesiones.

## Procedimiento para cambios en seguridad o acceso

1. Revisar `config/access.php`.
2. Revisar middleware, requests, rutas y seeders.
3. Validar usuarios inactivos, roles y contrasena temporal.
4. Actualizar [`ACCESS_CONTROL.md`](c:/laragon/www/SJSEGURIDAD/docs/ACCESS_CONTROL.md).
5. Documentar impacto operativo en el modulo afectado.

## Procedimiento para recuperar el acceso local

1. Confirmar que Laragon tenga `MySQL` iniciado.
2. Ejecutar `artisan app:doctor`.
3. Si el diagnostico marca errores, ejecutar `artisan app:stabilize-local`.
4. Verificar de nuevo con `artisan app:doctor`.
5. Probar inicio de sesion con el admin semilla definido en `.env`.

## Procedimiento para pruebas automatizadas

1. Ejecutar pruebas con `phpunit.xml`, que usa `sqlite` en memoria.
2. No ejecutar pruebas automatizadas contra la base local `sjseguridad`, porque `RefreshDatabase` puede limpiar tablas reales de desarrollo.
3. En Laragon, si SQLite no esta habilitado en `php.ini`, ejecutar PHPUnit indicando extensiones:

```powershell
& "C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" -d extension_dir="C:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\ext" -d extension=pdo_sqlite -d extension=sqlite3 vendor\bin\phpunit
```

## Procedimiento para nuevas areas del negocio

1. Agregar area en `config/access.php`.
2. Actualizar permisos derivados y seeders.
3. Crear rutas, controladores, vistas y validaciones del modulo.
4. Incluir la nueva area en dashboard o navegacion si aplica.
5. Crear un archivo del modulo en `docs/modules`.

## Procedimiento de documentacion obligatoria

Cada cambio debe responder, dentro del repositorio, estas preguntas:

- Que cambio se hizo
- Por que se hizo
- Que archivos o capas toca
- Que permisos, rutas o tablas afecta
- Como se valida localmente
- Que riesgos o dependencias deja

## Convencion para modulos documentados

Cada modulo debe tener un archivo `docs/modules/<modulo>.md` con:

- objetivo
- alcance actual
- rutas
- permisos
- controladores y requests
- vistas
- tablas implicadas
- riesgos y pendientes
