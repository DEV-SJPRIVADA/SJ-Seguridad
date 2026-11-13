<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Export Apo: solo grilla editable (antes modo Manual). Precarga por IDs o cédulas. --}}
    <div class="page-section cursos-registros-page req-manage-page acreditaciones-export-apo-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger cursos-registros-page__alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="acreditaciones-export-apo-page__mode">
                @include('areas.gestion_humana.acreditaciones.partials.export-apo-manual')
            </div>
        </div>
    </div>
</x-app-layout>
