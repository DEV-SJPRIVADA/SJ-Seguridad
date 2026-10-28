{{-- Variables: $reference (array from EmployeeFichaProfilePrefill::requisitionReferenceForEntry) --}}
{{-- Bloque informativo: NO son campos del formulario. Se muestran como datos de consulta
     (dl/dt/dd) para que no parezcan editables; los datos de nómina se eligen en los catálogos. --}}
@php
    $referenceSalary = filled($reference['base_salary'] ?? null)
        ? '$ '.number_format((float) $reference['base_salary'], 0, ',', '.')
        : null;

    $referenceHiringDate = filled($reference['hiring_date'] ?? null)
        ? \Illuminate\Support\Carbon::parse($reference['hiring_date'])->format('d/m/Y')
        : null;

    $referenceItems = [
        ['label' => 'Código requisición', 'value' => $reference['code'] ?? null, 'modifier' => 'code'],
        ['label' => 'Cliente', 'value' => $reference['client_name'] ?? null],
        ['label' => 'Cargo (requisición)', 'value' => $reference['position_name'] ?? null],
        ['label' => 'Tipo contrato (requisición)', 'value' => $reference['contract_type_name'] ?? null],
        ['label' => 'Salario sugerido', 'value' => $referenceSalary, 'modifier' => 'salary'],
        ['label' => 'Fecha ingreso sugerida', 'value' => $referenceHiringDate, 'modifier' => 'date'],
        ['label' => 'Centro de costo (texto requisición)', 'value' => $reference['cost_center_hint'] ?? null],
        ['label' => 'Ciudad (requisición)', 'value' => $reference['city_name'] ?? null],
    ];
@endphp

<section
    class="ficha-empleados-form__section ficha-empleados-form__section--reference"
    aria-labelledby="ficha-referencia-requisicion-title"
>
    <header class="ficha-empleados-form__reference-head">
        <span class="ficha-empleados-form__section-icon" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 2H9a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V3a1 1 0 0 0-1-1Z"/>
                <path d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-2"/>
                <path d="M8 11h8"/><path d="M8 16h5"/>
            </svg>
        </span>

        <div class="ficha-empleados-form__reference-heading">
            <h3 class="ficha-empleados-form__section-title" id="ficha-referencia-requisicion-title">
                Referencia de requisición
            </h3>
            <p class="ficha-empleados-form__section-lead">
                Datos de la requisición de origen, solo consulta: se editan en el módulo Requisiciones.
                En este formulario elija los campos exportables a nómina en los catálogos.
            </p>
        </div>

        <span class="ficha-empleados-form__reference-badge">Solo consulta</span>
    </header>

    <dl class="ficha-empleados-form__reference-grid">
        @foreach ($referenceItems as $item)
            @php
                $value = filled($item['value'] ?? null) ? $item['value'] : null;
                $modifier = $item['modifier'] ?? null;
            @endphp
            <div @class([
                'ficha-empleados-form__reference-item',
                'ficha-empleados-form__reference-item--'.$modifier => $modifier !== null,
            ])>
                <dt class="ficha-empleados-form__reference-label">{{ $item['label'] }}</dt>
                <dd @class([
                    'ficha-empleados-form__reference-value',
                    'ficha-empleados-form__reference-value--empty' => $value === null,
                ])>{{ $value ?? '—' }}</dd>
            </div>
        @endforeach
    </dl>
</section>
