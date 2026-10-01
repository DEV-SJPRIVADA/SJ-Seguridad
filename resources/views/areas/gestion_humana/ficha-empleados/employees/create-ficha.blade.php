<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section ficha-empleados-page ficha-empleados-page--form">
        <div class="app-container">
            <div class="ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form">
                <div class="panel-heading-row ficha-empleados-page__title-row block-spaced-sm">
                    <div class="ficha-empleados-page__title-copy">
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
                                Nuevo vínculo laboral desde requisición. Los datos personales se conservan; actualice las condiciones laborales.
                            @elseif ($fichaEntry)
                                Completa o corrige los datos antes de moverlo a Ficha empleados.
                            @else
                                Registro manual sin requisición — empleados históricos o carga directa en ficha.
                            @endif
                        </p>
                    </div>

                    <div class="ficha-empleados-page__title-actions" role="toolbar" aria-label="Acciones de creación">
                        <a
                            href="{{ route('gestion-humana.ficha-empleados.employees.index', $fichaEntry ? ['estado' => 'pendientes'] : []) }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Volver al listado"
                            aria-label="Volver al listado"
                        >
                            <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                        </a>
                        <button
                            type="submit"
                            form="ficha-empleados-form"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            title="{{ ($isRehire ?? false) ? 'Confirmar reingreso' : 'Crear empleado' }}"
                            aria-label="{{ ($isRehire ?? false) ? 'Confirmar reingreso' : 'Crear empleado' }}"
                        >
                            <x-lucide-save width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>

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

                    @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-fields', [
                        'profile' => $profile,
                        'catalogs' => $catalogs,
                        'lockIdentityFields' => $isRehire ?? false,
                        'canEditRequiresCourses' => $canEditRequiresCourses ?? false,
                        'canEditRequiresAcreditacion' => $canEditRequiresAcreditacion ?? false,
                        'canViewRequirementFlags' => $canViewRequirementFlags ?? false,
                        'showHiredDocumentSection' => true,
                        'fichaEntry' => $fichaEntry,
                        'isRehire' => $isRehire ?? false,
                    ])
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
