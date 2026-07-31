<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
        <div class="app-container ficha-empleados-page__workspace-header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Ficha — {{ $entry->hired_full_name }}</h2>
                <p class="panel-text">
                    Cédula {{ $entry->hired_document }}
                    · {{ $entry->requisitionCode() ?: 'Sin requisición' }}
                </p>
            </div>
        </div>
    </x-slot>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
    @endpush

    <div class="page-section ficha-empleados-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-page__alert">{{ session('status') }}</div>
            @endif

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
                    ])
                </div>

                <div class="panel__footer panel__footer--actions ficha-empleados-form__footer">
                    <a href="{{ route('gestion-humana.ficha-empleados.employees.index', $entry->moved_to_ficha_at ? [] : ['estado' => 'pendientes']) }}" class="btn btn--secondary">Volver</a>
                    <button type="submit" class="btn btn--primary">Guardar ficha</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
