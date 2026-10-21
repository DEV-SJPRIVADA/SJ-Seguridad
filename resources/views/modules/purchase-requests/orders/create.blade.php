<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--form purchase-orders-page">
        <div class="app-container">
            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver al procesamiento"
                        aria-label="Volver al procesamiento"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>
                <h2 class="page-title">Emitir orden de compra</h2>
                <p class="page-subtitle">FO-AD-07. Selecciona el proveedor y captura los precios por línea; el número de OC se asigna automáticamente.</p>
                <span class="status-pill status-pill--info">{{ $sourceLabel }}</span>
            </div>

            @if ($errors->any())
                <div class="alert alert--danger" role="alert">
                    <p class="alert__title">Revise los datos de la orden:</p>
                    <ul class="form-error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (count($providerOptions) === 0)
                <div class="alert alert--warning" role="alert">
                    No hay proveedores activos en el catálogo. Registre uno antes de emitir la orden de compra.
                </div>
            @endif

            @if ($issuedOrders->isNotEmpty())
                <div class="alert alert--info purchase-orders-page__issued" role="status">
                    <p class="alert__title">
                        Esta solicitud ya tiene {{ $issuedOrders->count() }} {{ $issuedOrders->count() === 1 ? 'orden de compra emitida' : 'órdenes de compra emitidas' }}.
                        El saldo pendiente de cada línea se descuenta de lo ya ordenado, así que puede repartir el resto entre varios proveedores.
                    </p>
                    <ul class="purchase-orders-page__issued-list">
                        @foreach ($issuedOrders as $issued)
                            <li>
                                <strong>OC N.º {{ $issued->numeroOc }}</strong>
                                — {{ $issued->provider_nombre }}
                                — {{ $issued->fecha }}
                                — {{ number_format((float) $issued->total, 2) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $storeUrl }}" class="pur-req-form" id="purchase-order-form">
                @csrf

                <div class="pur-req-form__meta">
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Origen</span>
                        <span class="pur-req-form__meta-value">{{ $sourceLabel }}</span>
                    </div>
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Solicitante</span>
                        <span class="pur-req-form__meta-value">{{ $source->user?->name ?? '—' }}</span>
                    </div>
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Área</span>
                        <span class="pur-req-form__meta-value">
                            {{ $sourceType === 'purchase' ? ($source->areaLabel() ?? '—') : (config('access.areas.'.$source->area_key, $source->area_key) ?? '—') }}
                        </span>
                    </div>
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Formato</span>
                        <span class="pur-req-form__meta-value">FO-AD-07</span>
                    </div>
                </div>

                <section class="pur-req-form__section">
                    <header class="pur-req-form__section-head">
                        <span class="pur-req-form__section-step">1</span>
                        <div>
                            <h3 class="pur-req-form__section-title">Proveedor y datos de la orden</h3>
                            <p class="pur-req-form__section-desc">Los datos del proveedor se congelan en la OC al emitirla.</p>
                        </div>
                    </header>

                    <div class="cursos-registros-page__form-grid">
                        <div class="form-field cursos-registros-page__form-span">
                            <label class="form-label" for="order_provider_id">Proveedor *</label>
                            <x-searchable-select
                                id="order_provider_id"
                                name="provider_id"
                                :options="$providerOptions"
                                :value="old('provider_id')"
                                placeholder="Seleccione un proveedor activo"
                                search-placeholder="Buscar por nombre o identificación…"
                                :allow-clear="false"
                                required
                            />
                            <x-input-error :messages="$errors->get('provider_id')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="order_fecha">Fecha *</label>
                            <input
                                id="order_fecha"
                                type="date"
                                name="fecha"
                                class="form-input"
                                value="{{ old('fecha', now()->toDateString()) }}"
                                required
                            >
                            <x-input-error :messages="$errors->get('fecha')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="order_ciudad">Ciudad de entrega</label>
                            <input
                                id="order_ciudad"
                                type="text"
                                name="ciudad"
                                class="form-input"
                                maxlength="120"
                                value="{{ old('ciudad', $defaultCity) }}"
                                placeholder="Ej: BOGOTÁ"
                            >
                            <x-input-error :messages="$errors->get('ciudad')" />
                        </div>

                        <div class="form-field cursos-registros-page__form-span">
                            <label class="form-label" for="order_observaciones">Observaciones</label>
                            <textarea
                                id="order_observaciones"
                                name="observaciones"
                                class="form-textarea"
                                rows="3"
                                maxlength="2000"
                                placeholder="Condiciones, tiempo de entrega u observaciones de la OC…"
                            >{{ old('observaciones') }}</textarea>
                            <x-input-error :messages="$errors->get('observaciones')" />
                        </div>
                    </div>
                </section>

                <section class="pur-req-form__section">
                    <header class="pur-req-form__section-head">
                        <span class="pur-req-form__section-step">2</span>
                        <div>
                            <h3 class="pur-req-form__section-title">Líneas y precios</h3>
                            <p class="pur-req-form__section-desc">Valor total de línea = cantidad × valor unidad + IVA.</p>
                        </div>
                    </header>

                    <div class="data-table-wrap pur-req-form__table-wrap">
                        <table class="supply-table purchase-order-lines">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Pendiente</th>
                                    <th>Cantidad</th>
                                    <th>Artículo</th>
                                    <th>Descripción</th>
                                    <th>Valor unidad</th>
                                    <th>IVA</th>
                                    <th>Valor total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lines as $index => $line)
                                    <tr data-order-line @if ($line['cantidad_pendiente'] <= 0) class="purchase-order-lines__row--sin-saldo" @endif>
                                        <td class="text-center">{{ $index + 1 }}</td>
                                        {{-- Saldo pendiente antes de la cantidad: referencia para capturar lo que se ordena. --}}
                                        <td class="js-line-pending">
                                            <span class="purchase-order-lines__pending-value">
                                                {{ number_format((float) $line['cantidad_pendiente'], 2) }}
                                            </span>
                                            <span class="purchase-order-lines__pending-hint">
                                                de {{ number_format((float) $line['cantidad_solicitada'], 0) }} solicitadas
                                                @if ($line['cantidad_ordenada'] > 0)
                                                    · {{ number_format((float) $line['cantidad_ordenada'], 0) }} ordenadas
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][cantidad]"
                                                class="form-input js-line-qty"
                                                step="0.01"
                                                min="0"
                                                max="{{ $line['cantidad_pendiente'] }}"
                                                value="{{ old('items.'.$index.'.cantidad', $line['cantidad_pendiente']) }}"
                                                required
                                                aria-label="Cantidad de la línea {{ $index + 1 }}"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                name="items[{{ $index }}][articulo]"
                                                class="form-input"
                                                maxlength="255"
                                                value="{{ old('items.'.$index.'.articulo', $line['articulo']) }}"
                                                aria-label="Artículo de la línea {{ $index + 1 }}"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="text"
                                                name="items[{{ $index }}][descripcion]"
                                                class="form-input"
                                                maxlength="500"
                                                value="{{ old('items.'.$index.'.descripcion', $line['descripcion']) }}"
                                                required
                                                aria-label="Descripción de la línea {{ $index + 1 }}"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][valor_unidad]"
                                                class="form-input js-line-unit"
                                                step="0.01"
                                                min="0"
                                                value="{{ old('items.'.$index.'.valor_unidad', $line['valor_unidad']) }}"
                                                required
                                                aria-label="Valor unidad de la línea {{ $index + 1 }}"
                                            >
                                        </td>
                                        <td>
                                            <input
                                                type="number"
                                                name="items[{{ $index }}][iva]"
                                                class="form-input js-line-iva"
                                                step="0.01"
                                                min="0"
                                                value="{{ old('items.'.$index.'.iva', $line['iva']) }}"
                                                aria-label="IVA de la línea {{ $index + 1 }}"
                                            >
                                        </td>
                                        <td class="text-right js-line-total">0.00</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="7" class="text-right">TOTAL</th>
                                    <th class="text-right" id="purchase-order-total">0.00</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>

                <div class="pur-req-form-actions">
                    <p class="pur-req-form-actions__note">
                        Al emitir, la OC queda registrada con estado <strong>Emitida</strong> y podrá descargarse en PDF/Excel.
                    </p>
                    <div class="pur-req-form-actions__group">
                        <a
                            href="{{ $backUrl }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Cancelar"
                            aria-label="Cancelar"
                        >
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </a>
                        <button
                            type="submit"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            title="Emitir orden de compra"
                            aria-label="Emitir orden de compra"
                        >
                            <x-lucide-check width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @php
        $providerDirectory = \App\Models\PurchaseProvider::query()
            ->where('estado', \App\Models\PurchaseProvider::ESTADO_ACTIVO)
            ->get(['id', 'ciudad'])
            ->mapWithKeys(fn ($provider) => [(string) $provider->id => ['ciudad' => (string) ($provider->ciudad ?? '')]])
            ->all();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const providerDirectory = @json($providerDirectory);
            const providerInput = document.querySelector('input[name="provider_id"]');
            const cityInput = document.getElementById('order_ciudad');

            const syncProviderCity = function () {
                if (!providerInput || !cityInput) {
                    return;
                }

                const provider = providerDirectory[String(providerInput.value)] || null;
                if (provider && provider.ciudad && cityInput.value.trim() === '') {
                    cityInput.value = provider.ciudad;
                }
            };

            if (providerInput) {
                providerInput.addEventListener('change', syncProviderCity);
            }

            const format = function (value) {
                return (Math.round((value + Number.EPSILON) * 100) / 100).toFixed(2);
            };

            const recalc = function () {
                let total = 0;

                document.querySelectorAll('[data-order-line]').forEach(function (row) {
                    const quantity = parseFloat(row.querySelector('.js-line-qty')?.value || '0') || 0;
                    const unit = parseFloat(row.querySelector('.js-line-unit')?.value || '0') || 0;
                    const iva = parseFloat(row.querySelector('.js-line-iva')?.value || '0') || 0;
                    const lineTotal = (quantity * unit) + iva;

                    total += lineTotal;

                    const cell = row.querySelector('.js-line-total');
                    if (cell) {
                        cell.textContent = format(lineTotal);
                    }
                });

                const totalCell = document.getElementById('purchase-order-total');
                if (totalCell) {
                    totalCell.textContent = format(total);
                }
            };

            document.querySelectorAll('.js-line-qty, .js-line-unit, .js-line-iva').forEach(function (input) {
                input.addEventListener('input', recalc);
            });

            recalc();
        });
    </script>
</x-app-layout>
