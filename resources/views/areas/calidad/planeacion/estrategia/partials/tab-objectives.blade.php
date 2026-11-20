@php
    // Avance promedio por objetivo para la barra de progreso de cada tarjeta.
    $objectiveProgress = $dofa->objectives->mapWithKeys(function ($objective): array {
        $count = $objective->strategies->count();

        return [$objective->id => $count > 0 ? (int) round($objective->strategies->avg('progress')) : 0];
    });
@endphp

{{-- Subpestaña Objetivos: lista de objetivos del DOFA con avance derivado de sus estrategias. --}}
<div class="planeacion-estrategia-tabpanel" role="tabpanel" x-data="{ creating: {{ $errors->any() ? 'true' : 'false' }} }">
    <div class="planeacion-estrategia-tabpanel__toolbar">
        <div>
            <h2 class="planeacion-estrategia-tabpanel__title">Objetivos</h2>
            <p class="planeacion-estrategia-tabpanel__lead">Cada objetivo hereda este DOFA y se ejecuta mediante una o varias estrategias.</p>
        </div>
        @if ($canEdit)
            <button type="button" class="btn btn--primary btn--sm" x-on:click="creating = ! creating">
                <x-lucide-plus width="16" height="16" aria-hidden="true" />
                <span x-text="creating ? 'Cancelar' : 'Nuevo objetivo'">Nuevo objetivo</span>
            </button>
        @endif
    </div>

    @if ($canEdit)
        {{-- Formulario de alta: se muestra bajo demanda para no competir con la lista. --}}
        <form
            method="POST"
            action="{{ route('calidad.planeacion.estrategia.objectives.store', $dofa) }}"
            class="planeacion-estrategia-create"
            x-show="creating"
            x-cloak
        >
            @csrf
            <div class="planeacion-estrategia-create__grid">
                <div class="form-field planeacion-estrategia-create__span">
                    <x-input-label for="objective_title_{{ $dofa->id }}" value="Objetivo *" />
                    <input id="objective_title_{{ $dofa->id }}" name="title" type="text" class="form-input" maxlength="200" required
                           placeholder="Ej.: Mejorar la eficacia de los procesos" value="{{ old('title') }}">
                </div>
                <div class="form-field planeacion-estrategia-create__span">
                    <x-input-label for="objective_description_{{ $dofa->id }}" value="Descripción" />
                    <textarea id="objective_description_{{ $dofa->id }}" name="description" rows="2" maxlength="2000" class="form-input"
                              placeholder="Resultado esperado del objetivo">{{ old('description') }}</textarea>
                </div>
                <div class="form-field">
                    <x-input-label for="objective_start_{{ $dofa->id }}" value="Desde" />
                    <input id="objective_start_{{ $dofa->id }}" name="starts_on" type="date" class="form-input" value="{{ old('starts_on') }}">
                </div>
                <div class="form-field">
                    <x-input-label for="objective_end_{{ $dofa->id }}" value="Hasta" />
                    <input id="objective_end_{{ $dofa->id }}" name="ends_on" type="date" class="form-input" value="{{ old('ends_on') }}">
                </div>
            </div>
            <div class="planeacion-estrategia-create__actions">
                <button type="button" class="btn btn--secondary btn--sm" x-on:click="creating = false">Cancelar</button>
                <button type="submit" class="btn btn--primary btn--sm">
                    <x-lucide-save width="16" height="16" aria-hidden="true" />
                    Agregar objetivo
                </button>
            </div>
        </form>
    @endif

    @forelse ($dofa->objectives as $objective)
        <article class="planeacion-estrategia-objective">
            <header class="planeacion-estrategia-objective__head">
                <span class="planeacion-estrategia-objective__index" aria-hidden="true">{{ $loop->iteration }}</span>
                <div class="planeacion-estrategia-objective__body">
                    <h3 class="planeacion-estrategia-objective__title">{{ $objective->title }}</h3>
                    @if ($objective->description)
                        <p class="planeacion-estrategia-objective__desc">{{ $objective->description }}</p>
                    @endif
                    <p class="planeacion-estrategia-objective__meta">
                        <span class="planeacion-estrategia-objective__chip">
                            <x-lucide-calendar-days width="14" height="14" aria-hidden="true" />
                            {{ $objective->starts_on?->format('d/m/Y') ?? 'Sin inicio' }} — {{ $objective->ends_on?->format('d/m/Y') ?? 'Sin cierre' }}
                        </span>
                        <span class="planeacion-estrategia-objective__chip">
                            <x-lucide-git-branch width="14" height="14" aria-hidden="true" />
                            {{ $objective->strategies->count() }} estrategia(s)
                        </span>
                    </p>
                </div>
                <div class="planeacion-estrategia-objective__side">
                    <a href="{{ route('calidad.planeacion.estrategia.show', ['dofa' => $dofa, 'tab' => 'estrategias']) }}#objetivo-{{ $objective->id }}"
                       class="btn btn--secondary btn--sm">
                        Ver estrategias
                        <x-lucide-arrow-right width="14" height="14" aria-hidden="true" />
                    </a>
                    @if ($canEdit)
                        <form method="POST" action="{{ route('calidad.planeacion.estrategia.objectives.destroy', $objective) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar objetivo" aria-label="Eliminar objetivo">
                                <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                            </button>
                        </form>
                    @endif
                </div>
            </header>

            {{-- Avance del objetivo: promedio simple del avance declarado en sus estrategias. --}}
            <div class="planeacion-estrategia-progress">
                <div class="planeacion-estrategia-progress__track">
                    <span class="planeacion-estrategia-progress__bar" style="width: {{ $objectiveProgress[$objective->id] }}%"></span>
                </div>
                <span class="planeacion-estrategia-progress__value">{{ $objectiveProgress[$objective->id] }}%</span>
            </div>
        </article>
    @empty
        <div class="planeacion-estrategia-empty">
            <x-lucide-target width="26" height="26" aria-hidden="true" />
            <p class="planeacion-estrategia-empty__title">Este DOFA aún no tiene objetivos</p>
            <p class="planeacion-estrategia-empty__text">Cree el primer objetivo para poder colgarle estrategias medibles.</p>
        </div>
    @endforelse
</div>
