<x-app-layout>
    <x-slot name="header">
        @include('modules.quality-documents.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Mis documentos</h3>
                    <p class="panel-text">Documentos de Calidad asignados directamente a tu usuario.</p>
                </div>

                <div class="panel__body">
                    @if ($documents->isEmpty())
                        <p class="panel-text text-muted">No tienes documentos asignados en este momento.</p>
                    @else
                        <div class="block-spaced">
                            <table class="supply-table js-datatable">
                                <thead>
                                    <tr>
                                        <th>Codigo</th>
                                        <th>Titulo</th>
                                        <th>Proceso</th>
                                        <th>Tipo documento</th>
                                        <th>Recurso</th>
                                        <th>Publicado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($documents as $document)
                                        <tr>
                                            <td><strong>{{ $document->code ?? '—' }}</strong></td>
                                            <td>
                                                {{ $document->title }}
                                                @if ($document->description)
                                                    <p class="text-small text-muted block-spaced-sm">{{ $document->description }}</p>
                                                @endif
                                            </td>
                                            <td>{{ $document->processLabel() ?? '—' }}</td>
                                            <td>{{ $document->documentTypeLabel() ?? '—' }}</td>
                                            <td>{{ $document->isFile() ? 'Archivo' : 'Enlace' }}</td>
                                            <td>{{ $document->created_at->format('d/m/Y') }}</td>
                                            <td class="table-actions">
                                                @if ($document->isFile())
                                                    <a href="{{ route('quality-documents.mine.download', ['module' => $module, 'qualityDocument' => $document->id]) }}" class="btn btn--secondary btn--sm">
                                                        Descargar
                                                    </a>
                                                @else
                                                    <a href="{{ route('quality-documents.mine.open', ['module' => $module, 'qualityDocument' => $document->id]) }}" class="btn btn--info btn--sm" target="_blank" rel="noopener noreferrer">
                                                        Abrir enlace
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
