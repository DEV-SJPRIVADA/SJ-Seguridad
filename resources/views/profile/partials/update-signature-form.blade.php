{{-- Firma manuscrita PNG: solo el dueño de la cuenta la carga o la borra. --}}
<section class="profile-section">
    <header class="profile-section__header">
        <div class="profile-section__icon" aria-hidden="true">
            <x-lucide-pencil width="20" height="20" />
        </div>
        <div>
            <h2 class="profile-section__title">Firma manuscrita</h2>
            <p class="profile-section__desc">
                Sube un PNG de tu firma (fondo transparente recomendado). Se usará en documentos
                autorizados, como la orden de compra.
            </p>
        </div>
    </header>

    <div class="profile-signature">
        <div class="profile-signature__preview" aria-label="Vista previa de la firma">
            @if ($signatureDataUri)
                <x-user-signature :src="$signatureDataUri" height="80" alt="Firma actual" />
            @else
                <p class="profile-signature__empty">Aún no has subido una firma.</p>
            @endif
        </div>

        <form
            method="post"
            action="{{ route('profile.signature.update') }}"
            enctype="multipart/form-data"
            class="form-stack profile-form__stack"
        >
            @csrf

            <div class="form-field">
                <x-input-label for="signature" value="Archivo PNG" />
                <input
                    id="signature"
                    name="signature"
                    type="file"
                    class="form-input"
                    accept="image/png,.png"
                    required
                >
                <p class="profile-signature__hint">Solo PNG. Máximo 2 MB.</p>
                <x-input-error :messages="$errors->get('signature')" />
            </div>

            <div class="content-actions content-actions--end profile-form__actions">
                <button
                    type="submit"
                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                    title="Guardar firma"
                    aria-label="Guardar firma"
                >
                    <x-lucide-save width="18" height="18" aria-hidden="true" />
                </button>

                @if (session('status') === 'signature-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2500)"
                        class="inline-feedback inline-feedback--success"
                    >Firma guardada.</p>
                @endif

                @if (session('status') === 'signature-deleted')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2500)"
                        class="inline-feedback inline-feedback--success"
                    >Firma eliminada.</p>
                @endif
            </div>
        </form>

        @if ($signatureDataUri)
            <form
                method="post"
                action="{{ route('profile.signature.destroy') }}"
                class="profile-signature__delete"
                onsubmit="return confirm('¿Quitar tu firma? Los documentos ya autorizados conservan la copia de ese momento.');"
            >
                @csrf
                @method('delete')
                <button
                    type="submit"
                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--danger"
                    title="Quitar firma"
                    aria-label="Quitar firma"
                >
                    <x-lucide-trash-2 width="18" height="18" aria-hidden="true" />
                </button>
            </form>
        @endif
    </div>
</section>
