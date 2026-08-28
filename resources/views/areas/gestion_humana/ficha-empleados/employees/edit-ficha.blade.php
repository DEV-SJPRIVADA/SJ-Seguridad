<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form">
            <div class="panel-heading-row ficha-empleados-page__title-row block-spaced-sm">
                <div>
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
                </div>
                @if ($employmentHistory->isNotEmpty())
                    <button
                        type="button"
                        class="ficha-empleados-page__history-icon"
                        title="Historial de vinculos"
                        aria-label="Ver historial de vinculos"
                        x-data=""
                        x-on:click="$dispatch('open-modal', 'ficha-employment-history')"
                    >
                        <x-lucide-history width="20" height="20" aria-hidden="true" />
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $showTerminateModal = $errors->hasAny([
            'termination_cause_code',
            'last_work_day',
            'termination_date',
            'is_rehireable',
            'termination_notes',
        ]);
    @endphp

    <div
        class="page-section ficha-empleados-page ficha-empleados-page--form"
        x-data="{ isEditing: {{ $errors->any() && ! $showTerminateModal ? 'true' : 'false' }} }"
    >
        <div class="app-container">
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

            <div class="panel-heading-row" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="text-small" style="font-weight: 500; color: #64748b;">Estado del formulario:</span>
                    <template x-if="!isEditing">
                        <span class="badge" style="background-color: #f1f5f9; color: #475569; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.8rem; border: 1px solid #cbd5e1;">Solo lectura</span>
                    </template>
                    <template x-if="isEditing">
                        <span class="badge" style="background-color: #fef3c7; color: #92400e; font-weight: 600; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.8rem; border: 1px solid #fde68a;">Edición habilitada</span>
                    </template>
                </div>

                <div>
                    <template x-if="!isEditing">
                        <button
                            type="button"
                            class="btn btn--primary"
                            @click="isEditing = true"
                        >
                            <x-lucide-edit-3 width="16" height="16" aria-hidden="true" style="margin-right: 0.35rem; display: inline-block; vertical-align: middle;" />
                            Habilitar edición
                        </button>
                    </template>
                    <template x-if="isEditing">
                        <button
                            type="button"
                            class="btn btn--secondary"
                            @click="isEditing = false"
                        >
                            Bloquear edición
                        </button>
                    </template>
                </div>
            </div>

            @if ($canGenerateContratacionLetters)
                <div class="panel__footer panel__footer--actions ficha-empleados-form__footer ficha-empleados-form__footer--letters" style="margin-bottom: 1rem;">
                    @include('areas.gestion_humana.ficha-empleados.partials.contratacion-letter-actions', [
                        'period' => $activePeriod,
                        'canGenerateContratacionLetters' => $canGenerateContratacionLetters,
                    ])
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.ficha.update', $entry) }}"
                class="panel ficha-empleados-form"
                :class="{ 'ficha-empleados-form--readonly': !isEditing }"
                id="ficha-empleados-form"
            >
                @csrf
                @method('PATCH')

                <div class="panel__body panel__body--compact">
                    @if ($requisitionReference ?? null)
                        @include('areas.gestion_humana.ficha-empleados.partials.ficha-requisition-reference', [
                            'reference' => $requisitionReference,
                        ])
                    @endif

                    <div class="ficha-empleados-form__fields">
                        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-fields', [
                            'profile' => $profile,
                            'catalogs' => $catalogs,
                            'lockIdentityFields' => false,
                        ])
                    </div>
                </div>

                <div class="panel__footer panel__footer--actions ficha-empleados-form__footer">
                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index') }}" class="btn btn--secondary">Volver</a>
                    @if ($canTerminate ?? false)
                        <button
                            type="button"
                            class="btn btn--secondary"
                            x-data=""
                            x-on:click.prevent="$dispatch('open-modal', 'ficha-terminate')"
                        >Desvinculación</button>
                    @endif

                    <template x-if="!isEditing">
                        <button
                            type="button"
                            class="btn btn--primary"
                            @click="isEditing = true"
                        >
                            <x-lucide-edit-3 width="16" height="16" aria-hidden="true" style="margin-right: 0.35rem; display: inline-block; vertical-align: middle;" />
                            Habilitar edición
                        </button>
                    </template>

                    <template x-if="isEditing">
                        <div style="display: inline-flex; gap: 0.5rem; align-items: center;">
                            <button
                                type="button"
                                class="btn btn--secondary"
                                @click="isEditing = false"
                            >Cancelar</button>
                            <button type="submit" class="btn btn--primary">Guardar ficha</button>
                        </div>
                    </template>
                </div>
            </form>

            @if ($canGenerateLetters ?? false)
                <div class="panel__footer panel__footer--actions ficha-empleados-form__footer ficha-empleados-form__footer--letters">
                    @include('areas.gestion_humana.ficha-empleados.partials.termination-letter-actions', [
                        'period' => $letterPeriod,
                        'canGenerateLetters' => $canGenerateLetters,
                    ])
                </div>
            @endif

            @include('areas.gestion_humana.ficha-empleados.partials.terminate-modal', [
                'entry' => $entry,
                'catalogs' => $catalogs,
                'canTerminate' => $canTerminate ?? false,
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
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
