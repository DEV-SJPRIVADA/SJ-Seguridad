{{--
    Barra "Vista" de las subpestañas de Autorizaciones compras.
    Selector compacto a la izquierda y contador de registros a la derecha; se envía al elegir una opción.
    Parámetros: $module, $vista, $currentEstado, $options (valor => etiqueta), $count, $singular, $plural, $metaLabels.
--}}
<div class="req-manage-shell__filters">
    <form
        method="GET"
        action="{{ route('purchase-requests.approval.index', ['module' => $module]) }}"
        class="pur-approval-bar"
        x-data
        x-on:change="$el.submit()"
    >
        @if ($vista !== 'solicitudes')
            <input type="hidden" name="vista" value="{{ $vista }}">
        @endif

        <div class="pur-approval-bar__view">
            <label class="pur-approval-bar__label" for="approval-filter-estado">Vista</label>
            <x-searchable-select
                id="approval-filter-estado"
                name="estado"
                class="pur-approval-bar__select"
                :options="$options"
                :value="$currentEstado"
                :allow-clear="false"
                :required="true"
                search-placeholder="Buscar vista…"
            />
        </div>

        {{-- Contador de la vista activa: siempre el total de la lista filtrada. --}}
        <p class="pur-approval-bar__count {{ $count === 0 ? 'pur-approval-bar__count--empty' : '' }}">
            <span class="pur-approval-bar__count-value">{{ number_format($count) }}</span>
            <span class="pur-approval-bar__count-text">
                {{ $count === 1 ? $singular : $plural }}
                {{ $metaLabels[$currentEstado] ?? '' }}
            </span>
        </p>
    </form>
</div>
