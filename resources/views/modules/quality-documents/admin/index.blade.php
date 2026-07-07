<x-app-layout>
    <x-slot name="header">
        @include('modules.quality-documents.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="panel-title">Administracion de Documentos</h3>
                            <p class="panel-text">Publica archivos o enlaces y define areas o usuarios que pueden consultarlos.</p>
                        </div>
                        <a href="{{ route('quality-documents.admin.create', ['module' => $module]) }}" class="btn btn--primary">
                            Nuevo documento
                        </a>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table js-datatable">
                            <thead>
                                <tr>
                                    <th>Codigo</th>
                                    <th>Titulo</th>
                                    <th>Tipo documento</th>
                                    <th>Proceso raiz</th>
                                    <th>Recurso</th>
                                    <th>Areas con acceso</th>
                                    <th>Usuarios</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documents as $document)
                                    <tr>
                                        <td><strong>{{ $document->code ?? '—' }}</strong></td>
                                        <td>
                                            {{ $document->title }}
                                            @if ($document->original_name)
                                                <p class="text-small text-muted block-spaced-sm">{{ $document->original_name }}</p>
                                            @endif
                                        </td>
                                        <td>{{ $document->documentTypeLabel() ?? '—' }}</td>
                                        <td>{{ $document->rootProcessLabel() ?? '—' }}</td>
                                        <td>{{ $document->isFile() ? 'Archivo' : 'Enlace' }}</td>
                                        <td>
                                            @forelse ($document->areas as $area)
                                                <span class="status-pill status-pill--info">{{ config("access.areas.{$area->area_key}") }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            @forelse ($document->assignedUsers as $assignment)
                                                <span class="status-pill status-pill--warning">{{ $assignment->user?->name ?? 'Usuario' }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            <span class="status-pill {{ $document->is_active ? 'status-pill--success' : 'status-pill--warning' }}">
                                                {{ $document->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="{{ route('quality-documents.admin.edit', ['module' => $module, 'qualityDocument' => $document->id]) }}" class="btn btn--secondary btn--sm">
                                                Editar
                                            </a>
                                            <form action="{{ route('quality-documents.admin.toggle', ['module' => $module, 'qualityDocument' => $document->id]) }}" method="POST" style="display: inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn--info btn--sm">
                                                    {{ $document->is_active ? 'Inactivar' : 'Activar' }}
                                                </button>
                                            </form>
                                            <form action="{{ route('quality-documents.admin.destroy', ['module' => $module, 'qualityDocument' => $document->id]) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este documento?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn--danger btn--sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-muted">No hay documentos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
