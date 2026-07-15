<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Nuevo cliente</h2>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <form method="POST" action="{{ route('comercial.matriz.clients.store') }}" class="panel__body form-stack">
                    @csrf
                    @include('areas.comercial.matriz-clientes.partials.client-fields', ['client' => $client])
                    <div class="form-actions">
                        <a href="{{ route('comercial.matriz.clients.index') }}" class="btn btn--secondary">Cancelar</a>
                        <x-primary-button>Guardar cliente</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
