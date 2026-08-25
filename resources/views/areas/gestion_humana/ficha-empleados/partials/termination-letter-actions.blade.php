@props([
    'period',
    'canGenerateLetters' => false,
    'compact' => false,
])

@if ($canGenerateLetters && $period && $period->status === \App\Models\EmployeeFichaEmploymentPeriod::STATUS_CERRADO)
    <div class="{{ $compact ? 'ficha-empleados-letter-actions ficha-empleados-letter-actions--compact' : 'ficha-empleados-letter-actions' }}">
        @if (filled($period->termination_letter_path))
            <a
                href="{{ route('gestion-humana.ficha-empleados.employees.period.letters.download', $period) }}"
                class="btn btn--secondary btn--sm"
            >Descargar cartas</a>
        @endif

        <button
            type="button"
            class="btn {{ filled($period->termination_letter_path) ? 'btn--secondary' : 'btn--primary' }} btn--sm"
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
        >Generar cartas</button>
    </div>
@endif
