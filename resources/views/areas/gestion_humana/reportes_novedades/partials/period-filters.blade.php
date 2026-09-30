@php
    /** @var array{q: string, mes: string, quincena: string, fecha_desde: string, fecha_hasta: string} $filters */
    $fechaDesdeLabel = $fechaDesdeLabel ?? 'Inicio desde';
    $fechaHastaLabel = $fechaHastaLabel ?? 'Inicio hasta';
    $quincenaOptions = [
        ['value' => '1', 'label' => 'Quincena #1 (01–15)'],
        ['value' => '2', 'label' => 'Quincena #2 (16–31)'],
    ];
@endphp

<div
    class="cursos-registros-page__filters"
    x-data="{
        mes: @js($filters['mes'] ?? ''),
        fechaDesde: @js($filters['fecha_desde'] ?? ''),
        fechaHasta: @js($filters['fecha_hasta'] ?? ''),
        clearDates() {
            this.fechaDesde = '';
            this.fechaHasta = '';
        },
        clearPeriod() {
            this.mes = '';
            const sel = document.getElementById('filter_quincena');
            if (sel && window.Alpine) {
                const wrap = sel.closest('[x-data]');
                if (wrap) {
                    window.Alpine.$data(wrap).value = '';
                }
            }
        },
        onFilterChange(event) {
            const name = event.target?.name || '';
            if (name === 'mes' || name === 'quincena') {
                this.clearDates();
            }
            if (name === 'fecha_desde' || name === 'fecha_hasta') {
                if ((this.fechaDesde || '').trim() !== '' || (this.fechaHasta || '').trim() !== '') {
                    this.clearPeriod();
                }
            }
        },
    }"
    x-on:change="onFilterChange($event)"
>
    <div class="form-field">
        <label class="form-label" for="filter_q">Buscar</label>
        <input id="filter_q" name="q" type="search" class="form-input" value="{{ $filters['q'] }}" placeholder="Cédula o nombre…">
    </div>

    <div class="form-field">
        <label class="form-label" for="filter_mes">Mes</label>
        <input
            id="filter_mes"
            name="mes"
            type="month"
            class="form-input"
            x-model="mes"
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="filter_quincena">Quincena</label>
        <x-searchable-select
            id="filter_quincena"
            name="quincena"
            :options="$quincenaOptions"
            :value="$filters['quincena'] ?? ''"
            placeholder="Seleccionar…"
            :allow-clear="false"
            :required="false"
        />
    </div>

    <div class="form-field">
        <label class="form-label" for="filter_fecha_desde">{{ $fechaDesdeLabel }}</label>
        <input
            id="filter_fecha_desde"
            name="fecha_desde"
            type="date"
            class="form-input"
            x-model="fechaDesde"
        >
    </div>

    <div class="form-field">
        <label class="form-label" for="filter_fecha_hasta">{{ $fechaHastaLabel }}</label>
        <input
            id="filter_fecha_hasta"
            name="fecha_hasta"
            type="date"
            class="form-input"
            x-model="fechaHasta"
        >
    </div>

    <div class="cursos-registros-page__filter-actions form-field">
        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
            <x-lucide-search width="18" height="18" aria-hidden="true" />
        </button>
        <a href="{{ $clearUrl }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar (mes y quincena actuales)" aria-label="Limpiar filtros">
            <x-lucide-rotate-ccw width="18" height="18" aria-hidden="true" />
        </a>
        @if (! empty($canExport) && ! empty($exportUrl))
            <x-export-excel
                route="{{ $exportUrl }}"
                label=""
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
            />
        @endif
    </div>
</div>
