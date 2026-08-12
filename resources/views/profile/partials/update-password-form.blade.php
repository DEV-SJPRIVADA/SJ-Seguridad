<section class="profile-section profile-form">
    <header class="profile-section__header">
        <div class="profile-section__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>
        <div>
            <h2 class="profile-section__title">Seguridad de la cuenta</h2>
            <p class="profile-section__desc">
                Usa una contrasena larga y unica para proteger tu acceso al sistema.
            </p>
        </div>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="form-stack profile-form__stack">
        @csrf
        @method('put')

        <div class="form-field">
            <x-input-label for="update_password_current_password" value="Contrasena actual" />
            <x-password-input id="update_password_current_password" name="current_password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="form-grid form-grid--two profile-form__grid">
            <div class="form-field">
                <x-input-label for="update_password_password" value="Nueva contrasena" />
                <x-password-input id="update_password_password" name="password" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" />
            </div>

            <div class="form-field">
                <x-input-label for="update_password_password_confirmation" value="Confirmar contrasena" />
                <x-password-input id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
            </div>
        </div>

        <div class="content-actions profile-form__actions">
            <x-primary-button>Actualizar contrasena</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="inline-feedback inline-feedback--success"
                >Contrasena actualizada.</p>
            @endif
        </div>
    </form>
</section>
