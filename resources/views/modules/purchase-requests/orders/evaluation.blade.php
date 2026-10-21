<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        // Criterios de la evaluación post-entrega. Los pesos y umbrales salen del config.
        $weights = config('purchase-orders.evaluation.weights');
        $labels = config('purchase-orders.evaluation.labels');
        $thresholds = config('purchase-orders.evaluation.thresholds');
        $ratingOptions = collect(range(1, 5))
            ->map(fn (int $score): array => ['value' => (string) $score, 'label' => (string) $score])
            ->all();
        $criterios = [
            [
                'name' => 'calif_referencias_cantidades',
                'label' => 'Referencias y cantidades',
                'weight' => $weights['referencias_cantidades'] ?? 25,
                'value' => $evaluation?->calif_referencias_cantidades,
                'resultado' => $evaluation?->resultado_referencias,
            ],
            [
                'name' => 'calif_oportunidad_entrega',
                'label' => 'Oportunidad de entrega',
                'weight' => $weights['oportunidad_entrega'] ?? 25,
                'value' => $evaluation?->calif_oportunidad_entrega,
                'resultado' => $evaluation?->resultado_oportunidad,
            ],
            [
                'name' => 'calif_calidad_producto',
                'label' => 'Calidad del producto',
                'weight' => $weights['calidad_producto'] ?? 50,
                'value' => $evaluation?->calif_calidad_producto,
                'resultado' => $evaluation?->resultado_calidad,
            ],
        ];
        $umbral = $evaluation?->umbral_codigo;
    @endphp

    <div class="page-section purchase-requests-page purchase-requests-page--form purchase-orders-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger" role="alert">
                    <p class="alert__title">Revise la evaluación:</p>
                    <ul class="form-error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="{{ $backLabel }}"
                        aria-label="{{ $backLabel }}"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>
                <h2 class="page-title">Evaluación de la orden N.º {{ $order->numeroOc }}</h2>
                <p class="page-subtitle">
                    {{ $order->provider_nombre }} · Fecha de recibido y calificación del proveedor. No se imprime en el FO-AD-07.
                </p>
            </div>

            {{-- Una fila por OC: se puede guardar la fecha sin las tres calificaciones. --}}
            <form method="POST" action="{{ route('purchase-requests.orders.evaluation.update', ['module' => $module, 'purchase_order' => $order->getKey()]) }}" class="pur-req-form">
                @csrf
                @method('PUT')

                <div class="pur-req-detail-layout">
                    <div class="pur-req-detail-layout__main">
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step">1</span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Recibido</h3>
                                    <p class="pur-req-form__section-desc">La fecha se puede guardar sin calificar. El cierre automático la exige junto con el saldo en cero.</p>
                                </div>
                            </header>

                            <div class="form-grid form-grid--two">
                                <div class="form-field">
                                    <label class="form-label" for="fecha_recibido">Fecha de recibido</label>
                                    <input
                                        type="date"
                                        name="fecha_recibido"
                                        id="fecha_recibido"
                                        class="form-input"
                                        value="{{ old('fecha_recibido', $evaluation?->fecha_recibido?->toDateString()) }}"
                                        required
                                    >
                                    <x-input-error :messages="$errors->get('fecha_recibido')" />
                                </div>
                            </div>

                            <div class="form-field" style="margin-top: 0.85rem;">
                                <label class="form-label" for="observaciones">Observaciones</label>
                                <textarea
                                    id="observaciones"
                                    name="observaciones"
                                    class="form-textarea"
                                    rows="3"
                                    maxlength="5000"
                                    placeholder="Notas de la entrega…"
                                >{{ old('observaciones', $evaluation?->observaciones) }}</textarea>
                                <x-input-error :messages="$errors->get('observaciones')" />
                            </div>
                        </section>

                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step">2</span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Calificación</h3>
                                    <p class="pur-req-form__section-desc">Enteros de 1 a 5. Pesos {{ $weights['referencias_cantidades'] ?? 25 }} / {{ $weights['oportunidad_entrega'] ?? 25 }} / {{ $weights['calidad_producto'] ?? 50 }}.</p>
                                </div>
                            </header>

                            <div class="form-grid form-grid--two">
                                @foreach ($criterios as $criterio)
                                    <div class="form-field">
                                        <label class="form-label" for="{{ $criterio['name'] }}">
                                            {{ $criterio['label'] }}
                                            <span class="text-muted">(peso {{ $criterio['weight'] }}%)</span>
                                        </label>
                                        <x-searchable-select
                                            :id="$criterio['name']"
                                            :name="$criterio['name']"
                                            :options="$ratingOptions"
                                            :value="$criterio['value']"
                                            placeholder="Sin calificación"
                                            searchPlaceholder="Buscar…"
                                            :allowClear="true"
                                        />
                                        <x-input-error :messages="$errors->get($criterio['name'])" />
                                        @if ($criterio['resultado'] !== null)
                                            <p class="pur-req-form__section-desc">Resultado: {{ number_format((float) $criterio['resultado'], 2) }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div style="margin-top: 1rem;">
                                <button type="submit" class="btn btn--primary">Guardar evaluación</button>
                            </div>
                        </section>
                    </div>

                    <aside class="pur-req-form-aside">
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Resultado</h3>
                                <p class="panel-text">Se calcula al guardar las tres calificaciones.</p>
                            </div>
                            <div class="panel__body">
                                @if ($evaluation?->puntaje_porcentaje !== null)
                                    <ul class="pur-req-form-guide__list">
                                        <li class="pur-req-form-guide__item">Puntaje: {{ number_format((float) $evaluation->puntaje_porcentaje, 2) }}%</li>
                                        <li class="pur-req-form-guide__item">{{ $labels[$umbral] ?? $umbral }}</li>
                                    </ul>
                                @else
                                    <p class="panel-text">Aún no hay puntaje. La fecha de recibido ya puede quedar registrada.</p>
                                @endif
                            </div>
                        </div>

                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Umbrales</h3>
                            </div>
                            <div class="panel__body">
                                <ul class="pur-req-form-guide__list">
                                    <li class="pur-req-form-guide__item">{{ $thresholds['satisfactoria'] ?? 80 }}–100: {{ $labels['satisfactoria'] ?? 'Compra satisfactoria' }}</li>
                                    <li class="pur-req-form-guide__item">{{ $thresholds['seguimiento'] ?? 60 }}–{{ ((int) ($thresholds['satisfactoria'] ?? 80)) - 1 }}: {{ $labels['seguimiento'] ?? 'Requiere seguimiento' }}</li>
                                    <li class="pur-req-form-guide__item">Menos de {{ $thresholds['seguimiento'] ?? 60 }}: {{ $labels['insatisfactoria'] ?? 'Compra insatisfactoria' }}</li>
                                </ul>
                            </div>
                        </div>
                    </aside>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
