<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $hasRun = filled($runToken) && is_array($runCounts);
        $gateToastMessage = trim((string) ($gate['message'] ?? ''));
        if ($gateOk) {
            $gateToastMessage = $gateToastMessage !== ''
                ? 'Listo para validar · '.$fecha.'. '.$gateToastMessage
                : 'Listo para validar · '.$fecha;
            if (! $hasRun) {
                $gateToastMessage .= ' Pulse Ejecutar validaciones para calcular las colas.';
            }
        } elseif ($gateToastMessage === '') {
            $gateToastMessage = 'No se puede ejecutar · '.$fecha.'. Cargue ambos orígenes en Reporte Diario.';
        } else {
            $gateToastMessage = 'No se puede ejecutar · '.$fecha.'. '.$gateToastMessage;
        }
        $totalHallazgos = $hasRun ? (int) array_sum($runCounts) : 0;
        $colaIcons = [
            'sin_acreditacion' => 'user-round-x',
            'ausente_reporte' => 'file-warning',
            'en_proceso_ya_acreditado' => 'git-compare-arrows',
            'vencidas' => 'calendar-clock',
        ];
    @endphp

    <div
        class="page-section cursos-registros-page req-manage-page acreditaciones-validaciones-page"
        x-data="validacionesPage({
            hasRun: {{ $hasRun ? 'true' : 'false' }},
            activeCola: @js($defaultCola),
            lookupUrl: @js($lookupUrl),
            bulkSelectableUrl: @js($bulkSelectableUrl ?? null),
            exportApoUrl: @js($exportApoUrl ?? null),
            fechaReporte: @js($fecha),
            runToken: @js($runToken),
        })"
        @acreditaciones-open-edit.window="openEdit($event.detail)"
    >
        <div class="app-container">
            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    {{-- Toolbar: fecha + acciones --}}
                    <div class="av-toolbar">
                        <form
                            method="GET"
                            action="{{ route('gestion-humana.acreditaciones.validaciones') }}"
                            class="av-toolbar__date"
                            id="validaciones-gate-form"
                        >
                            <div class="form-field av-toolbar__field">
                                <label class="form-label av-toolbar__label" for="validaciones_fecha_reporte">Fecha</label>
                                <div class="av-toolbar__date-row">
                                    <input
                                        id="validaciones_fecha_reporte"
                                        name="fecha_reporte"
                                        type="date"
                                        class="form-input"
                                        max="{{ $today }}"
                                        value="{{ $fecha }}"
                                        required
                                    >
                                    <button
                                        type="submit"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Evaluar gate"
                                        aria-label="Evaluar gate"
                                    >
                                        <x-lucide-search width="18" height="18" aria-hidden="true" />
                                    </button>
                                    <a
                                        href="{{ route('gestion-humana.acreditaciones.validaciones') }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Usar fecha de hoy"
                                        aria-label="Usar fecha de hoy"
                                    >
                                        <x-lucide-calendar-days width="18" height="18" aria-hidden="true" />
                                    </a>
                                    <a
                                        href="{{ $reporteDiarioUrl }}"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Ir a Reporte Diario"
                                        aria-label="Ir a Reporte Diario"
                                    >
                                        <x-lucide-external-link width="18" height="18" aria-hidden="true" />
                                    </a>
                                    @if ($hasRun && $exportConsolidatedUrl)
                                        <x-export-excel
                                            :route="$exportConsolidatedUrl"
                                            label=""
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            title="Exportar Excel sin filtros (4 colas)"
                                            aria-label="Exportar Excel sin filtros (4 colas)"
                                        />
                                    @endif
                                </div>
                            </div>
                        </form>

                        @if ($hasRun)
                            <p class="av-toolbar__summary" aria-live="polite">
                                <strong>{{ number_format($totalHallazgos) }}</strong>
                                hallazgo{{ $totalHallazgos === 1 ? '' : 's' }}
                            </p>
                        @endif

                        <form
                            method="POST"
                            action="{{ $runUrl }}"
                            class="av-toolbar__run"
                            x-data="{ submitting: false }"
                            @submit="if (! {{ $gateOk ? 'true' : 'false' }} || submitting) { $event.preventDefault(); return; } submitting = true"
                        >
                            @csrf
                            <input type="hidden" name="fecha_reporte" value="{{ $fecha }}">
                            <button
                                type="submit"
                                class="btn btn--primary btn--sm av-toolbar__run-btn"
                                title="Ejecutar validaciones"
                                @if (! $gateOk)
                                    disabled
                                @else
                                    :disabled="submitting"
                                    :aria-busy="submitting"
                                @endif
                            >
                                <span x-show="! submitting" class="av-toolbar__run-inner">
                                    <x-lucide-play width="16" height="16" aria-hidden="true" />
                                    Ejecutar
                                </span>
                                <span x-show="submitting" x-cloak class="av-toolbar__run-inner">
                                    <x-lucide-loader-2 width="16" height="16" class="animate-spin" aria-hidden="true" />
                                    Ejecutando…
                                </span>
                            </button>
                        </form>
                    </div>

                    @if ($runExpired)
                        <div class="alert alert--warning cursos-registros-page__alert" role="alert">
                            La corrida expiró o no está disponible. Vuelva a ejecutar validaciones.
                        </div>
                    @endif

                    @if ($hasRun)
                        <div class="av-results">
                            <div class="module-subnav av-colas-nav" aria-label="Colas de validación">
                                <div class="module-subnav__inner module-tabs" role="tablist">
                                    @foreach ($colaDefs as $colaCode => $colaDef)
                                        @php
                                            $count = (int) ($runCounts[$colaCode] ?? 0);
                                            $icon = $colaIcons[$colaCode] ?? 'list';
                                        @endphp
                                        <button
                                            type="button"
                                            class="module-tab av-cola-tab"
                                            :class="{ 'module-tab--active': activeCola === '{{ $colaCode }}' }"
                                            @click="setCola('{{ $colaCode }}')"
                                            role="tab"
                                            :aria-selected="(activeCola === '{{ $colaCode }}').toString()"
                                        >
                                            <span class="av-cola-tab__icon" aria-hidden="true">
                                                @switch($icon)
                                                    @case('user-round-x')
                                                        <x-lucide-user-round-x width="15" height="15" />
                                                        @break
                                                    @case('file-warning')
                                                        <x-lucide-file-warning width="15" height="15" />
                                                        @break
                                                    @case('git-compare-arrows')
                                                        <x-lucide-git-compare-arrows width="15" height="15" />
                                                        @break
                                                    @case('calendar-clock')
                                                        <x-lucide-calendar-clock width="15" height="15" />
                                                        @break
                                                    @default
                                                        <x-lucide-list width="15" height="15" />
                                                @endswitch
                                            </span>
                                            <span class="av-cola-tab__label">{{ $colaDef['label'] }}</span>
                                            <span
                                                class="av-cola-tab__count {{ $count > 0 ? 'av-cola-tab__count--hot' : '' }}"
                                                id="validaciones-count-badge-{{ $colaCode }}"
                                            >{{ $count }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @foreach ($colaDefs as $colaCode => $colaDef)
                                <div
                                    class="av-cola-panel"
                                    x-show="activeCola === '{{ $colaCode }}'"
                                    x-cloak
                                    role="tabpanel"
                                >
                                    <div class="av-cola-panel__toolbar">
                                        <details class="req-manage-filters req-manage-filters__panel">
                                            <summary class="req-manage-filters__panel-toggle">
                                                <span>Filtros</span>
                                            </summary>
                                            <div class="req-manage-filters__panel-body">
                                        <form
                                            class="req-manage-filters js-validaciones-cola-filters"
                                            data-cola="{{ $colaCode }}"
                                            autocomplete="off"
                                        >
                                            <div class="cursos-registros-page__filters av-cola-filters">
                                                <div class="form-field">
                                                    <label class="sr-only" for="filter_{{ $colaCode }}_document_number">Cédula</label>
                                                    <input
                                                        id="filter_{{ $colaCode }}_document_number"
                                                        name="document_number"
                                                        type="text"
                                                        class="form-input"
                                                        placeholder="Cédula"
                                                    >
                                                </div>
                                                <div class="form-field">
                                                    <label class="sr-only" for="filter_{{ $colaCode }}_full_name">Nombre</label>
                                                    <input
                                                        id="filter_{{ $colaCode }}_full_name"
                                                        name="full_name"
                                                        type="text"
                                                        class="form-input"
                                                        placeholder="Nombre"
                                                    >
                                                </div>
                                                @if ($colaCode === 'sin_acreditacion')
                                                    <div class="form-field">
                                                        <label class="sr-only" for="filter_{{ $colaCode }}_cargo">Cargo Ficha</label>
                                                        <x-searchable-select
                                                            id="filter_{{ $colaCode }}_cargo"
                                                            name="cargo"
                                                            :options="$filterCargoFichaOptions"
                                                            value=""
                                                            placeholder="Cargo Ficha"
                                                            :allow-clear="true"
                                                        />
                                                    </div>
                                                    <div class="form-field">
                                                        <label class="sr-only" for="filter_{{ $colaCode }}_personal_tipo">Tipo</label>
                                                        <x-searchable-select
                                                            id="filter_{{ $colaCode }}_personal_tipo"
                                                            name="personal_tipo"
                                                            :options="$filterPersonalTipoOptions"
                                                            value=""
                                                            placeholder="Tipo"
                                                            :allow-clear="true"
                                                        />
                                                    </div>
                                                @elseif ($colaCode === 'ausente_reporte')
                                                    <div class="form-field">
                                                        <label class="sr-only" for="filter_{{ $colaCode }}_cargo">Cargo Ficha</label>
                                                        <x-searchable-select
                                                            id="filter_{{ $colaCode }}_cargo"
                                                            name="cargo"
                                                            :options="$filterCargoFichaOptions"
                                                            value=""
                                                            placeholder="Cargo Ficha"
                                                            :allow-clear="true"
                                                        />
                                                    </div>
                                                    <div class="form-field">
                                                        <label class="sr-only" for="filter_{{ $colaCode }}_cargo_apo">CARGO APO</label>
                                                        <x-searchable-select
                                                            id="filter_{{ $colaCode }}_cargo_apo"
                                                            name="cargo_apo"
                                                            :options="$filterCargoApoOptions"
                                                            value=""
                                                            placeholder="CARGO APO"
                                                            :allow-clear="true"
                                                        />
                                                    </div>
                                                    <div class="form-field">
                                                        <label class="sr-only" for="filter_{{ $colaCode }}_estado">Estado</label>
                                                        <x-searchable-select
                                                            id="filter_{{ $colaCode }}_estado"
                                                            name="estado"
                                                            :options="$filterEstadoOptions"
                                                            value=""
                                                            placeholder="Estado"
                                                            :allow-clear="true"
                                                        />
                                                    </div>
                                                @else
                                                    <div class="form-field">
                                                        <label class="sr-only" for="filter_{{ $colaCode }}_cargo_apo">CARGO APO</label>
                                                        <x-searchable-select
                                                            id="filter_{{ $colaCode }}_cargo_apo"
                                                            name="cargo_apo"
                                                            :options="$filterCargoApoOptions"
                                                            value=""
                                                            placeholder="CARGO APO"
                                                            :allow-clear="true"
                                                        />
                                                    </div>
                                                    <div class="form-field">
                                                        <label class="sr-only" for="filter_{{ $colaCode }}_estado">Estado</label>
                                                        <x-searchable-select
                                                            id="filter_{{ $colaCode }}_estado"
                                                            name="estado"
                                                            :options="$filterEstadoOptions"
                                                            value=""
                                                            placeholder="Estado"
                                                            :allow-clear="true"
                                                        />
                                                    </div>
                                                @endif
                                                <div class="form-field cursos-registros-page__filter-actions">
                                                    <span class="av-cola-filters__count" aria-live="polite">
                                                        <strong id="validaciones-count-{{ $colaCode }}">{{ (int) ($runCounts[$colaCode] ?? 0) }}</strong>
                                                    </span>
                                                    <button
                                                        type="submit"
                                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                        title="Filtrar"
                                                        aria-label="Filtrar"
                                                    >
                                                        <x-lucide-search width="18" height="18" aria-hidden="true" />
                                                    </button>
                                                    <x-multi-cedula-filter
                                                        :id="'validaciones-'.$colaCode"
                                                        name="document_numbers"
                                                        value=""
                                                        :submit-on-apply="false"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost js-validaciones-cola-filters-clear"
                                                        title="Limpiar filtros"
                                                        aria-label="Limpiar filtros"
                                                    >
                                                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                                        x-show="selectedCount > 0"
                                                        x-cloak
                                                        x-on:click="cargarEnExportApo()"
                                                        title="Cargar en Export Apo"
                                                        aria-label="Cargar en Export Apo"
                                                    >
                                                        <x-lucide-file-output width="18" height="18" aria-hidden="true" />
                                                    </button>
                                                    @if (! empty($exportUrls[$colaCode]))
                                                        <x-export-excel
                                                            :route="$exportUrls[$colaCode]"
                                                            label=""
                                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost js-validaciones-cola-export"
                                                            title="Exportar Excel de esta cola con filtros"
                                                            aria-label="Exportar Excel de esta cola con filtros"
                                                            data-cola="{{ $colaCode }}"
                                                            :data-export-base="$exportUrls[$colaCode]"
                                                        />
                                                    @endif
                                                </div>
                                            </div>
                                        </form>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="data-table-wrap data-table-wrap--booting req-manage-shell__table av-cola-panel__table">
                                        <table
                                            id="validaciones-datatable-{{ $colaCode }}"
                                            class="data-table js-validaciones-datatable"
                                            data-dt-cola="{{ $colaCode }}"
                                            data-dt-url="{{ $datatableUrl }}"
                                            data-dt-fecha="{{ $fecha }}"
                                            data-dt-token="{{ $runToken }}"
                                            data-dt-columns='@json($colaDef['columns'])'
                                            style="width:100%"
                                        >
                                            <thead>
                                                <tr>
                                                    @foreach ($colaDef['columns'] as $column)
                                                        @if (($column['data'] ?? '') === 'select')
                                                            <th class="cursos-registros-page__select-col" data-orderable="false">
                                                                <label class="cursos-registros-page__select-label" title="Seleccionar todos los del filtro actual">
                                                                    <input
                                                                        type="checkbox"
                                                                        class="cursos-registros-page__select-checkbox"
                                                                        x-bind:checked="allEligibleSelected(@js($colaCode))"
                                                                        x-bind:disabled="bulkSelectableLoading || eligibleRowsFor(@js($colaCode)).length === 0"
                                                                        x-on:change="toggleSelectAll(@js($colaCode), $event.target.checked)"
                                                                        aria-label="Seleccionar todos"
                                                                    >
                                                                </label>
                                                            </th>
                                                        @else
                                                            <th>{{ $column['title'] }}</th>
                                                        @endif
                                                    @endforeach
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($gateOk)
                        <div class="av-empty" role="status">
                            <span class="av-empty__icon" aria-hidden="true">
                                <x-lucide-shield-check width="28" height="28" />
                            </span>
                            <p class="av-empty__title">Sin corrida activa</p>
                            <p class="av-empty__text">
                                El gate está completo para <strong>{{ $fecha }}</strong>. Ejecute las validaciones para ver las cuatro colas de trabajo.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            @include('areas.gestion_humana.acreditaciones.partials.nuevo-modal', [
                'cargoApoOptions' => $cargoApoOptions,
                'renovacionOptions' => $renovacionOptions,
                'lookupUrl' => $lookupUrl,
                'show' => $showNuevoModal,
                'validacionesReturn' => $validacionesReturn,
            ])

            @include('areas.gestion_humana.acreditaciones.partials.edit-modal', [
                'cargoApoOptions' => $cargoApoOptions,
                'renovacionOptions' => $renovacionOptions,
                'validacionesReturn' => $validacionesReturn,
            ])
        </div>
    </div>

    @push('scripts')
        <script>
            (function () {
                const gateOk = @json((bool) $gateOk);
                const hasRun = @json((bool) $hasRun);
                const message = @json($gateToastMessage);
                document.addEventListener('DOMContentLoaded', function () {
                    if (typeof window.showToast !== 'function' || ! message) {
                        return;
                    }
                    // Con corrida activa el estado del gate ya no aporta; evita ruido al refrescar.
                    if (gateOk && hasRun) {
                        return;
                    }
                    window.showToast(message, gateOk ? 'success' : 'error');
                });
            })();

            function validacionesPage(config) {
                return {
                    hasRun: !!config.hasRun,
                    activeCola: config.activeCola,
                    lookupUrl: config.lookupUrl,
                    bulkSelectableUrl: config.bulkSelectableUrl || '',
                    exportApoUrl: config.exportApoUrl || '',
                    fechaReporte: config.fechaReporte || '',
                    runToken: config.runToken || '',
                    bulkSelectableByCola: {},
                    bulkSelectableLoading: false,
                    selectedMap: {},
                    editOpen: false,
                    editIdentityLocked: true,
                    editForm: {
                        document_number: '',
                        full_name: '',
                        cargo: '',
                        cargo_apo: '',
                        vigencia_acr: '',
                        fecha_solicitud: '',
                        estado: '',
                        estado_label: '',
                        renovacion: '',
                        observaciones: '',
                        update_url: '',
                    },
                    get selectedIds() {
                        return Object.keys(this.selectedMap)
                            .filter((id) => this.selectedMap[id])
                            .map((id) => Number(id));
                    },
                    get selectedCount() {
                        return this.selectedIds.length;
                    },
                    eligibleRowsFor(cola) {
                        return Array.isArray(this.bulkSelectableByCola[cola])
                            ? this.bulkSelectableByCola[cola]
                            : [];
                    },
                    allEligibleSelected(cola) {
                        const rows = this.eligibleRowsFor(cola);
                        if (rows.length === 0) {
                            return false;
                        }
                        return rows.every((row) => this.selectedMap[row.id]);
                    },
                    isSelected(id) {
                        return !! this.selectedMap[id];
                    },
                    toggleRow(id, checked) {
                        this.selectedMap = {
                            ...this.selectedMap,
                            [id]: !! checked,
                        };
                    },
                    toggleSelectAll(cola, checked) {
                        const rows = this.eligibleRowsFor(cola);
                        const next = { ...this.selectedMap };
                        rows.forEach((row) => {
                            if (checked) {
                                next[row.id] = true;
                            } else {
                                delete next[row.id];
                            }
                        });
                        this.selectedMap = next;
                        this.syncPageCheckboxes(cola);
                    },
                    syncPageCheckboxes(cola) {
                        const selector = cola
                            ? '.js-validaciones-datatable[data-dt-cola="' + cola + '"] .js-validaciones-row-select'
                            : '.js-validaciones-row-select';
                        document.querySelectorAll(selector).forEach((input) => {
                            const id = Number(input.value);
                            input.checked = !! this.selectedMap[id];
                        });
                    },
                    collectColaFilters(cola) {
                        const form = document.querySelector('.js-validaciones-cola-filters[data-cola="' + cola + '"]');
                        const filters = {};
                        if (! form) {
                            return filters;
                        }
                        const data = new FormData(form);
                        data.forEach((value, key) => {
                            const trimmed = String(value || '').trim();
                            if (trimmed !== '') {
                                filters[key] = trimmed;
                            }
                        });
                        return filters;
                    },
                    async loadBulkSelectable(cola) {
                        if (! this.bulkSelectableUrl || ! this.runToken || ! cola) {
                            this.bulkSelectableByCola = {
                                ...this.bulkSelectableByCola,
                                [cola]: [],
                            };
                            return;
                        }
                        this.bulkSelectableLoading = true;
                        try {
                            const url = new URL(this.bulkSelectableUrl, window.location.origin);
                            url.searchParams.set('fecha_reporte', this.fechaReporte);
                            url.searchParams.set('run_token', this.runToken);
                            url.searchParams.set('cola', cola);
                            const filters = this.collectColaFilters(cola);
                            Object.keys(filters).forEach((key) => {
                                url.searchParams.set(key, filters[key]);
                            });
                            const res = await fetch(url.toString(), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (! res.ok) {
                                this.bulkSelectableByCola = {
                                    ...this.bulkSelectableByCola,
                                    [cola]: [],
                                };
                                return;
                            }
                            const payload = await res.json();
                            this.bulkSelectableByCola = {
                                ...this.bulkSelectableByCola,
                                [cola]: Array.isArray(payload.data) ? payload.data : [],
                            };
                        } catch (e) {
                            this.bulkSelectableByCola = {
                                ...this.bulkSelectableByCola,
                                [cola]: [],
                            };
                        } finally {
                            this.bulkSelectableLoading = false;
                            this.syncPageCheckboxes(cola);
                        }
                    },
                    cargarEnExportApo() {
                        const ids = this.selectedIds;
                        if (ids.length < 1 || ! this.exportApoUrl) {
                            return;
                        }
                        const url = new URL(this.exportApoUrl, window.location.origin);
                        ids.forEach((id) => {
                            url.searchParams.append('ids[]', String(id));
                        });
                        window.location.href = url.toString();
                    },
                    setCola(cola) {
                        this.activeCola = cola;
                        this.$nextTick(() => {
                            window.dispatchEvent(new CustomEvent('validaciones-cola-shown', { detail: { cola } }));
                        });
                    },
                    closeEdit() {
                        this.editOpen = false;
                    },
                    unlockEditIdentity() {
                        this.editIdentityLocked = false;
                        this.editForm.document_number = '';
                        this.editForm.full_name = '';
                        this.editForm.cargo = '';
                    },
                    syncEditCargoApo(value) {
                        this.$nextTick(() => {
                            const wrap = document.querySelector('.js-edit-cargo-apo-select');
                            if (! wrap || ! window.Alpine || typeof window.Alpine.$data !== 'function') {
                                return;
                            }
                            try {
                                const data = window.Alpine.$data(wrap);
                                if (data && 'value' in data) {
                                    data.value = String(value || '');
                                }
                            } catch (e) {}
                        });
                    },
                    syncEditRenovacion(value) {
                        this.$nextTick(() => {
                            const wrap = document.querySelector('.js-edit-renovacion-select');
                            if (! wrap || ! window.Alpine || typeof window.Alpine.$data !== 'function') {
                                return;
                            }
                            try {
                                const data = window.Alpine.$data(wrap);
                                if (data && 'value' in data) {
                                    data.value = String(value || '');
                                }
                            } catch (e) {}
                        });
                    },
                    openEdit(detail) {
                        this.editForm = {
                            document_number: detail?.document_number || '',
                            full_name: detail?.full_name || '',
                            cargo: detail?.cargo || '',
                            cargo_apo: detail?.cargo_apo || '',
                            vigencia_acr: detail?.vigencia_acr || '',
                            fecha_solicitud: detail?.fecha_solicitud || '',
                            estado: detail?.estado || '',
                            estado_label: detail?.estado_label || detail?.estado || '',
                            renovacion: detail?.renovacion || '',
                            observaciones: detail?.observaciones || '',
                            update_url: detail?.update_url || '',
                        };
                        this.editIdentityLocked = Boolean(this.editForm.document_number);
                        this.editOpen = true;
                        this.syncEditCargoApo(this.editForm.cargo_apo);
                        this.syncEditRenovacion(this.editForm.renovacion);
                        if (this.editForm.document_number) {
                            this.lookupName(this.editForm.document_number, 'edit');
                        }
                    },
                    async lookupName(cedula, mode) {
                        const value = String(cedula || '').trim();
                        if (! value || ! this.lookupUrl) {
                            return;
                        }
                        try {
                            const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (! res.ok) {
                                return;
                            }
                            const data = await res.json();
                            if (data.found && data.full_name) {
                                if (mode === 'edit') {
                                    this.editForm.document_number = data.document_number || value;
                                    this.editForm.full_name = data.full_name;
                                    this.editForm.cargo = data.cargo || '';
                                    this.editIdentityLocked = true;
                                }
                            }
                        } catch (e) {}
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (!window.jQuery || !window.jQuery.fn.DataTable) {
                    return;
                }

                const tables = {};

                function getAlpineRoot() {
                    const root = document.querySelector('.acreditaciones-validaciones-page');
                    if (! root || ! window.Alpine) {
                        return null;
                    }
                    return window.Alpine.$data(root);
                }

                function collectColaFilters(cola) {
                    const form = document.querySelector('.js-validaciones-cola-filters[data-cola="' + cola + '"]');
                    const filters = {};
                    if (!form) {
                        return filters;
                    }
                    const data = new FormData(form);
                    data.forEach(function (value, key) {
                        const trimmed = String(value || '').trim();
                        if (trimmed !== '') {
                            filters[key] = trimmed;
                        }
                    });
                    return filters;
                }

                function buildColaExportUrl(baseUrl, cola) {
                    const url = new URL(baseUrl, window.location.origin);
                    const filters = collectColaFilters(cola);
                    ['document_number', 'document_numbers', 'full_name', 'cargo', 'cargo_apo', 'estado', 'personal_tipo'].forEach(function (key) {
                        url.searchParams.delete(key);
                    });
                    Object.keys(filters).forEach(function (key) {
                        url.searchParams.set(key, filters[key]);
                    });
                    return url.toString();
                }

                function syncColaExportLinks() {
                    document.querySelectorAll('.js-validaciones-cola-export').forEach(function (link) {
                        const cola = String(link.getAttribute('data-cola') || '');
                        const base = String(link.getAttribute('data-export-base') || link.getAttribute('href') || '');
                        if (!cola || !base) {
                            return;
                        }
                        link.setAttribute('href', buildColaExportUrl(base, cola));
                    });
                }

                function reloadColaTable(cola) {
                    if (tables[cola]) {
                        tables[cola].ajax.reload();
                    }
                }

                function clearColaFilters(form) {
                    form.querySelectorAll('input[type="text"], input[type="search"]').forEach(function (input) {
                        input.value = '';
                    });
                    form.querySelectorAll('[data-multi-cedula-hidden]').forEach(function (input) {
                        input.value = '';
                        const uid = input.getAttribute('data-multi-cedula-for');
                        const btn = form.querySelector('[data-multi-cedula-open][data-multi-cedula-for="' + uid + '"]');
                        if (btn) {
                            btn.classList.remove('req-manage-filters__icon-btn--primary');
                            btn.classList.add('req-manage-filters__icon-btn--ghost');
                            btn.setAttribute('title', 'Filtrar varias cédulas');
                            btn.setAttribute('aria-label', 'Filtrar varias cédulas');
                        }
                        const badge = form.querySelector('[data-multi-cedula-badge][data-multi-cedula-for="' + uid + '"]')
                            || document.querySelector('[data-multi-cedula-badge][data-multi-cedula-for="' + uid + '"]');
                        if (badge) {
                            badge.hidden = true;
                            badge.textContent = '0';
                        }
                        const textarea = document.querySelector('[data-multi-cedula-textarea][data-multi-cedula-for="' + uid + '"]');
                        if (textarea) {
                            textarea.value = '';
                        }
                    });
                    form.querySelectorAll('.searchable-select-wrap').forEach(function (wrap) {
                        if (typeof Alpine === 'undefined' || !Alpine.$data) {
                            const hidden = wrap.querySelector('input[type="hidden"]');
                            if (hidden) {
                                hidden.value = '';
                            }
                            return;
                        }
                        try {
                            const data = Alpine.$data(wrap);
                            if (data && typeof data.clear === 'function') {
                                data.clear();
                            } else if (data) {
                                data.value = '';
                                data.selectedLabel = '';
                                data.query = '';
                            }
                        } catch (e) {}
                    });
                }

                function initTable($table) {
                    const cola = String($table.data('dt-cola') || '');
                    if (!cola || tables[cola]) {
                        return;
                    }

                    const wrap = $table.closest('.data-table-wrap');
                    const reveal = function () {
                        wrap.removeClass('data-table-wrap--booting');
                    };
                    const columns = $table.data('dt-columns') || [];
                    const updateMeta = function (count) {
                        const el = document.getElementById('validaciones-count-' + cola);
                        if (el) {
                            el.textContent = String(count);
                        }
                        const badge = document.getElementById('validaciones-count-badge-' + cola);
                        if (badge) {
                            badge.textContent = String(count);
                            badge.classList.toggle('av-cola-tab__count--hot', Number(count) > 0);
                        }
                    };

                    const api = $table.DataTable({
                        processing: true,
                        serverSide: true,
                        deferLoading: 0,
                        ajax: {
                            url: $table.data('dt-url'),
                            data: function (d) {
                                d.fecha_reporte = $table.data('dt-fecha');
                                d.run_token = $table.data('dt-token');
                                d.cola = cola;
                                const filters = collectColaFilters(cola);
                                Object.keys(filters).forEach(function (key) {
                                    d[key] = filters[key];
                                });
                            },
                            dataSrc: function (json) {
                                if (json && json.expired) {
                                    const banner = document.querySelector('.acreditaciones-validaciones-page');
                                    if (banner && !document.getElementById('validaciones-expired-live')) {
                                        const alert = document.createElement('div');
                                        alert.id = 'validaciones-expired-live';
                                        alert.className = 'alert alert--warning cursos-registros-page__alert';
                                        alert.setAttribute('role', 'alert');
                                        alert.textContent = 'La corrida expiró o no está disponible. Vuelva a ejecutar validaciones.';
                                        banner.querySelector('.app-container')?.prepend(alert);
                                    }
                                }
                                return (json && json.data) ? json.data : [];
                            },
                        },
                        columns: columns,
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                            emptyTable: 'Sin filas en esta cola.',
                        },
                        dom: '<"req-manage-table-scroll"t><"req-manage-dt-bottom"lip>',
                        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                        pageLength: 25,
                        responsive: false,
                        order: [[1, 'asc']],
                        columnDefs: [{ targets: [0], orderable: false, searchable: false }],
                    });

                    api.on('xhr.dt', function (_event, _settings, json) {
                        reveal();
                        if (json && typeof json.recordsFiltered !== 'undefined') {
                            updateMeta(json.recordsFiltered);
                        }
                        const alpine = getAlpineRoot();
                        if (alpine && typeof alpine.loadBulkSelectable === 'function') {
                            alpine.loadBulkSelectable(cola);
                        }
                    });

                    api.on('draw.dt', function () {
                        const alpine = getAlpineRoot();
                        if (alpine && typeof alpine.syncPageCheckboxes === 'function') {
                            alpine.syncPageCheckboxes(cola);
                        }
                    });

                    $table.on('change', '.js-validaciones-row-select', function () {
                        const alpine = getAlpineRoot();
                        if (! alpine) {
                            return;
                        }
                        alpine.toggleRow(Number(this.value), this.checked);
                    });

                    $table.on('click', '.js-validaciones-edit', function () {
                        try {
                            const row = JSON.parse(this.getAttribute('data-validaciones-edit') || '{}');
                            window.dispatchEvent(new CustomEvent('acreditaciones-open-edit', { detail: row }));
                        } catch (e) {}
                    });

                    $table.on('click', '.js-validaciones-nuevo', function () {
                        try {
                            const row = JSON.parse(this.getAttribute('data-validaciones-nuevo') || '{}');
                            window.dispatchEvent(new CustomEvent('acreditaciones-open-nuevo', { detail: row }));
                            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'acreditaciones-nuevo' }));
                        } catch (e) {}
                    });

                    tables[cola] = api;
                    api.ajax.reload(null, false);
                }

                document.querySelectorAll('.js-validaciones-cola-filters').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        event.preventDefault();
                        reloadColaTable(String(form.getAttribute('data-cola') || ''));
                        syncColaExportLinks();
                    });
                    const clearBtn = form.querySelector('.js-validaciones-cola-filters-clear');
                    if (clearBtn) {
                        clearBtn.addEventListener('click', function () {
                            clearColaFilters(form);
                            reloadColaTable(String(form.getAttribute('data-cola') || ''));
                            syncColaExportLinks();
                        });
                    }
                });

                window.addEventListener('multi-cedula-applied', function (event) {
                    const root = event.detail && event.detail.root;
                    if (!root) {
                        return;
                    }
                    const form = root.closest('.js-validaciones-cola-filters');
                    if (!form) {
                        return;
                    }
                    reloadColaTable(String(form.getAttribute('data-cola') || ''));
                    syncColaExportLinks();
                });

                document.querySelectorAll('.js-validaciones-cola-export').forEach(function (link) {
                    link.addEventListener('mouseenter', syncColaExportLinks);
                    link.addEventListener('focus', syncColaExportLinks);
                    link.addEventListener('click', function () {
                        syncColaExportLinks();
                    });
                });
                syncColaExportLinks();

                const $first = window.jQuery('.js-validaciones-datatable').first();
                if ($first.length) {
                    initTable($first);
                }

                window.addEventListener('validaciones-cola-shown', function (event) {
                    const cola = event.detail && event.detail.cola;
                    if (!cola) {
                        return;
                    }
                    syncColaExportLinks();
                    const $table = window.jQuery('.js-validaciones-datatable[data-dt-cola="' + cola + '"]');
                    if (!$table.length) {
                        return;
                    }
                    if (tables[cola]) {
                        tables[cola].columns.adjust();
                        return;
                    }
                    initTable($table);
                });
            });
        </script>
    @endpush
</x-app-layout>
