{{-- Variables: $profile, $catalogs, $lockIdentityFields ?? false --}}
@php
    $identityLocked = (bool) ($lockIdentityFields ?? false);

    $payrollExtra = static function (string $key, mixed $default = null) use ($profile): mixed {
        return old('payroll_extra.'.$key, $profile->payrollExtraValue($key, $default));
    };
@endphp

<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Identificación</h3>
        <p class="ficha-empleados-form__section-lead">Tipo de documento, datos demográficos y expedición.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'document_type',
            'name' => 'document_type',
            'label' => 'Tipo documento',
            'catalogKey' => 'document_type',
            'catalogs' => $catalogs,
            'value' => old('document_type', $profile->document_type),
        ])
        <div class="form-field">
            <label class="form-label" for="birth_date">Fecha nacimiento</label>
            <input id="birth_date" type="date" name="birth_date" class="form-input" value="{{ old('birth_date', optional($profile->birth_date)->format('Y-m-d')) }}" @readonly($identityLocked)>
        </div>
        <div class="form-field">
            <label class="form-label" for="sex">Género <span class="text-danger">*</span></label>
            <select id="sex" name="sex" class="form-select js-ficha-select" required>
                <option value="">—</option>
                <option value="M" @selected(old('sex', $profile->sex) === 'M')>Masculino</option>
                <option value="F" @selected(old('sex', $profile->sex) === 'F')>Femenino</option>
            </select>
        </div>
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'expedition_city_code',
            'name' => 'expedition_city_code',
            'label' => 'Ciudad expedición documento',
            'catalogKey' => 'city',
            'catalogs' => $catalogs,
            'value' => old('expedition_city_code', $profile->expedition_city_code),
            'disabled' => $identityLocked,
        ])
        <div class="form-field">
            <label class="form-label" for="expedition_date">Fecha expedición documento</label>
            <input id="expedition_date" type="date" name="expedition_date" class="form-input" value="{{ old('expedition_date', optional($profile->expedition_date)->format('Y-m-d')) }}" @readonly($identityLocked)>
        </div>
    </div>
</section>

<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Contacto y ubicación</h3>
        <p class="ficha-empleados-form__section-lead">Correo, teléfonos, dirección y ciudad de residencia.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        <div class="form-field">
            <label class="form-label" for="email">Correo</label>
            <input id="email" type="email" name="email" class="form-input" value="{{ old('email', $profile->email) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="phone">Teléfono</label>
            <input id="phone" name="phone" class="form-input" value="{{ old('phone', $profile->phone) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="phone_secondary">Teléfono 2</label>
            <input id="phone_secondary" name="phone_secondary" class="form-input" value="{{ old('phone_secondary', $profile->phone_secondary) }}">
        </div>
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'residence_city_code',
            'name' => 'residence_city_code',
            'label' => 'Ciudad residencia',
            'catalogKey' => 'city',
            'catalogs' => $catalogs,
            'value' => old('residence_city_code', $profile->residence_city_code),
        ])
        <div class="form-field form-grid__full">
            <label class="form-label" for="address">Dirección</label>
            <input id="address" name="address" class="form-input" value="{{ old('address', $profile->address) }}">
        </div>
    </div>
</section>

