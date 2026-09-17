<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page">
        <div class="app-container">
            <div class="panel development-requests-page__panel">
                <div class="panel__header">
                    <h3 class="panel-title">{{ $title ?? 'Solicitudes de desarrollo' }}</h3>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
