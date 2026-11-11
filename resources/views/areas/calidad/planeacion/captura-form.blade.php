@php
    $monthLabels = \App\Services\Calidad\Planeacion\PlaneacionPeriodService::MONTH_LABELS;
    $monthName = $monthLabels[$month] ?? (string) $month;
    $fields = [];
    foreach ($version?->fields ?? [] as $field) {
        if (! is_array($field)) {
            continue;
        }
        $key = (string) ($field['key'] ?? '');
        if ($key === '') {
            continue;
        }
        $fields[] = [
            'key' => $key,
            'label' => (string) ($field['label'] ?? $key),
            'decimals' => in_array((int) ($field['decimals'] ?? 2), [0, 2], true) ? (int) ($field['decimals'] ?? 2) : 2,
            'required' => (bool) ($field['required'] ?? false),
            'value' => old('input_data.'.$key, $input[$key] ?? ''),
        ];
    }
    $unitSuffix = ($version?->unit === 'percentage') ? '%' : '';
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.planeacion.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Captura dinámica: campos de la versión del mes, métricas en vivo y reemplazo confirmado. --}}
    <div
        class="page-section planeacion-capture-page"
        x-data="planeacionCaptureForm(@js([
            'fields' => $fields,
            'formula' => $version->formula ?? '',
            'operator' => $version->target_operator ?? '>=',
            'target' => (float) ($version->target_value ?? 0),
            'unitSuffix' => $unitSuffix,
            'periodClosed' => $periodClosed,
            'hasExisting' => $existingCapture !== null,
            'monthLabel' => $monthName,
            'year' => $year,
        ]))"
        @submit.capture="onSubmit($event)"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="cursos-registros-page__table-toolbar">
                <p class="req-manage-filters__meta">
                    <strong>{{ $indicator->code }}</strong>
                    <span>· {{ $version?->name ?? 'Sin versión para este mes' }}</span>
                </p>
                @php
                    $fichaExportQuery = array_filter([
                        'year' => $year,
                        'month' => $month,
                        'capturador_id' => $ficha['capturador_id'] ?? auth()->id(),
                    ], fn ($value) => $value !== null && $value !== '');
                @endphp
                <div class="cursos-registros-page__table-actions">
                    <a href="{{ route('calidad.planeacion.captura') }}" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Volver al listado" aria-label="Volver al listado">
                        <x-lucide-x width="18" height="18" aria-hidden="true" />
                    </a>
                    <x-export-excel
                        route="{{ route('calidad.planeacion.indicadores.export.excel', array_merge(['indicator' => $indicator], $fichaExportQuery)) }}"
                        label=""
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Exportar ficha a Excel"
                        aria-label="Exportar ficha a Excel"
                    />
                    <a
                        href="{{ route('calidad.planeacion.indicadores.export.pdf', array_merge(['indicator' => $indicator], $fichaExportQuery)) }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Exportar ficha a PDF"
                        aria-label="Exportar ficha a PDF"
                    >
                        <x-lucide-file-text width="18" height="18" aria-hidden="true" />
                    </a>
                </div>
            </div>

            {{-- Selector de año/mes: recarga la versión y la captura existentes. --}}
            <div class="panel">
                <div class="panel__body panel__body--compact">
                    <form method="GET" action="{{ route('calidad.planeacion.captura.show', $indicator) }}" class="planeacion-capture-period">
                        <div class="form-field">
                            <label class="form-label" for="capture_year">Año</label>
                            <x-searchable-select
                                id="capture_year"
                                name="year"
                                :options="$yearOptions"
                                :value="(string) $year"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="capture_month">Mes</label>
                            <x-searchable-select
                                id="capture_month"
                                name="month"
                                :options="$monthOptions"
                                :value="(string) $month"
                                :allow-clear="false"
                            />
                        </div>
                        <div class="form-field cursos-registros-page__filter-actions">
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Ver periodo" aria-label="Ver periodo">
                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if ($periodClosed || $version === null)
                <div class="alert alert--warning">{{ $periodMessage ?: 'Este periodo no admite captura.' }}</div>
            @endif

            @if ($version)
                <form
                    method="POST"
                    action="{{ route('calidad.planeacion.captura.store', $indicator) }}"
                    class="form-stack js-planeacion-capture-form"
                    x-ref="captureForm"
                >
                    @csrf
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="replace_confirmed" value="0" x-ref="replaceConfirmed">

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Campos de captura · v{{ $version->version_number }}</h3>
                            <p class="panel-text">Fórmula: <code>{{ $formulaDisplay }}</code></p>
                        </div>
                        <div class="panel__body">
                            <div class="planeacion-capture-fields">
                                <template x-for="field in fields" :key="field.key">
                                    <div class="form-field">
                                        <label class="form-label" :for="'input_' + field.key">
                                            <span x-text="field.label"></span>
                                            <span x-show="field.required"> *</span>
                                        </label>
                                        <input
                                            class="form-input"
                                            type="number"
                                            :step="field.decimals === 0 ? '1' : '0.01'"
                                            :id="'input_' + field.key"
                                            :name="'input_data[' + field.key + ']'"
                                            :required="field.required"
                                            :disabled="periodClosed || ! {{ $canSave ? 'true' : 'false' }}"
                                            x-model="field.value"
                                            @input="compute()"
                                        >
                                    </div>
                                </template>
                            </div>
                            <p class="planeacion-formula__error" x-show="inputError" x-cloak x-text="inputError"></p>
                        </div>
                    </div>

                    @include('areas.calidad.planeacion.partials.capture-metrics')

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Análisis del periodo</h3>
                        </div>
                        <div class="panel__body form-stack">
                            <div class="form-field">
                                <x-input-label for="analysis_text" value="Análisis" />
                                <textarea
                                    id="analysis_text"
                                    name="analysis_text"
                                    class="form-input"
                                    rows="3"
                                    @disabled($periodClosed || ! $canSave)
                                >{{ old('analysis_text', $existingCapture?->analysis_text) }}</textarea>
                            </div>
                            <div class="form-field" x-show="! complies" x-cloak>
                                <x-input-label for="improvement_required" value="Plan de mejora *" />
                                <textarea
                                    id="improvement_required"
                                    name="improvement_required"
                                    class="form-input"
                                    rows="3"
                                    x-bind:required="! complies"
                                    @disabled($periodClosed || ! $canSave)
                                >{{ old('improvement_required', $existingCapture?->improvement_required) }}</textarea>
                                <p class="req-form__hint">Obligatorio cuando el resultado no cumple la meta.</p>
                            </div>
                        </div>
                    </div>

                    @if ($canSave)
                        <div class="req-form-actions">
                            <p class="req-form-actions__note">
                                El resultado se calcula con la fórmula publicada. Una división entre cero no se guarda.
                            </p>
                            <div class="req-form-actions__group">
                                <a
                                    href="{{ route('calidad.planeacion.captura') }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cancelar"
                                    aria-label="Cancelar"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Guardar captura"
                                    aria-label="Guardar captura"
                                >
                                    <x-lucide-save width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    @endif
                </form>
            @endif

            @include('areas.calidad.planeacion.partials.ficha-gestion', [
                'indicator' => $indicator,
                'ficha' => $ficha,
            ])
        </div>
    </div>

    <x-confirm-replace-modal />

    @vite(['resources/js/planeacion-ficha-charts.js'])

    <script>
        window.planeacionCaptureForm = function (initial) {
            return {
                fields: initial.fields || [],
                formula: initial.formula || '',
                operator: initial.operator || '>=',
                target: Number(initial.target || 0),
                unitSuffix: initial.unitSuffix || '',
                periodClosed: Boolean(initial.periodClosed),
                hasExisting: Boolean(initial.hasExisting),
                monthLabel: initial.monthLabel || '',
                year: initial.year,
                result: null,
                complies: true,
                inputError: '',
                init() {
                    this.compute();
                },
                compute() {
                    this.inputError = '';
                    const values = {};
                    this.fields.forEach((field) => {
                        const raw = field.value;
                        if (raw === '' || raw === null || typeof raw === 'undefined') {
                            return;
                        }
                        values[field.key] = Number(raw);
                    });
                    try {
                        const result = this.evaluate(this.formula, values);
                        this.result = result;
                        this.complies = this.matchesTarget(result);
                    } catch (error) {
                        this.result = null;
                        this.complies = false;
                        this.inputError = error.message || 'No se pudo calcular el resultado.';
                    }
                },
                matchesTarget(result) {
                    const target = this.target;
                    switch (this.operator) {
                        case '>=': return result >= target;
                        case '<=': return result <= target;
                        case '>': return result > target;
                        case '<': return result < target;
                        case '=':
                        case '==': return Math.abs(result - target) < 0.0001;
                        default: return false;
                    }
                },
                formattedResult() {
                    if (this.result === null || ! Number.isFinite(this.result)) {
                        return '—';
                    }
                    return this.result.toLocaleString('es-CO', { maximumFractionDigits: 4 }) + this.unitSuffix;
                },
                onSubmit(event) {
                    const form = event.target;
                    if (! (form instanceof HTMLFormElement) || ! form.classList.contains('js-planeacion-capture-form')) {
                        return;
                    }
                    if (this.periodClosed) {
                        event.preventDefault();
                        return;
                    }
                    this.compute();
                    if (this.inputError) {
                        event.preventDefault();
                        return;
                    }
                    if (this.hasExisting && form.dataset.replaceArmed !== '1') {
                        event.preventDefault();
                        window.dispatchEvent(new CustomEvent('open-confirm-replace', {
                            detail: {
                                form,
                                title: 'Confirmar reemplazo',
                                message: 'Ya existe una captura de ' + this.monthLabel + ' ' + this.year + '. Si continúa, se reemplazarán los datos actuales.',
                            },
                        }));
                    }
                },
                evaluate(formula, values) {
                    const tokens = this.tokenize(formula);
                    const rpn = this.toRpn(tokens);
                    return this.evalRpn(rpn, values);
                },
                tokenize(formula) {
                    const tokens = [];
                    let i = 0;
                    const text = String(formula || '');
                    while (i < text.length) {
                        const char = text[i];
                        if (/\s/.test(char)) {
                            i += 1;
                            continue;
                        }
                        if ((char >= '0' && char <= '9') || (char === '.' && i + 1 < text.length && text[i + 1] >= '0' && text[i + 1] <= '9')) {
                            let raw = '';
                            while (i < text.length && ((text[i] >= '0' && text[i] <= '9') || text[i] === '.')) {
                                raw += text[i];
                                i += 1;
                            }
                            tokens.push({ type: 'number', value: raw });
                            continue;
                        }
                        if (char >= 'a' && char <= 'z') {
                            let ident = '';
                            while (i < text.length && /[a-z0-9_]/.test(text[i])) {
                                ident += text[i];
                                i += 1;
                            }
                            tokens.push({ type: 'ident', value: ident });
                            continue;
                        }
                        if (char === '(' || char === ')') {
                            tokens.push({ type: char === '(' ? 'lparen' : 'rparen', value: char });
                            i += 1;
                            continue;
                        }
                        if ('+-*/'.includes(char)) {
                            const prev = tokens[tokens.length - 1];
                            const unary = char === '-' && (! prev || prev.type === 'op' || prev.type === 'lparen');
                            tokens.push({ type: 'op', value: unary ? 'u-' : char });
                            i += 1;
                            continue;
                        }
                        throw new Error('La fórmula tiene un carácter no permitido.');
                    }
                    if (! tokens.length) {
                        throw new Error('La fórmula está vacía.');
                    }
                    return tokens;
                },
                toRpn(tokens) {
                    const output = [];
                    const ops = [];
                    const prec = { 'u-': 4, '*': 3, '/': 3, '+': 2, '-': 2 };
                    const rightAssoc = { 'u-': true };
                    tokens.forEach((token) => {
                        if (token.type === 'number' || token.type === 'ident') {
                            output.push(token);
                            return;
                        }
                        if (token.type === 'lparen') {
                            ops.push(token);
                            return;
                        }
                        if (token.type === 'rparen') {
                            let found = false;
                            while (ops.length) {
                                const top = ops.pop();
                                if (top.type === 'lparen') {
                                    found = true;
                                    break;
                                }
                                output.push(top);
                            }
                            if (! found) {
                                throw new Error('Los paréntesis de la fórmula no están balanceados.');
                            }
                            return;
                        }
                        while (ops.length) {
                            const top = ops[ops.length - 1];
                            if (top.type !== 'op') {
                                break;
                            }
                            const shouldPop = rightAssoc[token.value]
                                ? prec[token.value] < prec[top.value]
                                : prec[token.value] <= prec[top.value];
                            if (! shouldPop) {
                                break;
                            }
                            output.push(ops.pop());
                        }
                        ops.push(token);
                    });
                    while (ops.length) {
                        const top = ops.pop();
                        if (top.type === 'lparen' || top.type === 'rparen') {
                            throw new Error('Los paréntesis de la fórmula no están balanceados.');
                        }
                        output.push(top);
                    }
                    return output;
                },
                evalRpn(rpn, values) {
                    const stack = [];
                    rpn.forEach((token) => {
                        if (token.type === 'number') {
                            stack.push(Number(token.value));
                            return;
                        }
                        if (token.type === 'ident') {
                            if (! Object.prototype.hasOwnProperty.call(values, token.value)) {
                                throw new Error('Complete el campo «' + token.value + '».');
                            }
                            stack.push(Number(values[token.value]));
                            return;
                        }
                        if (token.value === 'u-') {
                            if (! stack.length) {
                                throw new Error('La fórmula está mal formada.');
                            }
                            stack.push(-1 * stack.pop());
                            return;
                        }
                        if (stack.length < 2) {
                            throw new Error('La fórmula está mal formada.');
                        }
                        const right = stack.pop();
                        const left = stack.pop();
                        if (token.value === '/') {
                            if (Math.abs(right) < 1e-12) {
                                throw new Error('No se puede dividir entre cero.');
                            }
                            stack.push(left / right);
                            return;
                        }
                        if (token.value === '+') {
                            stack.push(left + right);
                            return;
                        }
                        if (token.value === '-') {
                            stack.push(left - right);
                            return;
                        }
                        if (token.value === '*') {
                            stack.push(left * right);
                        }
                    });
                    if (stack.length !== 1) {
                        throw new Error('La fórmula está mal formada.');
                    }
                    return stack[0];
                },
            };
        };
    </script>
</x-app-layout>
