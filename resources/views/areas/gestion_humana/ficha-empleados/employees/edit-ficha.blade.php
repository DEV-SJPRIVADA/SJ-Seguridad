<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $canManageFicha = (bool) ($canManage ?? false);
        $showTerminateModal = $errors->hasAny([
            'termination_cause_code',
            'last_work_day',
            'termination_date',
            'is_rehireable',
            'termination_notes',
            'novedades_conflict',
            'force_novedades_conflict',
        ]);
        $startInEditMode = $canManageFicha && $errors->any() && ! $showTerminateModal;
    @endphp

    <div
        class="page-section ficha-empleados-page ficha-empleados-page--form"
        x-data="{
            canManage: {{ $canManageFicha ? 'true' : 'false' }},
            isEditing: {{ $startInEditMode ? 'true' : 'false' }},
            saveValidationMessage: '',
            init() {
                this.$watch('isEditing', () => this.syncFieldLock());
                this.$nextTick(() => this.syncFieldLock());
            },
            syncFieldLock() {
                const root = this.$refs.fichaFields;
                if (! root) {
                    return;
                }

                const editable = this.canManage && this.isEditing;

                root.querySelectorAll('input, textarea').forEach((el) => {
                    if (['hidden', 'file', 'checkbox', 'radio', 'submit', 'button', 'reset'].includes(el.type)) {
                        return;
                    }

                    el.readOnly = ! editable;
                });

                root.querySelectorAll('input[type=checkbox], input[type=radio], select').forEach((el) => {
                    if (el.dataset.lockDisabled === '1') {
                        el.disabled = true;
                        return;
                    }

                    el.disabled = ! editable;
                });
            },
            handleFichaSubmit(event) {
                this.saveValidationMessage = '';

                if (! this.canManage || ! this.isEditing) {
                    event.preventDefault();
                    this.saveValidationMessage = 'Habilita la edición antes de guardar la ficha.';
                    return;
                }

                const form = event.target;
                if (! form.checkValidity()) {
                    event.preventDefault();

                    const invalid = form.querySelector(':invalid');
                    if (invalid) {
                        const wrap = invalid.closest('.form-field')
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

                    this.saveValidationMessage = 'Hay campos obligatorios incompletos. Completa los marcados (suelen estar más arriba en el formulario) e intenta de nuevo.';
                }
            },
        }"
    >
        <div class="app-container">
            <div class="ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form">
                <div class="panel-heading-row ficha-empleados-page__title-row block-spaced-sm">
                    <div class="ficha-empleados-page__title-copy">
                        <h2 class="panel-title panel-title--page">Ficha — {{ $entry->hired_full_name }}</h2>
                        <p class="panel-text">
                            Cédula {{ $entry->hired_document }}
                            · {{ $entry->requisitionCode() ?: 'Sin requisición' }}
                            @if ($activePeriod)
                                · Vinculo #{{ $activePeriod->sequence }} activo
                            @elseif ($profile->employment_status === \App\Models\EmployeeFichaProfile::STATUS_DESVINCULADO)
                                · Desvinculado
                            @endif
                        </p>
                        <div class="ficha-empleados-page__status-line">
                            <template x-if="!isEditing">
                                <span class="status-pill status-pill--muted">Solo lectura</span>
                            </template>
                            <template x-if="isEditing">
                                <span class="status-pill status-pill--warning">Edición habilitada</span>
                            </template>
                        </div>
                    </div>

                    <div class="ficha-empleados-page__title-actions" role="toolbar" aria-label="Acciones de ficha">
                        @if ($canViewEmployeeCursos ?? false)
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Consultar cursos"
                                aria-label="Consultar cursos del empleado"
                                x-on:click="$dispatch('open-modal', 'ficha-employee-cursos')"
                            >
                                <x-lucide-graduation-cap width="18" height="18" aria-hidden="true" />
                            </button>
                        @endif

                        @if ($canViewEmployeeAcreditaciones ?? false)
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Consultar acreditación"
                                aria-label="Consultar acreditación del empleado"
                                x-on:click="$dispatch('open-modal', 'ficha-employee-acreditaciones')"
                            >
                                <x-lucide-badge-check width="18" height="18" aria-hidden="true" />
                            </button>
                        @endif

                        @if ($employmentHistory->isNotEmpty())
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Historial de vínculos"
                                aria-label="Ver historial de vínculos"
                                x-on:click="$dispatch('open-modal', 'ficha-employment-history')"
                            >
                                <x-lucide-history width="18" height="18" aria-hidden="true" />
                            </button>
                        @endif

                        {{-- Cartas de desvinculación: junto a Historial --}}
                        @include('areas.gestion_humana.ficha-empleados.partials.termination-letter-actions', [
                            'period' => $letterPeriod ?? null,
                            'canGenerateLetters' => $canGenerateLetters ?? false,
                            'iconOnly' => true,
                        ])

                        @include('areas.gestion_humana.ficha-empleados.partials.contratacion-letter-actions', [
                            'period' => $activePeriod,
                            'canGenerateContratacionLetters' => $canGenerateContratacionLetters ?? false,
                            'iconOnly' => true,
                        ])

                        @if ($canTerminate ?? false)
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Desvinculación"
                                aria-label="Abrir desvinculación"
                                x-on:click.prevent="$dispatch('open-modal', 'ficha-terminate')"
                            >
                                <x-lucide-user-x width="18" height="18" aria-hidden="true" />
                            </button>
                        @endif

                        <a
                            href="{{ route('gestion-humana.ficha-empleados.employees.index') }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Volver al listado"
                            aria-label="Volver al listado"
                        >
                            <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                        </a>
                    </div>
                </div>
            </div>

            @if (session('status'))
                <div class="alert alert--success ficha-empleados-page__alert">{{ session('status') }}</div>
            @endif

            @if ($errors->any() && ! $showTerminateModal)
                <div class="alert alert--danger ficha-empleados-page__alert">
                    <p class="font-semibold" style="margin-bottom: 0.5rem;">Por favor corrige los siguientes errores en el formulario:</p>
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
                x-text="saveValidationMessage"
            ></div>

            @if ($canManageFicha)
                <div class="panel__footer panel__footer--actions ficha-empleados-form__footer ficha-empleados-form__footer--letters ficha-empleados-form__footer--letters-top">
                    <div class="ficha-empleados-letter-actions ficha-empleados-page__edit-actions">
                        <template x-if="!isEditing">
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                title="Habilitar edición"
                                aria-label="Habilitar edición"
                                x-on:click="isEditing = true"
                            >
                                <x-lucide-pencil width="18" height="18" aria-hidden="true" />
                            </button>
                        </template>
                        <template x-if="isEditing">
                            <span class="ficha-empleados-page__title-actions-group">
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cancelar edición"
                                    aria-label="Cancelar edición"
                                    x-on:click="isEditing = false"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </button>
                                <button
                                    type="submit"
                                    form="ficha-empleados-form"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Guardar ficha"
                                    aria-label="Guardar ficha"
                                >
                                    <x-lucide-save width="18" height="18" aria-hidden="true" />
                                </button>
                            </span>
                        </template>
                    </div>
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.ficha.update', $entry) }}"
                class="panel ficha-empleados-form"
                :class="{ 'ficha-empleados-form--readonly': !isEditing }"
                id="ficha-empleados-form"
                @submit="handleFichaSubmit($event)"
            >
                @csrf
                @method('PATCH')

                <div class="panel__body panel__body--compact">
                    @if ($requisitionReference ?? null)
                        @include('areas.gestion_humana.ficha-empleados.partials.ficha-requisition-reference', [
                            'reference' => $requisitionReference,
                        ])
                    @endif

                    <div
                        class="ficha-empleados-form__fields"
                        x-ref="fichaFields"
                        @beforeinput="if (!canManage || !isEditing) { $event.preventDefault() }"
                        @paste="if (!canManage || !isEditing) { $event.preventDefault() }"
                        @cut="if (!canManage || !isEditing) { $event.preventDefault() }"
                    >
                        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-fields', [
                            'profile' => $profile,
                            'catalogs' => $catalogs,
                            'lockIdentityFields' => false,
                            'canEditRequiresCourses' => $canEditRequiresCourses ?? false,
                            'canEditRequiresAcreditacion' => $canEditRequiresAcreditacion ?? false,
                            'canViewRequirementFlags' => $canViewRequirementFlags ?? false,
                        ])
                    </div>
                </div>
            </form>

            @include('areas.gestion_humana.ficha-empleados.partials.terminate-modal', [
                'entry' => $entry,
                'catalogs' => $catalogs,
                'canTerminate' => $canTerminate ?? false,
                'canForceNovedadesConflict' => $canForceNovedadesConflict ?? false,
                'show' => $showTerminateModal,
            ])

            @include('areas.gestion_humana.ficha-empleados.partials.termination-letter-generate-modal', [
                'canGenerateLetters' => $canGenerateLetters ?? false,
            ])

            @include('areas.gestion_humana.ficha-empleados.partials.contratacion-letter-generate-modal', [
                'canGenerateContratacionLetters' => $canGenerateContratacionLetters ?? false,
            ])

            @include('areas.gestion_humana.ficha-empleados.partials.employment-period-history-modal', [
                'employmentHistory' => $employmentHistory,
                'canGenerateLetters' => $canGenerateLetters ?? false,
            ])

            @include('areas.gestion_humana.ficha-empleados.partials.employee-cursos-modal', [
                'entry' => $entry,
                'employeeCursos' => $employeeCursos ?? collect(),
                'canViewEmployeeCursos' => $canViewEmployeeCursos ?? false,
            ])

            @include('areas.gestion_humana.ficha-empleados.partials.employee-acreditaciones-modal', [
                'entry' => $entry,
                'employeeAcreditaciones' => $employeeAcreditaciones ?? collect(),
                'canViewEmployeeAcreditaciones' => $canViewEmployeeAcreditaciones ?? false,
            ])
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
