<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 class="panel-title">Catalogo de Suministros</h3>
                            <p class="panel-text">Gestiona los productos disponibles para que el personal realice sus pedidos.</p>
                        </div>
                        <div style="display:flex;gap:0.5rem;align-items:center;">
                            <x-export-excel route="{{ route('supplies.products.export', ['module' => $module]) }}" />
                            <button type="button" class="btn btn--primary" onclick="openCreateModal()">
                                + Nuevo Producto
                            </button>
                        </div>
                    </div>
                </div>

                <div class="panel__body">
                    <div class="block-spaced">
                        <table class="supply-table js-datatable" data-no-excel>
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th>Producto</th>
                                    <th>Descripcion</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    <tr>
                                        <td class="text-center">
                                            <span class="badge badge--info">{{ $product->category ?: 'General' }}</span>
                                        </td>
                                        <td style="font-weight: 600; color: var(--color-primary);">{{ $product->name }}</td>
                                        <td>{{ $product->description }}</td>
                                        <td class="text-center">
                                            @if($product->is_active)
                                                <span class="status-pill status-pill--success">Activo</span>
                                            @else
                                                <span class="status-pill status-pill--danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn--secondary btn--sm" 
                                                onclick='openEditModal(@json($product))'>
                                                Editar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar -->
    <div id="product-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="panel" style="width: 500px; max-width: 90vw;">
            <div class="panel__header">
                <h3 class="panel-title" id="modal-title">Nuevo Producto</h3>
            </div>
            <div class="panel__body">
                <form id="product-form" method="POST" action="{{ route('supplies.products.store', ['module' => $module]) }}">
                    @csrf
                    <div id="method-field"></div>
                    
                    <div class="form-stack">
                        <div class="form-field">
                            <label class="form-label">Nombre del Producto</label>
                            <input type="text" name="name" id="p-name" class="supply-input" required placeholder="Ej: Resmas de papel Carta">
                        </div>

                        <div class="form-field">
                            <label class="form-label">Categoria</label>
                            <input type="text" name="category" id="p-category" class="supply-input" placeholder="Ej: PAPELERIA">
                        </div>

                        <div class="form-field">
                            <label class="form-label">Descripcion / Presentacion</label>
                            <input type="text" name="description" id="p-description" class="supply-input" placeholder="Ej: Paquete x 500 hojas">
                        </div>

                        <div class="form-field" id="status-field" style="display: none;">
                            <label class="form-label">Estado</label>
                            <select name="is_active" id="p-status" class="supply-input supply-select">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem;">
                        <button type="button" class="btn btn--secondary" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn btn--primary" id="submit-btn">Guardar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('product-modal');
        const form = document.getElementById('product-form');
        const title = document.getElementById('modal-title');
        const methodField = document.getElementById('method-field');
        const statusField = document.getElementById('status-field');
        const baseUrl = "{{ route('supplies.products.store', ['module' => $module]) }}";

        function openCreateModal() {
            title.innerText = 'Nuevo Producto';
            form.action = baseUrl;
            methodField.innerHTML = '';
            statusField.style.display = 'none';
            form.reset();
            modal.style.display = 'flex';
        }

        function openEditModal(product) {
            title.innerText = 'Editar Producto';
            form.action = `{{ url('supplies/'.$module.'/catalogo') }}/${product.id}`;
            methodField.innerHTML = '@method("PATCH")';
            statusField.style.display = 'block';
            
            document.getElementById('p-name').value = product.name;
            document.getElementById('p-category').value = product.category;
            document.getElementById('p-description').value = product.description;
            document.getElementById('p-status').value = product.is_active ? "1" : "0";
            
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Cerrar al hacer click fuera
        window.onclick = function(event) {
            if (event.target == modal) closeModal();
        }
    </script>
</x-app-layout>
