@php
    $isEdit = $dofa !== null;
    $modalKey = 'planeacion-dofa-'.($isEdit ? 'edit-'.$dofa->id : 'create');
@endphp

{{-- Modal de alta/edición de DOFA: solo identificación y periodo.
     Las líneas de los cuadrantes se agregan y eliminan desde la matriz. --}}
<x-modal name="{{ $modalKey }}" maxWidth="3xl" focusable>
    <div class="modal-card ficha-empleados-masivos-modal planeacion-estrategia-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-layout-grid width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">{{ $isEdit ? 'Editar DOFA' : 'Nuevo DOFA' }}</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        El periodo es libre: puede ser un mes, un trimestre o varios años.
                        @unless ($isEdit)
                            Después de crearlo agregará las líneas de cada cuadrante desde la matriz.
                        @endunless
                    </p>
                </div>
            </div>
            <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', '{{ $modalKey }}')">
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        <div class="planeacion-share-modal__body">
            <form
                method="POST"
                action="{{ $isEdit ? route('calidad.planeacion.estrategia.update', $dofa) : route('calidad.planeacion.estrategia.store') }}"
                class="planeacion-estrategia-form"
            >
                @csrf
                @if ($isEdit)
                    @method('PATCH')
                @endif

                <div class="planeacion-estrategia-form__grid">
                    <div class="form-field planeacion-estrategia-form__span">
                        <x-input-label for="dofa_title_{{ $isEdit ? $dofa->id : 'new' }}" value="Título *" />
                        <input id="dofa_title_{{ $isEdit ? $dofa->id : 'new' }}" name="title" type="text" class="form-input" maxlength="160" required
                               placeholder="Ej.: DOFA institucional 2026"
                               value="{{ old('title', $dofa->title ?? '') }}">
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_period_{{ $isEdit ? $dofa->id : 'new' }}" value="Periodo *" />
                        <input id="dofa_period_{{ $isEdit ? $dofa->id : 'new' }}" name="period_label" type="text" class="form-input" maxlength="80" required
                               placeholder="Ej.: 2026, T1 2026, 2026-2028"
                               value="{{ old('period_label', $dofa->period_label ?? '') }}">
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_status_{{ $isEdit ? $dofa->id : 'new' }}" value="Estado" />
                        <x-searchable-select
                            id="dofa_status_{{ $isEdit ? $dofa->id : 'new' }}"
                            name="status"
                            :options="[
                                ['value' => 'draft', 'label' => 'Borrador'],
                                ['value' => 'active', 'label' => 'Vigente'],
                            ]"
                            :value="old('status', $dofa->status ?? 'draft')"
                            :allow-clear="false"
                        />
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_start_{{ $isEdit ? $dofa->id : 'new' }}" value="Desde *" />
                        <input id="dofa_start_{{ $isEdit ? $dofa->id : 'new' }}" name="starts_on" type="date" class="form-input" required
                               value="{{ old('starts_on', $dofa?->starts_on?->toDateString()) }}">
                    </div>
                    <div class="form-field">
                        <x-input-label for="dofa_end_{{ $isEdit ? $dofa->id : 'new' }}" value="Hasta *" />
                        <input id="dofa_end_{{ $isEdit ? $dofa->id : 'new' }}" name="ends_on" type="date" class="form-input" required
                               value="{{ old('ends_on', $dofa?->ends_on?->toDateString()) }}">
                    </div>
                    {{-- Misión y visión: texto libre que acompaña el análisis del periodo. --}}
                    <div class="form-field planeacion-estrategia-form__span">
                        <x-input-label for="dofa_mission_{{ $isEdit ? $dofa->id : 'new' }}" value="Misión" />
                        <textarea
                            id="dofa_mission_{{ $isEdit ? $dofa->id : 'new' }}"
                            name="mission"
                            rows="5"
                            maxlength="2000"
                            class="form-input planeacion-estrategia-form__text"
                            placeholder="Razón de ser de la organización: qué hace, para quién y cómo"
                        >{{ old('mission', $dofa->mission ?? '') }}</textarea>
                    </div>
                    <div class="form-field planeacion-estrategia-form__span">
                        <x-input-label for="dofa_vision_{{ $isEdit ? $dofa->id : 'new' }}" value="Visión" />
                        <textarea
                            id="dofa_vision_{{ $isEdit ? $dofa->id : 'new' }}"
                            name="vision"
                            rows="5"
                            maxlength="2000"
                            class="form-input planeacion-estrategia-form__text"
                            placeholder="Estado futuro que se espera alcanzar al cierre del periodo"
                        >{{ old('vision', $dofa->vision ?? '') }}</textarea>
                    </div>
                    {{-- Resumen con más aire: es el texto que se lee en la cabecera del DOFA. --}}
                    <div class="form-field planeacion-estrategia-form__span">
                        <x-input-label for="dofa_summary_{{ $isEdit ? $dofa->id : 'new' }}" value="Resumen" />
                        <textarea
                            id="dofa_summary_{{ $isEdit ? $dofa->id : 'new' }}"
                            name="summary"
                            rows="7"
                            maxlength="3000"
                            class="form-input planeacion-estrategia-form__summary"
                            placeholder="Contexto del análisis, alcance del periodo y criterios usados"
                        >{{ old('summary', $dofa->summary ?? '') }}</textarea>
                        <p class="planeacion-estrategia-form__hint">Máximo 3000 caracteres. Se muestra en la cabecera del DOFA.</p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__footer">
                    <button type="button" class="btn btn--secondary" x-on:click="$dispatch('close-modal', '{{ $modalKey }}')">
                        <x-lucide-x width="16" height="16" aria-hidden="true" />
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn--primary">
                        <x-lucide-save width="16" height="16" aria-hidden="true" />
                        {{ $isEdit ? 'Guardar cambios' : 'Crear DOFA' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-modal>
