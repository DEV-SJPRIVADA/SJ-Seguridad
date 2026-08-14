{{-- Variables: $consultationTypes, $show --}}
<x-modal name="archivo-consult" maxWidth="2xl" :show="$show ?? false" focusable>
    <div class="modal-card ficha-empleados-masivos-modal archivo-consult-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-search width="20" height="20" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Consulta multiple de archivo</h3>
                    <p class="ficha-empleados-masivos-modal__lead">Ingrese varias cedulas y seleccione el motivo de consulta. Se registrara el historial y se filtrara el listado.</p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'archivo-consult')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        <form
            method="POST"
            action="{{ route('gestion-humana.archivo.consult') }}"
            class="archivo-consult-modal__form"
            data-archivo-consult-form
        >
            @csrf

            <div
                class="alert alert--warning archivo-consult-modal__alert"
                data-archivo-consult-types-alert
                @unless ($errors->has('consultation_types') || $errors->has('consultation_types.*')) hidden @endunless
                role="alert"
            >
                Debe seleccionar un motivo de consulta.
            </div>

            <div class="archivo-consult-modal__grid">
                <div class="archivo-consult-modal__panel">
                    <label class="req-manage-filters__label" for="archivo-consult-documents">Cedulas a consultar</label>
                    <textarea
                        id="archivo-consult-documents"
                        name="documents"
                        class="form-input archivo-consult-modal__textarea"
                        rows="10"
                        placeholder="Una cedula por linea, o separadas por coma o punto y coma"
                        required
                    >{{ old('documents') }}</textarea>
                    <p class="archivo-consult-modal__hint">Ejemplo: 1234567890, 9876543210 (hasta varias cedulas a la vez).</p>
                    @error('documents')
                        <p class="archivo-page__inline-error">{{ $message }}</p>
                    @enderror
                </div>

                <fieldset class="archivo-consult-modal__panel archivo-consult-modal__types">
                    <legend class="req-manage-filters__label">Motivo de consulta</legend>
                    <div class="archivo-consult-modal__checks" data-archivo-consult-types>
                        @foreach ($consultationTypes as $key => $label)
                            <label class="archivo-consult-modal__check">
                                <input
                                    type="checkbox"
                                    name="consultation_types[]"
                                    value="{{ $key }}"
                                    @checked(in_array($key, old('consultation_types', []), true))
                                >
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('consultation_types')
                        <p class="archivo-page__inline-error">{{ $message }}</p>
                    @enderror
                </fieldset>
            </div>

            <div class="archivo-consult-modal__delivered">
                <label class="req-manage-filters__label" for="archivo-consult-delivered-to">Entregada a</label>
                <input
                    id="archivo-consult-delivered-to"
                    type="text"
                    name="delivered_to"
                    class="form-input"
                    maxlength="150"
                    value="{{ old('delivered_to') }}"
                    placeholder="Persona o area a quien se entrega"
                >
                @error('delivered_to')
                    <p class="archivo-page__inline-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="archivo-consult-modal__actions">
                <button
                    type="button"
                    class="btn btn--secondary btn--sm"
                    x-on:click="$dispatch('close-modal', 'archivo-consult')"
                >
                    Cancelar
                </button>
                <button type="submit" class="btn btn--primary btn--sm">
                    <x-lucide-search width="15" height="15" aria-hidden="true" />
                    Consultar
                </button>
            </div>
        </form>
    </div>
</x-modal>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.querySelector('[data-archivo-consult-form]');
            if (!form) {
                return;
            }

            var typesContainer = form.querySelector('[data-archivo-consult-types]');
            var typesAlert = form.querySelector('[data-archivo-consult-types-alert]');

            function hasSelectedType() {
                if (!typesContainer) {
                    return false;
                }

                return typesContainer.querySelectorAll('input[type="checkbox"]:checked').length > 0;
            }

            function showTypesRequiredNotice() {
                if (typesAlert) {
                    typesAlert.hidden = false;
                }

                if (typeof window.showToast === 'function') {
                    window.showToast('Debe seleccionar un motivo de consulta.', 'error');
                }
            }

            form.addEventListener('submit', function (event) {
                if (hasSelectedType()) {
                    if (typesAlert) {
                        typesAlert.hidden = true;
                    }

                    return;
                }

                event.preventDefault();
                showTypesRequiredNotice();
            });

            if (typesContainer) {
                typesContainer.addEventListener('change', function () {
                    if (hasSelectedType() && typesAlert) {
                        typesAlert.hidden = true;
                    }
                });
            }

            @if ($errors->has('consultation_types') || $errors->has('consultation_types.*'))
                if (typeof window.showToast === 'function') {
                    window.showToast('Debe seleccionar un motivo de consulta.', 'error');
                }
            @endif
        });
    </script>
@endpush
