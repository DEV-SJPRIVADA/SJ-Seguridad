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
            let itemIndex = 0;

            function updateCartState() {
                const hasItems = cartItems.children.length > 0;
                cartEmpty.style.display = hasItems ? 'none' : 'block';
                submitBtn.disabled = !hasItems;
            }

            function addCatalogItem(productId, productName) {
                const existing = cartItems.querySelector(`[data-product-id="${productId}"][data-item-type="catalog"]`);
                if (existing) {
                    const qtyInput = existing.querySelector('input[name$="[quantity]"]');
                    qtyInput.value = parseInt(qtyInput.value || '0', 10) + 1;
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.className = 'supply-cart-row';
                wrapper.dataset.itemType = 'catalog';
                wrapper.dataset.productId = productId;
                wrapper.innerHTML = `
                    <div class="supply-cart-row__info">
                        <strong>${productName}</strong>
                    </div>
                    <div class="supply-cart-row__fields">
                        <input type="hidden" name="items[${itemIndex}][type]" value="catalog">
                        <input type="hidden" name="items[${itemIndex}][product_id]" value="${productId}">
                        <label class="supply-cart-field">
                            <span>Inventario</span>
                            <input type="number" name="items[${itemIndex}][current_inventory]" class="supply-input" min="0" value="0" required>
                        </label>
                        <label class="supply-cart-field">
                            <span>Cantidad</span>
                            <input type="number" name="items[${itemIndex}][quantity]" class="supply-input" min="1" value="1" required>
                        </label>
                        <button type="button" class="btn btn--secondary btn--sm supply-cart-row__remove">Quitar</button>
                    </div>
                `;
                cartItems.appendChild(wrapper);
                itemIndex++;
                bindRemove(wrapper);
                updateCartState();
            }

            function addCustomItem() {
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
                            <input type="text" name="items[${itemIndex}][custom_name]" class="supply-input" placeholder="Describe el producto" required>
                        </label>
                        <label class="supply-cart-field">
                            <span>Cantidad</span>
                            <input type="number" name="items[${itemIndex}][quantity]" class="supply-input" min="1" value="1" required>
                        </label>
                        <button type="button" class="btn btn--secondary btn--sm supply-cart-row__remove">Quitar</button>
                    </div>
                `;
                cartItems.appendChild(wrapper);
                itemIndex++;
                bindRemove(wrapper);
                updateCartState();
            }

            function bindRemove(wrapper) {
                wrapper.querySelector('.supply-cart-row__remove').addEventListener('click', function () {
                    wrapper.remove();
                    updateCartState();
                });
            }

            catalogItems.forEach(function (button) {
                button.addEventListener('click', function () {
                    addCatalogItem(button.dataset.productId, button.dataset.productName);
                });
            });

            document.getElementById('add-custom-item-btn').addEventListener('click', addCustomItem);

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

            updateCartState();
        });
    </script>
    @endpush
</x-app-layout>
