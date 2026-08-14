{{-- Variables: $reference (array from EmployeeFichaProfilePrefill::requisitionReferenceForEntry) --}}
<section class="ficha-empleados-form__section ficha-empleados-form__section--reference">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Referencia de requisición</h3>
        <p class="ficha-empleados-form__section-lead">
            Solo lectura. Use estos datos como guía; los campos exportables a nómina deben elegirse en los catálogos del formulario.
        </p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        <div class="form-field">
            <label class="form-label">Código requisición</label>
            <input class="form-input" value="{{ $reference['code'] ?? '—' }}" disabled readonly>
        </div>
        <div class="form-field">
            <label class="form-label">Cliente</label>
            <input class="form-input" value="{{ $reference['client_name'] ?? '—' }}" disabled readonly>
        </div>
        <div class="form-field">
            <label class="form-label">Cargo (requisición)</label>
            <input class="form-input" value="{{ $reference['position_name'] ?? '—' }}" disabled readonly>
        </div>
        <div class="form-field">
            <label class="form-label">Tipo contrato (requisición)</label>
            <input class="form-input" value="{{ $reference['contract_type_name'] ?? '—' }}" disabled readonly>
        </div>
        <div class="form-field">
            <label class="form-label">Salario sugerido</label>
            <input class="form-input" value="{{ isset($reference['base_salary']) ? number_format((float) $reference['base_salary'], 0, ',', '.') : '—' }}" disabled readonly>
        </div>
        <div class="form-field">
            <label class="form-label">Fecha ingreso sugerida</label>
            <input class="form-input" value="{{ $reference['hiring_date'] ?? '—' }}" disabled readonly>
        </div>
        <div class="form-field">
            <label class="form-label">Centro de costo (texto requisición)</label>
            <input class="form-input" value="{{ $reference['cost_center_hint'] ?? '—' }}" disabled readonly>
        </div>
        <div class="form-field">
            <label class="form-label">Ciudad (requisición)</label>
            <input class="form-input" value="{{ $reference['city_name'] ?? '—' }}" disabled readonly>
        </div>
    </div>
</section>
