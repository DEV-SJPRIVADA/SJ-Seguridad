<x-guest-layout shell-class="guest-shell--login">
    <div class="auth-form auth-form--login">
        <div class="auth-form__header">
            <h1 class="auth-title">Tu sesion expiro</h1>
            <p class="auth-subtitle">
                Por seguridad, tu sesion ya no es valida. Vuelve a iniciar sesion para continuar en la plataforma.
            </p>
        </div>

        <p class="auth-copy">
            Esto puede ocurrir si estuviste inactivo por un tiempo o si recargaste una accion que ya no esta disponible.
        </p>

        <div class="auth-row auth-row--login">
            <a href="{{ route('login') }}" class="btn btn--primary btn--auth">
                Ir al inicio de sesion
            </a>
        </div>
    </div>
</x-guest-layout>
