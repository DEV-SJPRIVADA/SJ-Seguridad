@props([
    'period',
    'canGenerateContratacionLetters' => false,
])

@if ($canGenerateContratacionLetters && $period && $period->status === \App\Models\EmployeeFichaEmploymentPeriod::STATUS_ACTIVO)
    <div class="ficha-empleados-letter-actions">
        <button
            type="button"
            class="btn btn--primary btn--sm"
            data-templates-url="{{ route('gestion-humana.ficha-empleados.employees.contratacion.templates', $period) }}"
            data-generate-url="{{ route('gestion-humana.ficha-empleados.employees.contratacion.generate', $period) }}"
            data-firmas-url="{{ route('gestion-humana.ficha-empleados.employees.contratacion.firmas', $period) }}"
            x-data=""
            x-on:click.prevent="
                $dispatch('ficha-prepare-generate-contratacion', {
                    templatesUrl: $el.dataset.templatesUrl,
                    generateUrl: $el.dataset.generateUrl,
                    firmasUrl: $el.dataset.firmasUrl,
                });
                $dispatch('open-modal', 'ficha-generate-contratacion');
            "
        >Generar carta de contratacion</button>
    </div>
@endif
