<x-app-layout>
    <div class="page-section">
        <div class="app-container dashboard-shell">
            <section class="panel dashboard-hero">
                <div class="panel__body">
                    @if ($selectedModule)
                        <div class="dashboard-hero__header">
                            <div>
                                <p class="eyebrow">Panel de trabajo</p>
                                <h2 class="page-title title-spaced">{{ $selectedModule['label'] }}</h2>
                                <p class="page-subtitle">Modulo habilitado por permisos directos de area o tableros autorizados.</p>
                            </div>

                            @if ($selectedBoard)
                                <div class="dashboard-badge-card">
                                    <span class="text-caption">Tablero activo</span>
                                    <strong>{{ $selectedBoard['label'] }}</strong>
                                </div>
                            @endif
                        </div>

                        <div class="dashboard-stat-grid block-spaced">
                            <article class="card card--muted">
                                <p class="text-caption">Visualizacion</p>
                                <p class="panel-title title-spaced">
                                    {{ ($selectedModule['can_view'] || $selectedModule['boards']->isNotEmpty()) ? 'Habilitada' : 'Sin acceso' }}
                                </p>
                                <p class="text-small text-muted block-spaced-sm">
                                    {{ ($selectedModule['can_view'] || $selectedModule['boards']->isNotEmpty()) ? 'Tu usuario puede consultar informacion del modulo.' : 'No existe permiso de consulta para este modulo.' }}
                                </p>
                            </article>

                            <article class="card card--muted">
                                <p class="text-caption">Gestion</p>
                                <p class="panel-title title-spaced">
                                    {{ $selectedModule['can_manage'] ? 'Habilitada' : 'Solo consulta' }}
                                </p>
                                <p class="text-small text-muted block-spaced-sm">
                                    {{ $selectedModule['can_manage'] ? 'Tu usuario puede operar funciones dentro del modulo.' : 'Las acciones operativas siguen restringidas.' }}
                                </p>
                            </article>

                            <article class="card card--info">
                                <p class="text-caption">Tableros visibles</p>
                                <p class="panel-title title-spaced">{{ $selectedModule['boards']->count() }}</p>
                                <p class="text-small text-small--info block-spaced-sm">
                                    {{ $selectedModule['boards']->isNotEmpty() ? 'Puedes cambiar entre tableros desde la franja superior.' : 'No hay tableros habilitados para este modulo.' }}
                                </p>
                            </article>
                        </div>

                        @if ($selectedModule['boards']->isNotEmpty())
                            <div class="card card--muted block-spaced dashboard-board-list">
                                <p class="text-caption">Tableros autorizados</p>
                                <div class="dashboard-board-list__items block-spaced-sm">
                                    @foreach ($selectedModule['boards'] as $board)
                                        <span class="status-pill {{ $selectedBoard && $selectedBoard['key'] === $board['key'] ? 'status-pill--info' : 'status-pill--muted' }}">
                                            {{ $board['label'] }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="dashboard-hero__header">
                            <div>
                                <p class="eyebrow">Panel de trabajo</p>
                                <h2 class="page-title title-spaced">Sin modulos visibles</h2>
                                <p class="page-subtitle">Tu usuario no tiene modulos o tableros habilitados en este momento.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
