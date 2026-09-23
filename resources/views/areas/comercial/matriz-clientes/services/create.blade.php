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
                                <span class="comercial-form__meta-label">Accion</span>
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
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de enviar</h3>
                            <p class="panel-text">Checklist rapido de alta.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="comercial-form-guide__list">
                                <li class="comercial-form-guide__item">Seleccione el cliente correcto por NIT o razon social.</li>
                                <li class="comercial-form-guide__item">Defina portafolio y tipo de servicio.</li>
                                <li class="comercial-form-guide__item">Registre fechas de vigencia para alertas automaticas.</li>
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
