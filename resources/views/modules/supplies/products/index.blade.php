<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--list req-manage-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-list__toolbar">
                    <div>
                        <h2 class="page-title">Catálogo de suministros</h2>
                        <p class="page-subtitle">Gestiona los productos disponibles para pedidos de insumos.</p>
                    </div>
                    <div class="pur-req-detail__toolbar-actions">
                        <x-export-excel
                            route="{{ route('supplies.products.export', ['module' => $module]) }}"
                            label=""
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Exportar Excel"
                            aria-label="Exportar Excel"
                        />
                    </div>
                </div>
            </div>

            <div class="panel purchase-requests-page__panel">
                <div class="panel__header panel__header--compact">
                    <div class="pur-req-list__header-row">
                        <div>
                            <h3 class="panel-title">Productos</h3>
                            <p class="panel-text panel-text--compact">
                                {{ $products->count() }}
                                {{ $products->count() === 1 ? 'producto' : 'productos' }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            onclick="openCreateModal()"
                            title="Nuevo producto"
                            aria-label="Nuevo producto"
                        >
                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                </div>

                <div class="panel__body req-manage-shell">
                    <div class="data-table-wrap req-manage-shell__table">
                        <table
                            class="supply-table js-datatable"
                            style="width:100%"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            data-dt-body-scroll="true"
                        >
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Producto</th>
                                    <th>Descripción</th>
                                    <th>Estado</th>
                                    <th class="purchase-request-actions-col">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge badge--info">{{ $product->category ?: 'General' }}</span>
                                        </td>
                                        <td class="pur-req-list__folio">{{ $product->name }}</td>
                                        <td>{{ $product->description }}</td>
                                        <td class="text-center">
                                            @if ($product->is_active)
                                                <span class="status-pill status-pill--success">Activo</span>
                                            @else
                                                <span class="status-pill status-pill--danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center purchase-request-actions-col">
                                            <div class="purchase-request-row-actions">
                                                <button
                                                    type="button"
                                                    class="cursos-catalogo-page__icon-btn"
                                                    onclick='openEditModal(@json($product))'
                                                    title="Editar"
                                                    aria-label="Editar"
                                                >
                                                    <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-muted">No hay productos en el catálogo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="product-modal" class="supply-product-modal" hidden>
        <div class="panel supply-product-modal__panel" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="panel__header panel__header--compact">
                <div class="pur-req-list__header-row">
                    <h3 class="panel-title" id="modal-title">Nuevo producto</h3>
                    <button
                        type="button"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        onclick="closeModal()"
                        title="Cerrar"
                        aria-label="Cerrar"
                    >
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
            </div>
            <div class="panel__body">
                <form id="product-form" method="POST" action="{{ route('supplies.products.store', ['module' => $module]) }}">
                    @csrf
                    <div id="method-field"></div>

                    <div class="form-stack">
                        <div class="form-field">
                            <label class="form-label" for="p-name">Nombre del producto</label>
                            <input type="text" name="name" id="p-name" class="form-input" required placeholder="Ej: Resmas de papel Carta">
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="p-category">Categoría</label>
                            <input type="text" name="category" id="p-category" class="form-input" placeholder="Ej: PAPELERIA">
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="p-description">Descripción / presentación</label>
                            <input type="text" name="description" id="p-description" class="form-input" placeholder="Ej: Paquete x 500 hojas">
                        </div>

                        <div class="form-field" id="status-field" hidden>
                            <label class="form-label" for="p-status">Estado</label>
                            <x-searchable-select
                                id="p-status"
                                name="is_active"
                                :options="[
                                    ['value' => '1', 'label' => 'Activo'],
                                    ['value' => '0', 'label' => 'Inactivo'],
                                ]"
                                value="1"
                                placeholder="Seleccione estado…"
                                :allowClear="false"
                            />
                        </div>
                    </div>

                    <div class="pur-req-form-actions" style="margin-top: 1.25rem;">
                        <p class="pur-req-form-actions__note">Los productos activos aparecen en el carrito de Solicitar.</p>
                        <div class="pur-req-form-actions__group">
                            <button type="button" class="btn btn--secondary" onclick="closeModal()">Cancelar</button>
                            <button type="submit" class="btn btn--primary" id="submit-btn">Guardar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .supply-product-modal {
                position: fixed;
                inset: 0;
                z-index: 1000;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(15, 23, 42, 0.45);
                padding: 1rem;
            }

            .supply-product-modal.is-open {
                display: flex;
            }

            .supply-product-modal__panel {
                width: min(500px, 100%);
                margin: 0;
                max-height: min(90vh, 640px);
                overflow: auto;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            const modal = document.getElementById('product-modal');
            const form = document.getElementById('product-form');
            const title = document.getElementById('modal-title');
            const methodField = document.getElementById('method-field');
            const statusField = document.getElementById('status-field');
            const statusSelect = document.getElementById('p-status');
            const baseUrl = @json(route('supplies.products.store', ['module' => $module]));
            const catalogBaseUrl = @json(url('supplies/'.$module.'/catalogo'));

            function setStatusValue(value) {
                if (! statusSelect) {
                    return;
                }

                statusSelect.value = String(value);
                statusSelect.dispatchEvent(new Event('change', { bubbles: true }));
                statusSelect.dispatchEvent(new Event('input', { bubbles: true }));
            }

            function openCreateModal() {
                title.innerText = 'Nuevo producto';
                form.action = baseUrl;
                methodField.innerHTML = '';
                statusField.hidden = true;
                form.reset();
                setStatusValue('1');
                modal.hidden = false;
                modal.classList.add('is-open');
            }

            function openEditModal(product) {
                title.innerText = 'Editar producto';
                form.action = `${catalogBaseUrl}/${product.id}`;
                methodField.innerHTML = '@method("PATCH")';
                statusField.hidden = false;

                document.getElementById('p-name').value = product.name ?? '';
                document.getElementById('p-category').value = product.category ?? '';
                document.getElementById('p-description').value = product.description ?? '';
                setStatusValue(product.is_active ? '1' : '0');

                modal.hidden = false;
                modal.classList.add('is-open');
            }

            function closeModal() {
                modal.classList.remove('is-open');
                modal.hidden = true;
            }

            window.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });

            window.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeModal();
                }
            });
        </script>
    @endpush
</x-app-layout>
