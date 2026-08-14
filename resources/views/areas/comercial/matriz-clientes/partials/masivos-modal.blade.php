@props(['filters', 'canManage', 'show' => false])

<x-modal name="comercial-masivos" maxWidth="lg" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-icon name="file-spreadsheet" :size="20" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Carga masiva comercial</h3>
                    <p class="ficha-empleados-masivos-modal__lead">Clientes, servicios y checklist documental en una sola plantilla.</p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'comercial-masivos')"
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

        @if ($canManage)
            <div class="ficha-empleados-masivos-modal__cards">
                <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                    <div class="ficha-empleados-masivos-modal__card-head">
                        <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                            <x-lucide-icon name="upload" :size="18" />
                        </span>
                        <div>
                            <h4 class="ficha-empleados-masivos-modal__card-title">Plantilla e importacion</h4>
                            <p class="ficha-empleados-masivos-modal__card-note">Una fila por servicio. Incluye datos del cliente y estados del checklist.</p>
                        </div>
                    </div>

                    <div class="ficha-empleados-masivos-modal__import">
                        <a href="{{ route('comercial.matriz.clients.import-template') }}" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                            <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                            Descargar plantilla vacia
                        </a>

                        <form method="GET" action="{{ route('comercial.matriz.clients.export-import-template') }}" class="ficha-empleados-masivos-modal__export ficha-empleados-masivos-modal__export--import">
                            <input type="hidden" name="q" value="{{ $filters['q'] ?? '' }}">
                            <input type="hidden" name="city" value="{{ $filters['city'] ?? '' }}">
                            @if ($filters['status'] ?? '')
                                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            @endif
                            <p class="ficha-empleados-masivos-modal__card-note ficha-empleados-masivos-modal__export-note">Exporte datos actuales (respeta filtros del listado), edite y vuelva a subir.</p>
                            <button type="submit" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action">
                                <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                                Exportar datos para actualizar
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('comercial.matriz.clients.import') }}"
                            enctype="multipart/form-data"
                            class="ficha-empleados-masivos-modal__import-form"
                            data-comercial-import-form
                        >
                            @csrf
                            @if ($filters['q'] ?? '')
                                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                            @endif
                            @if ($filters['city'] ?? '')
                                <input type="hidden" name="city" value="{{ $filters['city'] }}">
                            @endif
                            @if ($filters['status'] ?? '')
                                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                            @endif
                            <input
                                type="file"
                                id="comercial-import-file"
                                name="import_file"
                                accept=".xlsx"
                                class="ficha-empleados-masivos-modal__file-input"
                                required
                                hidden
                                data-comercial-import-file
                            >
                            <span class="ficha-empleados-masivos-modal__file-name" data-comercial-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions">
                                <label for="comercial-import-file" class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action" data-comercial-import-choose>
                                    <x-lucide-icon name="upload" :size="15" />
                                    Elegir archivo
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm ficha-empleados-masivos-modal__action ficha-empleados-masivos-modal__action--primary" data-comercial-import-submit disabled>
                                    <x-lucide-icon name="upload" :size="15" />
                                    Importar
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        @endif

        <div class="ficha-empleados-masivos-modal__loading" data-comercial-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Procesando filas, esto puede tardar unos minutos. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
