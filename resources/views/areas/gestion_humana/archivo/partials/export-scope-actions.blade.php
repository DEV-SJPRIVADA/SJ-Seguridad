{{-- Variables: $filters (opcional) --}}
@php
    $archiveExportBase = array_filter([
        'q' => $filters['q'] ?? request()->query('q'),
    ], fn ($value) => $value !== null && $value !== '');

    $archiveExportRoute = fn (string $status): string => route(
        'gestion-humana.ficha-empleados.employees.export-archive-template',
        array_merge($archiveExportBase, ['employment_status' => $status]),
    );
@endphp

<div class="archivo-export-scope">
    <p class="ficha-empleados-masivos-modal__card-note archivo-export-scope__lead">
        Elija qué empleados incluir en el Excel (datos de ficha + estantes y cajas):
    </p>
    <div class="archivo-export-scope__actions" style="display:flex;flex-wrap:wrap;gap:0.5rem;">
        <x-export-excel
            route="{{ $archiveExportRoute('activo') }}"
            label="Solo activos"
            class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action"
        />
        <x-export-excel
            route="{{ $archiveExportRoute('desvinculado') }}"
            label="Solo retirados"
            class="btn btn--secondary btn--sm ficha-empleados-masivos-modal__action"
        />
        <x-export-excel
            route="{{ $archiveExportRoute('todos') }}"
            label="Todos"
            class="btn btn--primary btn--sm ficha-empleados-masivos-modal__action"
        />
    </div>
</div>
