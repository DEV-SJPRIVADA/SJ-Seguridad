@php
    // Metadatos visuales por cuadrante: icono, hint y etiqueta del botón de alta.
    $quadrantMeta = [
        'fortaleza' => ['icon' => 'shield-check', 'hint' => 'Factores internos que favorecen el cumplimiento.'],
        'oportunidad' => ['icon' => 'trending-up', 'hint' => 'Factores externos que se pueden aprovechar.'],
        'debilidad' => ['icon' => 'triangle-alert', 'hint' => 'Factores internos que limitan el desempeño.'],
        'amenaza' => ['icon' => 'shield-alert', 'hint' => 'Factores externos que generan riesgo.'],
    ];
@endphp

{{-- Subpestaña DOFA: matriz F/O/D/A. Cada sección agrega y elimina sus propias líneas. --}}
<div class="planeacion-estrategia-tabpanel" role="tabpanel">
    <div class="planeacion-estrategia-tabpanel__toolbar">
        <div>
            <h2 class="planeacion-estrategia-tabpanel__title">Matriz DOFA</h2>
            <p class="planeacion-estrategia-tabpanel__lead">Análisis interno (fortalezas y debilidades) y externo (oportunidades y amenazas) del periodo.</p>
        </div>
        @if ($canEdit)
            <div class="planeacion-estrategia-tabpanel__actions">
                @if ($errors->any())
                    <span class="status-pill status-pill--danger">Revise la línea ingresada</span>
                @endif
                {{-- El modal solo edita identificación y periodo; la matriz se alimenta aquí. --}}
                <button type="button" class="btn btn--secondary btn--sm" x-data="" x-on:click="$dispatch('open-modal', 'planeacion-dofa-edit-{{ $dofa->id }}')">
                    <x-lucide-settings-2 width="16" height="16" aria-hidden="true" />
                    Datos del DOFA
                </button>
            </div>
        @endif
    </div>

    <div class="planeacion-estrategia-matrix">
        @foreach ($quadrants as $key => $label)
            @php
                $meta = $quadrantMeta[$key] ?? ['icon' => 'layout-grid', 'hint' => ''];
                $rows = $itemsByQuadrant[$key] ?? [];
                // Solo la sección con error conserva el formulario abierto tras un 422.
                $hasError = $errors->has('description') && old('quadrant') === $key;
            @endphp
            <section
                class="planeacion-estrategia-matrix__cell planeacion-estrategia-matrix__cell--{{ $key }}"
                x-data="{ adding: {{ $hasError ? 'true' : 'false' }} }"
            >
                <header class="planeacion-estrategia-matrix__head">
                    <span class="planeacion-estrategia-matrix__icon" aria-hidden="true">
                        @switch($meta['icon'])
                            @case('shield-check')
                                <x-lucide-shield-check width="16" height="16" />
                                @break
                            @case('trending-up')
                                <x-lucide-trending-up width="16" height="16" />
                                @break
                            @case('triangle-alert')
                                <x-lucide-triangle-alert width="16" height="16" />
                                @break
                            @default
                                <x-lucide-shield-alert width="16" height="16" />
                        @endswitch
                    </span>
                    <h3>{{ $label }}</h3>
                    <span class="planeacion-estrategia-matrix__count">{{ count($rows) }}</span>
                    @if ($canEdit)
                        <button
                            type="button"
                            class="planeacion-estrategia-matrix__add"
                            title="Agregar línea en {{ $label }}"
                            aria-label="Agregar línea en {{ $label }}"
                            x-on:click="adding = ! adding"
                        >
                            <x-lucide-plus width="16" height="16" aria-hidden="true" />
                        </button>
                    @endif
                </header>

                @if (count($rows) === 0 && ! $canEdit)
                    <p class="planeacion-estrategia-matrix__hint">{{ $meta['hint'] }}</p>
                @endif

                <ul class="planeacion-estrategia-matrix__list">
                    @forelse ($rows as $item)
                        <li class="planeacion-estrategia-matrix__row">
                            <span class="planeacion-estrategia-matrix__text">{{ $item->description }}</span>
                            @if ($canEdit)
                                <form
                                    method="POST"
                                    action="{{ route('calidad.planeacion.estrategia.items.destroy', $item) }}"
                                    class="js-confirm-delete planeacion-estrategia-matrix__delete"
                                    data-confirm-title="Eliminar línea del DOFA"
                                    data-confirm-message="¿Eliminar esta línea de {{ mb_strtolower($label) }}? Esta acción no se puede deshacer."
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar línea" aria-label="Eliminar línea">
                                        <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                    </button>
                                </form>
                            @endif
                        </li>
                    @empty
                        <li class="planeacion-estrategia-matrix__empty">Sin líneas registradas.</li>
                    @endforelse
                </ul>

                @if ($canEdit)
                    {{-- Alta rápida de una línea en este cuadrante. --}}
                    <form
                        method="POST"
                        action="{{ route('calidad.planeacion.estrategia.items.store', $dofa) }}"
                        class="planeacion-estrategia-matrix__form"
                        x-show="adding"
                        x-cloak
                    >
                        @csrf
                        <input type="hidden" name="quadrant" value="{{ $key }}">
                        {{-- Área de texto alta: permite escribir párrafos con saltos de línea (Enter). --}}
                        <textarea
                            name="description"
                            class="form-input planeacion-estrategia-matrix__textarea"
                            rows="5"
                            maxlength="500"
                            required
                            placeholder="Escriba la línea. Puede usar varias líneas con Enter."
                            aria-label="Nueva línea en {{ $label }}"
                        >{{ $hasError ? old('description') : '' }}</textarea>
                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Guardar línea" aria-label="Guardar línea">
                            <x-lucide-check width="18" height="18" aria-hidden="true" />
                        </button>
                        <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Cancelar" aria-label="Cancelar" x-on:click="adding = false">
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                    </form>
                    @if (count($rows) === 0)
                        <p class="planeacion-estrategia-matrix__hint">{{ $meta['hint'] }}</p>
                    @endif
                @endif
            </section>
        @endforeach
    </div>
</div>
