{{-- Variables: $filters --}}
<x-modal name="archivo-export-scope" maxWidth="lg" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-file-spreadsheet width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Exportar archivo</h3>
                    <p class="ficha-empleados-masivos-modal__lead">Descargue la plantilla con datos de empleados en ficha.</p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'archivo-export-scope')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        <div class="ficha-empleados-masivos-modal__cards">
            <section class="ficha-empleados-masivos-modal__card">
                @include('areas.gestion_humana.archivo.partials.export-scope-actions', [
                    'filters' => $filters ?? [],
                ])
            </section>
        </div>
    </div>
</x-modal>
