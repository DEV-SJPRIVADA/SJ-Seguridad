<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Consulta: filtros de periodo/capturador y ficha de gestión consolidada. --}}
    <div class="page-section planeacion-show-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif

            <div class="cursos-registros-page__table-toolbar">
                <p class="req-manage-filters__meta">
                    <strong>{{ $indicator->code }}</strong>
                    <span>· v{{ $version->version_number }} · {{ $indicator->isArchived() ? 'Archivado' : $version->statusLabel() }}</span>
                    @if ($consultVersion)
                        <span>· Consultando v{{ $consultVersion->version_number }} ({{ $consultVersion->statusLabel() }})</span>
                    @endif
                </p>
                @php
                    $fichaExportQuery = array_filter([
                        'year' => $year,
                        'month' => $month,
                        'capturador_id' => $ficha['capturador_id'] ?? null,
                        'version' => $ficha['consult_version_id'] ?? null,
                    ], fn ($value) => $value !== null && $value !== '');
                @endphp
                <div class="cursos-registros-page__table-actions">
                    <a href="{{ route('calidad.planeacion.indicadores') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Volver al listado" aria-label="Volver al listado">
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </a>
                    <x-export-excel
                        route="{{ route('calidad.planeacion.indicadores.export.excel', array_merge(['indicator' => $indicator], $fichaExportQuery)) }}"
                        label=""
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Exportar ficha a Excel"
                        aria-label="Exportar ficha a Excel"
                    />
                    <a
                        href="{{ route('calidad.planeacion.indicadores.export.pdf', array_merge(['indicator' => $indicator], $fichaExportQuery)) }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Exportar ficha a PDF"
                        aria-label="Exportar ficha a PDF"
                    >
                        <x-lucide-file-text width="18" height="18" aria-hidden="true" />
                    </a>
                    @if ($canRestore ?? false)
                        <form method="POST" action="{{ route('calidad.planeacion.indicadores.restore', $indicator) }}">
                            @csrf
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Restablecer indicador" aria-label="Restablecer indicador">
                                <x-lucide-rotate-ccw width="18" height="18" aria-hidden="true" />
                            </button>
                        </form>
                    @elseif ($canEdit)
                        @include('areas.calidad.planeacion.partials.share', [
                            'indicator' => $indicator,
                            'shareUsers' => $shareUsers,
                            'shareShowModal' => false,
                        ])
                        <a href="{{ route('calidad.planeacion.indicadores.edit', $indicator) }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Editar" aria-label="Editar">
                            <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                        </a>
                        @if ($version->isDraft())
                            <form method="POST" action="{{ route('calidad.planeacion.indicadores.publish', $indicator) }}">
                                @csrf
                                <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Publicar" aria-label="Publicar">
                                    <x-lucide-send width="18" height="18" aria-hidden="true" />
                                </button>
                            </form>
                        @endif
                        <form
                            method="POST"
                            action="{{ route('calidad.planeacion.indicadores.archive', $indicator) }}"
                            class="js-confirm-archive"
                            data-confirm-title="Archivar indicador"
                            data-confirm-message="¿Archivar {{ $indicator->code }}? Dejará de capturarse y de aparecer en el dashboard hasta que lo restablezca."
                        >
                            @csrf
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--danger" title="Archivar" aria-label="Archivar">
                                <x-lucide-archive width="18" height="18" aria-hidden="true" />
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel__body panel__body--compact">
                    <form method="GET" action="{{ route('calidad.planeacion.indicadores.show', $indicator) }}" class="planeacion-ficha-filters">
                        <div class="form-field">
                            <label class="form-label" for="ficha_year">Año</label>
                            <x-searchable-select
                                id="ficha_year"
                                name="year"
                                :options="$yearOptions"
                                :value="(string) $year"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="ficha_month">Mes</label>
                            <x-searchable-select
                                id="ficha_month"
                                name="month"
                                :options="$monthOptions"
                                :value="(string) $month"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="ficha_version">Versión</label>
                            <x-searchable-select
                                id="ficha_version"
                                name="version"
                                :options="$versionOptions"
                                :value="(string) (($ficha['consult_version_id'] ?? null) ?: '0')"
                                :allow-clear="false"
                            />
                        </div>
                        @if ($ficha['show_capturador_filter'])
                            <div class="form-field">
                                <label class="form-label" for="ficha_capturador">Capturador</label>
                                <x-searchable-select
                                    id="ficha_capturador"
                                    name="capturador_id"
                                    :options="$ficha['capturador_options']"
                                    :value="(string) ($ficha['capturador_id'] ?? '')"
                                    placeholder="Todos los capturadores"
                                    search-placeholder="Buscar capturador…"
                                    :allow-clear="false"
                                />
                            </div>
                        @endif
                        <div class="form-field cursos-registros-page__filter-actions">
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($consultVersion)
                <div class="alert alert--warning">
                    Está consultando la versión {{ $consultVersion->version_number }} ({{ $consultVersion->statusLabel() }}).
                    La ficha y el gráfico muestran esa definición y sus capturas. Las acciones de publicar o editar siguen aplicando a la versión de trabajo.
                </div>
            @endif

            @include('areas.calidad.planeacion.partials.ficha-gestion', [
                'indicator' => $indicator,
                'ficha' => $ficha,
            ])

            @include('areas.calidad.planeacion.partials.version-history', [
                'versionHistory' => $versionHistory,
                'activeVersionId' => $consultVersion?->id,
                'consultQuery' => array_filter([
                    'indicator' => $indicator,
                    'year' => $year,
                    'month' => $month,
                    'capturador_id' => $ficha['capturador_id'] ?? null,
                ], fn ($value) => $value !== null && $value !== ''),
            ])

            @if ($canEdit)
                @include('areas.calidad.planeacion.partials.share', [
                    'indicator' => $indicator,
                    'shareUsers' => $shareUsers,
                    'shareShowTrigger' => false,
                ])
            @endif
        </div>
    </div>

    @if ($canEdit)
        @include('areas.calidad.planeacion.partials.confirm-archive-modal')
    @endif

    @vite(['resources/js/planeacion-ficha-charts.js'])
</x-app-layout>
