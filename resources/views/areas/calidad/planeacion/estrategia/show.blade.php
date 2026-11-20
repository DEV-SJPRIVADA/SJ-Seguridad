@php
    // Métricas del DOFA para la franja de indicadores y las subpestañas.
    $dofaItemsCount = $dofa->items->count();
    $allStrategies = $dofa->objectives->flatMap(fn ($objective) => $objective->strategies);
    $averageProgress = $allStrategies->isEmpty() ? 0 : (int) round($allStrategies->avg('progress'));

    // Subpestañas del detalle: matriz DOFA, objetivos y estrategias encadenadas.
    $tabs = [
        ['key' => 'dofa', 'label' => 'DOFA', 'count' => $dofaItemsCount, 'icon' => 'layout-grid'],
        ['key' => 'objetivos', 'label' => 'Objetivos', 'count' => $objectivesCount, 'icon' => 'target'],
        ['key' => 'estrategias', 'label' => 'Estrategias', 'count' => $strategiesCount, 'icon' => 'git-branch'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Detalle del DOFA: cabecera con vigencia, KPIs y tres subpestañas (DOFA / Objetivos / Estrategias). --}}
    <div class="page-section planeacion-page planeacion-estrategia-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Cabecera del DOFA: identificación, vigencia, resumen y acciones de estado. --}}
            <section class="planeacion-estrategia-hero">
                <div class="planeacion-estrategia-hero__main">
                    <a href="{{ route('calidad.planeacion.estrategia') }}" class="planeacion-estrategia-hero__back">
                        <x-lucide-arrow-left width="16" height="16" aria-hidden="true" />
                        Volver al listado
                    </a>
                    <div class="planeacion-estrategia-hero__title-row">
                        <h1 class="planeacion-estrategia-hero__title">{{ $dofa->title }}</h1>
                        <span class="status-pill {{ $dofa->status === 'active' ? 'status-pill--success' : ($dofa->isArchived() ? 'status-pill--muted' : 'status-pill--warning') }}">
                            {{ $dofa->statusLabel() }}
                        </span>
                    </div>
                    <p class="planeacion-estrategia-hero__meta">
                        <span class="planeacion-estrategia-hero__meta-item">
                            <x-lucide-calendar-range width="15" height="15" aria-hidden="true" />
                            {{ $dofa->period_label }}
                        </span>
                        <span class="planeacion-estrategia-hero__meta-item">
                            <x-lucide-calendar-days width="15" height="15" aria-hidden="true" />
                            {{ $dofa->starts_on?->format('d/m/Y') ?? '—' }} — {{ $dofa->ends_on?->format('d/m/Y') ?? '—' }}
                        </span>
                    </p>
                    @if ($dofa->summary)
                        <p class="planeacion-estrategia-hero__summary">{{ $dofa->summary }}</p>
                    @endif
                </div>

                <div class="planeacion-estrategia-hero__side">
                    <dl class="planeacion-estrategia-kpis">
                        <div class="planeacion-estrategia-kpis__item">
                            <dt>Líneas DOFA</dt>
                            <dd>{{ $dofaItemsCount }}</dd>
                        </div>
                        <div class="planeacion-estrategia-kpis__item">
                            <dt>Objetivos</dt>
                            <dd>{{ $objectivesCount }}</dd>
                        </div>
                        <div class="planeacion-estrategia-kpis__item">
                            <dt>Estrategias</dt>
                            <dd>{{ $strategiesCount }}</dd>
                        </div>
                        <div class="planeacion-estrategia-kpis__item">
                            <dt>Avance promedio</dt>
                            <dd>{{ $averageProgress }}%</dd>
                        </div>
                    </dl>

                    @if ($canEdit)
                        <div class="planeacion-estrategia-hero__actions">
                            <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Editar DOFA" aria-label="Editar DOFA" x-data="" x-on:click="$dispatch('open-modal', 'planeacion-dofa-edit-{{ $dofa->id }}')">
                                <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                            </button>
                            @if ($dofa->status !== 'active')
                                <form method="POST" action="{{ route('calidad.planeacion.estrategia.activate', $dofa) }}">
                                    @csrf
                                    <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Marcar como vigente" aria-label="Marcar como vigente">
                                        <x-lucide-check width="18" height="18" aria-hidden="true" />
                                    </button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('calidad.planeacion.estrategia.archive', $dofa) }}">
                                @csrf
                                <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--danger" title="Archivar DOFA" aria-label="Archivar DOFA">
                                    <x-lucide-archive width="18" height="18" aria-hidden="true" />
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </section>

            {{-- Misión y visión del periodo: solo se muestran si fueron diligenciadas. --}}
            @if ($dofa->mission || $dofa->vision)
                <div class="planeacion-estrategia-principles">
                    @if ($dofa->mission)
                        <section class="planeacion-estrategia-principles__item">
                            <h2 class="planeacion-estrategia-principles__title">
                                <x-lucide-compass width="16" height="16" aria-hidden="true" />
                                Misión
                            </h2>
                            <p class="planeacion-estrategia-principles__text">{{ $dofa->mission }}</p>
                        </section>
                    @endif
                    @if ($dofa->vision)
                        <section class="planeacion-estrategia-principles__item">
                            <h2 class="planeacion-estrategia-principles__title">
                                <x-lucide-eye width="16" height="16" aria-hidden="true" />
                                Visión
                            </h2>
                            <p class="planeacion-estrategia-principles__text">{{ $dofa->vision }}</p>
                        </section>
                    @endif
                </div>
            @endif

            {{-- Subpestañas del detalle: navegan por querystring para conservar el estado al recargar. --}}
            <div class="module-subnav planeacion-estrategia-subnav">
                <div class="module-subnav__inner module-tabs" role="tablist" aria-label="Detalle del DOFA">
                    @foreach ($tabs as $tab)
                        <a
                            href="{{ route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => $tab['key']]) }}"
                            class="module-tab planeacion-estrategia-tab {{ $activeTab === $tab['key'] ? 'module-tab--active' : '' }}"
                            role="tab"
                            aria-selected="{{ $activeTab === $tab['key'] ? 'true' : 'false' }}"
                        >
                            @switch($tab['icon'])
                                @case('layout-grid')
                                    <x-lucide-layout-grid width="15" height="15" aria-hidden="true" />
                                    @break
                                @case('target')
                                    <x-lucide-target width="15" height="15" aria-hidden="true" />
                                    @break
                                @default
                                    <x-lucide-git-branch width="15" height="15" aria-hidden="true" />
                            @endswitch
                            <span>{{ $tab['label'] }}</span>
                            <span class="planeacion-estrategia-tab__count">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Contenido de la subpestaña activa. --}}
            @if ($activeTab === 'dofa')
                @include('areas.calidad.planeacion.estrategia.partials.tab-dofa')
            @elseif ($activeTab === 'objetivos')
                @include('areas.calidad.planeacion.estrategia.partials.tab-objectives')
            @else
                @include('areas.calidad.planeacion.estrategia.partials.tab-strategies')
            @endif
        </div>
    </div>

    @if ($canEdit)
            @include('areas.calidad.planeacion.estrategia.partials.dofa-modal', [
                'dofa' => $dofa,
            ])
    @endif
</x-app-layout>
