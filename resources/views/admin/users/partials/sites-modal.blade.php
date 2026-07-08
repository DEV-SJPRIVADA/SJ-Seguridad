<div id="sites-modal" class="sites-modal" style="display: none;">
    <div class="sites-modal__backdrop" data-sites-modal-close></div>
    <div class="panel sites-modal__panel">
        <div class="panel__header" style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
            <div>
                <h3 class="panel-title">Gestionar sedes físicas</h3>
                <p class="panel-text">Crea, edita o elimina sedes para asignarlas a los usuarios y al reporte FO-AD-44.</p>
            </div>
            <button type="button" class="btn btn--secondary btn--sm" data-sites-modal-close>Cerrar</button>
        </div>

        <div class="panel__body">
            <p id="sites-modal-feedback" class="text-small" style="display: none; margin-bottom: 1rem;"></p>

            <div class="sites-modal__layout">
                <section class="sites-modal__list">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                        <h4 class="text-small font-bold">Sedes registradas</h4>
                        <button type="button" class="btn btn--secondary btn--sm" id="sites-new-btn">+ Nueva sede</button>
                    </div>

                    <div class="sites-modal__table-wrap">
                        <table class="supply-table">
                            <thead>
                                <tr>
                                    <th>Utilización</th>
                                    <th>Ciudad</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="sites-table-body">
                                <tr>
                                    <td colspan="4" class="text-muted">Cargando sedes...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="sites-modal__form">
                    <h4 class="text-small font-bold" id="sites-form-title">Nueva sede</h4>
                    <form id="sites-form" class="form-stack" style="margin-top: 0.75rem;">
                        <input type="hidden" id="sites-form-id" value="">

                        <div class="form-field">
                            <label class="form-label" for="sites-utilization">Utilización</label>
                            <input type="text" id="sites-utilization" class="form-input" required placeholder="Ej: Sede Principal y central de monitoreo">
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="sites-city">Ciudad / Ubicación</label>
                            <input type="text" id="sites-city" class="form-input" required placeholder="Ej: Cali">
                        </div>

                        <div class="form-field" id="sites-active-field" style="display: none;">
                            <label class="form-label" for="sites-active">Estado</label>
                            <select id="sites-active" class="form-select">
                                <option value="1">Activa</option>
                                <option value="0">Inactiva</option>
                            </select>
                        </div>

                        <p id="sites-form-errors" class="text-small" style="color: var(--color-danger); display: none;"></p>

                        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                            <button type="submit" class="btn btn--primary" id="sites-save-btn">Guardar sede</button>
                            <button type="button" class="btn btn--secondary" id="sites-cancel-edit-btn" style="display: none;">Cancelar edición</button>
                            <button type="button" class="btn btn--danger btn--sm" id="sites-delete-btn" style="display: none; margin-left: auto;">Eliminar</button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
    .sites-modal {
        position: fixed;
        inset: 0;
        z-index: 1100;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }

    .sites-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.55);
    }

    .sites-modal__panel {
        position: relative;
        width: min(960px, 100%);
        max-height: 90vh;
        overflow: auto;
        z-index: 1;
    }

    .sites-modal__layout {
        display: grid;
        grid-template-columns: 1.2fr 0.8fr;
        gap: 1.5rem;
    }

    .sites-modal__table-wrap {
        max-height: 360px;
        overflow: auto;
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
    }

    .sites-modal__form {
        border: 1px solid var(--color-border);
        border-radius: var(--radius-lg);
        padding: 1rem;
        background: #f8fafc;
    }

    @media (max-width: 900px) {
        .sites-modal__layout {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    $initialSites = collect($allSites ?? [])->map(function ($site) {
        return [
            'id' => $site->id,
            'name' => $site->name,
            'utilization' => $site->utilization,
            'city' => $site->city,
            'is_active' => (bool) $site->is_active,
            'users_count' => $site->users_count ?? 0,
            'requests_count' => $site->supply_requests_count ?? 0,
        ];
    })->values();
@endphp

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('sites-modal');
        const openBtn = document.getElementById('open-sites-modal');
        const sedeSelect = document.getElementById('user-sede-id');
        const tableBody = document.getElementById('sites-table-body');
        const form = document.getElementById('sites-form');
        const formId = document.getElementById('sites-form-id');
        const formTitle = document.getElementById('sites-form-title');
        const utilizationInput = document.getElementById('sites-utilization');
        const cityInput = document.getElementById('sites-city');
        const activeField = document.getElementById('sites-active-field');
        const activeSelect = document.getElementById('sites-active');
        const formErrors = document.getElementById('sites-form-errors');
        const feedback = document.getElementById('sites-modal-feedback');
        const cancelEditBtn = document.getElementById('sites-cancel-edit-btn');
        const deleteBtn = document.getElementById('sites-delete-btn');
        const newBtn = document.getElementById('sites-new-btn');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        const routes = {
            index: @json(route('admin.supply-sites.index')),
            store: @json(route('admin.supply-sites.store')),
            updateBase: @json(url('/admin/supply-sites')),
        };

        let sites = @json($initialSites);

        function showFeedback(message, isError = false) {
            feedback.textContent = message;
            feedback.style.display = 'block';
            feedback.style.color = isError ? 'var(--color-danger)' : 'var(--color-success, #15803d)';
        }

        function hideFeedback() {
            feedback.style.display = 'none';
        }

        function showFormErrors(message) {
            formErrors.textContent = message;
            formErrors.style.display = message ? 'block' : 'none';
        }

        function renderSitesTable() {
            if (!sites.length) {
                tableBody.innerHTML = '<tr><td colspan="4" class="text-muted">No hay sedes registradas. Cree la primera sede.</td></tr>';
                return;
            }

            tableBody.innerHTML = sites.map(site => `
                <tr>
                    <td>${escapeHtml(site.utilization)}</td>
                    <td>${escapeHtml(site.city)}</td>
                    <td>
                        <span class="status-pill ${site.is_active ? 'status-pill--success' : 'status-pill--danger'}">
                            ${site.is_active ? 'Activa' : 'Inactiva'}
                        </span>
                    </td>
                    <td>
                        <button type="button" class="btn btn--secondary btn--sm" data-edit-site="${site.id}">Editar</button>
                    </td>
                </tr>
            `).join('');

            tableBody.querySelectorAll('[data-edit-site]').forEach(button => {
                button.addEventListener('click', () => {
                    const site = sites.find(item => String(item.id) === button.getAttribute('data-edit-site'));
                    if (site) {
                        openEditForm(site);
                    }
                });
            });
        }

        function refreshSedeSelect(selectedId = null) {
            if (!sedeSelect) {
                return;
            }

            const current = selectedId ?? sedeSelect.value;
            const activeSites = sites.filter(site => site.is_active);

            sedeSelect.innerHTML = '<option value="">Sin sede asignada</option>' + activeSites.map(site => `
                <option value="${site.id}">${escapeHtml(site.utilization)} (${escapeHtml(site.city)})</option>
            `).join('');

            if (current && activeSites.some(site => String(site.id) === String(current))) {
                sedeSelect.value = current;
            }
        }

        function resetForm() {
            formId.value = '';
            form.reset();
            formTitle.textContent = 'Nueva sede';
            activeField.style.display = 'none';
            cancelEditBtn.style.display = 'none';
            deleteBtn.style.display = 'none';
            showFormErrors('');
        }

        function openCreateForm() {
            resetForm();
            activeSelect.value = '1';
        }

        function openEditForm(site) {
            formId.value = site.id;
            utilizationInput.value = site.utilization;
            cityInput.value = site.city;
            activeSelect.value = site.is_active ? '1' : '0';
            formTitle.textContent = 'Editar sede';
            activeField.style.display = 'block';
            cancelEditBtn.style.display = 'inline-flex';
            deleteBtn.style.display = 'inline-flex';
            showFormErrors('');
        }

        function escapeHtml(value) {
            return String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;');
        }

        async function loadSites() {
            const response = await fetch(routes.index, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                showFeedback('No se pudieron cargar las sedes.', true);
                return;
            }

            const data = await response.json();
            sites = data.sites ?? [];
            renderSitesTable();
            refreshSedeSelect();
        }

        async function saveSite(event) {
            event.preventDefault();
            hideFeedback();
            showFormErrors('');

            const payload = {
                utilization: utilizationInput.value.trim(),
                city: cityInput.value.trim(),
                is_active: activeField.style.display === 'none' ? true : activeSelect.value === '1',
            };

            const editingId = formId.value;
            const url = editingId ? `${routes.updateBase}/${editingId}` : routes.store;
            const method = editingId ? 'PATCH' : 'POST';

            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (!response.ok) {
                const errors = data.errors
                    ? Object.values(data.errors).flat().join(' ')
                    : (data.message ?? 'No se pudo guardar la sede.');
                showFormErrors(errors);
                return;
            }

            if (editingId) {
                sites = sites.map(site => site.id === data.site.id ? data.site : site);
            } else {
                sites.push(data.site);
            }

            renderSitesTable();
            refreshSedeSelect(data.site.is_active ? String(data.site.id) : null);
            showFeedback(data.message ?? 'Sede guardada.');
            openCreateForm();
        }

        async function deleteSite() {
            const editingId = formId.value;
            if (!editingId) {
                return;
            }

            if (!confirm('¿Eliminar esta sede? Esta acción no se puede deshacer.')) {
                return;
            }

            hideFeedback();
            showFormErrors('');

            const response = await fetch(`${routes.updateBase}/${editingId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
            });

            const data = await response.json();

            if (!response.ok) {
                showFormErrors(data.message ?? 'No se pudo eliminar la sede.');
                return;
            }

            sites = sites.filter(site => String(site.id) !== String(editingId));
            renderSitesTable();
            refreshSedeSelect();
            showFeedback(data.message ?? 'Sede eliminada.');
            openCreateForm();
        }

        function openModal() {
            modal.style.display = 'flex';
            hideFeedback();
            openCreateForm();
            renderSitesTable();
            refreshSedeSelect();
            loadSites();
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        openBtn?.addEventListener('click', openModal);
        newBtn?.addEventListener('click', openCreateForm);
        cancelEditBtn?.addEventListener('click', openCreateForm);
        deleteBtn?.addEventListener('click', deleteSite);
        form?.addEventListener('submit', saveSite);

        modal?.querySelectorAll('[data-sites-modal-close]').forEach(element => {
            element.addEventListener('click', closeModal);
        });

        renderSitesTable();
        refreshSedeSelect();
    });
</script>
