<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">
                    {{ $fichaEntry ? 'Gestionar empleado — '.$fichaEntry->hired_full_name : 'Nuevo empleado' }}
                </h2>
                <p class="panel-text">
                    @if ($fichaEntry)
                        Completa o corrige los datos antes de moverlo a Ficha empleados.
                    @else
                        Registro manual sin requisición — empleados históricos o carga directa en ficha.
                    @endif
                </p>
            </div>
        </div>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    @endpush

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
                    @if ($fichaEntry && $fichaEntry->requisition)
                        <section class="ficha-empleados-form__section">
                            <header class="ficha-empleados-form__section-head">
                                <h3 class="ficha-empleados-form__section-title">Referencia de requisición</h3>
                                <p class="ficha-empleados-form__section-lead">Datos de solo lectura tomados de la requisición contratada.</p>
                            </header>
                            <div class="form-grid form-grid--two ficha-empleados-form__grid">
                                <div class="form-field">
                                    <label class="form-label">Código requisición</label>
                                    <input class="form-input" value="{{ $fichaEntry->requisitionCode() ?: '—' }}" disabled readonly>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Cliente</label>
                                    <input class="form-input" value="{{ $fichaEntry->clientName() ?: '—' }}" disabled readonly>
                                </div>
                                <div class="form-field">
                                    <label class="form-label">Cargo</label>
                                    <input class="form-input" value="{{ $fichaEntry->positionName() ?: '—' }}" disabled readonly>
                                </div>
                            </div>
                        </section>
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
                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index', $fichaEntry ? ['estado' => 'pendientes'] : []) }}" class="btn btn--secondary">Volver</a>
                    <button type="submit" class="btn btn--primary">Crear empleado</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
