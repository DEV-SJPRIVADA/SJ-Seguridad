<x-app-layout>
    <div class="page-section">
        <div class="app-container">
            @include('requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Nueva requisicion</h3>
                    <p class="panel-text">La solicitud se registra sobre tu area base y luego solo gestion humana podra modificarla.</p>
                </div>

                <form method="POST" action="{{ route('requisitions.store', ['module' => $moduleKey]) }}" class="panel__body form-stack">
                    @csrf

                    @include('requisitions.partials.form-fields', [
                        'moduleLabel' => $moduleLabel,
                        'requisition' => null,
                        'showHumanResourcesFields' => false,
                        'areaOptions' => $areaOptions,
                        'catalogs' => $catalogs,
                        'sexOptions' => $sexOptions,
                        'statusLabels' => [],
                    ])

                    <div class="form-actions">
                        <p class="text-small text-muted">Verifica cliente, motivo y centro de costo antes de enviar la solicitud.</p>
                        <div class="form-actions__group">
                            <a href="{{ route('requisitions.dashboard', ['module' => $moduleKey]) }}" class="btn btn--secondary">Volver</a>
                            <x-primary-button>Generar solicitud</x-primary-button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
