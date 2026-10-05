{{-- Icono Generar Cartas (vínculo activo → preselecciona contratación). --}}
@props([
    'period',
    'canGenerateContratacionLetters' => false,
    'letterGenerateTypes' => [],
    'iconOnly' => false,
])

@php
    $preferredTypeCode = (string) config('employee_ficha.word_document_type_codes.contratacion');
@endphp

@if ($canGenerateContratacionLetters && $period && $period->status === \App\Models\EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
    @if ($iconOnly)
        <button
            type="button"
            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
            title="Generar Cartas"
            aria-label="Generar Cartas"
            data-letter-types='@json($letterGenerateTypes)'
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
    @else
        <div class="ficha-empleados-letter-actions">
            <button
                type="button"
                class="btn btn--primary btn--sm"
                data-letter-types='@json($letterGenerateTypes)'
                data-preferred-type="{{ $preferredTypeCode }}"
                x-data=""
                x-on:click.prevent="
                    $dispatch('ficha-prepare-generate-cartas', {
                        types: JSON.parse($el.dataset.letterTypes || '[]'),
                        preferredTypeCode: $el.dataset.preferredType || '',
                    });
                    $dispatch('open-modal', 'ficha-generate-cartas');
                "
            >Generar Cartas</button>
        </div>
    @endif
@endif
