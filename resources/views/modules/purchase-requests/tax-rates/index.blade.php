<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.catalogos-subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Catálogo de tasas de IVA para las órdenes de compra. Las tasas no se borran: se inactivan. --}}
    <div class="page-section purchase-requests-page purchase-requests-page--detail">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            <div class="pur-req-detail-layout">
                <div class="pur-req-detail-layout__main">
                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step" aria-hidden="true">
                                <x-lucide-percent width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="pur-req-form__section-title">Tasas de IVA</h3>
                                <p class="pur-req-form__section-desc">Solo las tasas activas aparecen al emitir una orden de compra. El IVA de cada línea se calcula sobre cantidad × valor unidad.</p>
                            </div>
                        </header>

                        <div class="data-table-wrap">
                            <table class="supply-table">
                                <thead>
                                    <tr>
                                        <th>Porcentaje</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        @if ($canEdit)
                                            <th class="purchase-request-actions-col">Acciones</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($rates as $rate)
                                        <tr>
                                            <td class="text-center"><strong>{{ \App\Models\PurchaseTaxRate::formatPorcentaje($rate->porcentaje) }}</strong></td>
                                            <td>{{ $rate->nombre }}</td>
                                            <td class="text-center">
                                                <span class="status-pill {{ $rate->activo ? 'status-pill--success' : 'status-pill--muted' }}">{{ $rate->activo ? 'Activa' : 'Inactiva' }}</span>
                                            </td>
                                            @if ($canEdit)
                                                <td class="text-center purchase-request-actions-col">
                                                    <a
                                                        href="{{ route('purchase-requests.tax-rates.index', ['module' => $module, 'editar' => $rate->id]) }}"
                                                        class="cursos-catalogo-page__icon-btn"
                                                        title="Editar tasa {{ \App\Models\PurchaseTaxRate::formatPorcentaje($rate->porcentaje) }}"
                                                        aria-label="Editar tasa {{ \App\Models\PurchaseTaxRate::formatPorcentaje($rate->porcentaje) }}"
                                                    >
                                                        <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                    </a>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canEdit ? 4 : 3 }}" class="text-muted">No hay tasas de IVA registradas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>

                @if ($canEdit)
                    {{-- Formulario: alta por defecto; edición si llega ?editar={id}. --}}
                    <aside class="pur-req-form-aside">
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">{{ $editing ? 'Editar tasa' : 'Nueva tasa' }}</h3>
                                <p class="panel-text">{{ $editing ? 'Cambiar una tasa no modifica las órdenes ya emitidas.' : 'Ejemplo: 19 para IVA 19%.' }}</p>
                            </div>
                            <div class="panel__body">
                                <form
                                    method="POST"
                                    action="{{ $editing ? route('purchase-requests.tax-rates.update', ['module' => $module, 'purchase_tax_rate' => $editing->id]) : route('purchase-requests.tax-rates.store', ['module' => $module]) }}"
                                    class="form-stack"
                                >
                                    @csrf
                                    @if ($editing)
                                        @method('PATCH')
                                    @endif

                                    <div class="form-field">
                                        <label class="form-label" for="tax_porcentaje">Porcentaje (%)</label>
                                        <input
                                            type="number"
                                            id="tax_porcentaje"
                                            name="porcentaje"
                                            class="form-input"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            required
                                            value="{{ old('porcentaje', $editing ? rtrim(rtrim(number_format((float) $editing->porcentaje, 2, '.', ''), '0'), '.') : '') }}"
                                        >
                                        <x-input-error :messages="$errors->get('porcentaje')" />
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="tax_nombre">Nombre</label>
                                        <input
                                            type="text"
                                            id="tax_nombre"
                                            name="nombre"
                                            class="form-input"
                                            maxlength="60"
                                            required
                                            placeholder="Ej: IVA 19%"
                                            value="{{ old('nombre', $editing?->nombre) }}"
                                        >
                                        <x-input-error :messages="$errors->get('nombre')" />
                                    </div>

                                    <label class="pur-req-form__check">
                                        <input type="checkbox" name="activo" value="1" class="form-check" @checked(old('activo', $editing ? $editing->activo : true))>
                                        <span>Activa (aparece al emitir órdenes)</span>
                                    </label>

                                    <div class="pur-req-form-actions__group">
                                        @if ($editing)
                                            <a
                                                href="{{ route('purchase-requests.tax-rates.index', ['module' => $module]) }}"
                                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                                title="Cancelar edición"
                                                aria-label="Cancelar edición"
                                            >
                                                <x-lucide-x width="18" height="18" aria-hidden="true" />
                                            </a>
                                        @endif
                                        <button
                                            type="submit"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                            title="{{ $editing ? 'Guardar cambios' : 'Crear tasa' }}"
                                            aria-label="{{ $editing ? 'Guardar cambios' : 'Crear tasa' }}"
                                        >
                                            <x-lucide-save width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
