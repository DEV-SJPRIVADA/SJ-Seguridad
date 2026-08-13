<section class="profile-section">
    <header class="profile-section__header">
        <div class="profile-section__icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <div>
            <h2 class="profile-section__title">Informacion personal</h2>
            <p class="profile-section__desc">
                Actualiza tu nombre y correo electronico de acceso al sistema.
            </p>
        </div>
    </header>

    @if (Route::has('verification.send'))
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">
            @csrf
        </form>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="form-stack profile-form__stack">
        @csrf
        @method('patch')

        <div class="form-grid form-grid--two profile-form__grid">
            <div class="form-field">
                <x-input-label for="name" value="Nombre completo" />
                <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" />
            </div>

            <div class="form-field">
                <x-input-label for="email" value="Correo electronico" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
        </div>

        @if (Route::has('verification.send') && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="notice notice--warning profile-form__notice">
                <p class="text-small">
                    Tu correo electronico aun no ha sido verificado.

                    <button form="send-verification" class="link-inline" type="submit">
                        Haz clic aqui para reenviar el correo de verificacion.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="inline-feedback inline-feedback--success block-spaced-sm">
                        Se envio un nuevo enlace de verificacion a tu correo electronico.
                    </p>
                @endif
            </div>
        @endif

        <div class="content-actions profile-form__actions">
            <x-primary-button>Guardar cambios</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="inline-feedback inline-feedback--success"
                >Cambios guardados.</p>
            @endif
        </div>
    </form>
</section>
