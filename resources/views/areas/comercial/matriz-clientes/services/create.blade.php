<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Agregar servicio</h2>
            <p class="panel-text" style="margin:0.25rem 0 0;">{{ $client->name }} ({{ $client->nit }})</p>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <form method="POST" action="{{ route('comercial.matriz.services.store', $client) }}" class="panel__body form-stack">
                    @csrf
                    @include('areas.comercial.matriz-clientes.partials.service-fields')
                    <div class="form-actions">
                        <a href="{{ route('comercial.matriz.clients.show', $client) }}" class="btn btn--secondary">Cancelar</a>
                        <x-primary-button>Guardar servicio</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
