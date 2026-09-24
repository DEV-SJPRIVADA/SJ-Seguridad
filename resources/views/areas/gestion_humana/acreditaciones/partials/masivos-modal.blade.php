{{-- Variables: $canEdit, $exportUrl, $importTemplateUrl, $importUrl, $show --}}
<x-modal name="acreditaciones-masivos" maxWidth="lg" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Plantilla masivos</h3>
                    <p class="ficha-empleados-masivos-modal__lead">Importar acreditados por Excel o exportar el listado filtrado.</p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'acreditaciones-masivos')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->has('import_file'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">{{ $errors->first('import_file') }}</div>
        @endif

        <div class="ficha-empleados-masivos-modal__cards">
            <section class="ficha-empleados-masivos-modal__card">
                <div class="ficha-empleados-masivos-modal__card-head">
                    <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--export" aria-hidden="true">
                        <x-lucide-file-spreadsheet width="18" height="18" aria-hidden="true" />
                    </span>
                    <div>
                        <h4 class="ficha-empleados-masivos-modal__card-title">Exportar</h4>
                        <p class="ficha-empleados-masivos-modal__card-note">Respeta los filtros actuales de la pantalla.</p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__export">
                    <a
                        href="{{ $exportUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Exportar listado"
                        aria-label="Exportar listado"
                    >
                        <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                    </a>
                </div>
            </section>

            @if ($canEdit)
                <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                    <div class="ficha-empleados-masivos-modal__card-head">
                        <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                            <x-lucide-upload width="18" height="18" aria-hidden="true" />
                        </span>
                        <div>
                            <h4 class="ficha-empleados-masivos-modal__card-title">Importar</h4>
                            <p class="ficha-empleados-masivos-modal__card-note">
                                Upsert por cédula + CARGO APO. La cédula debe existir en Ficha; el nombre se toma de Ficha.
                                CARGO APO debe existir en el catálogo. Al menos una fecha (VIGEN.ACR o FECHA SOLICITUD).
                                No incluye columna ESTADO (se calcula automáticamente).
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
                            data-acreditaciones-import-form
                        >
                            @csrf
                            <input
                                type="file"
                                id="acreditaciones-import-file"
                                name="import_file"
                                accept=".xlsx,.xls,.csv"
                                class="ficha-empleados-masivos-modal__file-input"
                                required
                                hidden
                                data-acreditaciones-import-file
                            >
                            <span class="ficha-empleados-masivos-modal__file-name" data-acreditaciones-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions">
                                <label
                                    for="acreditaciones-import-file"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Elegir archivo"
                                    aria-label="Elegir archivo"
                                    data-acreditaciones-import-choose
                                >
                                    <x-lucide-file-up width="18" height="18" aria-hidden="true" />
                                </label>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Importar"
                                    aria-label="Importar"
                                    data-acreditaciones-import-submit
                                    disabled
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <div class="ficha-empleados-masivos-modal__loading" data-acreditaciones-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Procesando filas. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
