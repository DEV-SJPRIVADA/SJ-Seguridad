<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form">
            <div class="panel-heading-row ficha-empleados-page__title-row">
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
                        <x-lucide-icon name="history" :size="20" />
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    @endpush

    @php
        $showTerminateModal = $errors->hasAny([
            'termination_cause_code',
            'last_work_day',
            'termination_date',
            'is_rehireable',
            'termination_notes',
        ]);
    @endphp

    <div class="page-section ficha-empleados-page ficha-empleados-page--form">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-page__alert">{{ session('status') }}</div>
            @endif

            @if ($errors->any() && ! $showTerminateModal)
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
                action="{{ route('gestion-humana.ficha-empleados.employees.ficha.update', $entry) }}"
                class="panel ficha-empleados-form"
                id="ficha-empleados-form"
            >
                @csrf
                @method('PATCH')

                <div class="panel__body panel__body--compact">
                    @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-fields', [
                        'profile' => $profile,
                        'catalogs' => $catalogs,
                        'lockIdentityFields' => true,
                    ])
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
                    <button type="submit" class="btn btn--primary">Guardar ficha</button>
                </div>
            </form>

            @include('areas.gestion_humana.ficha-empleados.partials.terminate-modal', [
                'entry' => $entry,
                'catalogs' => $catalogs,
                'canTerminate' => $canTerminate ?? false,
                'show' => $showTerminateModal,
            ])

            @include('areas.gestion_humana.ficha-empleados.partials.employment-period-history-modal', [
                'employmentHistory' => $employmentHistory,
            ])
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
