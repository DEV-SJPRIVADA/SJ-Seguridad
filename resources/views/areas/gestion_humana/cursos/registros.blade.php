<x-app-layout>
    @php
        $showNuevoModal = $canEdit && $errors->any() && ! $errors->has('import_file');
        $showMasivosModal = $errors->has('import_file');
    @endphp

    <x-slot name="header">
        @include('areas.gestion_humana.cursos.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Cursos</h2>
                <p class="panel-text">Gestion humana — registros de cursos por persona</p>
            </div>
        </div>
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page"
        x-data="cursosRegistros({
            lookupUrl: @js($lookupUrl),
            canEdit: @js($canEdit),
            bulkMarkSolicitadoUrl: @js($bulkMarkSolicitadoUrl ?? null),
            activeFilterQuery: @js($activeFilterQuery ?? []),
            bulkSelectableRows: @js($bulkSelectableRows ?? []),
        })"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success cursos-registros-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger cursos-registros-page__alert">{{ session('error') }}</div>
            @endif

            @if (session('import_failures'))
                <div class="alert alert--danger cursos-registros-page__alert">
                    <p class="mb-2">Errores de importación (máx. 50 en pantalla):</p>
                    <ul class="mb-2">
                        @foreach (session('import_failures') as $failure)
                            <li>
                                Fila {{ $failure['row'] ?? '?' }}
                                @if (! empty($failure['identifier']))
                                    ({{ $failure['identifier'] }})
                                @endif
                                : {{ $failure['reason'] ?? '' }}
                            </li>
                        @endforeach
                    </ul>
                    @if (session('import_report_token'))
                        <a
                            class="btn btn--secondary btn--sm"
                            href="{{ route('gestion-humana.cursos.registros.import-report', session('import_report_token')) }}"
                        >Descargar reporte</a>
                    @endif
                </div>
            @endif

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <form method="GET" action="{{ route('gestion-humana.cursos.registros') }}" class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="filter_document_number">Cédula</label>
                                    <input id="filter_document_number" name="document_number" type="text" class="form-input" value="{{ $filters['document_number'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_full_name">Nombre</label>
                                    <input id="filter_full_name" name="full_name" type="text" class="form-input" value="{{ $filters['full_name'] }}">
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_curso_tipo_id">Tipo curso</label>
                                    <x-searchable-select
                                        id="filter_curso_tipo_id"
                                        name="curso_tipo_id"
                                        :options="$filterTipoOptions"
                                        :value="$filters['curso_tipo_id']"
                                        placeholder="Todos"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_vigencia">Vigencia</label>
                                    <x-searchable-select
                                        id="filter_vigencia"
                                        name="vigencia"
                                        :options="$filterVigenciaOptions"
                                        :value="$filters['vigencia']"
                                        placeholder="Todas"
                                        :allow-clear="true"
                                    />
                                </div>
                                <div class="form-field">
                                    <label class="form-label" for="filter_estado">Estado</label>
                                    <x-searchable-select
                                        id="filter_estado"
                                        name="estado"
                                        :options="$filterEstadoOptions"
                                        :value="$filters['estado']"
                                        placeholder="Todos"
                                        :allow-clear="false"
                                    />
                                </div>
                                <div class="form-field cursos-registros-page__solo-actualizar">
                                    <label class="form-label" for="filter_solo_actualizar">Solo ACTUALIZAR</label>
                                    <label class="cursos-registros-page__checkbox-label">
                                        <input
                                            id="filter_solo_actualizar"
                                            name="solo_actualizar"
                                            type="checkbox"
                                            value="1"
                                            @checked($filters['solo_actualizar'])
                                        >
                                        Filtrar
                                    </label>
                                </div>
                                <div class="form-field cursos-registros-page__filter-actions">
                                    <button type="submit" class="btn btn--primary btn--sm">Filtrar</button>
                                    <a href="{{ route('gestion-humana.cursos.registros') }}" class="btn btn--secondary btn--sm">Limpiar</a>
                                    <a
                                        href="{{ $exportUrl }}"
                                        class="ficha-empleados-filters__bulk-icon cursos-registros-page__export-icon"
                                        title="Exportar a Excel (respeta filtros)"
                                        aria-label="Exportar a Excel"
                                    >
                                        <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                                    </a>
                                    <button
                                        type="button"
                                        class="ficha-empleados-filters__bulk-icon"
                                        title="Plantilla masivos — importar"
                                        aria-label="Plantilla masivos — importar"
                                        x-on:click.prevent="$dispatch('open-modal', 'cursos-masivos')"
                                    >
                                        <x-lucide-upload width="20" height="20" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="cursos-registros-page__table-toolbar">
                            <p class="req-manage-filters__meta">{{ $registros->count() }} registro(s)</p>

                            @if ($canEdit)
                                <div class="cursos-registros-page__table-actions">
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm"
                                        x-show="selectedCount > 0"
                                        x-cloak
                                        x-on:click="openBulkConfirm()"
                                    >
                                        Marcar SOLICITADO
                                        (<span x-text="selectedCount"></span>)
                                    </button>
                                    <button
                                        type="button"
                                        class="ficha-empleados-filters__bulk-icon cursos-registros-page__add-btn"
                                        title="Nuevo registro"
                                        aria-label="Nuevo registro"
                                        x-on:click.prevent="$dispatch('open-modal', 'cursos-nuevo')"
                                    >
                                        <x-lucide-plus width="20" height="20" aria-hidden="true" />
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            class="data-table js-datatable"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                            @if ($canEdit) data-order='[[1, "asc"]]' @endif
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    @if ($canEdit)
                                        <th class="cursos-registros-page__select-col" data-orderable="false">
                                            <label class="cursos-registros-page__select-label" title="Seleccionar todos los elegibles del filtro actual">
                                                <input
                                                    type="checkbox"
                                                    class="cursos-registros-page__select-checkbox"
                                                    x-bind:checked="allEligibleSelected"
                                                    x-bind:disabled="bulkSelectableRows.length === 0"
                                                    x-on:change="toggleSelectAll($event.target.checked)"
                                                    aria-label="Seleccionar todos"
                                                >
                                            </label>
                                        </th>
                                    @endif
                                    <th>CEDULA</th>
                                    <th>NOMBRE COMPLETO</th>
                                    <th>TIPO CURSO</th>
                                    <th>ESCUELA</th>
                                    <th>CODIGO</th>
                                    <th>NIT</th>
                                    <th>FECHA EXPEDICION</th>
                                    <th>No.CURSO</th>
                                    <th>VIGENCIA</th>
                                    <th>ESTADO</th>
                                    <th>OBSERVACIONES</th>
                                    <th>DOCUMENTO</th>
                                    @if ($canEdit)
                                        <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($registros as $curso)
                                    @php
                                        $vigencia = $curso->computeVigencia();
                                        $vigenciaClass = match ($vigencia) {
                                            \App\Models\EmployeeCurso::VIGENCIA_VIGENTE => 'status-pill status-pill--success',
                                            \App\Models\EmployeeCurso::VIGENCIA_ACTUALIZAR => 'status-pill status-pill--warning',
                                            default => 'status-pill status-pill--danger',
                                        };
                                        $canSelect = $canEdit && $curso->estado !== \App\Models\EmployeeCurso::ESTADO_SOLICITADO;
                                    @endphp
                                    <tr>
                                        @if ($canEdit)
                                            <td class="cursos-registros-page__select-col" data-order="{{ $canSelect ? 0 : 1 }}">
                                                @if ($canSelect)
                                                    <label class="cursos-registros-page__select-label">
                                                        <input
                                                            type="checkbox"
                                                            class="cursos-registros-page__select-checkbox"
                                                            value="{{ $curso->id }}"
                                                            x-bind:checked="isSelected({{ $curso->id }})"
                                                            x-on:change="toggleRow({{ $curso->id }}, $event.target.checked)"
                                                            aria-label="Seleccionar curso {{ $curso->numero_curso }}"
                                                        >
                                                    </label>
                                                @else
                                                    <span class="cursos-registros-page__select-disabled" title="Ya está SOLICITADO">—</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>{{ $curso->document_number }}</td>
                                        <td>{{ $curso->full_name }}</td>
                                        <td>{{ $curso->cursoTipo?->tipo_curso }}</td>
                                        <td>{{ $curso->escuela_nombre ?: '—' }}</td>
                                        <td>{{ $curso->escuela_codigo ?: '—' }}</td>
                                        <td>{{ $curso->escuela_nit ?: '—' }}</td>
                                        <td>{{ optional($curso->fecha_expedicion)?->format('Y-m-d') }}</td>
                                        <td>{{ $curso->numero_curso }}</td>
                                        <td><span class="{{ $vigenciaClass }}">{{ $vigencia }}</span></td>
                                        <td>{{ $curso->estado ?: '—' }}</td>
                                        <td>{{ \Illuminate\Support\Str::limit((string) $curso->observaciones, 60) ?: '—' }}</td>
                                        <td>
                                            <div class="cursos-registros-page__document-cell">
                                                @if ($curso->hasDocument())
                                                    <div class="cursos-registros-page__document-links">
                                                        <a
                                                            class="btn btn--secondary btn--sm"
                                                            href="{{ route('gestion-humana.cursos.registros.document.download', $curso) }}"
                                                        >Descargar</a>
                                                        @if ($canEdit)
                                                            <form
                                                                method="POST"
                                                                action="{{ route('gestion-humana.cursos.registros.document.destroy', $curso) }}"
                                                                class="inline"
                                                                onsubmit="return confirm('¿Quitar el documento?');"
                                                            >
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn--ghost btn--sm">Quitar</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @else
                                                    <span class="panel-text">Sin archivo</span>
                                                @endif
                                                @if ($canEdit)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.cursos.registros.document.upload', $curso) }}"
                                                        enctype="multipart/form-data"
                                                        class="cursos-registros-page__upload"
                                                        x-data="{ fileName: '' }"
                                                    >
                                                        @csrf
                                                        <input
                                                            id="curso-document-{{ $curso->id }}"
                                                            name="document"
                                                            type="file"
                                                            class="cursos-registros-page__file-input"
                                                            accept=".pdf,.jpg,.jpeg,.png,.webp"
                                                            required
                                                            @change="fileName = $event.target.files?.[0]?.name || ''"
                                                        >
                                                        <span
                                                            class="cursos-registros-page__file-name cursos-registros-page__file-name--compact"
                                                            x-text="fileName || 'Sin archivo'"
                                                        ></span>
                                                        <div class="cursos-registros-page__file-actions">
                                                            <label
                                                                for="curso-document-{{ $curso->id }}"
                                                                class="btn btn--secondary btn--sm"
                                                            >
                                                                <x-lucide-upload width="14" height="14" aria-hidden="true" />
                                                                Elegir archivo
                                                            </label>
                                                            <button type="submit" class="btn btn--primary btn--sm">Subir</button>
                                                        </div>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                        @if ($canEdit)
                                            <td class="table-actions">
                                                <div class="cursos-registros-page__row-actions">
                                                    <button
                                                        type="button"
                                                        class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit"
                                                        title="Editar"
                                                        aria-label="Editar"
                                                        @click="openEdit(@js([
                                                            'id' => $curso->id,
                                                            'document_number' => $curso->document_number,
                                                            'full_name' => $curso->full_name,
                                                            'curso_tipo_id' => (string) $curso->curso_tipo_id,
                                                            'curso_escuela_id' => $curso->curso_escuela_id ? (string) $curso->curso_escuela_id : '',
                                                            'fecha_expedicion' => optional($curso->fecha_expedicion)?->format('Y-m-d'),
                                                            'numero_curso' => $curso->numero_curso,
                                                            'estado' => $curso->estado ?? '',
                                                            'observaciones' => $curso->observaciones ?? '',
                                                            'update_url' => route('gestion-humana.cursos.registros.update', $curso),
                                                        ]))"
                                                    >
                                                        <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.cursos.registros.destroy', $curso) }}"
                                                        class="cursos-catalogo-page__delete-form"
                                                        onsubmit="return confirm('¿Eliminar este registro y su documento?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                                            title="Eliminar"
                                                            aria-label="Eliminar"
                                                        >
                                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @include('areas.gestion_humana.cursos.partials.masivos-modal', [
                'filters' => $filters,
                'canEdit' => $canEdit,
                'exportUrl' => $exportUrl,
                'importTemplateUrl' => $importTemplateUrl,
                'importUrl' => $importUrl,
                'show' => $showMasivosModal,
            ])

            @if ($canEdit)
                @include('areas.gestion_humana.cursos.partials.nuevo-modal', [
                    'tipoOptions' => $tipoOptions,
                    'escuelaOptions' => $escuelaOptions,
                    'estadoOptions' => $estadoOptions,
                    'lookupUrl' => $lookupUrl,
                    'show' => $showNuevoModal,
                ])

                <div
                    class="cursos-registros-page__modal"
                    x-show="editOpen"
                    x-cloak
                    @keydown.escape.window="editOpen = false"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="editOpen = false"></div>
                    <div class="cursos-registros-page__modal-panel panel" role="dialog" aria-modal="true">
                        <div class="panel__header panel-heading-row">
                            <h3 class="panel-title">Editar registro</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="editOpen = false">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <form method="POST" :action="editForm.update_url" class="cursos-registros-page__form">
                                @csrf
                                @method('PATCH')
                                <div class="cursos-registros-page__form-grid">
                                    <div class="form-field">
                                        <label class="form-label" for="edit_document_number">CEDULA</label>
                                        <input
                                            id="edit_document_number"
                                            name="document_number"
                                            type="text"
                                            class="form-input"
                                            maxlength="50"
                                            required
                                            x-model="editForm.document_number"
                                            @blur="lookupName($event.target.value, 'edit')"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_full_name">NOMBRE COMPLETO</label>
                                        <input
                                            id="edit_full_name"
                                            name="full_name"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            required
                                            x-model="editForm.full_name"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_curso_tipo_id">TIPO CURSO</label>
                                        <select id="edit_curso_tipo_id" name="curso_tipo_id" class="form-input" required x-model="editForm.curso_tipo_id">
                                            @foreach ($tipoOptions as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_curso_escuela_id">ESCUELA</label>
                                        <select id="edit_curso_escuela_id" name="curso_escuela_id" class="form-input" required x-model="editForm.curso_escuela_id">
                                            <option value="">Seleccionar escuela</option>
                                            @foreach ($escuelaOptions as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_fecha_expedicion">FECHA EXPEDICION</label>
                                        <input
                                            id="edit_fecha_expedicion"
                                            name="fecha_expedicion"
                                            type="date"
                                            class="form-input"
                                            required
                                            x-model="editForm.fecha_expedicion"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_numero_curso">No.CURSO</label>
                                        <input
                                            id="edit_numero_curso"
                                            name="numero_curso"
                                            type="text"
                                            class="form-input"
                                            maxlength="100"
                                            required
                                            x-model="editForm.numero_curso"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="edit_estado">ESTADO</label>
                                        <select id="edit_estado" name="estado" class="form-input" x-model="editForm.estado" required>
                                            <option value="SOLICITADO">SOLICITADO</option>
                                            <option value="ACTUALIZADO">ACTUALIZADO</option>
                                            <option value="PENDIENTE">PENDIENTE</option>
                                        </select>
                                    </div>
                                    <div class="form-field cursos-registros-page__form-span">
                                        <label class="form-label" for="edit_observaciones">OBSERVACIONES</label>
                                        <textarea
                                            id="edit_observaciones"
                                            name="observaciones"
                                            class="form-input"
                                            rows="2"
                                            x-model="editForm.observaciones"
                                        ></textarea>
                                    </div>
                                </div>
                                <div class="cursos-registros-page__form-actions">
                                    <button type="submit" class="btn btn--primary">Actualizar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    class="cursos-registros-page__modal"
                    x-show="bulkConfirmOpen"
                    x-cloak
                    @keydown.escape.window="closeBulkConfirm()"
                >
                    <div class="cursos-registros-page__modal-backdrop" @click="closeBulkConfirm()"></div>
                    <div
                        class="cursos-registros-page__modal-panel panel cursos-registros-page__bulk-modal"
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="cursos-bulk-solicitado-title"
                    >
                        <div class="panel__header panel-heading-row">
                            <h3 id="cursos-bulk-solicitado-title" class="panel-title">Confirmar marcado a SOLICITADO</h3>
                            <button type="button" class="btn btn--ghost btn--sm" @click="closeBulkConfirm()">Cerrar</button>
                        </div>
                        <div class="panel__body">
                            <div class="alert alert--danger cursos-registros-page__bulk-warning">
                                Esta acción <strong>no se puede revertir</strong> desde el marcado masivo.
                                El estado quedará en <strong>SOLICITADO</strong> y el proceso diario de sincronización lo conservará.
                                Solo podrá cambiarlo editando cada registro de forma individual.
                            </div>

                            <p class="panel-text cursos-registros-page__bulk-summary">
                                Se actualizarán <strong x-text="selectedCount"></strong> registro(s):
                            </p>

                            <div class="cursos-registros-page__bulk-list-wrap">
                                <table class="data-table cursos-registros-page__bulk-list">
                                    <thead>
                                        <tr>
                                            <th>Cédula</th>
                                            <th>Nombre</th>
                                            <th>Tipo</th>
                                            <th>No.CURSO</th>
                                            <th>Vigencia</th>
                                            <th>Estado actual</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in selectedRows" :key="row.id">
                                            <tr>
                                                <td x-text="row.document_number"></td>
                                                <td x-text="row.full_name"></td>
                                                <td x-text="row.tipo_curso"></td>
                                                <td x-text="row.numero_curso"></td>
                                                <td x-text="row.vigencia"></td>
                                                <td x-text="row.estado"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <form
                                method="POST"
                                :action="bulkMarkSolicitadoUrl"
                                class="cursos-registros-page__bulk-form"
                                x-on:submit="submittingBulk = true"
                            >
                                @csrf
                                <template x-for="id in selectedIds" :key="'bulk-id-' + id">
                                    <input type="hidden" name="ids[]" :value="id">
                                </template>
                                <template x-for="(value, key) in activeFilterQuery" :key="'filter-' + key">
                                    <input type="hidden" :name="key" :value="value">
                                </template>

                                <label class="cursos-registros-page__bulk-confirm-label">
                                    <input type="checkbox" name="confirmed" value="1" x-model="bulkConfirmAccepted">
                                    Confirmo que revisé el listado y estoy seguro de ejecutar el cambio a SOLICITADO.
                                </label>

                                <div class="cursos-registros-page__form-actions">
                                    <button type="button" class="btn btn--secondary" @click="closeBulkConfirm()" :disabled="submittingBulk">
                                        Cancelar
                                    </button>
                                    <button
                                        type="submit"
                                        class="btn btn--primary"
                                        :disabled="! bulkConfirmAccepted || selectedCount < 1 || submittingBulk"
                                    >
                                        <span x-show="! submittingBulk">Ejecutar cambio</span>
                                        <span x-show="submittingBulk" x-cloak>Ejecutando…</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
        <script>
            function cursosRegistros(config) {
                return {
                    lookupUrl: config.lookupUrl,
                    canEdit: config.canEdit,
                    bulkMarkSolicitadoUrl: config.bulkMarkSolicitadoUrl || '',
                    activeFilterQuery: config.activeFilterQuery || {},
                    bulkSelectableRows: config.bulkSelectableRows || [],
                    selectedMap: {},
                    bulkConfirmOpen: false,
                    bulkConfirmAccepted: false,
                    submittingBulk: false,
                    editOpen: false,
                    editForm: {
                        id: null,
                        document_number: '',
                        full_name: '',
                        curso_tipo_id: '',
                        curso_escuela_id: '',
                        fecha_expedicion: '',
                        numero_curso: '',
                        estado: 'ACTUALIZADO',
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
                    get selectedRows() {
                        const selected = new Set(this.selectedIds);
                        return this.bulkSelectableRows.filter((row) => selected.has(Number(row.id)));
                    },
                    get allEligibleSelected() {
                        if (this.bulkSelectableRows.length === 0) {
                            return false;
                        }
                        return this.bulkSelectableRows.every((row) => this.selectedMap[row.id]);
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
                    toggleSelectAll(checked) {
                        const next = {};
                        if (checked) {
                            this.bulkSelectableRows.forEach((row) => {
                                next[row.id] = true;
                            });
                        }
                        this.selectedMap = next;
                    },
                    openBulkConfirm() {
                        if (this.selectedCount < 1) {
                            return;
                        }
                        this.bulkConfirmAccepted = false;
                        this.submittingBulk = false;
                        this.bulkConfirmOpen = true;
                        this.editOpen = false;
                    },
                    closeBulkConfirm() {
                        if (this.submittingBulk) {
                            return;
                        }
                        this.bulkConfirmOpen = false;
                        this.bulkConfirmAccepted = false;
                    },
                    openEdit(row) {
                        this.editForm = { ...row };
                        this.editOpen = true;
                        this.bulkConfirmOpen = false;
                    },
                    async lookupName(cedula, target) {
                        const value = String(cedula || '').trim();
                        if (!value || !this.canEdit) {
                            return;
                        }

                        try {
                            const res = await fetch(this.lookupUrl + '?cedula=' + encodeURIComponent(value), {
                                headers: { 'Accept': 'application/json' },
                            });
                            if (!res.ok) {
                                return;
                            }
                            const data = await res.json();
                            if (!data.found || !data.full_name) {
                                return;
                            }
                            if (target === 'edit') {
                                this.editForm.full_name = data.full_name;
                            } else if (this.$refs.createName) {
                                this.$refs.createName.value = data.full_name;
                            }
                        } catch (e) {
                            // ignore lookup errors
                        }
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-cursos-import-form]');
                if (!form) {
                    return;
                }

                const fileInput = form.querySelector('[data-cursos-import-file]');
                const fileName = form.querySelector('[data-cursos-import-name]');
                const submitBtn = form.querySelector('[data-cursos-import-submit]');
                const loading = document.querySelector('[data-cursos-import-loading]');

                fileInput?.addEventListener('change', () => {
                    const name = fileInput.files?.[0]?.name || 'Sin archivo seleccionado';
                    if (fileName) {
                        fileName.textContent = name;
                    }
                    if (submitBtn) {
                        submitBtn.disabled = !fileInput.files?.length;
                    }
                });

                form.addEventListener('submit', () => {
                    if (loading) {
                        loading.hidden = false;
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="ficha-empleados-masivos-modal__btn-spinner" aria-hidden="true"></span> Importando…';
                    }
                });
            });
        </script>
    @endpush
</x-app-layout>
