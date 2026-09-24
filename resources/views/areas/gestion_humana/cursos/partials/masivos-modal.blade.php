{{-- Variables: $filters, $canEdit, $exportUrl, $importTemplateUrl, $importUrl, $show --}}
<x-modal name="cursos-masivos" maxWidth="lg" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-graduation-cap width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Plantilla masivos</h3>
                    <p class="ficha-empleados-masivos-modal__lead">Importar cursos por Excel o exportar el listado filtrado.</p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'cursos-masivos')"
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
                        <p class="ficha-empleados-masivos-modal__card-note">Respeta los filtros actuales de la pantalla (incluye vigencia calculada).</p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__export">
                    <a href="{{ $exportUrl }}" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                        <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                        Exportar listado
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
                            <p class="ficha-empleados-masivos-modal__card-note">Alta por cédula + No.CURSO. La escuela se deduce del No.CURSO (digitos a la izquierda del guion, ej. ECSP0015-M256412 → codigo 15). Renovación: use No.CURSO ANTERIOR para ubicar el registro (si no hay match, la fila falla; no crea duplicados). La cédula debe existir en Ficha; el nombre se toma de Ficha. No incluye documentos.</p>
                        </div>
                    </div>

                    <div class="ficha-empleados-masivos-modal__import">
                        <a href="{{ $importTemplateUrl }}" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                            <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                            Descargar plantilla vacía
                        </a>

                        <form
                            method="POST"
                            action="{{ $importUrl }}"
                            enctype="multipart/form-data"
                            class="ficha-empleados-masivos-modal__import-form"
                            data-cursos-import-form
                        >
                            @csrf
                            <input
                                type="file"
                                id="cursos-import-file"
                                name="import_file"
                                accept=".xlsx,.xls,.csv"
                                class="ficha-empleados-masivos-modal__file-input"
                                required
                                hidden
                                data-cursos-import-file
                            >
                            <span class="ficha-empleados-masivos-modal__file-name" data-cursos-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions">
                                <label for="cursos-import-file" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action" data-cursos-import-choose>
                                    <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                    Elegir archivo
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm ficha-empleados-masivos-modal__action ficha-empleados-masivos-modal__action--primary" data-cursos-import-submit disabled>
                                    <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                    Importar
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <div class="ficha-empleados-masivos-modal__loading" data-cursos-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Procesando filas. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
