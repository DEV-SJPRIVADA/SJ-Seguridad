<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.archivo.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Historial de consultas</h2>
                <p class="panel-text">Registro de consultas de historias laborales y seguimiento de entregas</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section archivo-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success archivo-page__alert">{{ session('status') }}</div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact">
                    <div class="req-manage-filters">
                        <div class="req-manage-filters__head">
                            <div class="panel-heading-row panel-heading-row--wrap">
                                <h3 class="panel-title">Consultas registradas</h3>
                                <p class="panel-text">Filtre por cedula, nombre, concepto o persona de entrega</p>
                            </div>
                        </div>

                        <form method="GET" class="archivo-consult-history-filters">
                            <div class="archivo-consult-history-filters__row">
                                <div class="archivo-consult-history-filters__field">
                                    <label class="req-manage-filters__label" for="archivo-history-search">Buscar</label>
                                    <input
                                        id="archivo-history-search"
                                        type="search"
                                        name="q"
                                        class="form-input"
                                        value="{{ $filters['q'] }}"
                                        placeholder="Cedula, nombre, concepto o entregada a"
                                    >
                                </div>
                                <div class="archivo-consult-history-filters__field archivo-consult-history-filters__field--sm">
                                    <label class="req-manage-filters__label" for="archivo-history-month">Mes</label>
                                    @php
                                        $monthOptions = [];
                                        for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {
                                            $monthOptions[] = [
                                                'value' => (string) $monthNumber,
                                                'label' => \App\Models\EmployeeArchiveConsultationItem::monthLabel($monthNumber),
                                            ];
                                        }
                                    @endphp
                                    <x-searchable-select
                                        id="archivo-history-month"
                                        name="month"
                                        :options="$monthOptions"
                                        :value="$filters['month'] ?? ''"
                                        placeholder="Todos los meses"
                                        searchPlaceholder="Buscar mes…"
                                    />
                                </div>
                                <div class="archivo-consult-history-filters__field archivo-consult-history-filters__field--sm">
                                    <label class="req-manage-filters__label" for="archivo-history-week">Semana</label>
                                    @php
                                        $weekOptions = [];
                                        for ($weekNumber = 1; $weekNumber <= 5; $weekNumber++) {
                                            $weekOptions[] = [
                                                'value' => (string) $weekNumber,
                                                'label' => 'Semana ' . $weekNumber,
                                            ];
                                        }
                                    @endphp
                                    <x-searchable-select
                                        id="archivo-history-week"
                                        name="week"
                                        :options="$weekOptions"
                                        :value="$filters['week'] ?? ''"
                                        placeholder="Todas las semanas"
                                        searchPlaceholder="Buscar semana…"
                                    />
                                </div>
                                <div class="archivo-consult-history-filters__actions">
                                    <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                                    <a href="{{ route('gestion-humana.archivo.consultation-history.index') }}" class="btn btn--secondary btn--sm">Limpiar</a>
                                </div>
                            </div>
                        </form>

                        <p class="req-manage-filters__meta">
                            <strong>{{ number_format($items->count()) }}</strong>
                            {{ $items->count() === 1 ? 'registro' : 'registros' }}
                        </p>
                    </div>

                    <div class="data-table-wrap">
                        <table class="data-table js-datatable" data-dt-responsive="false" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Concepto</th>
                                    <th>Cedula</th>
                                    <th>Nombre</th>
                                    <th>Estante</th>
                                    <th>Caja</th>
                                    <th>Entregada a</th>
                                    <th>Recibida</th>
                                    <th>Observacion</th>
                                    <th>Semana</th>
                                    <th>Mes</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($items as $item)
                                    @php
                                        $rowFormId = 'archivo-consult-item-'.$item->id;
                                    @endphp
                                    <tr>
                                        <td>{{ $item->created_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                        <td>{{ $item->concept ?: '—' }}</td>
                                        <td>{{ $item->document_number }}</td>
                                        <td>{{ $item->full_name ?: '—' }}</td>
                                        <td>{{ $item->archive_shelf ?: '—' }}</td>
                                        <td>{{ $item->archive_box ?: '—' }}</td>
                                        <td>{{ $item->delivered_to ?: '—' }}</td>
                                        <td class="archivo-consult-history__received-cell">
                                            <label class="archivo-consult-history__received-label">
                                                <input
                                                    type="checkbox"
                                                    name="received"
                                                    value="1"
                                                    form="{{ $rowFormId }}"
                                                    @checked(old('received', $item->received))
                                                >
                                                <span class="sr-only">Recibida</span>
                                            </label>
                                        </td>
                                        <td class="archivo-page__field-cell">
                                            <label class="sr-only" for="{{ $rowFormId }}-observation">Observacion</label>
                                            <input
                                                id="{{ $rowFormId }}-observation"
                                                form="{{ $rowFormId }}"
                                                type="text"
                                                name="observation"
                                                class="form-input archivo-page__inline-input archivo-consult-history__observation-input"
                                                maxlength="1000"
                                                value="{{ old('observation', $item->observation) }}"
                                                placeholder="Observacion"
                                            >
                                        </td>
                                        <td>{{ $item->week_of_month }}</td>
                                        <td>{{ $item->month_label }}</td>
                                        <td class="table-actions archivo-page__actions-cell">
                                            <button type="submit" form="{{ $rowFormId }}" class="btn btn--primary btn--sm">
                                                Actualizar
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12">No hay consultas registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @foreach ($items as $item)
                        <form
                            id="archivo-consult-item-{{ $item->id }}"
                            method="POST"
                            action="{{ route('gestion-humana.archivo.consultation-history.update', $item) }}"
                            class="archivo-page__row-form"
                            hidden
                        >
                            @csrf
                            @method('PATCH')
                            @if ($filters['q'] ?? '')
                                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                            @endif
                            @if ($filters['month'] ?? null)
                                <input type="hidden" name="month" value="{{ $filters['month'] }}">
                            @endif
                            @if ($filters['week'] ?? null)
                                <input type="hidden" name="week" value="{{ $filters['week'] }}">
                            @endif
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
