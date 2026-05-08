<x-app-layout>
    <x-slot name="header">
        @include('modules.supplies.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Nueva Solicitud de Suministros</h3>
                    <p class="panel-text">Selecciona los productos y cantidades necesarias. Esta solicitud pasara a revision por Calidad.</p>
                </div>

                <div class="panel__body">
                    <form action="{{ route('supplies.store', ['module' => $module]) }}" method="POST">
                        @csrf
                        
                        <div class="form-group block-spaced">
                            <label class="form-label">Observaciones Generales</label>
                            <textarea name="observations" class="supply-textarea" placeholder="Explica brevemente el motivo del pedido si es necesario..."></textarea>
                        </div>

                        <div class="block-spaced">
                            <table class="supply-table" id="items-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40%;">Producto</th>
                                        <th style="width: 25%;">Inventario Actual</th>
                                        <th style="width: 25%;">Cantidad a Pedir</th>
                                        <th style="width: 10%;">Accion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- Se agregan dinamicamente con JS --}}
                                </tbody>
                            </table>
                        </div>

                        <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                            <button type="button" class="btn btn--secondary" id="add-item-btn">
                                + Agregar Item
                            </button>
                            <button type="submit" class="btn btn--primary">
                                Enviar Solicitud
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Plantilla para nueva fila --}}
    <template id="row-template">
        <tr>
            <td>
                <select name="items[{index}][product_id]" class="supply-input supply-select" required>
                    <option value="">Seleccione un producto...</option>
                    @foreach ($products as $category => $catProducts)
                        <optgroup label="{{ $category ?: 'General' }}">
                            @foreach ($catProducts as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->description }})</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="items[{index}][current_inventory]" class="supply-input" min="0" required>
            </td>
            <td>
                <input type="number" name="items[{index}][quantity]" class="supply-input" min="1" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn-secondary btn-sm remove-row-btn" style="color: var(--color-danger); border: none; background: transparent;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </td>
        </tr>
    </template>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tableBody = document.querySelector('#items-table tbody');
            const addBtn = document.querySelector('#add-item-btn');
            const template = document.querySelector('#row-template').innerHTML;
            let index = 0;

            function addRow() {
                const html = template.replace(/{index}/g, index);
                const tr = document.createElement('tr');
                tr.innerHTML = html;
                tableBody.appendChild(tr);
                index++;

                tr.querySelector('.remove-row-btn').addEventListener('click', function() {
                    tr.remove();
                });
            }

            addBtn.addEventListener('click', addRow);
            
            // Agregar la primera fila por defecto
            addRow();
        });
    </script>
    @endpush
</x-app-layout>
