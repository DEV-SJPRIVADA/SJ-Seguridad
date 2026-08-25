{{-- Modal seleccionar plantillas Word para generar cartas (FEAT-029). --}}
@if ($canGenerateLetters ?? false)
    <x-modal name="ficha-generate-letters" maxWidth="lg" focusable>
        <div
            class="modal-card ficha-empleados-generate-letters-modal"
            x-data="fichaGenerateLettersModal()"
            x-on:ficha-prepare-generate-letters.window="prepare($event.detail)"
            x-on:open-modal.window="if ($event.detail === 'ficha-generate-letters') { loadTemplates(); loadFirmas(); }"
        >
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Generar cartas</h3>
                        <p class="ficha-empleados-masivos-modal__lead">Seleccione una o mas plantillas de desvinculacion. Una genera .docx; varias generan .zip.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'ficha-generate-letters')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="panel__body form-stack">
                <template x-if="loading">
                    <p class="text-muted">Cargando plantillas…</p>
                </template>

                <template x-if="! loading && errorMessage">
                    <div class="alert alert--danger" x-text="errorMessage"></div>
                </template>

                <template x-if="! loading && ! errorMessage && templates.length === 0">
                    <p class="text-muted">No hay plantillas de desvinculacion con archivo. Subalas en el tablero Plantillas Word.</p>
                </template>

                <template x-if="! loading && templates.length > 0">
                    <div class="form-stack">
                        <p class="form-hint">Seleccione al menos una plantilla.</p>
                        <template x-for="template in templates" :key="template.id">
                            <div class="form-field">
                                <label>
                                    <input
                                        type="checkbox"
                                        class="form-check"
                                        :value="template.id"
                                        x-model="selectedIds"
                                    >
                                    <span x-text="template.label"></span>
                                </label>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="! loading && firmas.length > 0">
                    <div class="form-field">
                        <label class="form-label" for="ficha-signatory">Firmante</label>
                        <select
                            id="ficha-signatory"
                            class="form-select"
                            x-model="selectedSignatoryId"
                            required
                        >
                            <option value="">— Seleccione firmante —</option>
                            <template x-for="firma in firmas" :key="firma.id">
                                <option :value="firma.id" x-text="firma.name + ' — ' + firma.code"></option>
                            </template>
                        </select>
                    </div>
                </template>
            </div>

            <div class="ficha-empleados-terminate-modal__actions">
                <button
                    type="button"
                    class="btn btn--secondary"
                    x-on:click="$dispatch('close-modal', 'ficha-generate-letters')"
                >Cancelar</button>
                <button
                    type="button"
                    class="btn btn--primary"
                    x-bind:disabled="loading || submitting || selectedIds.length < 1 || ! selectedSignatoryId"
                    x-on:click="submitGenerate()"
                >
                    <span x-show="! submitting">Generar y descargar</span>
                    <span x-show="submitting" x-cloak>Generando…</span>
                </button>
            </div>
        </div>
    </x-modal>

    @once
        @push('scripts')
            <script>
                function fichaGenerateLettersModal() {
                    return {
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

                        prepare(detail) {
                            this.templatesUrl = detail?.templatesUrl || '';
                            this.generateUrl = detail?.generateUrl || '';
                            this.firmasUrl = detail?.firmasUrl || '';
                            this.templates = [];
                            this.firmas = [];
                            this.selectedIds = [];
                            this.selectedSignatoryId = '';
                            this.errorMessage = '';
                            this.loading = false;
                            this.submitting = false;
                        },

                        async loadTemplates() {
                            if (! this.templatesUrl) {
                                this.errorMessage = 'No se pudo determinar el periodo.';
                                return;
                            }

                            this.loading = true;
                            this.errorMessage = '';
                            this.templates = [];
                            this.selectedIds = [];

                            try {
                                const response = await fetch(this.templatesUrl, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    throw new Error('No se pudieron cargar las plantillas.');
                                }

                                const payload = await response.json();
                                this.templates = Array.isArray(payload.templates) ? payload.templates : [];
                            } catch (error) {
                                this.errorMessage = error?.message || 'No se pudieron cargar las plantillas.';
                            } finally {
                                this.loading = false;
                            }
                        },

                        async loadFirmas() {
                            if (! this.firmasUrl) {
                                return;
                            }

                            try {
                                const response = await fetch(this.firmasUrl, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    credentials: 'same-origin',
                                });

                                if (! response.ok) {
                                    return;
                                }

                                const payload = await response.json();
                                this.firmas = Array.isArray(payload.firmas) ? payload.firmas : [];
                            } catch {
                                this.firmas = [];
                            }
                        },

                        async submitGenerate() {
                            if (this.selectedIds.length < 1 || ! this.selectedSignatoryId || ! this.generateUrl || this.submitting) {
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
                                        : [payload.message || 'Datos invalidos.'];
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

                                window.dispatchEvent(new CustomEvent('close-modal', { detail: 'ficha-generate-letters' }));
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
