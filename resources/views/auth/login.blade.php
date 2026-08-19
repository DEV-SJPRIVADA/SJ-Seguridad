<x-guest-layout shell-class="guest-shell--login">
    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="auth-form auth-form--login">
        @csrf

        <div class="auth-form__header">
            <h1 class="auth-title">{{ __('Iniciar sesion') }}</h1>
            <p class="auth-subtitle">
                Accede a {{ config('app.name') }} con tus credenciales asignadas.
            </p>
        </div>

        <!-- Email Address -->
        <div class="form-field">
            <x-input-label for="email" class="form-label--auth" :value="__('Correo electronico')" />
            <x-text-input id="email" class="form-input--auth" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <!-- Password -->
        <div class="form-field">
            <x-input-label for="password" class="form-label--auth" :value="__('Contrasena')" />

            <x-password-input id="password" class="form-input--auth" name="password" required />

            <x-input-error :messages="$errors->get('password')" />
        </div>

        <!-- Remember Me -->
        <div>
            <label for="remember_me" class="auth-checkbox">
                <input id="remember_me" type="checkbox" class="form-check" name="remember">
                <span>{{ __('Recordarme') }}</span>
            </label>
        </div>

        <div class="auth-row auth-row--login">
            @if (Route::has('password.request'))
                <a class="auth-link" href="{{ route('password.request') }}">
                    {{ __('Olvidaste tu contrasena?') }}
                </a>
            @endif

            <x-primary-button class="btn--auth">
                {{ __('Iniciar sesion') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
