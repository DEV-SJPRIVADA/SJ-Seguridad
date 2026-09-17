<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Editar solicitud</h3>
                    <p class="panel-text">{{ $developmentRequest->code ?: 'Sin codigo (borrador / devuelta)' }} — {{ $developmentRequest->estadoLabel() }}</p>
                </div>
                <div class="panel__body">
                    <form
                        action="{{ route('development-requests.update', ['module' => $module, 'development_request' => $developmentRequest]) }}"
                        method="POST"
                        enctype="multipart/form-data"
                    >
                        @csrf
                        @method('PATCH')
                        @include('modules.development-requests.partials.form-fields', [
                            'developmentRequest' => $developmentRequest,
                            'defaultRequester' => [],
                        ])
                        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                            <button type="submit" name="action" value="draft" class="btn btn--secondary">Guardar</button>
                            <button type="submit" name="action" value="submit" class="btn btn--primary">Enviar / radicar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
