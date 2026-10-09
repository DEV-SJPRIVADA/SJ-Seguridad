<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="page-header-inner" style="padding-top: 0; margin-bottom: 1.25rem;">
                <h2 class="page-title">Solicitar personal</h2>
                <p class="page-subtitle">Completa las secciones del formulario. La solicitud queda registrada en tu área y solo Gestión Humana podrá modificarla después.</p>
            </div>

            <div class="req-form-layout">
                <div class="req-form-layout__main">
                    <form method="POST" action="{{ route('requisitions.store', ['module' => $moduleKey]) }}" class="form-stack">
                        @csrf

                        @include('modules.requisitions.partials.form-fields-requester', [
                            'moduleLabel' => $moduleLabel,
                            'areaOptions' => $areaOptions,
                            'catalogs' => $catalogs,
                            'sexOptions' => $sexOptions,
                            'clientSearchUrl' => $clientSearchUrl,
                            'selectedCommercialClient' => $selectedCommercialClient,
                        ])

                        <div class="req-form-actions">
                            <p class="req-form-actions__note">Revisa cliente, motivo y centro de costo antes de enviar. No podrás editar la solicitud una vez creada.</p>
                            <div class="req-form-actions__group">
                                <a
                                    href="{{ route('requisitions.dashboard', ['module' => $moduleKey]) }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cancelar"
                                    aria-label="Cancelar"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Generar solicitud"
                                    aria-label="Generar solicitud"
                                >
                                    <x-lucide-send width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de enviar</h3>
                            <p class="panel-text">Verifica estos puntos para evitar devoluciones.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="req-form-guide__list">
                                <li class="req-form-guide__item">Interno: personal administrativo; no requiere cliente de matriz.</li>
                                <li class="req-form-guide__item">Externo: debe elegir cliente por nombre o NIT.</li>
                                <li class="req-form-guide__item">Motivo alineado con la necesidad real del servicio.</li>
                                <li class="req-form-guide__item">Reemplazo: incluir datos del colaborador saliente.</li>
                                <li class="req-form-guide__item">Cargo o servicio nuevo: puede indicar cantidad de plazas.</li>
                                <li class="req-form-guide__item">Perfil detallado: funciones, turno y responsabilidades.</li>
                                <li class="req-form-guide__item">Centro de costo válido para facturación interna.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Qué pasa después</h3>
                        </div>
                        <div class="panel__body">
                            <p class="panel-text" style="margin: 0; line-height: 1.55;">
                                Gestión Humana recibe la solicitud, asigna reclutador y actualiza el estado.
                                Puedes consultar el estado desde Mis requisiciones de tu área.
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
