<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:1rem; flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title">Documentacion del modulo</h3>
                            <p class="panel-text">Versiones internas de procedimientos y soportes de indicadores.</p>
                        </div>
                        <a href="{{ route('indicadores.admin.documents.create') }}" class="btn btn--primary">Nuevo documento</a>
                    </div>
                </div>
                <div class="panel__body">
                    <table class="supply-table js-datatable">
                        <thead>
                            <tr>
                                <th>Titulo</th>
                                <th>Slug</th>
                                <th>Alcance</th>
                                <th>Indicador</th>
                                <th>Version</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td>{{ $document->title }}</td>
                                    <td>{{ $document->slug }}</td>
                                    <td>{{ $document->scope }}</td>
                                    <td>{{ $document->indicator?->code ?? 'General' }}</td>
                                    <td>{{ $document->currentVersion?->version_number ? 'v'.$document->currentVersion->version_number : '-' }}</td>
                                    <td style="display:flex; gap:0.5rem; justify-content:center; flex-wrap:wrap;">
                                        <a href="{{ route('indicadores.admin.documents.show', $document) }}" class="btn btn--secondary btn--sm">Ver</a>
                                        <a href="{{ route('indicadores.admin.documents.edit', $document) }}" class="btn btn--secondary btn--sm">Editar</a>
                                        <form method="POST" action="{{ route('indicadores.admin.documents.destroy', $document) }}" onsubmit="return confirm('Eliminar documento?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn--secondary btn--sm">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No hay documentos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $documents->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
