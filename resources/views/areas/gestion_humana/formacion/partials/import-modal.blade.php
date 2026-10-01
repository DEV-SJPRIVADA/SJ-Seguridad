{{-- Variables: $canEdit, $importTemplateUrl, $importUrl, $show --}}
<x-modal name="formacion-import" maxWidth="md" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal formacion-import-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Importar formaciones</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        La carga <strong>reemplaza todos</strong> los registros actuales.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                title="Cerrar"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'formacion-import')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->has('import_file') || $errors->has('confirm_replace'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                {{ $errors->first('import_file') ?: $errors->first('confirm_replace') }}
            </div>
        @endif

        <div class="ficha-empleados-masivos-modal__cards">
            <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                <div class="ficha-empleados-masivos-modal__card-head">
                    <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                        <x-lucide-replace width="18" height="18" aria-hidden="true" />
                    </span>
                    <div>
                        <h4 class="ficha-empleados-masivos-modal__card-title">Plantilla e importar</h4>
                        <p class="ficha-empleados-masivos-modal__card-note">
                            Excel con columnas: Número de ID, Nombre completo, Fecha de inicio del curso, Nombre completo del curso, Calificación, Nombre de la categoría.
                        </p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__import">
                    <form
                        method="POST"
                        action="{{ $importUrl }}"
                        enctype="multipart/form-data"
                        class="ficha-empleados-masivos-modal__import-form"
                        data-formacion-import-form
                    >
                        @csrf
                        <input
                            type="file"
                            id="formacion-import-file"
                            name="import_file"
                            accept=".xlsx,.xls,.csv"
                            class="ficha-empleados-masivos-modal__file-input"
                            required
                            hidden
                            data-formacion-import-file
                        >

                        <div class="formacion-import-modal__toolbar">
                            <a
                                href="{{ $importTemplateUrl }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Descargar plantilla vacía"
                                aria-label="Descargar plantilla vacía"
                            >
                                <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                            </a>
                            <span class="ficha-empleados-masivos-modal__file-name formacion-import-modal__file-name" data-formacion-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions formacion-import-modal__actions">
                                <label
                                    for="formacion-import-file"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Elegir archivo"
                                    aria-label="Elegir archivo"
                                    data-formacion-import-choose
                                >
                                    <x-lucide-file-up width="18" height="18" aria-hidden="true" />
                                </label>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Importar (reemplaza todos)"
                                    aria-label="Importar"
                                    data-formacion-import-submit
                                    disabled
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>

                        <label class="formacion-import-modal__confirm">
                            <input
                                type="checkbox"
                                name="confirm_replace"
                                value="1"
                                class="formacion-import-modal__checkbox"
                                data-formacion-import-confirm
                                @checked(old('confirm_replace'))
                            >
                            <span class="formacion-import-modal__confirm-text">
                                Confirmo que se borrarán todos los registros actuales
                            </span>
                        </label>
                    </form>
                </div>
            </section>
        </div>

        <div class="ficha-empleados-masivos-modal__loading" data-formacion-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Validando filas y reemplazando el dataset. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
