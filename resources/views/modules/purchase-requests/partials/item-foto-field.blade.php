<td class="purchase-item-foto-cell">
    <div class="purchase-item-foto" data-purchase-item-foto role="button" tabindex="0" title="Subir foto del producto (opcional)">
        <input
            type="file"
            name="items[{{ $index }}][foto]"
            class="purchase-item-foto__input"
            accept="image/jpeg,image/png,image/webp,image/gif"
        >
        <div class="purchase-item-foto__placeholder">
            <span class="purchase-item-foto__icon" aria-hidden="true">📷</span>
            <span class="purchase-item-foto__hint">Subir foto</span>
        </div>
        <div class="purchase-item-foto__preview" hidden>
            <img src="" alt="Vista previa" class="purchase-item-foto__img">
            <span class="purchase-item-foto__name"></span>
            <button type="button" class="purchase-item-foto__clear" aria-label="Quitar foto">&times;</button>
        </div>
    </div>
    <x-input-error :messages="$errors->get('items.'.$index.'.foto')" />
</td>
