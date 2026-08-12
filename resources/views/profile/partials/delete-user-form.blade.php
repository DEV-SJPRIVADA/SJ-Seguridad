<section class="profile-section profile-section--danger">
    <header class="profile-section__header">
        <div class="profile-section__icon profile-section__icon--danger" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"/>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"/>
                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                <line x1="10" y1="11" x2="10" y2="17"/>
                <line x1="14" y1="11" x2="14" y2="17"/>
            </svg>
        </div>
        <div>
            <h2 class="profile-section__title">Eliminar cuenta</h2>
            <p class="profile-section__desc">
                Al eliminar tu cuenta se borraran de forma permanente tus datos y accesos.
                Descarga cualquier informacion que necesites conservar antes de continuar.
            </p>
        </div>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Eliminar mi cuenta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="modal-card form-stack">
            @csrf
            @method('delete')

            <h2 class="panel-title">Confirmar eliminacion de cuenta</h2>

            <p class="panel-text">
                Esta accion no se puede deshacer. Ingresa tu contrasena para confirmar
                que deseas eliminar permanentemente tu cuenta.
            </p>

            <div class="form-field">
                <x-input-label for="password" value="Contrasena" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="Contrasena"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="content-actions content-actions--end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button>
                    Eliminar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
