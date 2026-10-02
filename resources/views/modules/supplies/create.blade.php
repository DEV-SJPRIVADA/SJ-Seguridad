<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $areaLabel = config("access.areas.{$module}", $module);
        $backUrl = route('supplies.index', ['module' => $module]);
    @endphp

    <div class="page-section purchase-requests-page purchase-requests-page--form">
        <div class="app-container">
            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver a mis solicitudes"
                        aria-label="Volver a mis solicitudes"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>
                <h2 class="page-title">Nueva solicitud de insumos</h2>
                <p class="page-subtitle">
                    Selecciona productos del catalogo o agrega items no listados. La solicitud pasa a aprobacion de Calidad.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert--danger" role="alert">
                    <p class="font-semibold" style="margin-bottom: 0.5rem;">No se pudo enviar la solicitud. Revisa lo siguiente:</p>
                    <ul class="ficha-empleados-form__error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @elseif (! ($canRequestSupplies ?? false))
                <div class="alert alert--warning" role="alert">
                    Debes tener una sede asignada y activa para enviar solicitudes de insumos. Contacta al administrador.
                </div>
            @endif

            <div class="pur-req-form-layout">
                <div class="pur-req-form-layout__main">
                    <form
                        action="{{ route('supplies.store', ['module' => $module]) }}"
                        method="POST"
                        id="supply-request-form"
                        class="form-stack"
                    >
                        @csrf

                        <div class="pur-req-form__meta">
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Formato</span>
                                <span class="pur-req-form__meta-value">FO-AD-44</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Area</span>
                                <span class="pur-req-form__meta-value">{{ $areaLabel }}</span>
                            </div>
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Estado</span>
                                <span class="pur-req-form__meta-value">Nueva solicitud</span>
                            </div>
                        </div>

                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step">1</span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Catalogo y pedido</h3>
                                    <p class="pur-req-form__section-desc">Agrega productos del catalogo o registra uno no listado.</p>
                                </div>
                            </header>

                            <div class="supply-cart-layout">
                                <aside class="supply-cart-layout__catalog">
                                    <div class="supply-cart-layout__search">
                                        <input type="search" id="catalog-search" class="form-input" placeholder="Buscar en el catalogo…">
                                    </div>

                                    <div class="supply-catalog-list" id="catalog-list">
                                        @foreach ($products as $category => $catProducts)
                                            <div class="supply-catalog-group" data-category="{{ $category ?: 'General' }}">
                                                <h4 class="supply-catalog-group__title">{{ $category ?: 'General' }}</h4>
                                                @foreach ($catProducts as $product)
                                                    <button
                                                        type="button"
                                                        class="supply-catalog-item"
                                                        data-product-id="{{ $product->id }}"
                                                        data-product-name="{{ $product->name }}"
                                                        data-product-description="{{ $product->description }}"
                                                        data-search="{{ strtolower($product->name.' '.$product->description.' '.($category ?: '')) }}"
                                                    >
                                                        <span class="supply-catalog-item__name">{{ $product->name }}</span>
                                                        @if ($product->description)
                                                            <span class="supply-catalog-item__desc">{{ $product->description }}</span>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                </aside>

                                <section class="supply-cart-layout__cart">
                                    <div class="supply-cart-layout__cart-header">
                                        <h4 class="form-label" style="margin: 0;">Mi pedido</h4>
                                        <button
                                            type="button"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                            id="add-custom-item-btn"
                                            title="Producto no listado"
                                            aria-label="Producto no listado"
                                        >
                                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>

                                    <div id="cart-empty" class="supply-cart-empty">
                                        Agrega productos desde el catalogo o registra uno no listado.
                                    </div>

                                    <div id="cart-items" class="supply-cart-items"></div>
                                </section>
                            </div>
                        </section>

                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step">2</span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Observaciones</h3>
                                    <p class="pur-req-form__section-desc">Opcional. Motivo o contexto del pedido para Calidad.</p>
                                </div>
                            </header>

                            <div class="form-field">
                                <label class="form-label" for="observations">Observaciones generales</label>
                                <textarea
                                    name="observations"
                                    id="observations"
                                    class="form-textarea"
                                    rows="3"
                                    placeholder="Explica brevemente el motivo del pedido si es necesario…"
                                >{{ old('observations') }}</textarea>
                            </div>
                        </section>

                        <div class="pur-req-form-actions">
                            <p class="pur-req-form-actions__note">
                                Revisa cantidades e inventario antes de enviar. La solicitud ira a aprobacion de Calidad.
                            </p>
                            <div class="pur-req-form-actions__group">
                                <a
                                    href="{{ $backUrl }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cancelar"
                                    aria-label="Cancelar"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    id="submit-request-btn"
                                    title="Enviar solicitud"
                                    aria-label="Enviar solicitud"
                                    disabled
                                >
                                    <x-lucide-send width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de enviar</h3>
                            <p class="panel-text">Reduce devoluciones con estos puntos.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Usa el catalogo cuando el producto exista.</li>
                                <li class="pur-req-form-guide__item">Reporta inventario actual en cada linea de catalogo.</li>
                                <li class="pur-req-form-guide__item">Producto no listado solo si no esta en catalogo.</li>
                                <li class="pur-req-form-guide__item">Debes tener sede asignada y activa.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Que pasa despues</h3>
                        </div>
                        <div class="panel__body">
                            <ol class="pur-req-form-flow">
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">1</span>
                                    <span>Calidad revisa y autoriza cantidades.</span>
                                </li>
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">2</span>
                                    <span>Si aprueba, entra a la bandeja de Compras.</span>
                                </li>
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">3</span>
                                    <span>Sigue el avance desde Mis solicitudes.</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('supply-request-form');
            const cartItems = document.getElementById('cart-items');
            const cartEmpty = document.getElementById('cart-empty');
            const submitBtn = document.getElementById('submit-request-btn');
            const searchInput = document.getElementById('catalog-search');
            const catalogItems = Array.from(document.querySelectorAll('.supply-catalog-item'));
            const productNames = @json($productNames ?? []);
            const oldItems = @json(array_values(old('items', [])));
            let itemIndex = 0;

            function escapeHtml(value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function updateCartState() {
                const hasItems = cartItems.children.length > 0;
                cartEmpty.style.display = hasItems ? 'none' : 'block';
                submitBtn.disabled = !hasItems;
            }

            function bindRemove(wrapper) {
                wrapper.querySelector('.supply-cart-row__remove').addEventListener('click', function () {
                    wrapper.remove();
                    updateCartState();
                });
            }

            function removeButtonHtml() {
                return `
                    <button
                        type="button"
                        class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger supply-cart-row__remove"
                        title="Quitar"
                        aria-label="Quitar"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/>
                        </svg>
                    </button>
                `;
            }

            function createCatalogRow(productId, productName, inventory, quantity) {
                const wrapper = document.createElement('div');
                wrapper.className = 'supply-cart-row';
                wrapper.dataset.itemType = 'catalog';
                wrapper.dataset.productId = productId;
                wrapper.innerHTML = `
                    <div class="supply-cart-row__info">
                        <strong>${escapeHtml(productName)}</strong>
                    </div>
                    <div class="supply-cart-row__fields">
                        <input type="hidden" name="items[${itemIndex}][type]" value="catalog">
                        <input type="hidden" name="items[${itemIndex}][product_id]" value="${escapeHtml(productId)}">
                        <label class="supply-cart-field">
                            <span>Inventario</span>
                            <input type="number" name="items[${itemIndex}][current_inventory]" class="supply-input" min="0" value="${escapeHtml(inventory)}" required>
                        </label>
                        <label class="supply-cart-field">
                            <span>Cantidad</span>
                            <input type="number" name="items[${itemIndex}][quantity]" class="supply-input" min="1" value="${escapeHtml(quantity)}" required>
                        </label>
                        ${removeButtonHtml()}
                    </div>
                `;
                cartItems.appendChild(wrapper);
                itemIndex++;
                bindRemove(wrapper);
                updateCartState();
            }

            function addCatalogItem(productId, productName) {
                const existing = cartItems.querySelector(`[data-product-id="${productId}"][data-item-type="catalog"]`);
                if (existing) {
                    const qtyInput = existing.querySelector('input[name$="[quantity]"]');
                    qtyInput.value = parseInt(qtyInput.value || '0', 10) + 1;
                    return;
                }

                createCatalogRow(productId, productName, 0, 1);
            }

            function addCustomItem(customName, quantity) {
                const wrapper = document.createElement('div');
                wrapper.className = 'supply-cart-row supply-cart-row--custom';
                wrapper.dataset.itemType = 'custom';
                wrapper.innerHTML = `
                    <div class="supply-cart-row__info">
                        <strong>Producto no listado</strong>
                        <span class="status-pill status-pill--warning">Fuera de catalogo</span>
                    </div>
                    <div class="supply-cart-row__fields">
                        <input type="hidden" name="items[${itemIndex}][type]" value="custom">
                        <label class="supply-cart-field supply-cart-field--wide">
                            <span>Nombre del producto</span>
                            <input type="text" name="items[${itemIndex}][custom_name]" class="supply-input" placeholder="Describe el producto" value="${escapeHtml(customName || '')}" required>
                        </label>
                        <label class="supply-cart-field">
                            <span>Cantidad</span>
                            <input type="number" name="items[${itemIndex}][quantity]" class="supply-input" min="1" value="${escapeHtml(quantity || 1)}" required>
                        </label>
                        ${removeButtonHtml()}
                    </div>
                `;
                cartItems.appendChild(wrapper);
                itemIndex++;
                bindRemove(wrapper);
                updateCartState();
            }

            function restoreOldItems() {
                oldItems.forEach(function (item) {
                    if ((item.type || '') === 'custom') {
                        addCustomItem(item.custom_name || '', item.quantity || 1);
                        return;
                    }

                    const productId = String(item.product_id || '');
                    const productName = productNames[productId] || productNames[Number(productId)] || ('Producto #' + productId);
                    createCatalogRow(productId, productName, item.current_inventory ?? 0, item.quantity || 1);
                });
            }

            catalogItems.forEach(function (button) {
                button.addEventListener('click', function () {
                    addCatalogItem(button.dataset.productId, button.dataset.productName);
                });
            });

            document.getElementById('add-custom-item-btn').addEventListener('click', function () {
                addCustomItem('', 1);
            });

            searchInput.addEventListener('input', function () {
                const query = searchInput.value.trim().toLowerCase();
                catalogItems.forEach(function (button) {
                    const matches = query === '' || (button.dataset.search || '').includes(query);
                    button.style.display = matches ? '' : 'none';
                });
            });

            form.addEventListener('submit', function (event) {
                if (cartItems.children.length === 0) {
                    event.preventDefault();
                }
            });

            restoreOldItems();
            updateCartState();
        });
    </script>
    @endpush
</x-app-layout>