<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Contrato y nómina</h3>
        <p class="ficha-empleados-form__section-lead">Cargo, compensación, tipo de vinculación y fechas contractuales.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'position_code',
            'name' => 'position_code',
            'label' => 'Cargo',
            'catalogKey' => 'position',
            'catalogs' => $catalogs,
            'value' => old('position_code', $profile->position_code),
            'required' => true,
        ])
        <div class="form-field">
            <label class="form-label" for="salary">Salario <span class="text-danger">*</span></label>
            <div class="currency-input-wrap ficha-empleados-form__currency">
                <input
                    id="salary"
                    name="salary"
                    type="text"
                    inputmode="numeric"
                    autocomplete="off"
                    class="form-input js-ficha-currency"
                    value="{{ old('salary', $profile->salary !== null ? (int) $profile->salary : '') }}"
                    data-initial-value="{{ old('salary', $profile->salary !== null ? (int) $profile->salary : '') }}"
                    required
                >
            </div>
        </div>
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'salary_type_code',
            'name' => 'salary_type_code',
            'label' => 'Tipo salario',
            'catalogKey' => 'salary_type',
            'catalogs' => $catalogs,
            'value' => old('salary_type_code', $profile->salary_type_code),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'contract_type_code',
            'name' => 'contract_type_code',
            'label' => 'Tipo contrato',
            'catalogKey' => 'contract_type',
            'catalogs' => $catalogs,
            'value' => old('contract_type_code', $profile->contract_type_code),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'linkage_type',
            'name' => 'linkage_type',
            'label' => 'Tipo vinculación',
            'catalogKey' => 'linkage_type',
            'catalogs' => $catalogs,
            'value' => old('linkage_type', $profile->linkage_type),
        ])
        <div class="form-field">
            <label class="form-label" for="salary_scale">Escala salarial</label>
            <input id="salary_scale" name="salary_scale" class="form-input" value="{{ old('salary_scale', $profile->salary_scale) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="hire_date">Fecha ingreso <span class="text-danger">*</span></label>
            <input id="hire_date" type="date" name="hire_date" class="form-input" value="{{ old('hire_date', optional($profile->hire_date)->format('Y-m-d')) }}" required>
        </div>
        <div class="form-field">
            <label class="form-label" for="contract_end_date">Fecha vencimiento contrato</label>
            <input id="contract_end_date" type="date" name="contract_end_date" class="form-input" value="{{ old('contract_end_date', optional($profile->contract_end_date)->format('Y-m-d')) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="contributor_type">Tipo cotizante</label>
            <input id="contributor_type" name="contributor_type" class="form-input" value="{{ old('contributor_type', $profile->contributor_type) }}">
        </div>
    </div>
</section>

<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Centros</h3>
        <p class="ficha-empleados-form__section-lead">Centro de costo nómina y centro de trabajo operativo.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'cost_center_code',
            'name' => 'cost_center_code',
            'label' => 'Centro de costo',
            'catalogKey' => 'cost_center',
            'catalogs' => $catalogs,
            'value' => old('cost_center_code', $profile->cost_center_code),
            'required' => true,
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_work_center_code',
            'name' => 'payroll_extra[work_center_code]',
            'label' => 'Centro de trabajo',
            'catalogKey' => 'work_center',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('work_center_code'),
        ])
    </div>
</section>

<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Seguridad social</h3>
        <p class="ficha-empleados-form__section-lead">EPS, AFP, ARP, caja de compensación y fechas de afiliación.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'eps_code',
            'name' => 'eps_code',
            'label' => 'EPS',
            'catalogKey' => 'eps',
            'catalogs' => $catalogs,
            'value' => old('eps_code', $profile->eps_code),
            'required' => true,
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'afp_code',
            'name' => 'afp_code',
            'label' => 'AFP',
            'catalogKey' => 'afp',
            'catalogs' => $catalogs,
            'value' => old('afp_code', $profile->afp_code),
            'required' => true,
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_ccf_code',
            'name' => 'payroll_extra[ccf_code]',
            'label' => 'Caja de compensación',
            'catalogKey' => 'ccf',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('ccf_code'),
            'required' => true,
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'risk_level',
            'name' => 'risk_level',
            'label' => 'Nivel riesgo ARP',
            'catalogKey' => 'risk_level',
            'catalogs' => $catalogs,
            'value' => old('risk_level', $profile->risk_level),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_arp_code',
            'name' => 'payroll_extra[arp_code]',
            'label' => 'Código ARP',
            'catalogKey' => 'arp',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('arp_code'),
        ])
        <div class="form-field">
            <label class="form-label" for="payroll_extra_eps_start_date">Fecha ingreso EPS</label>
            <input id="payroll_extra_eps_start_date" type="date" name="payroll_extra[eps_start_date]" class="form-input" value="{{ old('payroll_extra.eps_start_date', $payrollExtra('eps_start_date')) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="payroll_extra_afp_start_date">Fecha ingreso AFP</label>
            <input id="payroll_extra_afp_start_date" type="date" name="payroll_extra[afp_start_date]" class="form-input" value="{{ old('payroll_extra.afp_start_date', $payrollExtra('afp_start_date')) }}">
        </div>
    </div>
