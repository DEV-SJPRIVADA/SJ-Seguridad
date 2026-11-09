@php
    $showMonthFilter = $showMonthFilter ?? false;
    $filtersActive = ($filters['area_key'] ?? null)
        || ($filters['sede_id'] ?? null)
        || ($filters['product'] ?? null)
        || ($showMonthFilter && ($filters['month'] ?? null));
    $filtersOpen = $filtersOpen ?? $filtersActive;
@endphp

{{-- Filtros de año, mes (solo Órdenes), área, sede y producto. Se aplican al cambiar un selector. --}}
<details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel req-manage-filters--bandeja pur-req-list__filters" @if ($filtersOpen) open @endif>
    <summary class="req-manage-filters__panel-toggle">
        <span>Filtros</span>
        @if ($filtersActive)
            <span class="req-manage-filters__panel-badge">Activos</span>
        @endif
    </summary>

    <div class="req-manage-filters__panel-body">
        <div class="req-manage-filters__head">
            <div class="req-manage-filters__actions">
                @if ($filtersActive)
                    <a
                        href="{{ route($clearRoute, ['module' => $module, 'year' => $filters['year']]) }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Limpiar filtros"
                        aria-label="Limpiar filtros"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </a>
                @endif
            </div>
        </div>

        <div class="req-manage-filters__toolbar req-manage-filters__toolbar--bandeja">
            <form method="GET" id="supply-seguimiento-filters-form" class="req-manage-filters__fields-col">
                <div class="req-manage-filters__query-row req-manage-filters__row--bandeja">
                    <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                        <label class="req-manage-filters__label" for="supply-seg-filter-year">Año</label>
                        <x-searchable-select
                            id="supply-seg-filter-year"
                            name="year"
                            :options="$filterOptions['years']"
                            :value="(string) $filters['year']"
                            placeholder="Año"
                            searchPlaceholder="Buscar año…"
                            :required="true"
                        />
                    </div>
                    @if ($showMonthFilter)
                        <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                            <label class="req-manage-filters__label" for="supply-seg-filter-month">Mes</label>
                            <x-searchable-select
                                id="supply-seg-filter-month"
                                name="month"
                                :options="$filterOptions['months']"
                                :value="($filters['month'] ?? null) !== null ? (string) $filters['month'] : ''"
                                placeholder="Todos los meses"
                                searchPlaceholder="Buscar mes…"
                            />
                        </div>
                    @endif
                    <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                        <label class="req-manage-filters__label" for="supply-seg-filter-area">Área solicitante</label>
                        <x-searchable-select
                            id="supply-seg-filter-area"
                            name="area_key"
                            :options="$filterOptions['areas']"
                            :value="$filters['area_key'] ?? ''"
                            placeholder="Todas las áreas"
                            searchPlaceholder="Buscar área…"
                        />
                    </div>
                    <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                        <label class="req-manage-filters__label" for="supply-seg-filter-sede">Sede</label>
                        <x-searchable-select
                            id="supply-seg-filter-sede"
                            name="sede_id"
                            :options="$filterOptions['sedes']"
                            :value="$filters['sede_id'] ?? ''"
                            placeholder="Todas las sedes"
                            searchPlaceholder="Buscar sede…"
                        />
                    </div>
                    <div class="req-manage-filters__query-field req-manage-filters__field--compact">
                        <label class="req-manage-filters__label" for="supply-seg-filter-product">Producto</label>
                        <x-searchable-select
                            id="supply-seg-filter-product"
                            name="product"
                            :options="$filterOptions['products']"
                            :value="$filters['product'] ?? ''"
                            placeholder="Todos los productos"
                            searchPlaceholder="Buscar producto…"
                        />
                    </div>
                </div>
            </form>
        </div>
    </div>
</details>

@pushOnce('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterForm = document.getElementById('supply-seguimiento-filters-form');
            if (!filterForm) {
                return;
            }
            filterForm.addEventListener('change', () => {
                setTimeout(() => filterForm.submit(), 20);
            });
        });
    </script>
@endPushOnce
