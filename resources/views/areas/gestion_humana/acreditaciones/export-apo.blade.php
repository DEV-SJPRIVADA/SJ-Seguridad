<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div
        class="page-section cursos-registros-page req-manage-page acreditaciones-export-apo-page"
        x-data="acreditacionesExportApo({
            previewUrl: @js($previewUrl),
            generateUrl: @js($generateUrl),
            defaultVigenciaPolicy: @js($defaultVigenciaPolicy),
            csrfToken: @js(csrf_token()),
            settings: @js([
                'nit' => $exportApoSettings->nit,
                'razon_social' => $exportApoSettings->razon_social,
                'tipo_documento' => $exportApoSettings->tipo_documento,
                'tipo_establecimiento' => $exportApoSettings->tipo_establecimiento,
                'telefono_r' => $exportApoSettings->telefono_r,
                'direccion_r' => $exportApoSettings->direccion_r,
                'direccion_p' => $exportApoSettings->direccion_p,
                'departamento' => $exportApoSettings->departamento,
                'ciudad' => $exportApoSettings->ciudad,
                'educacion_bm' => $exportApoSettings->educacion_bm,
                'educacion_s' => $exportApoSettings->educacion_s,
                'discapacidad' => $exportApoSettings->discapacidad,
            ]),
        })"
        x-on:export-apo-generate.window="submitGenerate($event.detail.include)"
    >
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

            <div class="panel cursos-registros-panel">
                <div class="panel__body panel__body--compact req-manage-shell">
                    <div class="req-manage-shell__filters">
                        <div class="req-manage-filters">
                            <div class="cursos-registros-page__filters">
                                <div class="form-field">
                                    <label class="form-label" for="vigencia_policy">Política vigencia curso</label>
                                    <x-searchable-select
                                        id="vigencia_policy"
                                        name="vigencia_policy"
                                        :options="$vigenciaPolicyOptions"
                                        :value="$defaultVigenciaPolicy"
                                        placeholder="Seleccionar"
                                        :allow-clear="false"
                                        :required="true"
                                    />
                                </div>
                                <div class="form-field cursos-registros-page__filter-actions">
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm"
                                        x-bind:disabled="previewLoading || generateLoading"
                                        x-on:click="runValidate()"
                                        title="Validar candidatos del universo Export Apo"
                                    >
                                        <x-lucide-eye width="16" height="16" aria-hidden="true" />
                                        <span x-text="previewLoading ? 'Validando…' : 'Validar'"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn--primary btn--sm"
                                        x-show="hasValidated"
                                        x-cloak
                                        x-bind:disabled="selectedCount === 0 || previewLoading || generateLoading"
                                        x-on:click="openGenerateModal()"
                                        title="Generar archivo APO .xls"
                                    >
                                        <x-lucide-download width="16" height="16" aria-hidden="true" />
                                        <span x-text="generateLoading ? 'Generando…' : 'Generar .xls'"></span>
                                    </button>
                                    <button
                                        type="button"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Limpiar resultados"
                                        aria-label="Limpiar"
                                        x-on:click="clearAll()"
                                    >
                                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                            <p class="panel-text" style="margin-top:0.35rem;font-size:0.85rem;">
                                Pulse <strong>Validar</strong> para cargar candidatos (<strong>EN PROCESO</strong>, <strong>POR VENCER</strong>, <strong>DESACREDITADO</strong> con ficha activa)
                                en columnas SuperVigilancia. Marque filas y genere el <strong>.xls</strong>.
                            </p>
                            <div class="alert alert--danger" x-show="previewError" x-text="previewError" x-cloak style="margin-top:0.5rem;"></div>
                        </div>

                        <div class="cursos-registros-page__table-toolbar" x-show="hasValidated" x-cloak>
                            <p class="req-manage-filters__meta">
                                <strong x-text="previewRows.length"></strong>
                                <span>fila(s)</span>
                                <span x-show="previewSummary" x-cloak>
                                    · Válidas: <strong x-text="previewSummary?.validas ?? 0"></strong>
                                    · Blandas: <strong x-text="previewSummary?.novedades_blandas ?? 0"></strong>
                                    · Bloqueadas: <strong x-text="previewSummary?.bloqueadas ?? 0"></strong>
                                </span>
                                <span x-show="selectedCount > 0" x-cloak>
                                    · <strong x-text="selectedCount"></strong> seleccionado(s)
                                </span>
                            </p>
                        </div>
                    </div>

                    <div
                        class="panel-text"
                        x-show="!hasValidated && !previewLoading"
                        x-cloak
                        style="padding:1.25rem 0.25rem;color:var(--text-muted, #64748b);"
                    >
                        No hay datos cargados. Elija la política de vigencia y pulse <strong>Validar</strong>.
                    </div>

                    <p class="panel-text" x-show="previewLoading" x-cloak style="padding:1rem 0.25rem;">Validando candidatos…</p>

                    <div class="data-table-wrap req-manage-shell__table" x-show="hasValidated && previewRows.length > 0" x-cloak>
                        <div class="req-manage-table-scroll">
                            <table class="data-table" style="width:100%;min-width:96rem;">
                                <thead>
                                    <tr>
                                        <th class="cursos-registros-page__select-col">
                                            <label class="cursos-registros-page__select-label" title="Seleccionar todos">
                                                <input
                                                    type="checkbox"
                                                    class="cursos-registros-page__select-checkbox"
                                                    x-bind:checked="allSelected"
                                                    x-on:change="toggleAll($event.target.checked)"
                                                    aria-label="Seleccionar todos"
                                                >
                                            </label>
                                        </th>
                                        <th>Nit</th>
                                        <th>RazonSocial</th>
                                        <th>TipoDocumento</th>
                                        <th>NoDocumento</th>
                                        <th>Nombre1</th>
                                        <th>Nombre2</th>
                                        <th>Apellido1</th>
                                        <th>Apellido2</th>
                                        <th>FechaNacimiento</th>
                                        <th>Genero</th>
                                        <th>Cargo</th>
                                        <th>Fechavinculacion</th>
                                        <th>CodigoCurso</th>
                                        <th>NitEscuela</th>
                                        <th>Nro</th>
                                        <th>TipoEstablecimiento</th>
                                        <th>TelefonoR</th>
                                        <th>DireccionR</th>
                                        <th>DireccionP</th>
                                        <th>Departamento</th>
                                        <th>Ciudad</th>
                                        <th>EducacionBM</th>
                                        <th>EducacionS</th>
                                        <th>Discapacidad</th>
                                        <th>Estado curso</th>
                                        <th>Valida</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="row in previewRows" :key="row.acreditado_id">
                                        <tr>
                                            <td>
                                                <label class="cursos-registros-page__select-label">
                                                    <input
                                                        type="checkbox"
                                                        class="cursos-registros-page__select-checkbox"
                                                        x-bind:value="row.acreditado_id"
                                                        x-bind:checked="isSelected(row.acreditado_id)"
                                                        x-on:change="toggleRow(row.acreditado_id, $event.target.checked)"
                                                        aria-label="Seleccionar fila"
                                                    >
                                                </label>
                                            </td>
                                            <td x-text="settings.nit"></td>
                                            <td x-text="settings.razon_social"></td>
                                            <td x-text="settings.tipo_documento"></td>
                                            <td x-text="row.document_number"></td>
                                            <td x-text="row.nombre1"></td>
                                            <td x-text="row.nombre2 || ''"></td>
                                            <td x-text="row.apellido1"></td>
                                            <td x-text="row.apellido2 || ''"></td>
                                            <td x-text="row.fecha_nacimiento"></td>
                                            <td x-text="row.genero"></td>
                                            <td x-text="row.cargo"></td>
                                            <td x-text="row.fecha_vinculacion"></td>
                                            <td x-text="row.codigo_curso || ''"></td>
                                            <td x-text="row.nit_escuela || ''"></td>
                                            <td x-text="row.nro || ''"></td>
                                            <td x-text="settings.tipo_establecimiento"></td>
                                            <td x-text="settings.telefono_r"></td>
                                            <td x-text="settings.direccion_r"></td>
                                            <td x-text="settings.direccion_p"></td>
                                            <td x-text="settings.departamento"></td>
                                            <td x-text="settings.ciudad"></td>
                                            <td x-text="settings.educacion_bm"></td>
                                            <td x-text="settings.educacion_s"></td>
                                            <td x-text="settings.discapacidad"></td>
                                            <td>
                                                <template x-if="row.estado_curso">
                                                    <span
                                                        class="status-pill"
                                                        :class="{
                                                            'status-pill--success': row.estado_curso === 'VIGENTE',
                                                            'status-pill--warning': row.estado_curso === 'ACTUALIZAR',
                                                            'status-pill--danger': row.estado_curso === 'VENCIDO',
                                                            'status-pill--muted': !['VIGENTE','ACTUALIZAR','VENCIDO'].includes(row.estado_curso),
                                                        }"
                                                        x-text="row.estado_curso"
                                                    ></span>
                                                </template>
                                                <span x-show="!row.estado_curso">—</span>
                                            </td>
                                            <td>
                                                <span
                                                    class="status-pill"
                                                    :class="row.valida ? 'status-pill--success' : (row.hard_block ? 'status-pill--danger' : 'status-pill--warning')"
                                                    x-text="row.valida ? 'Sí' : 'No'"
                                                ></span>
                                            </td>
                                            <td x-text="row.motivo || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        class="panel-text"
                        x-show="hasValidated && !previewLoading && previewRows.length === 0 && !previewError"
                        x-cloak
                        style="padding:1rem 0.25rem;"
                    >
                        No hay candidatos en el universo Export Apo.
                    </div>
                </div>
            </div>
        </div>

        <x-modal name="export-apo-novedades" maxWidth="md">
            <div class="modal-body">
                <h3 class="panel-title" style="margin-bottom:0.5rem;">Incluir novedades blandas</h3>
                <p class="panel-text" style="margin-bottom:1rem;">
                    Hay filas con novedades blandas (curso/escuela/código). ¿Desea incluirlas en el archivo .xls?
                    Las filas con bloqueo duro (ficha incompleta) nunca se exportan.
                </p>
                <div style="display:flex;gap:0.5rem;justify-content:flex-end;flex-wrap:wrap;">
                    <button
                        type="button"
                        class="btn btn--secondary btn--sm"
                        x-on:click="$dispatch('close-modal', 'export-apo-novedades')"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn--secondary btn--sm"
                        x-on:click="$dispatch('export-apo-generate', { include: false })"
                    >
                        No
                    </button>
                    <button
                        type="button"
                        class="btn btn--primary btn--sm"
                        x-on:click="$dispatch('export-apo-generate', { include: true })"
                    >
                        Sí
                    </button>
                </div>
            </div>
        </x-modal>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('acreditacionesExportApo', (config) => ({
                    previewUrl: config.previewUrl,
                    generateUrl: config.generateUrl,
                    defaultVigenciaPolicy: config.defaultVigenciaPolicy,
                    csrfToken: config.csrfToken || '',
                    settings: config.settings || {},
                    selected: {},
                    previewLoading: false,
                    generateLoading: false,
                    hasValidated: false,
                    previewError: '',
                    previewRows: [],
                    previewSummary: null,

                    get selectedCount() {
                        return Object.keys(this.selected).length;
                    },

                    get allSelected() {
                        return this.previewRows.length > 0
                            && this.previewRows.every((row) => this.isSelected(row.acreditado_id));
                    },

                    isSelected(id) {
                        return !! this.selected[id];
                    },

                    toggleRow(id, checked) {
                        if (checked) {
                            this.selected[id] = true;
                        } else {
                            delete this.selected[id];
                        }
                    },

                    toggleAll(checked) {
                        if (! checked) {
                            this.selected = {};
                            return;
                        }

                        const next = {};
                        this.previewRows.forEach((row) => {
                            next[row.acreditado_id] = true;
                        });
                        this.selected = next;
                    },

                    selectedIds() {
                        return Object.keys(this.selected).map((id) => Number(id));
                    },

                    currentVigenciaPolicy() {
                        const input = document.querySelector('input[name="vigencia_policy"]');
                        const value = input ? String(input.value || '').trim() : '';
                        return value || this.defaultVigenciaPolicy;
                    },

                    async runValidate() {
                        this.previewError = '';
                        this.selected = {};
                        this.hasValidated = true;
                        this.previewLoading = true;
                        this.previewRows = [];
                        this.previewSummary = null;

                        try {
                            const token = this.csrfToken
                                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                                || '';
                            const response = await fetch(this.previewUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': token,
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                body: JSON.stringify({
                                    vigencia_policy: this.currentVigenciaPolicy(),
                                }),
                            });

                            const payload = await response.json().catch(() => ({}));
                            if (! response.ok) {
                                const msg = payload.message
                                    || payload.errors?.vigencia_policy?.[0]
                                    || 'No se pudo validar.';
                                throw new Error(msg);
                            }

                            this.previewRows = Array.isArray(payload.rows) ? payload.rows : [];
                            this.previewSummary = payload.summary || null;
                        } catch (error) {
                            this.previewRows = [];
                            this.previewSummary = null;
                            this.previewError = error?.message || 'Error al validar.';
                        } finally {
                            this.previewLoading = false;
                        }
                    },

                    openGenerateModal() {
                        this.previewError = '';
                        if (this.selectedCount === 0) {
                            this.previewError = 'Seleccione al menos un candidato.';
                            return;
                        }
                        window.dispatchEvent(new CustomEvent('open-modal', {
                            detail: 'export-apo-novedades',
                        }));
                    },

                    submitGenerate(includeNovedades) {
                        window.dispatchEvent(new CustomEvent('close-modal', {
                            detail: 'export-apo-novedades',
                        }));

                        const ids = this.selectedIds();
                        if (ids.length === 0) {
                            this.previewError = 'Seleccione al menos un candidato.';
                            return;
                        }

                        this.generateLoading = true;

                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = this.generateUrl;
                        form.style.display = 'none';

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden';
                        csrf.name = '_token';
                        csrf.value = this.csrfToken
                            || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                            || '';
                        form.appendChild(csrf);

                        ids.forEach((id) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = String(id);
                            form.appendChild(input);
                        });

                        const policy = document.createElement('input');
                        policy.type = 'hidden';
                        policy.name = 'vigencia_policy';
                        policy.value = this.currentVigenciaPolicy();
                        form.appendChild(policy);

                        const novedades = document.createElement('input');
                        novedades.type = 'hidden';
                        novedades.name = 'include_novedades';
                        novedades.value = includeNovedades ? '1' : '0';
                        form.appendChild(novedades);

                        document.body.appendChild(form);
                        form.submit();
                    },

                    clearAll() {
                        this.selected = {};
                        this.hasValidated = false;
                        this.previewError = '';
                        this.previewRows = [];
                        this.previewSummary = null;
                        this.previewLoading = false;
                        this.generateLoading = false;
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
