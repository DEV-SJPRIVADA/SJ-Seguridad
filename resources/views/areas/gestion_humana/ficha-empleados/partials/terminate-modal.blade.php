{{-- Variables: $entry, $catalogs, $canTerminate, $show --}}
@if ($canTerminate ?? false)
    <x-modal name="ficha-terminate" maxWidth="lg" :show="$show ?? false" focusable>
        <div class="modal-card ficha-empleados-terminate-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Desvinculación</h3>
                        <p class="ficha-empleados-masivos-modal__lead">{{ $entry->hired_full_name }} — {{ $entry->hired_document }}</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'ficha-terminate')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            @if ($errors->any() && ($show ?? false))
                <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                    <ul class="ficha-empleados-form__error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry) }}" class="ficha-empleados-terminate-modal__form">
                @csrf

                <div class="form-grid form-grid--two ficha-empleados-form__grid">
                    <div class="form-field form-grid__full">
                        <label class="form-label" for="termination_cause_code">Causal <span class="text-danger">*</span></label>
                        <x-searchable-select
                            id="termination_cause_code"
                            name="termination_cause_code"
                            :options="collect($catalogs['termination_cause'] ?? [])->map(fn($item) => ['value' => $item['code'], 'label' => $item['code'] . ' — ' . $item['name']])->all()"
                            :value="old('termination_cause_code')"
                            placeholder="— Seleccione causal —"
                            searchPlaceholder="Buscar causal…"
                            :required="true"
                        />
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="last_work_day">Ultimo dia de trabajo <span class="text-danger">*</span></label>
                        <input id="last_work_day" type="date" name="last_work_day" class="form-input" value="{{ old('last_work_day') }}" required>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="termination_date">Fecha de desvinculacion <span class="text-danger">*</span></label>
                        <input id="termination_date" type="date" name="termination_date" class="form-input" value="{{ old('termination_date') }}" required>
                    </div>

                    <div class="form-field form-grid__full">
                        <label class="form-label" for="is_rehireable">Recontratable <span class="text-danger">*</span></label>
                        <x-searchable-select
                            id="is_rehireable"
                            name="is_rehireable"
                            :options="[
                                ['value' => '1', 'label' => 'Si — puede reingresar por requisicion'],
                                ['value' => '0', 'label' => 'No'],
                            ]"
                            :value="old('is_rehireable', '1')"
                            placeholder="Seleccione…"
                            :required="true"
                            :allowClear="false"
                        />
                    </div>

                    <div class="form-field form-grid__full">
                        <label class="form-label" for="termination_notes">Observaciones</label>
                        <textarea id="termination_notes" name="termination_notes" class="form-input supply-textarea" rows="3" maxlength="1000">{{ old('termination_notes') }}</textarea>
                    </div>
                </div>

                <div class="ficha-empleados-terminate-modal__actions">
                    <button type="button" class="btn btn--secondary" x-on:click="$dispatch('close-modal', 'ficha-terminate')">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Confirmar desvinculacion</button>
                </div>
            </form>
        </div>
    </x-modal>
@endif
