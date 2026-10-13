<section class="profile-section">
    <header class="profile-section__header">
        <div class="profile-section__icon" aria-hidden="true">
            <x-lucide-user width="20" height="20" />
        </div>
        <div>
            <h2 class="profile-section__title">Información personal</h2>
            <p class="profile-section__desc">
                Actualiza tu nombre y correo electrónico de acceso al sistema.
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
                <x-input-label for="email" value="Correo electrónico" />
                <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" />
            </div>
        </div>

        @if (Route::has('verification.send') && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="notice notice--warning profile-form__notice">
                <p class="text-small">
                    Tu correo electrónico aún no ha sido verificado.

                    <button form="send-verification" class="link-inline" type="submit">
                        Haz clic aquí para reenviar el correo de verificación.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="inline-feedback inline-feedback--success block-spaced-sm">
                        Se envió un nuevo enlace de verificación a tu correo electrónico.
                    </p>
                @endif
            </div>
        @endif

        <div class="content-actions content-actions--end profile-form__actions">
            <button
                type="submit"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                title="Guardar cambios"
                aria-label="Guardar cambios"
            >
                <x-lucide-save width="18" height="18" aria-hidden="true" />
            </button>

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
