<x-app-layout>
    <x-slot name="header">
        <div class="app-container" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
            <h2 class="panel-title" style="margin:0;">Editar servicio</h2>
            <p class="panel-text" style="margin:0.25rem 0 0;">
                {{ $service->client?->name ?: 'Cliente' }} · {{ $service->contract_number ?: 'Sin contrato' }}
            </p>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <form method="POST" action="{{ route('comercial.matriz.services.update', $service) }}" class="panel__body form-stack">
                    @csrf
                    @method('PATCH')
                    @include('areas.comercial.matriz-clientes.partials.service-fields')
                    <div class="form-actions">
                        <a href="{{ route('comercial.matriz.services.index') }}" class="btn btn--secondary">Volver</a>
                        <x-primary-button>Guardar cambios</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .client-picker-results {
                margin-top: 0.4rem;
                border: 1px solid #d0d7de;
                border-radius: 0.5rem;
                background: #fff;
                max-height: 240px;
                overflow: auto;
            }
            .client-picker-results__item {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.15rem;
                width: 100%;
                padding: 0.65rem 0.85rem;
                border: 0;
                border-bottom: 1px solid #eef1f4;
                background: transparent;
                text-align: left;
                cursor: pointer;
            }
            .client-picker-results__item:last-child { border-bottom: 0; }
            .client-picker-results__item:hover { background: rgba(0, 51, 102, 0.06); }
            .client-picker-results__item span { color: #5b6570; font-size: 0.875rem; }
            .client-picker-selected__card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                padding: 0.85rem 1rem;
                border: 1px solid #c9d6e5;
                border-radius: 0.5rem;
                background: rgba(0, 51, 102, 0.04);
            }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ asset('js/comercial-client-picker.js') }}?v={{ @filemtime(public_path('js/comercial-client-picker.js')) ?: time() }}"></script>
    @endpush
</x-app-layout>
