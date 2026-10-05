{{-- Modal Generar cartas (contratación / desvinculación / tipos del catálogo). --}}
@if (($canGenerateContratacionLetters ?? false) || ($canGenerateLetters ?? false))
    <x-modal name="ficha-generate-cartas" maxWidth="2xl" focusable>
        <div
            class="modal-card ficha-empleados-generate-letters-modal"
            x-data="fichaGenerateCartasModal()"
            x-on:ficha-prepare-generate-cartas.window="prepare($event.detail)"
        >
            <div class="ficha-empleados-generate-letters-modal__header">
                <div class="ficha-empleados-generate-letters-modal__heading">
                    <span class="ficha-empleados-generate-letters-modal__heading-icon" aria-hidden="true">
                        <x-lucide-file-text width="18" height="18" />
                    </span>
                    <div class="ficha-empleados-generate-letters-modal__heading-copy">
                        <div class="ficha-empleados-generate-letters-modal__title-row">
                            <h3 class="ficha-empleados-generate-letters-modal__title">Generar cartas</h3>
                            <span
                                class="ficha-empleados-generate-letters-modal__badge"
                                x-show="selectedIds.length > 0"
                                x-cloak
                                x-text="selectedIds.length"
                                title="Plantillas seleccionadas"
                            ></span>
                        </div>
                        <p class="ficha-empleados-generate-letters-modal__lead">
                            Elija el tipo, las plantillas y el firmante. Una plantilla descarga <strong>.docx</strong>; varias, un <strong>.zip</strong>.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-generate-letters-modal__close"
                    title="Cerrar"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'ficha-generate-cartas')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="ficha-empleados-generate-letters-modal__scroll" role="region" aria-label="Formulario de generación">
                {{-- Paso 1: tipo --}}
                <section class="ficha-empleados-generate-letters-modal__section">
                    <div class="ficha-empleados-generate-letters-modal__section-head">
                        <span class="ficha-empleados-generate-letters-modal__step" aria-hidden="true">1</span>
                        <div>
                            <h4 class="ficha-empleados-generate-letters-modal__section-title">Tipo de documento</h4>
                            <p class="ficha-empleados-generate-letters-modal__section-hint">Disponibles según el estado del vínculo.</p>
                        </div>
                    </div>

                    <div class="ficha-empleados-letter-type-grid" role="listbox" aria-label="Tipo de documento">
                        <template x-for="type in types" :key="type.code">
                            <button
                                type="button"
                                class="ficha-empleados-letter-type-card"
                                role="option"
                                :aria-selected="selectedTypeCode === type.code"
                                :aria-disabled="! type.enabled"
                                :disabled="! type.enabled"
                                :class="{
                                    'ficha-empleados-letter-type-card--selected': selectedTypeCode === type.code,
                                    'ficha-empleados-letter-type-card--disabled': ! type.enabled,
                                }"
                                x-on:click="selectType(type)"
                            >
                                <span class="ficha-empleados-letter-type-card__top">
                                    <span class="ficha-empleados-letter-type-card__name" x-text="type.name"></span>
                                    <span class="ficha-empleados-letter-type-card__mark" aria-hidden="true" x-show="selectedTypeCode === type.code" x-cloak>
                                        <x-lucide-check width="14" height="14" />
                                    </span>
                                </span>
                                <span
                                    class="ficha-empleados-letter-type-card__hint"
                                    x-show="! type.enabled && type.disabled_reason"
                                    x-cloak
                                    x-text="type.disabled_reason"
                                ></span>
                            </button>
                        </template>
                    </div>

                    <p class="ficha-empleados-generate-letters-modal__state" x-show="types.length === 0" x-cloak>
                        No hay tipos de documento activos en Plantillas Word.
                    </p>
                </section>

                <template x-if="errorMessage">
                    <div class="alert alert--danger ficha-empleados-generate-letters-modal__alert" x-text="errorMessage"></div>
                </template>

                {{-- Paso 2: plantillas --}}
                <section class="ficha-empleados-generate-letters-modal__section">
                    <div class="ficha-empleados-generate-letters-modal__section-head">
                        <span class="ficha-empleados-generate-letters-modal__step" aria-hidden="true">2</span>
                        <div class="ficha-empleados-generate-letters-modal__section-copy">
                            <h4 class="ficha-empleados-generate-letters-modal__section-title">Plantillas</h4>
                            <p class="ficha-empleados-generate-letters-modal__section-hint" x-show="! loading && templates.length > 0" x-cloak>
                                Seleccione una o más
                            </p>
                        </div>
                    </div>

                    <p class="ficha-empleados-generate-letters-modal__state ficha-empleados-generate-letters-modal__state--loading" x-show="loading" x-cloak>
                        <x-lucide-loader-2 width="16" height="16" class="animate-spin" aria-hidden="true" />
                        <span>Cargando plantillas…</span>
                    </p>

                    <p class="ficha-empleados-generate-letters-modal__state" x-show="! loading && ! selectedTypeCode" x-cloak>
                        Seleccione un tipo de documento disponible.
                    </p>

                    <p class="ficha-empleados-generate-letters-modal__state" x-show="! loading && selectedTypeCode && templates.length === 0 && ! errorMessage" x-cloak>
                        No hay plantillas con archivo para este tipo. Súbalas en Plantillas Word.
                    </p>

                    <div class="ficha-empleados-letter-template-list" x-show="! loading && templates.length > 0" x-cloak>
                        <template x-for="template in templates" :key="template.id">
                            <label
                                class="ficha-empleados-letter-template-card"
                                :class="{ 'ficha-empleados-letter-template-card--selected': isTemplateSelected(template.id) }"
                            >
                                <input
                                    type="checkbox"
                                    class="ficha-empleados-letter-template-card__check"
                                    :value="String(template.id)"
                                    :checked="isTemplateSelected(template.id)"
                                    x-on:change="toggleTemplate(template.id, $event.target.checked)"
                                >
                                <span class="ficha-empleados-letter-template-card__icon" aria-hidden="true">
                                    <x-lucide-file-type width="16" height="16" />
                                </span>
                                <span class="ficha-empleados-letter-template-card__body">
                                    <span class="ficha-empleados-letter-template-card__label" x-text="template.label"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </section>

                {{-- Paso 3: firmante --}}
                <section class="ficha-empleados-generate-letters-modal__section" x-show="! loading && selectedTypeCode" x-cloak>
                    <div class="ficha-empleados-generate-letters-modal__section-head">
                        <span class="ficha-empleados-generate-letters-modal__step" aria-hidden="true">3</span>
                        <div>
                            <h4 class="ficha-empleados-generate-letters-modal__section-title">Firmante</h4>
                            <p class="ficha-empleados-generate-letters-modal__section-hint">Quién firma el documento generado.</p>
                        </div>
                    </div>

                    <p class="ficha-empleados-generate-letters-modal__state" x-show="firmas.length === 0" x-cloak>
                        No hay firmantes activos en el catálogo. Configure firmas en Catálogos de ficha.
                    </p>

                    <div class="ficha-empleados-letter-template-list" x-show="firmas.length > 0" x-cloak role="radiogroup" aria-label="Firmante">
                        <template x-for="firma in firmas" :key="firma.id">
                            <label
                                class="ficha-empleados-letter-template-card"
                                :class="{ 'ficha-empleados-letter-template-card--selected': String(selectedSignatoryId) === String(firma.id) }"
                            >
                                <input
                                    type="radio"
                                    class="ficha-empleados-letter-template-card__check"
                                    name="ficha-cartas-signatory"
                                    :value="String(firma.id)"
                                    x-model="selectedSignatoryId"
                                >
                                <span class="ficha-empleados-letter-template-card__icon" aria-hidden="true">
                                    <x-lucide-pen-line width="16" height="16" />
                                </span>
                                <span class="ficha-empleados-letter-template-card__body">
                                    <span class="ficha-empleados-letter-template-card__label" x-text="firma.name"></span>
                                    <span class="ficha-empleados-letter-template-card__meta" x-text="firma.code"></span>
                                </span>
                            </label>
                        </template>
                    </div>
                </section>
            </div>

            <div class="ficha-empleados-generate-letters-modal__actions" role="toolbar" aria-label="Acciones de generación">
                <p class="ficha-empleados-generate-letters-modal__actions-hint">
                    <template x-if="selectedIds.length > 0">
                        <span>
                            <span x-text="selectedIds.length"></span>
                            <span x-text="selectedIds.length === 1 ? 'plantilla seleccionada' : 'plantillas seleccionadas'"></span>
                        </span>
                    </template>
                    <template x-if="selectedIds.length === 0">
                        <span class="ficha-empleados-generate-letters-modal__actions-hint--muted">Sin plantillas seleccionadas</span>
                    </template>
                </p>

                <div class="ficha-empleados-generate-letters-modal__actions-btns">
                    <button
                        type="button"
                        class="btn btn--secondary btn--sm"
                        x-on:click="$dispatch('close-modal', 'ficha-generate-cartas')"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        class="btn btn--primary btn--sm ficha-empleados-generate-letters-modal__submit"
                        x-bind:disabled="! canSubmit"
                        x-on:click="submitGenerate()"
                    >
                        <span x-show="! submitting" class="ficha-empleados-generate-letters-modal__submit-inner">
                            <x-lucide-download width="16" height="16" aria-hidden="true" />
                            <span>Generar y descargar</span>
                        </span>
                        <span x-show="submitting" class="ficha-empleados-generate-letters-modal__submit-inner" x-cloak>
                            <x-lucide-loader-2 width="16" height="16" class="animate-spin" aria-hidden="true" />
                            <span>Generando…</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </x-modal>

    @once
        @push('scripts')
            <script>
                function fichaGenerateCartasModal() {
                    return {
                        types: [],
                        selectedTypeCode: '',
                        templatesUrl: '',
                        generateUrl: '',
                        firmasUrl: '',
                        templates: [],
                        firmas: [],
                        selectedIds: [],
                        selectedSignatoryId: '',
                        loading: false,
                        submitting: false,
                        errorMessage: '',
                        loadToken: 0,

                        get canSubmit() {
                            return ! this.loading
                                && ! this.submitting
                                && this.selectedIds.length > 0
                                && !! this.selectedSignatoryId
                                && !! this.generateUrl;
                        },

                        prepare(detail) {
                            this.types = Array.isArray(detail?.types) ? detail.types : [];
                            this.selectedTypeCode = '';
                            this.templatesUrl = '';
                            this.generateUrl = '';
                            this.firmasUrl = '';
                            this.templates = [];
                            this.firmas = [];
                            this.selectedIds = [];
                            this.selectedSignatoryId = '';
                            this.errorMessage = '';
                            this.loading = false;
                            this.submitting = false;
                            this.loadToken += 1;

                            const preferred = detail?.preferredTypeCode || '';
                            const preferredType = this.types.find((type) => type.enabled && type.code === preferred);
                            const firstEnabled = preferredType || this.types.find((type) => type.enabled);
                            if (firstEnabled) {
                                this.selectType(firstEnabled);
                            }
                        },

                        selectType(type) {
                            if (! type || ! type.enabled) {
                                return;
                            }

                            this.selectedTypeCode = type.code;
                            this.templatesUrl = type.templates_url || '';
                            this.generateUrl = type.generate_url || '';
                            this.firmasUrl = type.firmas_url || '';
                            this.selectedIds = [];
                            this.selectedSignatoryId = '';
                            this.errorMessage = '';
                            this.loadTemplatesAndFirmas();
                        },

                        isTemplateSelected(id) {
                            return this.selectedIds.includes(String(id));
                        },

                        toggleTemplate(id, checked) {
                            const value = String(id);
                            if (checked) {
                                if (! this.selectedIds.includes(value)) {
                                    this.selectedIds = [...this.selectedIds, value];
                                }
                                return;
                            }

                            this.selectedIds = this.selectedIds.filter((item) => item !== value);
                        },

                        async loadTemplatesAndFirmas() {
                            const token = ++this.loadToken;

                            if (! this.templatesUrl) {
                                this.templates = [];
                                this.firmas = [];
                                return;
                            }

                            this.loading = true;
                            this.errorMessage = '';
                            this.templates = [];
                            this.firmas = [];
                            this.selectedIds = [];

                            try {
                                const [templatesResponse, firmasResponse] = await Promise.all([
                                    fetch(this.templatesUrl, {
                                        headers: {
                                            'Accept': 'application/json',
                                            'X-Requested-With': 'XMLHttpRequest',
                                        },
                                        credentials: 'same-origin',
                                    }),
                                    this.firmasUrl
                                        ? fetch(this.firmasUrl, {
                                            headers: {
                                                'Accept': 'application/json',
                                                'X-Requested-With': 'XMLHttpRequest',
                                            },
                                            credentials: 'same-origin',
                                        })
                                        : Promise.resolve(null),
                                ]);

                                if (token !== this.loadToken) {
                                    return;
                                }

                                if (! templatesResponse.ok) {
                                    throw new Error('No se pudieron cargar las plantillas.');
                                }

                                const templatesPayload = await templatesResponse.json();
                                this.templates = Array.isArray(templatesPayload.templates) ? templatesPayload.templates : [];

                                if (firmasResponse && firmasResponse.ok) {
                                    const firmasPayload = await firmasResponse.json();
                                    this.firmas = Array.isArray(firmasPayload.firmas) ? firmasPayload.firmas : [];
                                } else {
                                    this.firmas = [];
                                }
                            } catch (error) {
                                if (token !== this.loadToken) {
                                    return;
                                }
                                this.errorMessage = error?.message || 'No se pudieron cargar las plantillas.';
                                this.templates = [];
                                this.firmas = [];
                            } finally {
                                if (token === this.loadToken) {
                                    this.loading = false;
                                }
                            }
                        },

                        async submitGenerate() {
                            if (! this.canSubmit) {
                                return;
                            }

                            this.submitting = true;
                            this.errorMessage = '';

                            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                            const body = new FormData();
                            this.selectedIds.forEach((id) => body.append('template_ids[]', String(id)));
                            body.append('signatory_id', String(this.selectedSignatoryId));

                            try {
                                const response = await fetch(this.generateUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/octet-stream',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': csrf,
                                    },
                                    credentials: 'same-origin',
                                    body,
                                });

                                if (response.status === 422) {
                                    const payload = await response.json();
                                    const messages = payload.errors
                                        ? Object.values(payload.errors).flat()
                                        : [payload.message || 'Datos inválidos.'];
                                    this.errorMessage = messages.join(' ');
                                    return;
                                }

                                if (! response.ok) {
                                    throw new Error('No se pudieron generar las cartas.');
                                }

                                const blob = await response.blob();
                                const disposition = response.headers.get('Content-Disposition') || '';
                                const match = disposition.match(/filename\*?=(?:UTF-8'')?["']?([^"';]+)/i);
                                const fileName = match ? decodeURIComponent(match[1]) : 'cartas.docx';

                                const objectUrl = URL.createObjectURL(blob);
                                const link = document.createElement('a');
                                link.href = objectUrl;
                                link.download = fileName;
                                document.body.appendChild(link);
                                link.click();
                                link.remove();
                                URL.revokeObjectURL(objectUrl);

                                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'ficha-generate-cartas' }));
                                window.location.reload();
                            } catch (error) {
                                this.errorMessage = error?.message || 'No se pudieron generar las cartas.';
                            } finally {
                                this.submitting = false;
                            }
                        },
                    };
                }
            </script>
        @endpush
    @endonce
@endif
