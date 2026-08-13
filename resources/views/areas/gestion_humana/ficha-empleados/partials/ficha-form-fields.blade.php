{{-- Variables: $profile, $catalogs, $lockIdentityFields ?? false --}}
<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Datos personales</h3>
        <p class="ficha-empleados-form__section-lead">Identificación y datos básicos del empleado.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        <div class="form-field">
            <label class="form-label" for="document_type">Tipo documento</label>
            <select id="document_type" name="document_type" class="form-select js-ficha-select">
                <option value="">—</option>
                @foreach ($catalogs['document_type'] ?? [] as $item)
                    <option value="{{ $item['code'] }}" @selected(old('document_type', $profile->document_type) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="birth_date">Fecha nacimiento</label>
            <input id="birth_date" type="date" name="birth_date" class="form-input" value="{{ old('birth_date', optional($profile->birth_date)->format('Y-m-d')) }}" @readonly($lockIdentityFields ?? false)>
        </div>
        <div class="form-field">
            <label class="form-label" for="sex">Género</label>
            <select id="sex" name="sex" class="form-select js-ficha-select">
                <option value="">—</option>
                <option value="M" @selected(old('sex', $profile->sex) === 'M')>Masculino</option>
                <option value="F" @selected(old('sex', $profile->sex) === 'F')>Femenino</option>
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="hire_date">Fecha ingreso</label>
            <input id="hire_date" type="date" name="hire_date" class="form-input" value="{{ old('hire_date', optional($profile->hire_date)->format('Y-m-d')) }}">
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
        <div class="form-field">
            <label class="form-label" for="residence_city_code">Ciudad</label>
            <select id="residence_city_code" name="residence_city_code" class="form-select js-ficha-select">
                <option value="">—</option>
                @foreach ($catalogs['city'] ?? [] as $item)
                    <option value="{{ $item['code'] }}" @selected(old('residence_city_code', $profile->residence_city_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field form-grid__full">
            <label class="form-label" for="address">Dirección</label>
            <input id="address" name="address" class="form-input" value="{{ old('address', $profile->address) }}">
        </div>
    </div>
</section>

<section class="ficha-empleados-form__section">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Información laboral</h3>
        <p class="ficha-empleados-form__section-lead">Cargo, salario, centro de costo y estado en nómina.</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        <div class="form-field">
            <label class="form-label" for="position_code">Cargo</label>
            <select id="position_code" name="position_code" class="form-select js-ficha-select">
                <option value="">—</option>
                @foreach ($catalogs['position'] ?? [] as $item)
                    <option value="{{ $item['code'] }}" @selected(old('position_code', $profile->position_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="salary">Salario</label>
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
                >
            </div>
        </div>
        <div class="form-field">
            <label class="form-label" for="cost_center_code">Centro de costo</label>
            <select id="cost_center_code" name="cost_center_code" class="form-select js-ficha-select">
                <option value="">—</option>
                @foreach ($catalogs['cost_center'] ?? [] as $item)
                    <option value="{{ $item['code'] }}" @selected(old('cost_center_code', $profile->cost_center_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
</section>

<section class="ficha-empleados-form__section ficha-empleados-form__section--last">
    <header class="ficha-empleados-form__section-head">
        <h3 class="ficha-empleados-form__section-title">Seguridad social</h3>
        <p class="ficha-empleados-form__section-lead">EPS y fondo de pensiones (AFP).</p>
    </header>
    <div class="form-grid form-grid--two ficha-empleados-form__grid">
        <div class="form-field">
            <label class="form-label" for="eps_code">EPS</label>
            <select id="eps_code" name="eps_code" class="form-select js-ficha-select">
                <option value="">—</option>
                @foreach ($catalogs['eps'] ?? [] as $item)
                    <option value="{{ $item['code'] }}" @selected(old('eps_code', $profile->eps_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-field">
            <label class="form-label" for="afp_code">AFP</label>
            <select id="afp_code" name="afp_code" class="form-select js-ficha-select">
                <option value="">—</option>
                @foreach ($catalogs['afp'] ?? [] as $item)
                    <option value="{{ $item['code'] }}" @selected(old('afp_code', $profile->afp_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                @endforeach
            </select>
        </div>
    </div>
</section>
