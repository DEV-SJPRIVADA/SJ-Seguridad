<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Nueva Solicitud de Insumos</h3>
                    <p class="panel-text">Selecciona productos del catalogo o agrega items no listados. La solicitud pasara a aprobacion de insumos.</p>
                </div>

                <div class="panel__body">
                    @if ($errors->any())
                        <div class="alert alert--danger" role="alert" style="margin-bottom: 1rem;">
                            <p class="font-semibold" style="margin-bottom: 0.5rem;">No se pudo enviar la solicitud. Revisa lo siguiente:</p>
                            <ul class="ficha-empleados-form__error-list">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @elseif (! ($canRequestSupplies ?? false))
                        <div class="alert alert--warning" role="alert" style="margin-bottom: 1rem;">
                            Debes tener una sede asignada y activa para enviar solicitudes de insumos. Contacta al administrador.
                        </div>
                    @endif

                    <form action="{{ route('supplies.store', ['module' => $module]) }}" method="POST" id="supply-request-form">
                        @csrf

                        <div class="supply-cart-layout">
                            <aside class="supply-cart-layout__catalog">
                                <div class="supply-cart-layout__search">
                                    <input type="search" id="catalog-search" class="form-input" placeholder="Buscar en el catalogo...">
                                </div>

                                <div class="supply-catalog-list" id="catalog-list">
                                    @foreach ($products as $category => $catProducts)
                                        <div class="supply-catalog-group" data-category="{{ $category ?: 'General' }}">
                                            <h4 class="supply-catalog-group__title">{{ $category ?: 'General' }}</h4>
                                            @foreach ($catProducts as $product)
                                                <button type="button"
                                                    class="supply-catalog-item"
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->name }}"
                                                    data-product-description="{{ $product->description }}"
                                                    data-search="{{ strtolower($product->name.' '.$product->description.' '.($category ?: '')) }}">
                                                    <span class="supply-catalog-item__name">{{ $product->name }}</span>
                                                    @if($product->description)
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
                                    <h4 class="form-label">Mi pedido</h4>
                                    <button type="button" class="btn btn--secondary btn--sm" id="add-custom-item-btn">
                                        + Producto no listado
                                    </button>
                                </div>

                                <div id="cart-empty" class="supply-cart-empty">
                                    Agrega productos desde el catalogo o registra uno no listado.
                                </div>

                                <div id="cart-items" class="supply-cart-items"></div>

                                <div class="form-group block-spaced" style="margin-top: 1.5rem;">
                                    <label class="form-label">Observaciones generales</label>
                                    <textarea name="observations" class="supply-textarea" placeholder="Explica brevemente el motivo del pedido si es necesario...">{{ old('observations') }}</textarea>
                                </div>

                                <div style="display: flex; justify-content: flex-end; margin-top: 1rem;">
                                    <button type="submit" class="btn btn--primary" id="submit-request-btn" disabled>
                                        Enviar solicitud
                                    </button>
                                </div>
                            </section>
                        </div>
                    </form>
                </div>
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
                        <button type="button" class="btn btn--secondary btn--sm supply-cart-row__remove">Quitar</button>
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
                        <button type="button" class="btn btn--secondary btn--sm supply-cart-row__remove">Quitar</button>
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
