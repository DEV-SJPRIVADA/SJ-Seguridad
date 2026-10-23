{{--
    Filtro "Vista" de una subpestaña de Autorizaciones compras: por defecto pendientes;
    las demás opciones consultan el historial. Se envía solo al elegir una opción.
    Parámetros: $module, $vista, $currentEstado, $options (valor => etiqueta), $count, $singular, $plural, $metaLabels.
--}}
<div class="req-manage-shell__filters">
    <div class="req-manage-filters req-manage-filters--approval">
        <form
            method="GET"
            action="{{ route('purchase-requests.approval.index', ['module' => $module]) }}"
            class="req-manage-filters__toolbar req-manage-filters__toolbar--approval"
            x-data
            x-on:change="$el.submit()"
        >
            @if ($vista !== 'solicitudes')
                <input type="hidden" name="vista" value="{{ $vista }}">
            @endif

            <div class="req-manage-filters__field req-manage-filters__field--approval">
                <label class="req-manage-filters__label req-manage-filters__label--inline" for="approval-filter-estado">Vista</label>
                <x-searchable-select
                    id="approval-filter-estado"
                    name="estado"
                    class="req-manage-filters__select--inline"
                    :options="$options"
                    :value="$currentEstado"
                    :allow-clear="false"
                    :required="true"
                    search-placeholder="Buscar vista…"
                />
            </div>

            <p class="req-manage-filters__meta req-manage-filters__meta--approval">
                <strong>{{ number_format($count) }}</strong>
                {{ $count === 1 ? $singular : $plural }}
                {{ $metaLabels[$currentEstado] ?? '' }}
            </p>
        </form>
    </div>
</div>
