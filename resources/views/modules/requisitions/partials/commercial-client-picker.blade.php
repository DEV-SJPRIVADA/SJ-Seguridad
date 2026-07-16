@php
    /** @var \App\Models\CommercialClient|null $selectedCommercialClient */
    $selectedCommercialClient = $selectedCommercialClient ?? null;
    $clientRequired = $clientRequired ?? true;
@endphp

<div
    class="form-field js-client-picker"
    data-search-url="{{ $clientSearchUrl }}"
>
    <x-input-label for="requisition_client_search" :value="'Cliente'.($clientRequired ? ' *' : '')" />
    <p class="req-form__hint">Busque por nombre o NIT en la matriz comercial (min. 2 caracteres).</p>

    <div class="js-client-picker-search" style="{{ $selectedCommercialClient ? 'display:none;' : '' }}">
        <x-text-input
            id="requisition_client_search"
            type="search"
            class="form-input js-client-picker-search-input"
            autocomplete="off"
            placeholder="Ej. MADEMAX o 901360444"
        />
        <div class="client-picker-results js-client-picker-results" hidden role="listbox" aria-label="Resultados de clientes"></div>
        <p class="panel-text js-client-picker-hint" style="margin:0.5rem 0 0;" hidden></p>
    </div>

    <div class="client-picker-selected js-client-picker-selected" style="{{ $selectedCommercialClient ? '' : 'display:none;' }}">
        <div class="client-picker-selected__card">
            <div>
                <strong class="js-client-picker-name">{{ $selectedCommercialClient?->name }}</strong>
                <div class="panel-text" style="margin:0.15rem 0 0;">
                    NIT <span class="js-client-picker-nit">{{ $selectedCommercialClient?->nit }}</span>
                    <span class="js-client-picker-city">{{ $selectedCommercialClient?->city ? ' · '.$selectedCommercialClient->city : '' }}</span>
                </div>
            </div>
            <button type="button" class="btn btn--secondary btn--sm js-client-picker-clear">Cambiar</button>
        </div>
    </div>

    <input
        type="hidden"
        id="commercial_client_id"
        name="commercial_client_id"
        class="js-client-picker-id"
        value="{{ old('commercial_client_id', $selectedCommercialClient?->id) }}"
        @if($clientRequired) required @endif
    >
    <x-input-error :messages="$errors->get('commercial_client_id')" />
</div>

@once
    @push('styles')
        <style>
            .client-picker-results {
                margin-top: 0.4rem;
                border: 1px solid var(--color-border-strong);
                border-radius: 12px;
                background: #fff;
                max-height: 240px;
                overflow: auto;
                box-shadow: var(--shadow-soft);
            }
            .client-picker-results__item {
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 0.15rem;
                width: 100%;
                padding: 0.65rem 0.85rem;
                border: 0;
                border-bottom: 1px solid #eef1f4;
                background: transparent;
                text-align: left;
                cursor: pointer;
            }
            .client-picker-results__item:last-child { border-bottom: 0; }
            .client-picker-results__item:hover { background: rgba(0, 51, 102, 0.06); }
            .client-picker-results__item span { color: #5b6570; font-size: 0.875rem; }
            .client-picker-selected__card {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 1rem;
                padding: 0.85rem 1rem;
                border: 1px solid #c9d6e5;
                border-radius: 12px;
                background: var(--brand-blue-pale);
            }
        </style>
    @endpush

    @push('scripts')
        <script src="{{ asset('js/comercial-client-picker.js') }}?v={{ @filemtime(public_path('js/comercial-client-picker.js')) ?: time() }}"></script>
    @endpush
@endonce
