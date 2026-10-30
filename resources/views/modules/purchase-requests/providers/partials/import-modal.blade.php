{{-- Variables: $canEdit, $importTemplateUrl, $importUrl, $show --}}
<x-modal name="provider-import" maxWidth="lg" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Carga masiva VINE01</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Solo se cargan las filas con <strong>PROVEEDOR = SI</strong>; se actualizan por identificación.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                title="Cerrar"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'provider-import')"
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
                        <h4 class="ficha-empleados-masivos-modal__card-title">Plantilla e importación</h4>
                        <p class="ficha-empleados-masivos-modal__card-note">
                            Columnas: IDENTIFICA, NOMBRE, TELEFONO, CORREO_ELE, CONTACTO, DIRECCION, CIUDAD, FORMA_PAGO, PROVEEDOR, ESTADO y DIAS_PAGO (opcional).
                        </p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__import">
                    <form
                        method="POST"
                        action="{{ $importUrl }}"
                        enctype="multipart/form-data"
                        class="ficha-empleados-masivos-modal__import-form"
                        data-provider-import-form
                    >
                        @csrf

                        <input
                            type="file"
                            id="provider-import-file"
                            name="import_file"
                            accept=".xlsx,.xls,.csv"
                            class="ficha-empleados-masivos-modal__file-input"
                            hidden
                            data-provider-import-file
                        >

                        <div class="purchase-providers-page__import-toolbar">
                            <a
                                href="{{ $importTemplateUrl }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Descargar plantilla VINE01"
                                aria-label="Descargar plantilla VINE01"
                            >
                                <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                            </a>
                            <span class="ficha-empleados-masivos-modal__file-name purchase-providers-page__import-file" data-provider-import-name>Sin archivo seleccionado</span>
                            <div class="purchase-providers-page__import-actions">
                                <label
                                    for="provider-import-file"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Elegir archivo"
                                    aria-label="Elegir archivo"
                                >
                                    <x-lucide-file-up width="18" height="18" aria-hidden="true" />
                                </label>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Importar VINE01"
                                    aria-label="Importar VINE01"
                                    data-provider-import-submit
                                    disabled
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div
            class="ficha-empleados-masivos-modal__loading"
            data-provider-import-loading
            hidden
            aria-live="polite"
            aria-busy="true"
        >
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando VINE01</p>
                <p class="ficha-empleados-masivos-modal__loading-text">
                    Procesando filas, esto puede tardar unos minutos. No cierre esta ventana.
                </p>
            </div>
        </div>
    </div>
</x-modal>
