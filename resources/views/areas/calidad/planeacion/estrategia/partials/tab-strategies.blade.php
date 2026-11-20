@php
    // Resumen de avance de todo el DOFA para la barra global de la subpestaña.
    $allStrategies = $dofa->objectives->flatMap(fn ($objective) => $objective->strategies);
    $overallProgress = $allStrategies->isEmpty() ? 0 : (int) round($allStrategies->avg('progress'));
@endphp

{{-- Subpestaña Estrategias: agrupadas por objetivo, con avance y alta en línea por objetivo. --}}
<div class="planeacion-estrategia-tabpanel" role="tabpanel">
    <div class="planeacion-estrategia-tabpanel__toolbar">
        <div>
            <h2 class="planeacion-estrategia-tabpanel__title">Estrategias</h2>
            <p class="planeacion-estrategia-tabpanel__lead">Cada estrategia ejecuta un objetivo del DOFA e informa responsable, fechas y avance.</p>
        </div>
        <div class="planeacion-estrategia-overall">
            <span class="planeacion-estrategia-overall__label">Avance del DOFA</span>
            <div class="planeacion-estrategia-progress planeacion-estrategia-progress--overall">
                <div class="planeacion-estrategia-progress__track">
                    <span class="planeacion-estrategia-progress__bar" style="width: {{ $overallProgress }}%"></span>
                </div>
                <span class="planeacion-estrategia-progress__value">{{ $overallProgress }}%</span>
            </div>
        </div>
    </div>

    @forelse ($dofa->objectives as $objective)
        <article class="planeacion-estrategia-group" id="objetivo-{{ $objective->id }}" x-data="{ adding: false }">
            <header class="planeacion-estrategia-group__head">
                <span class="planeacion-estrategia-objective__index" aria-hidden="true">{{ $loop->iteration }}</span>
                <div class="planeacion-estrategia-group__title-wrap">
                    <p class="planeacion-estrategia-group__eyebrow">Objetivo</p>
                    <h3 class="planeacion-estrategia-group__title">{{ $objective->title }}</h3>
                </div>
                <span class="planeacion-estrategia-group__count">{{ $objective->strategies->count() }} estrategia(s)</span>
                @if ($canEdit)
                    <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar estrategia" aria-label="Agregar estrategia" x-on:click="adding = ! adding">
                        <x-lucide-plus width="18" height="18" aria-hidden="true" />
                    </button>
                @endif
            </header>

            {{-- Alta de estrategia: formulario completo bajo demanda para cada objetivo. --}}
            @if ($canEdit)
                <form
                    method="POST"
                    action="{{ route('calidad.planeacion.estrategia.strategies.store', $objective) }}"
                    class="planeacion-estrategia-create"
                    x-show="adding"
                    x-cloak
                >
                    @csrf
                    <div class="planeacion-estrategia-create__grid">
                        <div class="form-field planeacion-estrategia-create__span">
                            <x-input-label :for="'strategy_title_'.$objective->id" value="Estrategia *" />
                            <input id="strategy_title_{{ $objective->id }}" name="title" type="text" class="form-input" maxlength="200" required
                                   placeholder="Ej.: Auditorías internas trimestrales">
                        </div>
                        <div class="form-field planeacion-estrategia-create__span">
                            <x-input-label :for="'strategy_description_'.$objective->id" value="Descripción" />
                            <textarea id="strategy_description_{{ $objective->id }}" name="description" rows="2" maxlength="2000" class="form-input"
                                      placeholder="Cómo se ejecutará la estrategia"></textarea>
                        </div>
                        <div class="form-field">
                            <x-input-label :for="'strategy_responsible_'.$objective->id" value="Responsable" />
                            <input id="strategy_responsible_{{ $objective->id }}" name="responsible" type="text" class="form-input" maxlength="160"
                                   placeholder="Área o persona">
                        </div>
                        <div class="form-field">
                            <x-input-label :for="'strategy_progress_'.$objective->id" value="Avance %" />
                            <input id="strategy_progress_{{ $objective->id }}" name="progress" type="number" min="0" max="100" class="form-input" value="0">
                        </div>
                        <div class="form-field">
                            <x-input-label :for="'strategy_start_'.$objective->id" value="Desde" />
                            <input id="strategy_start_{{ $objective->id }}" name="starts_on" type="date" class="form-input">
                        </div>
                        <div class="form-field">
                            <x-input-label :for="'strategy_end_'.$objective->id" value="Hasta" />
                            <input id="strategy_end_{{ $objective->id }}" name="ends_on" type="date" class="form-input">
                        </div>
                    </div>
                    <div class="planeacion-estrategia-create__actions">
                        <button type="button" class="btn btn--secondary btn--sm" x-on:click="adding = false">Cancelar</button>
                        <button type="submit" class="btn btn--primary btn--sm">
                            <x-lucide-save width="16" height="16" aria-hidden="true" />
                            Agregar estrategia
                        </button>
                    </div>
                </form>
            @endif

            <ul class="planeacion-estrategia-strategies">
                @forelse ($objective->strategies as $strategy)
                    <li class="planeacion-estrategia-strategy">
                        <div class="planeacion-estrategia-strategy__body">
                            <div class="planeacion-estrategia-strategy__top">
                                <p class="planeacion-estrategia-strategy__title">{{ $strategy->title }}</p>
                                <span class="status-pill {{ $strategy->progress >= 100 ? 'status-pill--success' : ($strategy->progress > 0 ? 'status-pill--info' : 'status-pill--muted') }}">
                                    {{ $strategy->progress }}%
                                </span>
                            </div>
                            @if ($strategy->description)
                                <p class="planeacion-estrategia-strategy__desc">{{ $strategy->description }}</p>
                            @endif
                            <p class="planeacion-estrategia-strategy__meta">
                                @if ($strategy->responsible)
                                    <span class="planeacion-estrategia-strategy__meta-item">
                                        <x-lucide-user-round width="14" height="14" aria-hidden="true" />
                                        {{ $strategy->responsible }}
                                    </span>
                                @endif
                                @if ($strategy->starts_on || $strategy->ends_on)
                                    <span class="planeacion-estrategia-strategy__meta-item">
                                        <x-lucide-calendar-days width="14" height="14" aria-hidden="true" />
                                        {{ $strategy->starts_on?->format('d/m/Y') ?? '—' }} — {{ $strategy->ends_on?->format('d/m/Y') ?? '—' }}
                                    </span>
                                @endif
                            </p>
                            <div class="planeacion-estrategia-progress planeacion-estrategia-progress--slim">
                                <div class="planeacion-estrategia-progress__track">
                                    <span class="planeacion-estrategia-progress__bar" style="width: {{ $strategy->progress }}%"></span>
                                </div>
                            </div>
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
                    <li class="planeacion-estrategia-strategies__empty">Sin estrategias todavía. Use el botón + para agregar la primera.</li>
                @endforelse
            </ul>
        </article>
    @empty
        <div class="planeacion-estrategia-empty">
            <x-lucide-git-branch width="26" height="26" aria-hidden="true" />
            <p class="planeacion-estrategia-empty__title">No hay objetivos para colgar estrategias</p>
            <p class="planeacion-estrategia-empty__text">Cree primero un objetivo en la subpestaña Objetivos.</p>
        </div>
    @endforelse
</div>
