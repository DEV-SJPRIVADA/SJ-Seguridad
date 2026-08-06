<x-app-layout>
    <x-slot name="header">
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Archivo — {{ $entry->profile?->full_name ?: $entry->hired_full_name }}</h2>
                <p class="panel-text">Cedula {{ $entry->profile?->document_number ?: $entry->hired_document }}</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section archivo-page">
        <div class="app-container">
            <div class="panel">
                <div class="panel__body section-stack">
                    <div class="panel-heading-row panel-heading-row--wrap">
                        <div>
                            <h3 class="panel-title">Ubicacion documental</h3>
                            <p class="panel-text">Registre estante y caja fisica del expediente del empleado.</p>
                        </div>
                        <a href="{{ route('gestion-humana.archivo.index', array_filter(['q' => request('q')])) }}" class="btn btn--secondary btn--sm">Volver al listado</a>
                    </div>

                    <dl class="archivo-page__reference">
                        <div><dt>Requisicion</dt><dd>{{ $entry->requisitionCode() ?: '—' }}</dd></div>
                        <div><dt>Cargo</dt><dd>{{ $entry->positionName() ?: '—' }}</dd></div>
                        <div><dt>Cliente</dt><dd>{{ $entry->clientName() ?: '—' }}</dd></div>
                    </dl>

                    <form method="POST" action="{{ route('gestion-humana.archivo.update', $entry) }}" class="section-stack">
                        @csrf
                        @method('PATCH')
                        @if (request('q'))
                            <input type="hidden" name="q" value="{{ request('q') }}">
                        @endif

                        <div class="form-grid form-grid--2">
                            <div class="form-field">
                                <label class="form-label" for="archive_shelf">Estantes</label>
                                <input
                                    id="archive_shelf"
                                    name="archive_shelf"
                                    type="text"
                                    class="form-input"
                                    maxlength="100"
                                    value="{{ old('archive_shelf', $entry->profile?->archive_shelf) }}"
                                    placeholder="Ej. A-03"
                                >
                                @error('archive_shelf')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label class="form-label" for="archive_box">Cajas</label>
                                <input
                                    id="archive_box"
                                    name="archive_box"
                                    type="text"
                                    class="form-input"
                                    maxlength="100"
                                    value="{{ old('archive_box', $entry->profile?->archive_box) }}"
                                    placeholder="Ej. 12"
                                >
                                @error('archive_box')
                                    <p class="form-error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn--primary">Guardar ubicacion</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
