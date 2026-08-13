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
            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period) }}"
                class="ficha-empleados-letter-actions__form"
                onsubmit="return confirm('Regenerar las cartas? Se reemplazara el ZIP anterior.');"
            >
                @csrf
                <button type="submit" class="btn btn--secondary btn--sm">Regenerar cartas</button>
            </form>
        @else
            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.period.letters.generate', $period) }}"
                class="ficha-empleados-letter-actions__form"
            >
                @csrf
                <button type="submit" class="btn btn--primary btn--sm">Generar cartas</button>
            </form>
        @endif
    </div>
@endif