</section>

<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Pagos</h3>
        <p class="ficha-empleados-form__section-lead">Forma de pago y datos bancarios para nómina.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payment_method_code',
            'name' => 'payment_method_code',
            'label' => 'Forma de pago',
            'catalogKey' => 'payment_method',
            'catalogs' => $catalogs,
            'value' => old('payment_method_code', $profile->payment_method_code),
            'required' => true,
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'bank_code',
            'name' => 'bank_code',
            'label' => 'Banco',
            'catalogKey' => 'bank',
            'catalogs' => $catalogs,
            'value' => old('bank_code', $profile->bank_code),
            'required' => true,
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'account_type',
            'name' => 'account_type',
            'label' => 'Tipo de cuenta',
            'catalogKey' => 'account_type',
            'catalogs' => $catalogs,
            'value' => old('account_type', $profile->account_type),
            'required' => true,
        ])
        <div class="form-field">
            <label class="form-label" for="account_number">Número de cuenta <span class="text-danger">*</span></label>
            <input id="account_number" name="account_number" class="form-input" value="{{ old('account_number', $profile->account_number) }}" required autocomplete="off">
        </div>
    </div>
</section>

<section class="ficha-empleados-form__section ficha-empleados-form__section--last">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Nómina avanzada</h3>
        <p class="ficha-empleados-form__section-lead">Parámetros adicionales de plantilla masivos (opcionales salvo los ya obligatorios arriba).</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_workday',
            'name' => 'payroll_extra[workday]',
            'label' => 'Jornada',
            'catalogKey' => 'workday',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('workday'),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_withholding_type',
            'name' => 'payroll_extra[withholding_type]',
            'label' => 'Tipo retención en la fuente',
            'catalogKey' => 'withholding_type',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('withholding_type'),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_expense_type',
            'name' => 'payroll_extra[expense_type]',
            'label' => 'Tipo gasto',
            'catalogKey' => 'expense_type',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('expense_type'),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'economic_activity_code',
            'name' => 'economic_activity_code',
            'label' => 'Actividad económica',
            'catalogKey' => 'economic_activity',
            'catalogs' => $catalogs,
            'value' => old('economic_activity_code', $profile->economic_activity_code),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_branch_code',
            'name' => 'payroll_extra[branch_code]',
            'label' => 'Sucursal',
            'catalogKey' => 'branch',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('branch_code'),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_destination_code',
            'name' => 'payroll_extra[destination_code]',
            'label' => 'Destino',
            'catalogKey' => 'destination',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('destination_code'),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_zone_code',
            'name' => 'payroll_extra[zone_code]',
            'label' => 'Zona',
            'catalogKey' => 'zone',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('zone_code'),
        ])
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
            'id' => 'payroll_extra_severance_admin_code',
            'name' => 'payroll_extra[severance_admin_code]',
            'label' => 'Administradora de cesantías',
            'catalogKey' => 'severance_admin',
            'catalogs' => $catalogs,
            'value' => $payrollExtra('severance_admin_code'),
        ])
        <div class="form-field">
            <label class="form-label" for="payroll_extra_vacation_base_date">Fecha base vacaciones</label>
            <input id="payroll_extra_vacation_base_date" type="date" name="payroll_extra[vacation_base_date]" class="form-input" value="{{ old('payroll_extra.vacation_base_date', $payrollExtra('vacation_base_date')) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="payroll_extra_military_book">Libreta militar</label>
            <input id="payroll_extra_military_book" name="payroll_extra[military_book]" class="form-input" value="{{ old('payroll_extra.military_book', $payrollExtra('military_book')) }}">
        </div>
        <div class="form-field">
            <label class="form-label" for="payroll_extra_exclude_overtime">Excluir horas extra</label>
            <select id="payroll_extra_exclude_overtime" name="payroll_extra[exclude_overtime]" class="form-select js-ficha-select">
                <option value="">—</option>
                <option value="0" @selected((string) $payrollExtra('exclude_overtime') === '0')>No</option>
                <option value="1" @selected((string) $payrollExtra('exclude_overtime') === '1')>Sí</option>
            </select>
        </div>
    </div>
</section>
