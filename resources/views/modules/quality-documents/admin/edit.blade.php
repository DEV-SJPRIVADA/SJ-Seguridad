<x-app-layout>
    <x-slot name="header">
        @include('modules.quality-documents.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Editar documento</h3>
                    <p class="panel-text">Actualiza la informacion, areas asignadas o reemplaza el archivo.</p>
                </div>

                <div class="panel__body">
                    <form action="{{ route('quality-documents.admin.update', ['module' => $module, 'qualityDocument' => $document->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        @include('modules.quality-documents.partials.form-fields', [
                            'areas' => $areas,
                            'documentTypes' => $documentTypes,
                            'users' => $users,
                            'document' => $document,
                            'selectedAreas' => $selectedAreas,
                            'selectedUsers' => $selectedUsers,
                        ])

                        <div class="form-actions block-spaced">
                            <a href="{{ route('quality-documents.admin.index', ['module' => $module]) }}" class="btn btn--secondary">Cancelar</a>
                            <button type="submit" class="btn btn--primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
