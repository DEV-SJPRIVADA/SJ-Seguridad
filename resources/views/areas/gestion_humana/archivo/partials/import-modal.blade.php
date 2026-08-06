{{-- Variables: $canManage, $show --}}
<x-modal name="archivo-import" maxWidth="lg" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-icon name="file-spreadsheet" :size="20" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Importar ubicaciones de archivo</h3>
                    <p class="ficha-empleados-masivos-modal__lead">Actualice estantes y cajas desde el Excel exportado. Solo se modifican esos campos.</p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'archivo-import')"
            >
                <x-lucide-icon name="x" :size="18" />
            </button>
        </div>

        @if ($errors->has('import_file'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">{{ $errors->first('import_file') }}</div>
        @endif

        @if ($canManage)
            <div class="ficha-empleados-masivos-modal__cards">
                <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                    <div class="ficha-empleados-masivos-modal__card-head">
                        <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                            <x-lucide-icon name="upload" :size="18" />
                        </span>
                        <div>
                            <h4 class="ficha-empleados-masivos-modal__card-title">Importar estantes y cajas</h4>
                            <p class="ficha-empleados-masivos-modal__card-note">Use el Excel de <strong>Exportar archivo</strong> (Ficha empleados o este modulo). Edite columnas <strong>estantes</strong> y <strong>cajas</strong>; la cedula es obligatoria por fila.</p>
                        </div>
                    </div>

                    <div class="ficha-empleados-masivos-modal__import">
                        @if ($canExportArchive ?? false)
                            <x-export-excel
                                route="{{ route('gestion-humana.ficha-empleados.employees.export-archive-template') }}"
                                label="Descargar plantilla con datos"
                                class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action"
                            />
                        @endif

                        <form
                            method="POST"
                            action="{{ route('gestion-humana.archivo.import') }}"
                            enctype="multipart/form-data"
                            class="ficha-empleados-masivos-modal__import-form"
                            data-archivo-import-form
                        >
                            @csrf
                            <input
                                type="file"
                                id="archivo-import-file"
                                name="import_file"
                                accept=".xlsx"
                                class="ficha-empleados-masivos-modal__file-input"
                                required
                                hidden
                                data-archivo-import-file
                            >
                            <span class="ficha-empleados-masivos-modal__file-name" data-archivo-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions">
                                <label for="archivo-import-file" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                                    <x-lucide-icon name="upload" :size="15" />
                                    Elegir archivo
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm ficha-empleados-masivos-modal__action ficha-empleados-masivos-modal__action--primary" data-archivo-import-submit disabled>
                                    <x-lucide-icon name="upload" :size="15" />
                                    Importar
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        @endif

        <div class="ficha-empleados-masivos-modal__loading" data-archivo-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Actualizando estantes y cajas. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
