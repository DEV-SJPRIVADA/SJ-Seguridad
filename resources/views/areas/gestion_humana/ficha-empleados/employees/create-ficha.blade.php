<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">
                    @if ($isRehire ?? false)
                        Reingreso — {{ $fichaEntry->hired_full_name }}
                    @elseif ($fichaEntry)
                        Gestionar empleado — {{ $fichaEntry->hired_full_name }}
                    @else
                        Nuevo empleado
                    @endif
                </h2>
                <p class="panel-text">
                    @if ($isRehire ?? false)
                        Nuevo vinculo laboral desde requisicion. Los datos personales se conservan; actualice las condiciones laborales.
                    @elseif ($fichaEntry)
                        Completa o corrige los datos antes de moverlo a Ficha empleados.
                    @else
                        Registro manual sin requisición — empleados históricos o carga directa en ficha.
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    <div class="page-section ficha-empleados-page ficha-empleados-page--form">
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

                @if ($fichaEntry)
                    <input type="hidden" name="ficha_entry_id" value="{{ $fichaEntry->id }}">
                @endif

                <div class="panel__body panel__body--compact">
                    @if ($requisitionReference)
                        @include('areas.gestion_humana.ficha-empleados.partials.ficha-requisition-reference', [
                            'reference' => $requisitionReference,
                        ])
                    @endif

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
                                    value="{{ old('hired_document', $fichaEntry->hired_document ?? '') }}"
                                    required
                                    autocomplete="off"
                                    @readonly($isRehire ?? false)
                                >
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="hired_full_name">Nombre completo <span class="text-danger">*</span></label>
                                <input
                                    id="hired_full_name"
                                    name="hired_full_name"
                                    class="form-input"
                                    value="{{ old('hired_full_name', $fichaEntry->hired_full_name ?? '') }}"
                                    required
                                    autocomplete="off"
                                    @readonly($isRehire ?? false)
                                >
                            </div>
                        </div>
                    </section>

                    @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-fields', [
                        'profile' => $profile,
                        'catalogs' => $catalogs,
                        'lockIdentityFields' => $isRehire ?? false,
                    ])
                </div>

                <div class="panel__footer panel__footer--actions ficha-empleados-form__footer">
                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index', $fichaEntry ? ['estado' => 'pendientes'] : []) }}" class="btn btn--secondary">Volver</a>
                    <button type="submit" class="btn btn--primary">{{ ($isRehire ?? false) ? 'Confirmar reingreso' : ($fichaEntry ? 'Crear empleado' : 'Crear empleado') }}</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
