<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Nuevo empleado</h2>
                <p class="panel-text">Registro manual sin requisición — empleados históricos o carga directa en ficha.</p>
            </div>
        </div>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    @endpush

    <div class="page-section ficha-empleados-page">
        <div class="app-container">
            @if ($errors->any())
                <div class="alert alert--danger ficha-empleados-page__alert">
                    <ul class="ficha-empleados-form__error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.store') }}"
                class="panel ficha-empleados-form"
                id="ficha-empleados-form"
            >
                @csrf

                <div class="panel__body panel__body--compact">
                    <section class="ficha-empleados-form__section">
                        <header class="ficha-empleados-form__section-head">
                            <h3 class="ficha-empleados-form__section-title">Identificación</h3>
                            <p class="ficha-empleados-form__section-lead">Datos mínimos para crear el empleado en ficha.</p>
                        </header>
                        <div class="form-grid form-grid--two ficha-empleados-form__grid">
                            <div class="form-field">
                                <label class="form-label" for="hired_document">Cédula <span class="text-danger">*</span></label>
                                <input
                                    id="hired_document"
                                    name="hired_document"
                                    class="form-input"
                                    value="{{ old('hired_document') }}"
                                    required
                                    autocomplete="off"
                                >
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="hired_full_name">Nombre completo <span class="text-danger">*</span></label>
                                <input
                                    id="hired_full_name"
                                    name="hired_full_name"
                                    class="form-input"
                                    value="{{ old('hired_full_name') }}"
                                    required
                                    autocomplete="off"
                                >
                            </div>
                        </div>
                    </section>

                    @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-fields', [
                        'profile' => $profile,
                        'catalogs' => $catalogs,
                    ])
                </div>

                <div class="panel__footer panel__footer--actions ficha-empleados-form__footer">
                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index') }}" class="btn btn--secondary">Volver</a>
                    <button type="submit" class="btn btn--primary">Crear empleado</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
