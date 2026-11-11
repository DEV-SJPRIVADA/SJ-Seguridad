<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Detalle del DOFA: matriz F/O/D/A y árbol objetivo → estrategias. --}}
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

            <div class="cursos-registros-page__table-toolbar">
                <p class="req-manage-filters__meta">
                    <strong>{{ $dofa->title }}</strong>
                    <span>· {{ $dofa->period_label }} · {{ $dofa->starts_on?->format('d/m/Y') }} — {{ $dofa->ends_on?->format('d/m/Y') }}</span>
                </p>
                <div class="cursos-registros-page__table-actions">
                    <a href="{{ route('calidad.planeacion.estrategia') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Volver al listado" aria-label="Volver al listado">
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </a>
                    @if ($canEdit)
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
                    @endif
                </div>
            </div>

            <div class="planeacion-estrategia-matrix">
                @foreach ($quadrants as $key => $label)
                    <section class="planeacion-estrategia-matrix__cell planeacion-estrategia-matrix__cell--{{ $key }}">
                        <header class="planeacion-estrategia-matrix__head">
                            <h3>{{ $label }}</h3>
                            <span class="planeacion-estrategia-matrix__count">{{ count($itemsByQuadrant[$key] ?? []) }}</span>
                        </header>
                        <ul class="planeacion-estrategia-matrix__list">
                            @forelse ($itemsByQuadrant[$key] ?? [] as $item)
                                <li>{{ $item->description }}</li>
                            @empty
                                <li class="planeacion-estrategia-matrix__empty">Sin líneas registradas.</li>
                            @endforelse
                        </ul>
                    </section>
                @endforeach
            </div>

            <section class="panel planeacion-estrategia-objectives">
                <div class="panel__header">
                    <h3 class="panel-title">Objetivos y estrategias</h3>
                    <p class="panel-text">Cada objetivo hereda este DOFA; cada estrategia ejecuta un objetivo.</p>
                </div>
                <div class="panel__body">
                    @forelse ($dofa->objectives as $objective)
                        <article class="planeacion-estrategia-objective">
                            <header class="planeacion-estrategia-objective__head">
                                <div>
                                    <h4 class="planeacion-estrategia-objective__title">{{ $objective->title }}</h4>
                                    @if ($objective->description)
                                        <p class="planeacion-estrategia-objective__desc">{{ $objective->description }}</p>
                                    @endif
                                </div>
                                @if ($canEdit)
                                    <form method="POST" action="{{ route('calidad.planeacion.estrategia.objectives.destroy', $objective) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar objetivo" aria-label="Eliminar objetivo">
                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                        </button>
                                    </form>
                                @endif
                            </header>

                            <ul class="planeacion-estrategia-strategies">
                                @forelse ($objective->strategies as $strategy)
                                    <li class="planeacion-estrategia-strategy">
                                        <div>
                                            <p class="planeacion-estrategia-strategy__title">{{ $strategy->title }}</p>
                                            @if ($strategy->description)
                                                <p class="planeacion-estrategia-strategy__desc">{{ $strategy->description }}</p>
                                            @endif
                                            <p class="planeacion-estrategia-strategy__meta">
                                                @if ($strategy->responsible)
                                                    <span>Responsable: {{ $strategy->responsible }}</span>
                                                @endif
                                                @if ($strategy->starts_on || $strategy->ends_on)
                                                    <span>{{ $strategy->starts_on?->format('d/m/Y') ?? '—' }} — {{ $strategy->ends_on?->format('d/m/Y') ?? '—' }}</span>
                                                @endif
                                                <span>Avance: {{ $strategy->progress }}%</span>
                                            </p>
                                        </div>
                                        @if ($canEdit)
                                            <form method="POST" action="{{ route('calidad.planeacion.estrategia.strategies.destroy', $strategy) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar estrategia" aria-label="Eliminar estrategia">
                                                    <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                </button>
                                            </form>
                                        @endif
                                    </li>
                                @empty
                                    <li class="planeacion-estrategia-strategies__empty">Sin estrategias todavía.</li>
                                @endforelse
                            </ul>

                            @if ($canEdit)
                                <form method="POST" action="{{ route('calidad.planeacion.estrategia.strategies.store', $objective) }}" class="planeacion-estrategia-inline-form">
                                    @csrf
                                    <div class="form-field">
                                        <x-input-label for="strategy_title_{{ $objective->id }}" value="Nueva estrategia" />
                                        <input id="strategy_title_{{ $objective->id }}" name="title" type="text" class="form-input" maxlength="200" required placeholder="Escriba la estrategia">
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="strategy_resp_{{ $objective->id }}" value="Responsable" />
                                        <input id="strategy_resp_{{ $objective->id }}" name="responsible" type="text" class="form-input" maxlength="160">
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="strategy_progress_{{ $objective->id }}" value="Avance %" />
                                        <input id="strategy_progress_{{ $objective->id }}" name="progress" type="number" min="0" max="100" class="form-input" value="0">
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar estrategia" aria-label="Agregar estrategia">
                                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </article>
                    @empty
                        <p class="planeacion-estrategia-objectives__empty">Este DOFA aún no tiene objetivos.</p>
                    @endforelse

                    @if ($canEdit)
                        <form method="POST" action="{{ route('calidad.planeacion.estrategia.objectives.store', $dofa) }}" class="planeacion-estrategia-inline-form planeacion-estrategia-inline-form--objective">
                            @csrf
                            <div class="form-field">
                                <x-input-label for="objective_title_{{ $dofa->id }}" value="Nuevo objetivo" />
                                <input id="objective_title_{{ $dofa->id }}" name="title" type="text" class="form-input" maxlength="200" required placeholder="Escriba el objetivo">
                            </div>
                            <div class="form-field">
                                <x-input-label for="objective_start_{{ $dofa->id }}" value="Desde" />
                                <input id="objective_start_{{ $dofa->id }}" name="starts_on" type="date" class="form-input">
                            </div>
                            <div class="form-field">
                                <x-input-label for="objective_end_{{ $dofa->id }}" value="Hasta" />
                                <input id="objective_end_{{ $dofa->id }}" name="ends_on" type="date" class="form-input">
                            </div>
                            <div class="form-field cursos-registros-page__filter-actions">
                                <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar objetivo" aria-label="Agregar objetivo">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </section>
        </div>
    </div>

    @if ($canEdit)
        @include('areas.calidad.planeacion.estrategia.partials.dofa-modal', [
            'dofa' => $dofa,
            'quadrants' => $quadrants,
            'itemsByQuadrant' => $itemsByQuadrant,
        ])
    @endif
</x-app-layout>
