<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">{{ $pageTitle }}</h2>
                <p class="panel-text">{{ $pageDescription }}</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section">
        <div class="app-container section-stack">
            <div class="panel">
                <div class="panel__body">
                    <p class="panel-text">Próximamente</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
