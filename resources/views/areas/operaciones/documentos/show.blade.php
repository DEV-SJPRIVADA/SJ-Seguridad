<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel" style="margin-bottom:1.5rem;">
                <div class="panel__header">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">{{ $document->title }}</h3>
                            <p class="panel-text">Slug: {{ $document->slug }} | Alcance: {{ $document->scope }}</p>
                        </div>
                        <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <a href="{{ route('indicadores.admin.documents.edit', $document) }}" class="btn btn--secondary btn--sm">Editar metadatos</a>
                            <a href="{{ route('indicadores.admin.documents.index') }}" class="btn btn--secondary btn--sm">Volver</a>
                        </div>
                    </div>
                </div>
                <div class="panel__body">
                    <p><strong>Indicador:</strong> {{ $document->indicator?->code ?? 'General' }}</p>
                    <p><strong>Version vigente:</strong> {{ $document->currentVersion?->version_number ? 'v'.$document->currentVersion->version_number : '-' }}</p>
                    @if ($document->currentVersion)
                        <div class="panel" style="margin-top:1rem; background:#f8fafc;">
                            <div class="panel__body">
                                <pre style="white-space:pre-wrap; font-family:inherit; margin:0;">{{ $document->currentVersion->content }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="panel" style="margin-bottom:1.5rem;">
                <div class="panel__header"><h4 class="panel-title">Nueva version</h4></div>
                <div class="panel__body">
                    <form method="POST" action="{{ route('indicadores.admin.documents.versions.store', $document) }}" class="form-stack">
                        @csrf
                        <div>
                            <label class="form-label">Estado</label>
                            <select name="status" class="supply-input supply-select">
                                <option value="draft">draft</option>
                                <option value="vigente">vigente</option>
                                <option value="archivado">archivado</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Contenido</label>
                            <textarea name="content" class="supply-textarea" rows="10" required>{{ old('content', $document->currentVersion?->content) }}</textarea>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">
                            <div>
                                <label class="form-label">Resumen del cambio</label>
                                <input type="text" name="change_summary" class="supply-input" required value="{{ old('change_summary') }}">
                            </div>
                            <div>
                                <label class="form-label">Motivo del cambio</label>
                                <input type="text" name="change_reason" class="supply-input" required value="{{ old('change_reason') }}">
                            </div>
                        </div>
                        <button type="submit" class="btn btn--primary">Crear version</button>
                    </form>
                </div>
            </div>

            <div class="panel">
                <div class="panel__header"><h4 class="panel-title">Historial de versiones</h4></div>
                <div class="panel__body">
                    <table class="supply-table">
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Estado</th>
                                <th>Autor</th>
                                <th>Resumen</th>
                                <th>Motivo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($document->versions->sortByDesc('version_number') as $version)
                                <tr>
                                    <td>v{{ $version->version_number }}</td>
                                    <td>{{ $version->status }}</td>
                                    <td>{{ $version->author?->name ?? '-' }}</td>
                                    <td>{{ $version->change_summary }}</td>
                                    <td>{{ $version->change_reason }}</td>
                                    <td>{{ $version->created_at?->format('Y-m-d H:i') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
