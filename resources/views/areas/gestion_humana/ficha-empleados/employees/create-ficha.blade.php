<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Alta / Gestionar empleado: avisar y llevar al usuario al campo que falló (HTML5 o servidor). --}}
    <div
        class="page-section ficha-empleados-page ficha-empleados-page--form"
        x-data="{
            saveValidationMessage: '',
            init() {
                if ({{ $errors->any() ? 'true' : 'false' }}) {
                    this.$nextTick(() => this.scrollToFirstServerError());
                }
            },
            scrollToFirstServerError() {
                const alertEl = document.querySelector('.ficha-empleados-page__alert.alert--danger');
                if (alertEl) {
                    alertEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                const fieldError = document.querySelector('.ficha-empleados-form .form-error-list, .ficha-empleados-form .text-danger, .ficha-empleados-form .form-error');
                const wrap = fieldError
                    ? (fieldError.closest('.form-field') || fieldError.closest('.searchable-select-wrap') || fieldError.closest('.ficha-empleados-form__section') || fieldError)
                    : null;

                if (wrap) {
                    setTimeout(() => wrap.scrollIntoView({ behavior: 'smooth', block: 'center' }), 250);
                }
            },
            handleFichaSubmit(event) {
                this.saveValidationMessage = '';

                const form = event.target;
                if (! form.checkValidity()) {
                    event.preventDefault();

                    const invalid = form.querySelector(':invalid');
                    if (invalid) {
                        const wrap = invalid.closest('.form-field')
                            || invalid.closest('.searchable-select-wrap')
                            || invalid.closest('.searchable-select')
                            || invalid.closest('.ficha-empleados-form__section')
                            || invalid;
                        wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        if (typeof invalid.focus === 'function') {
                            try { invalid.focus({ preventScroll: true }); } catch (e) { invalid.focus(); }
                        }
                        if (typeof invalid.reportValidity === 'function') {
                            invalid.reportValidity();
                        }
                    }

                    this.saveValidationMessage = 'Hay campos obligatorios incompletos o inválidos. Revise los marcados con * (suelen estar más arriba en el formulario) e intente de nuevo.';
                }
            },
            scrollToTop() {
                const main = document.querySelector('.app-main');
                if (main) {
                    main.scrollTo({ top: 0, behavior: 'smooth' });
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });

                const top = document.getElementById('ficha-empleados-create-top');
                if (top) {
                    top.focus({ preventScroll: true });
                }
            },
        }"
    >
        <div class="app-container">
            <div
                id="ficha-empleados-create-top"
                class="ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form"
                tabindex="-1"
            >
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

            @if (session('status'))
                <div class="alert alert--success ficha-empleados-page__alert">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger ficha-empleados-page__alert" role="alert" aria-live="assertive">
                    <p class="font-semibold" style="margin-bottom: 0.5rem;">
                        No se pudo guardar. Corrija los siguientes errores:
                    </p>
                    <ul class="ficha-empleados-form__error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div
                class="alert alert--danger ficha-empleados-page__alert"
                x-show="saveValidationMessage"
                x-cloak
                role="alert"
                aria-live="assertive"
                x-text="saveValidationMessage"
            ></div>

            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.store') }}"
                class="panel ficha-empleados-form"
                id="ficha-empleados-form"
                @submit="handleFichaSubmit($event)"
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

            {{-- Volver al inicio del scroll tras recorrer el formulario largo --}}
            <div class="ficha-empleados-page__scroll-top">
                <button
                    type="button"
                    class="ficha-empleados-page__scroll-top-btn"
                    title="Ir al inicio"
                    aria-label="Ir al inicio del formulario"
                    x-on:click="scrollToTop()"
                >
                    <x-lucide-arrow-up-to-line width="18" height="18" aria-hidden="true" />
                    <span>Ir al inicio</span>
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
