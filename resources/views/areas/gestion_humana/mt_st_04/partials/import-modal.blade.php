{{-- Variables: $canEdit, $importTemplateUrl, $importUrl, $show --}}
@php
    $show = (bool) ($show ?? false);
@endphp
<x-modal name="mt-st-04-import" maxWidth="md" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Importar matriz MT-ST-04</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Upsert por cédula. Si la cédula está en Ficha, nombre y cargo se leen de ahí; si no, igual se importa y queda marcada como Sin Ficha.
                        Si hay cédulas duplicadas en el archivo, la última fila gana.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                title="Cerrar"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'mt-st-04-import')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->has('import_file'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">{{ $errors->first('import_file') }}</div>
        @endif

        <div class="ficha-empleados-masivos-modal__cards">
            <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                <div class="ficha-empleados-masivos-modal__card-head">
                    <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                        <x-lucide-upload width="18" height="18" aria-hidden="true" />
                    </span>
                    <div>
                        <h4 class="ficha-empleados-masivos-modal__card-title">Plantilla e importar</h4>
                        <p class="ficha-empleados-masivos-modal__card-note">
                            Descargue la plantilla, complete CEDULA, ARMA, fechas de examen, APTO y observaciones.
                            No incluya ESTADO ni vencimientos (se calculan al importar).
                        </p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__import">
                    <a
                        href="{{ $importTemplateUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Descargar plantilla vacía"
                        aria-label="Descargar plantilla vacía"
                    >
                        <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                    </a>

                    <form
                        method="POST"
                        action="{{ $importUrl }}"
                        enctype="multipart/form-data"
                        class="ficha-empleados-masivos-modal__import-form"
                        data-mt-st-04-import-form
                    >
                        @csrf
                        <input
                            type="file"
                            id="mt-st-04-import-file"
                            name="import_file"
                            accept=".xlsx,.xls,.csv"
                            class="ficha-empleados-masivos-modal__file-input"
                            required
                            hidden
                            data-mt-st-04-import-file
                        >
                        <span class="ficha-empleados-masivos-modal__file-name" data-mt-st-04-import-name>Sin archivo seleccionado</span>
                        <div class="ficha-empleados-masivos-modal__import-actions">
                            <label
                                for="mt-st-04-import-file"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Elegir archivo"
                                aria-label="Elegir archivo"
                            >
                                <x-lucide-file-up width="18" height="18" aria-hidden="true" />
                            </label>
                            <button
                                type="submit"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                title="Importar"
                                aria-label="Importar"
                                data-mt-st-04-import-submit
                                disabled
                            >
                                <x-lucide-upload width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="ficha-empleados-masivos-modal__loading" data-mt-st-04-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Procesando filas. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
