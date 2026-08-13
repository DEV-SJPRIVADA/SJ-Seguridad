{{-- Variables: $filters, $canManage, $show --}}
<x-modal name="ficha-masivos" maxWidth="lg" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-icon name="users" :size="20" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Plantilla masivos</h3>
                    <p class="ficha-empleados-masivos-modal__lead">Exportar empleados en ficha o importar actualizaciones masivas.</p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'ficha-masivos')"
            >
                <x-lucide-icon name="x" :size="18" />
            </button>
        </div>

        @if ($errors->has('export'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">{{ $errors->first('export') }}</div>
        @endif

        @if ($errors->has('import_file'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">{{ $errors->first('import_file') }}</div>
        @endif

        <div class="ficha-empleados-masivos-modal__cards">
            <section class="ficha-empleados-masivos-modal__card">
                <div class="ficha-empleados-masivos-modal__card-head">
                    <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--export" aria-hidden="true">
                        <x-lucide-icon name="file-spreadsheet" :size="18" />
                    </span>
                    <div>
                        <h4 class="ficha-empleados-masivos-modal__card-title">Exportar</h4>
                        <p class="ficha-empleados-masivos-modal__card-note">Sin rango de fechas se exportan solo empleados activos.</p>
                    </div>
                </div>

                <form method="GET" action="{{ route('gestion-humana.ficha-empleados.employees.export') }}" class="ficha-empleados-masivos-modal__export">
                    <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                    <div class="ficha-empleados-masivos-modal__dates">
                        <div class="form-field">
                            <label class="form-label" for="ficha-export-desde">Desde</label>
                            <input type="date" id="ficha-export-desde" name="fecha_desde" class="form-input" value="{{ request('fecha_desde') }}">
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="ficha-export-hasta">Hasta</label>
                            <input type="date" id="ficha-export-hasta" name="fecha_hasta" class="form-input" value="{{ request('fecha_hasta') }}">
                        </div>
                    </div>
                    <button type="submit" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                        <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                        Exportar plantilla
                    </button>
                </form>
            </section>

            @if ($canManage)
                <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                    <div class="ficha-empleados-masivos-modal__card-head">
                        <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                            <x-lucide-icon name="upload" :size="18" />
                        </span>
                        <div>
                            <h4 class="ficha-empleados-masivos-modal__card-title">Importar</h4>
                            <p class="ficha-empleados-masivos-modal__card-note">Cargue un archivo .xlsx con la plantilla de empleados.</p>
                        </div>
                    </div>

                    <div class="ficha-empleados-masivos-modal__import">
                        <a href="{{ route('gestion-humana.ficha-empleados.employees.import-template') }}" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                            <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                            Descargar plantilla vacia
                        </a>

                        <form method="GET" action="{{ route('gestion-humana.ficha-empleados.employees.export-import-template') }}" class="ficha-empleados-masivos-modal__export ficha-empleados-masivos-modal__export--import">
                            <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                            <p class="ficha-empleados-masivos-modal__card-note ficha-empleados-masivos-modal__export-note">Exporte datos actuales en formato importable, edite y vuelva a subir.</p>
                            <div class="ficha-empleados-masivos-modal__dates">
                                <div class="form-field">
                                    <label class="form-label" for="ficha-import-export-desde">Desde</label>
                                    <input type="date" id="ficha-import-export-desde" name="fecha_desde" class="form-input" value="{{ request('fecha_desde') }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="ficha-import-export-hasta">Hasta</label>
                                    <input type="date" id="ficha-import-export-hasta" name="fecha_hasta" class="form-input" value="{{ request('fecha_hasta') }}">
                                </div>
                            </div>
                            <button type="submit" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                                <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                                Exportar datos para actualizar
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('gestion-humana.ficha-empleados.employees.import') }}"
                            enctype="multipart/form-data"
                            class="ficha-empleados-masivos-modal__import-form"
                            data-ficha-import-form
                        >
                            @csrf
                            <input
                                type="file"
                                id="ficha-import-file"
                                name="import_file"
                                accept=".xlsx"
                                class="ficha-empleados-masivos-modal__file-input"
                                required
                                hidden
                                data-ficha-import-file
                            >
                            <span class="ficha-empleados-masivos-modal__file-name" data-ficha-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions">
                                <label for="ficha-import-file" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action" data-ficha-import-choose>
                                    <x-lucide-icon name="upload" :size="15" />
                                    Elegir archivo
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm ficha-empleados-masivos-modal__action ficha-empleados-masivos-modal__action--primary" data-ficha-import-submit disabled>
                                    <x-lucide-icon name="upload" :size="15" />
                                    Importar
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            @endif
        </div>

        <div class="ficha-empleados-masivos-modal__loading" data-ficha-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Procesando filas, esto puede tardar unos minutos. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
