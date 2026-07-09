<x-app-layout>
    <x-slot name="header">
        @include('modules.quality-documents.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Nuevo documento</h3>
                    <p class="panel-text">Sube un archivo Word/Excel o registra un enlace externo.</p>
                </div>

                <div class="panel__body">
                    <form action="{{ route('quality-documents.admin.store', ['module' => $module]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @include('modules.quality-documents.partials.form-fields', [
                            'areas' => $areas,
                            'catalogs' => $catalogs,
                            'users' => $users,
                            'selectedUsers' => $selectedUsers,
                        ])

                        <div class="form-actions block-spaced">
                            <a href="{{ route('quality-documents.admin.index', ['module' => $module]) }}" class="btn btn--secondary">Cancelar</a>
                            <button type="submit" class="btn btn--primary">Publicar documento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
