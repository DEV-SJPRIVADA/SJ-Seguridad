{{-- Acciones de cartas de desvinculación (generar / descargar) por período cerrado. --}}
@props([
    'period',
    'canGenerateLetters' => false,
    'compact' => false,
    'iconOnly' => false,
])

@if ($canGenerateLetters && $period && $period->status === \App\Models\EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
    @php
        $hasGenerated = filled($period->termination_letter_path);
    @endphp

    @if ($iconOnly)
        <span class="ficha-empleados-page__title-actions-group" role="group" aria-label="Cartas de desvinculación">
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
                title="Generar cartas"
                aria-label="Generar cartas"
                data-templates-url="{{ route('gestion-humana.ficha-empleados.employees.period.letters.templates', $period) }}"
                data-generate-url="{{ route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period) }}"
                data-firmas-url="{{ route('gestion-humana.ficha-empleados.employees.period.letters.firmas', $period) }}"
                x-data=""
                x-on:click.prevent="
                    $dispatch('ficha-prepare-generate-letters', {
                        templatesUrl: $el.dataset.templatesUrl,
                        generateUrl: $el.dataset.generateUrl,
                        firmasUrl: $el.dataset.firmasUrl,
                    });
                    $dispatch('open-modal', 'ficha-generate-letters');
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
                title="Generar cartas"
                aria-label="Generar cartas"
                data-templates-url="{{ route('gestion-humana.ficha-empleados.employees.period.letters.templates', $period) }}"
                data-generate-url="{{ route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period) }}"
                data-firmas-url="{{ route('gestion-humana.ficha-empleados.employees.period.letters.firmas', $period) }}"
                x-data=""
                x-on:click.prevent="
                    $dispatch('ficha-prepare-generate-letters', {
                        templatesUrl: $el.dataset.templatesUrl,
                        generateUrl: $el.dataset.generateUrl,
                        firmasUrl: $el.dataset.firmasUrl,
                    });
                    $dispatch('open-modal', 'ficha-generate-letters');
                "
            >
                @if ($compact)
                    <x-lucide-file-text width="16" height="16" aria-hidden="true" />
                @else
                    Generar cartas
                @endif
            </button>
        </div>
    @endif
@endif
