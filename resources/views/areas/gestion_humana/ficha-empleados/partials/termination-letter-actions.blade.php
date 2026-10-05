{{-- Acciones de cartas (generar / descargar) por período cerrado → mismo modal Generar Cartas. --}}
@props([
    'period',
    'canGenerateLetters' => false,
    'letterGenerateTypes' => [],
    'activePeriod' => null,
    'canGenerateContratacionLetters' => false,
    'compact' => false,
    'iconOnly' => false,
])

@if ($canGenerateLetters && $period && $period->status === \App\Models\EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
    @php
        $hasGenerated = filled($period->termination_letter_path);
        $preferredTypeCode = (string) config('employee_ficha.word_document_type_codes.desvinculacion');

        // Si no llega el payload (p. ej. historial por fila), construir tipos para este período cerrado.
        $typesPayload = is_array($letterGenerateTypes) && $letterGenerateTypes !== []
            ? $letterGenerateTypes
            : app(\App\Services\GestionHumana\FichaLetterGenerateTypesBuilder::class)->build(
                $activePeriod,
                $period,
                (bool) $canGenerateContratacionLetters,
                true,
            );
    @endphp

    @if ($iconOnly)
        <span class="ficha-empleados-page__title-actions-group" role="group" aria-label="Cartas">
            @if ($hasGenerated)
                <a
                    href="{{ route('gestion-humana.ficha-empleados.employees.period.letters.download', $period) }}"
                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                    title="Descargar cartas"
                    aria-label="Descargar cartas"
                >
                    <x-lucide-download width="18" height="18" aria-hidden="true" />
                </a>
            @endif

            <button
                type="button"
                class="req-manage-filters__icon-btn {{ $hasGenerated ? 'req-manage-filters__icon-btn--ghost' : 'req-manage-filters__icon-btn--primary' }}"
                title="Generar Cartas"
                aria-label="Generar Cartas"
                data-letter-types='@json($typesPayload)'
                data-preferred-type="{{ $preferredTypeCode }}"
                x-data=""
                x-on:click.prevent="
                    $dispatch('ficha-prepare-generate-cartas', {
                        types: JSON.parse($el.dataset.letterTypes || '[]'),
                        preferredTypeCode: $el.dataset.preferredType || '',
                    });
                    $dispatch('open-modal', 'ficha-generate-cartas');
                "
            >
                <x-lucide-file-text width="18" height="18" aria-hidden="true" />
            </button>
        </span>
    @else
        <div class="{{ $compact ? 'ficha-empleados-letter-actions ficha-empleados-letter-actions--compact' : 'ficha-empleados-letter-actions' }}">
            @if ($hasGenerated)
                <a
                    href="{{ route('gestion-humana.ficha-empleados.employees.period.letters.download', $period) }}"
                    class="{{ $compact ? 'cursos-catalogo-page__icon-btn' : 'btn btn--secondary btn--sm' }}"
                    title="Descargar cartas"
                    aria-label="Descargar cartas"
                >
                    @if ($compact)
                        <x-lucide-download width="16" height="16" aria-hidden="true" />
                    @else
                        Descargar cartas
                    @endif
                </a>
            @endif

            <button
                type="button"
                class="{{ $compact
                    ? 'cursos-catalogo-page__icon-btn'
                    : 'btn '.($hasGenerated ? 'btn--secondary' : 'btn--primary').' btn--sm' }}"
                title="Generar Cartas"
                aria-label="Generar Cartas"
                data-letter-types='@json($typesPayload)'
                data-preferred-type="{{ $preferredTypeCode }}"
                x-data=""
                x-on:click.prevent="
                    $dispatch('ficha-prepare-generate-cartas', {
                        types: JSON.parse($el.dataset.letterTypes || '[]'),
                        preferredTypeCode: $el.dataset.preferredType || '',
                    });
                    $dispatch('open-modal', 'ficha-generate-cartas');
                "
            >
                @if ($compact)
                    <x-lucide-file-text width="16" height="16" aria-hidden="true" />
                @else
                    Generar Cartas
                @endif
            </button>
        </div>
    @endif
@endif
