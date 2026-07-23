<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container indicadores-board">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Ajustes de indicadores</h3>
                    <p class="panel-text">Periodos de captura, metas por indicador y registro de auditoria.</p>
                </div>
                <div class="panel__body">
                    <nav class="indicadores-section-tabs" aria-label="Secciones de ajustes">
                        <a href="{{ route('indicadores.admin.ajustes', ['section' => 'periodos']) }}"
                           class="indicadores-section-tab {{ $section === 'periodos' ? 'indicadores-section-tab--active' : '' }}">
                            Periodos
                        </a>
                        <a href="{{ route('indicadores.admin.ajustes', ['section' => 'metas']) }}"
                           class="indicadores-section-tab {{ $section === 'metas' ? 'indicadores-section-tab--active' : '' }}">
                            Metas
                        </a>
                        <a href="{{ route('indicadores.admin.ajustes', ['section' => 'auditoria']) }}"
                           class="indicadores-section-tab {{ $section === 'auditoria' ? 'indicadores-section-tab--active' : '' }}">
                            Logs
                        </a>
                    </nav>

                    <div class="indicadores-section-content">
                        @if ($section === 'periodos')
                            @include('areas.operaciones.ajustes.partials.periodos')
                        @elseif ($section === 'metas')
                            @include('areas.operaciones.ajustes.partials.metas')
                        @else
                            @include('areas.operaciones.ajustes.partials.auditoria')
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
