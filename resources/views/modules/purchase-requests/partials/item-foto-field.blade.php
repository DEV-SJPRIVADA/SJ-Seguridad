<td class="purchase-item-foto-cell">
    @php
        $existingFotoPath = $existingFotoPath ?? null;
        $existingFotoUrl = $existingFotoPath ? \Illuminate\Support\Facades\Storage::disk('public')->url($existingFotoPath) : null;
        $hasExistingFoto = filled($existingFotoUrl);
    @endphp
    <div class="purchase-item-foto @if($hasExistingFoto) has-file @endif" data-purchase-item-foto role="button" tabindex="0" title="Subir foto del producto (opcional)">
        <input type="hidden" name="items[{{ $index }}][existing_foto_path]" value="{{ old('items.'.$index.'.existing_foto_path', $existingFotoPath) }}" class="purchase-item-foto__existing-path">
        <input
            type="file"
            name="items[{{ $index }}][foto]"
            class="purchase-item-foto__input"
            accept="image/jpeg,image/png,image/webp,image/gif"
        >
        <div class="purchase-item-foto__placeholder" @if($hasExistingFoto) hidden @endif>
            <span class="purchase-item-foto__icon" aria-hidden="true">📷</span>
            <span class="purchase-item-foto__hint">Subir foto</span>
        </div>
        <div class="purchase-item-foto__preview" @if(! $hasExistingFoto) hidden @endif>
            <img src="{{ $existingFotoUrl ?? '' }}" alt="Vista previa" class="purchase-item-foto__img">
            <span class="purchase-item-foto__name">{{ $hasExistingFoto ? 'Foto actual' : '' }}</span>
            <button type="button" class="purchase-item-foto__clear" aria-label="Quitar foto">&times;</button>
        </div>
    </div>
    <x-input-error :messages="$errors->get('items.'.$index.'.foto')" />
</td>
