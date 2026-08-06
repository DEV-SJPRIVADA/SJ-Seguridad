@component('mail::message')
# Bienvenido a {{ config('app.name') }}

Hola **{{ $user->name }}**,

Se creo su cuenta de acceso a la plataforma. Use las siguientes credenciales para ingresar:

@component('mail::panel')
**Correo:** {{ $user->email }}

**Contrasena temporal:** {{ $temporaryPassword }}

@if ($user->document_number)
**Cedula registrada:** {{ $user->document_number }}
@endif
@endcomponent

@if ($mustChangePassword)
Debera cambiar su contrasena en el primer ingreso por seguridad.
@endif

@component('mail::button', ['url' => $loginUrl])
Ingresar a la plataforma
@endcomponent

Si usted no esperaba este correo, contacte al administrador del sistema.

Gracias,<br>
{{ config('app.name') }}
@endcomponent
