<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Completar ficha — {{ $entry->hired_full_name }}</h2>
                <p class="panel-text">Cédula {{ $entry->hired_document }} · {{ $entry->requisitionCode() ?: 'Sin requisición' }}</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section ficha-empleados-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('gestion-humana.ficha-empleados.employees.ficha.update', $entry) }}" class="panel">
                @csrf
                @method('PATCH')
                <div class="panel__body">
                    <div class="form-grid form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="document_type">Tipo documento</label>
                            <input id="document_type" name="document_type" class="form-input" value="{{ old('document_type', $profile->document_type) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="birth_date">Fecha nacimiento</label>
                            <input id="birth_date" type="date" name="birth_date" class="form-input" value="{{ old('birth_date', optional($profile->birth_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="sex">Sexo (M/F)</label>
                            <input id="sex" name="sex" class="form-input" value="{{ old('sex', $profile->sex) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Correo</label>
                            <input id="email" type="email" name="email" class="form-input" value="{{ old('email', $profile->email) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Teléfono</label>
                            <input id="phone" name="phone" class="form-input" value="{{ old('phone', $profile->phone) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone_secondary">Teléfono 2</label>
                            <input id="phone_secondary" name="phone_secondary" class="form-input" value="{{ old('phone_secondary', $profile->phone_secondary) }}">
                        </div>
                        <div class="form-group form-grid__full">
                            <label class="form-label" for="address">Dirección</label>
                            <input id="address" name="address" class="form-input" value="{{ old('address', $profile->address) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="residence_city_code">Código ciudad</label>
                            <select id="residence_city_code" name="residence_city_code" class="form-input">
                                <option value="">—</option>
                                @foreach ($catalogs['city'] ?? [] as $item)
                                    <option value="{{ $item['code'] }}" @selected(old('residence_city_code', $profile->residence_city_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="residence_city_name">Ciudad</label>
                            <input id="residence_city_name" name="residence_city_name" class="form-input" value="{{ old('residence_city_name', $profile->residence_city_name) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="hire_date">Fecha ingreso</label>
                            <input id="hire_date" type="date" name="hire_date" class="form-input" value="{{ old('hire_date', optional($profile->hire_date)->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="salary">Salario</label>
                            <input id="salary" type="number" step="0.01" name="salary" class="form-input" value="{{ old('salary', $profile->salary) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="position_code">Código cargo</label>
                            <select id="position_code" name="position_code" class="form-input">
                                <option value="">—</option>
                                @foreach ($catalogs['position'] ?? [] as $item)
                                    <option value="{{ $item['code'] }}" @selected(old('position_code', $profile->position_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="position_name">Nombre cargo</label>
                            <input id="position_name" name="position_name" class="form-input" value="{{ old('position_name', $profile->position_name) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="eps_code">Código EPS</label>
                            <select id="eps_code" name="eps_code" class="form-input">
                                <option value="">—</option>
                                @foreach ($catalogs['eps'] ?? [] as $item)
                                    <option value="{{ $item['code'] }}" @selected(old('eps_code', $profile->eps_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="eps_name">Nombre EPS</label>
                            <input id="eps_name" name="eps_name" class="form-input" value="{{ old('eps_name', $profile->eps_name) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="afp_code">Código AFP</label>
                            <select id="afp_code" name="afp_code" class="form-input">
                                <option value="">—</option>
                                @foreach ($catalogs['afp'] ?? [] as $item)
                                    <option value="{{ $item['code'] }}" @selected(old('afp_code', $profile->afp_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="afp_name">Nombre AFP</label>
                            <input id="afp_name" name="afp_name" class="form-input" value="{{ old('afp_name', $profile->afp_name) }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cost_center_code">Centro costo</label>
                            <select id="cost_center_code" name="cost_center_code" class="form-input">
                                <option value="">—</option>
                                @foreach ($catalogs['cost_center'] ?? [] as $item)
                                    <option value="{{ $item['code'] }}" @selected(old('cost_center_code', $profile->cost_center_code) === $item['code'])>{{ $item['code'] }} — {{ $item['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="termination_date">Fecha retiro (desvinculado)</label>
                            <input id="termination_date" type="date" name="termination_date" class="form-input" value="{{ old('termination_date', optional($profile->termination_date)->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
                <div class="panel__footer panel__footer--actions">
                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index', ['estado' => $entry->moved_to_ficha_at ? 'en_ficha' : 'pendientes']) }}" class="btn btn--secondary">Volver</a>
                    <button type="submit" class="btn btn--primary">Guardar ficha</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
