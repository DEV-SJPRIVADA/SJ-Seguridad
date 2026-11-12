<x-app-layout>
    <x-slot name="header">
        @include('areas.comercial.partials.gestion-clientes-subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section comercial-services-page comercial-services-page--form">
        <div class="app-container">
            <div class="comercial-detail__toolbar">
                <a href="{{ route('comercial.matriz.services.index') }}" class="comercial-detail__back">
                    <x-lucide-arrow-left width="16" height="16" aria-hidden="true" />
                    Volver a servicios
                </a>
            </div>

            <div class="comercial-form-layout">
                <div class="comercial-form-layout__main">
                    <form method="POST" action="{{ route('comercial.matriz.services.store') }}" class="form-stack">
                        @csrf

                        <div class="comercial-form__meta">
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Acción</span>
                                <span class="comercial-form__meta-value">Nuevo servicio</span>
                            </div>
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Estado</span>
                                <span class="comercial-form__meta-value">Alta comercial</span>
                            </div>
                        </div>

                        @include('areas.comercial.matriz-clientes.partials.service-fields')

                        <div class="comercial-form-actions">
                            <p class="comercial-form-actions__note">
                                Busque el cliente, complete contrato y vigencia, luego guarde.
                            </p>
                            <div class="comercial-form-actions__group">
                                <a href="{{ route('comercial.matriz.services.index') }}" class="btn btn--secondary">Cancelar</a>
                                <x-primary-button>Guardar servicio</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="comercial-form-aside">
                    {{-- Checklist de alta del servicio. --}}
                    <x-form-aside-guide
                        title="Antes de enviar"
                        subtitle="Checklist rápido de alta."
                        icon="list-checks"
                        :items="[
                            'Seleccione el cliente correcto por NIT o razón social.',
                            'Defina portafolio y tipo de servicio.',
                            'Registre fechas de vigencia para alertas automáticas.',
                        ]"
                    />
                </aside>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/comercial-client-picker.js') }}?v={{ @filemtime(public_path('js/comercial-client-picker.js')) ?: time() }}"></script>
    @endpush
</x-app-layout>
