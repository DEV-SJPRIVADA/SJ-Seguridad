<x-app-layout>
    @php
        $eficaciaActiva = (string) ($filters['eficacia'] ?? 'abierto');
        $hasActiveFilters = collect($filters)->except(['recurrencia', 'eficacia'])->contains(fn ($value) => $value !== null && $value !== '')
            || ! in_array($eficaciaActiva, ['', 'abierto'], true);
        $queryBase = collect($filters)->except(['eficacia', 'recurrencia'])->filter(fn ($value) => $value !== null && $value !== '')->all();
    @endphp

    <x-slot name="header">
        @include('areas.calidad.servicio_cliente.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Listado FO-GI-22: filtros, eficacia en botones y tabla server-side. --}}
    <div
        class="page-section servicio-cliente-page req-manage-page"
        x-data="{
            deleteOpen: false,
            deleteUrl: '',
            deleteLabel: '',
            historialLoading: false,
            historialItems: [],
            openDelete(url, label) {
                this.deleteUrl = url;
                this.deleteLabel = label;
                this.deleteOpen = true;
            },
            openHistorial() {
                this.historialLoading = true;
                this.historialItems = [];
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'fo-gi-22-historial' }));
                fetch(@js($historialUrl), { headers: { Accept: 'application/json' } })
                    .then((response) => response.json())
                    .then((data) => { this.historialItems = data.data || []; })
                    .catch(() => { this.historialItems = []; })
                    .finally(() => { this.historialLoading = false; });
            },
        }"
        @fo-gi-22-delete.window="openDelete($event.detail.url, $event.detail.label)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger">{{ session('error') }}</div>
            @endif

            <div class="panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <details class="req-manage-shell__filters req-manage-filters req-manage-filters__panel" @if ($hasActiveFilters) open @endif>
                        <summary class="req-manage-filters__panel-toggle">
                            <span>Filtros</span>
                            @if ($hasActiveFilters)
                                <span class="req-manage-filters__panel-badge">Activos</span>
                            @endif
                        </summary>
                        <div class="req-manage-filters__panel-body">
                            <form method="GET" action="{{ route('calidad.servicio-cliente.fo-gi-22.index') }}" class="req-manage-filters">
                                <input type="hidden" name="eficacia" value="{{ $eficaciaActiva }}">
                                <div class="cursos-registros-page__filters">
                                    <div class="form-field">
                                        <label class="form-label" for="filter_tipo_cliente">Tipo de cliente</label>
                                        <x-searchable-select id="filter_tipo_cliente" name="tipo_cliente" :options="$options['tipos']" :value="$filters['tipo_cliente']" placeholder="Todos" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_tipologia_id">Tipología</label>
                                        <x-searchable-select id="filter_tipologia_id" name="tipologia_id" :options="$options['tipologias']" :value="$filters['tipologia_id']" placeholder="Todas" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_fuente_id">Fuente</label>
                                        <x-searchable-select id="filter_fuente_id" name="fuente_id" :options="$options['fuentes']" :value="$filters['fuente_id']" placeholder="Todas" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="filter_responsable_id">Responsable</label>
                                        <x-searchable-select id="filter_responsable_id" name="responsable_id" :options="$options['responsables']" :value="$filters['responsable_id']" placeholder="Todos" :allow-clear="true" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="recepcion_desde">Recepción desde</label>
                                        <input id="recepcion_desde" name="recepcion_desde" type="date" class="form-input" value="{{ $filters['recepcion_desde'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="recepcion_hasta">Recepción hasta</label>
                                        <input id="recepcion_hasta" name="recepcion_hasta" type="date" class="form-input" value="{{ $filters['recepcion_hasta'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="verificacion_desde">Verificación desde</label>
                                        <input id="verificacion_desde" name="verificacion_desde" type="date" class="form-input" value="{{ $filters['verificacion_desde'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="verificacion_hasta">Verificación hasta</label>
                                        <input id="verificacion_hasta" name="verificacion_hasta" type="date" class="form-input" value="{{ $filters['verificacion_hasta'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="plazo_desde">Plazo desde</label>
                                        <input id="plazo_desde" name="plazo_desde" type="date" class="form-input" value="{{ $filters['plazo_desde'] }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="plazo_hasta">Plazo hasta</label>
                                        <input id="plazo_hasta" name="plazo_hasta" type="date" class="form-input" value="{{ $filters['plazo_hasta'] }}">
                                    </div>
                                    <div class="form-field cursos-registros-page__filter-actions">
                                        <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar" aria-label="Filtrar">
                                            <x-lucide-search width="18" height="18" aria-hidden="true" />
                                        </button>
                                        <a href="{{ route('calidad.servicio-cliente.fo-gi-22.index') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Limpiar filtros" aria-label="Limpiar filtros">
                                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                                        </a>
                                        <x-export-excel
                                            route="{{ $exportUrl }}"
                                            label=""
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            title="Exportar a Excel (respeta filtros)"
                                            aria-label="Exportar a Excel"
                                        />
                                    </div>
                                </div>
                            </form>
                            <div class="servicio-cliente-page__eficacia" role="group" aria-label="Eficacia del seguimiento">
                                <a href="{{ route('calidad.servicio-cliente.fo-gi-22.index', array_merge($queryBase, ['eficacia' => 'todos'])) }}" class="module-tab {{ $eficaciaActiva === 'todos' ? 'module-tab--active' : '' }}">Todos</a>
                                @foreach ($options['eficacia'] as $option)
                                    <a href="{{ route('calidad.servicio-cliente.fo-gi-22.index', array_merge($queryBase, ['eficacia' => $option['value']])) }}" class="module-tab {{ $eficaciaActiva === $option['value'] ? 'module-tab--active' : '' }}">{{ $option['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    </details>

                    <div class="cursos-registros-page__table-toolbar">
                        <p class="req-manage-filters__meta">
                            <strong id="fo-gi-22-count">…</strong>
                            <span>registro(s)</span>
                        </p>
                        <div class="cursos-registros-page__table-actions">
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Historial de cambios"
                                aria-label="Historial de cambios"
                                x-on:click.prevent="openHistorial()"
                            >
                                <x-lucide-history width="18" height="18" aria-hidden="true" />
                            </button>
                            @if ($canEdit)
                                <a href="{{ route('calidad.servicio-cliente.fo-gi-22.create') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Nuevo caso" aria-label="Nuevo caso">
                                    <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap data-table-wrap--booting">
                        <table
                            class="data-table js-fo-gi-22-datatable"
                            data-dt-url="{{ $datatableUrl }}"
                            data-dt-can-edit="{{ $canEdit ? '1' : '0' }}"
                        >
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Recepción</th>
                                    <th>Tipo</th>
                                    <th>Tipología</th>
                                    <th>Fuente</th>
                                    <th>Cliente</th>
                                    <th>Ciudad</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th>Corrección</th>
                                    <th>Acción</th>
                                    <th>Responsable</th>
                                    <th>Ejecución</th>
                                    <th>Verificación</th>
                                    <th>Plazo</th>
                                    <th>Días</th>
                                    <th>Resp. seguimiento</th>
                                    <th>Obs.</th>
                                    <th>Eficacia</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Confirmación de borrado definitivo. --}}
        <div class="cursos-registros-page__modal" x-show="deleteOpen" x-cloak @keydown.escape.window="deleteOpen = false">
            <div class="cursos-registros-page__modal-backdrop" @click="deleteOpen = false"></div>
            <div class="modal-card ficha-empleados-masivos-modal" role="dialog" aria-modal="true" aria-labelledby="fo-gi-22-delete-title">
                <div class="ficha-empleados-masivos-modal__header">
                    <div>
                        <h3 id="fo-gi-22-delete-title" class="ficha-empleados-masivos-modal__title">Eliminar caso <span x-text="deleteLabel"></span></h3>
                        <p class="ficha-empleados-masivos-modal__lead">El caso, sus observaciones y sus archivos se borran de la base. Esta acción queda en el log de auditoría.</p>
                    </div>
                    <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" @click="deleteOpen = false">
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
                <form method="POST" :action="deleteUrl" class="ficha-empleados-masivos-modal__footer">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn--secondary" @click="deleteOpen = false">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Eliminar definitivamente</button>
                </form>
            </div>
        </div>

        <x-modal name="fo-gi-22-historial" maxWidth="2xl" focusable>
            <div class="modal-card ficha-empleados-masivos-modal rn-historial-modal">
                <div class="ficha-empleados-masivos-modal__header">
                    <div class="ficha-empleados-masivos-modal__heading">
                        <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                            <x-lucide-history width="18" height="18" />
                        </span>
                        <div>
                            <h3 class="ficha-empleados-masivos-modal__title">Historial de cambios</h3>
                            <p class="ficha-empleados-masivos-modal__lead">Eventos registrados en FO-GI-22: casos, observaciones, adjuntos y catálogos.</p>
                        </div>
                    </div>
                    <button type="button" class="ficha-empleados-masivos-modal__close" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'fo-gi-22-historial')">
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
                <div class="rn-historial-modal__body">
                    <div class="rn-historial-modal__state" x-show="historialLoading" x-cloak>
                        <span class="rn-historial-modal__spinner" aria-hidden="true"></span>
                        <p class="rn-historial-modal__state-title">Cargando historial…</p>
                    </div>
                    <div class="rn-historial-modal__state rn-historial-modal__state--empty" x-show="! historialLoading && historialItems.length === 0" x-cloak>
                        <p class="rn-historial-modal__state-title">Sin eventos</p>
                        <p class="rn-historial-modal__state-desc">Aún no hay cambios registrados.</p>
                    </div>
                    <ol class="rn-historial-modal__timeline" x-show="! historialLoading && historialItems.length > 0" x-cloak>
                        <template x-for="item in historialItems" :key="item.id">
                            <li class="rn-historial-modal__item">
                                <span class="rn-historial-modal__dot" :class="'rn-historial-modal__dot--' + item.kind" aria-hidden="true"></span>
                                <div class="rn-historial-modal__card">
                                    <div class="rn-historial-modal__card-top">
                                        <time class="rn-historial-modal__when" x-text="item.created_at_display"></time>
                                        <span class="rn-historial-modal__badge" :class="'rn-historial-modal__badge--' + item.kind" x-text="item.action_label"></span>
                                    </div>
                                    <p class="rn-historial-modal__summary" x-text="item.summary"></p>
                                    <div class="rn-historial-modal__actor">
                                        <span x-text="item.user_name || 'Sistema'"></span>
                                    </div>
                                    <p class="rn-historial-modal__reason" x-show="item.reason" x-cloak x-text="item.reason"></p>
                                </div>
                            </li>
                        </template>
                    </ol>
                </div>
            </div>
        </x-modal>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const $ = window.jQuery;
            if (! $ || ! $.fn.DataTable) {
                return;
            }

            const $table = $('.js-fo-gi-22-datatable');
            if (! $table.length) {
                return;
            }

            const api = $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: { url: $table.data('dt-url') },
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json',
                    emptyTable: 'No hay casos para este filtro.',
                },
                dom: '<"req-manage-dt-top"lf><"req-manage-table-scroll"t><"req-manage-dt-bottom"ip>',
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                pageLength: 25,
                responsive: false,
                order: [[0, 'desc']],
                columnDefs: [{ targets: [19], orderable: false, searchable: false }],
            });

            api.on('xhr.dt', function (_event, _settings, json) {
                $table.closest('.data-table-wrap').removeClass('data-table-wrap--booting');
                const el = document.getElementById('fo-gi-22-count');
                if (el && json && typeof json.recordsFiltered !== 'undefined') {
                    el.textContent = json.recordsFiltered;
                }
            });

            $table.on('click', '.js-fo-gi-22-delete', function () {
                window.dispatchEvent(new CustomEvent('fo-gi-22-delete', {
                    detail: {
                        url: this.dataset.deleteUrl,
                        label: this.dataset.deleteLabel,
                    },
                }));
            });
        });
    </script>
</x-app-layout>
