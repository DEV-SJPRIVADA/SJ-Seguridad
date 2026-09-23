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
                    <form method="POST" action="{{ route('comercial.matriz.services.update', $service) }}" class="form-stack">
                        @csrf
                        @method('PATCH')

                        <div class="comercial-form__meta">
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Accion</span>
                                <span class="comercial-form__meta-value">Editar servicio</span>
                            </div>
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Cliente</span>
                                <span class="comercial-form__meta-value">{{ $service->client?->name ?: '—' }}</span>
                            </div>
                            <div class="comercial-form__meta-item">
                                <span class="comercial-form__meta-label">Contrato</span>
                                <span class="comercial-form__meta-value">{{ $service->contract_number ?: 'Sin contrato' }}</span>
                            </div>
                        </div>

                        @include('areas.comercial.matriz-clientes.partials.service-fields')

                        <div class="comercial-form-actions">
                            <p class="comercial-form-actions__note">
                                Revise cliente, portafolio y vigencia antes de guardar.
                            </p>
                            <div class="comercial-form-actions__group">
                                <a href="{{ route('comercial.matriz.services.index') }}" class="btn btn--secondary">Cancelar</a>
                                <x-primary-button>Guardar cambios</x-primary-button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="comercial-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de guardar</h3>
                            <p class="panel-text">Evite inconsistencias en la matriz.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="comercial-form-guide__list">
                                <li class="comercial-form-guide__item">El cliente se selecciona por busqueda de nombre o NIT.</li>
                                <li class="comercial-form-guide__item">Portafolio y tipo de servicio deben coincidir con la operacion real.</li>
                                <li class="comercial-form-guide__item">Fin de contrato alimenta alertas de por vencer / vencido.</li>
                                <li class="comercial-form-guide__item">Inactivar el servicio se hace desde el detalle del cliente o listado.</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/comercial-client-picker.js') }}?v={{ @filemtime(public_path('js/comercial-client-picker.js')) ?: time() }}"></script>
    @endpush
</x-app-layout>
