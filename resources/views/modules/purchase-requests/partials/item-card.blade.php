{{-- Tarjeta de producto FO-AD-44: foto + descripción, referencia ancha, luego utilización/ubicación/cantidad. --}}
@php
    $index = (int) ($index ?? 0);
    $item = $item ?? [];
    $cantidad = old('items.'.$index.'.cantidad', $item['cantidad'] ?? 1);
    $descripcion = old('items.'.$index.'.descripcion', $item['descripcion'] ?? '');
    $referencia = old('items.'.$index.'.referencia', $item['referencia'] ?? '');
    $utilizacion = old('items.'.$index.'.utilizacion', $item['utilizacion'] ?? '');
    $ubicacion = old('items.'.$index.'.ubicacion', $item['ubicacion'] ?? '');
    $existingFotoPath = $item['existing_foto_path'] ?? null;
@endphp

<article class="purchase-item-card" data-purchase-item-row>
    <header class="purchase-item-card__head">
        <span class="purchase-item-card__title">Producto</span>
        <button
            type="button"
            class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
            data-remove-item
            title="Quitar producto"
            aria-label="Quitar producto"
        >
            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
        </button>
    </header>

    {{-- Fila 1: foto primero, descripción con más altura --}}
    <div class="purchase-item-card__top">
        <div class="form-field purchase-item-card__foto">
            <span class="form-label">Foto <span class="text-muted">(opcional)</span></span>
            @include('modules.purchase-requests.partials.item-foto-field', [
                'index' => $index,
                'existingFotoPath' => $existingFotoPath,
                'wrapInTd' => false,
            ])
        </div>

        <div class="form-field purchase-item-card__desc">
            <label class="form-label" for="item-descripcion-{{ $index }}">Descripción</label>
            <textarea
                id="item-descripcion-{{ $index }}"
                name="items[{{ $index }}][descripcion]"
                class="form-textarea purchase-item-card__desc-input"
                rows="4"
                placeholder="Descripción del producto"
                required
            >{{ $descripcion }}</textarea>
            <x-input-error :messages="$errors->get('items.'.$index.'.descripcion')" />
        </div>
    </div>

    {{-- Referencia al mismo ancho que el bloque superior (foto + descripción) --}}
    <div class="form-field purchase-item-card__referencia">
        <label class="form-label" for="item-referencia-{{ $index }}">Referencia</label>
        <input
            type="text"
            id="item-referencia-{{ $index }}"
            name="items[{{ $index }}][referencia]"
            class="form-input"
            value="{{ $referencia }}"
            placeholder="Marca-Modelo / código"
            required
        >
        <x-input-error :messages="$errors->get('items.'.$index.'.referencia')" />
    </div>

    {{-- Fila 2: utilización, ubicación y cantidad --}}
    <div class="purchase-item-card__bottom">
        <div class="form-field">
            <label class="form-label" for="item-utilizacion-{{ $index }}">Utilización</label>
            <input
                type="text"
                id="item-utilizacion-{{ $index }}"
                name="items[{{ $index }}][utilizacion]"
                class="form-input"
                value="{{ $utilizacion }}"
                placeholder="Para quién / qué uso"
                required
            >
            <x-input-error :messages="$errors->get('items.'.$index.'.utilizacion')" />
        </div>

        <div class="form-field">
            <label class="form-label" for="item-ubicacion-{{ $index }}">Ubicación</label>
            <input
                type="text"
                id="item-ubicacion-{{ $index }}"
                name="items[{{ $index }}][ubicacion]"
                class="form-input"
                value="{{ $ubicacion }}"
                placeholder="Ubicación / sede"
                required
            >
            <x-input-error :messages="$errors->get('items.'.$index.'.ubicacion')" />
        </div>

        <div class="form-field purchase-item-card__cantidad">
            <label class="form-label" for="item-cantidad-{{ $index }}">Cantidad</label>
            <input
                type="number"
                id="item-cantidad-{{ $index }}"
                name="items[{{ $index }}][cantidad]"
                class="form-input"
                min="1"
                value="{{ $cantidad }}"
                required
            >
            <x-input-error :messages="$errors->get('items.'.$index.'.cantidad')" />
        </div>
    </div>
</article>
