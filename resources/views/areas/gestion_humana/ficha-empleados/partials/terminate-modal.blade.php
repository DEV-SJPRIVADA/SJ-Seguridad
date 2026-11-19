{{-- Variables: $entry, $catalogs, $canTerminate, $show --}}
@php
    // El bloque de aprobación del cruce se muestra si el envío anterior lo detectó o si
    // el usuario ya había marcado la casilla; el modal además lo revela en vivo al elegir fechas.
    $approvalOpenOnLoad = $errors->has('novedades_conflict_ack')
        || $errors->has('novedades_conflict_reason')
        || (bool) old('novedades_conflict_ack');
@endphp
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

            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.ficha.terminate', $entry) }}"
                class="ficha-empleados-terminate-modal__form"
                x-data="fichaTerminateNovedades({
                    url: @js(route('gestion-humana.ficha-empleados.employees.ficha.terminate.novedades', $entry)),
                    approvalOpenOnLoad: @js($errors->has('novedades_conflict_ack') || $errors->has('novedades_conflict_reason') || (bool) old('novedades_conflict_ack')),
                    acknowledged: @js((bool) old('novedades_conflict_ack')),
                    reason: @js((string) old('novedades_conflict_reason')),
                })"
            >
                @csrf

                <div class="form-grid form-grid--two ficha-empleados-form__grid">
                    <div class="form-field form-grid__full">
                        <label class="form-label" for="termination_cause_code">Causal <span class="text-danger">*</span></label>
                        <x-searchable-select
                            id="termination_cause_code"
                            name="termination_cause_code"
                            :options="collect($catalogs['termination_cause'] ?? [])->map(fn($item) => ['value' => $item['code'], 'label' => $item['name']])->all()"
                            :value="old('termination_cause_code')"
                            placeholder="— Seleccione causal —"
                            searchPlaceholder="Buscar causal…"
                            :required="true"
                        />
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="last_work_day">Último día de trabajo <span class="text-danger">*</span></label>
                        <input id="last_work_day" type="date" name="last_work_day" class="form-input" value="{{ old('last_work_day') }}" required>
                    </div>

                    <div class="form-field">
                        <label class="form-label" for="termination_date">Fecha de desvinculación <span class="text-danger">*</span></label>
                        <input id="termination_date" type="date" name="termination_date" class="form-input" value="{{ old('termination_date') }}" required>
                    </div>

                    <div class="form-field form-grid__full">
                        <label class="form-label" for="is_rehireable">Recontratable <span class="text-danger">*</span></label>
                        <x-searchable-select
                            id="is_rehireable"
                            name="is_rehireable"
                            :options="[
                                ['value' => '1', 'label' => 'Sí — puede reingresar por requisición'],
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

                    {{-- Aviso en vivo: el modal consulta el cruce con MT-GH-04 al elegir las fechas --}}
                    <div class="form-field form-grid__full" x-show="hasConflicts" x-cloak>
                        <div class="alert alert--warning ficha-empleados-terminate-modal__alert" role="alert">
                            <p><strong>Esta persona tiene novedades que se cruzan con el retiro:</strong></p>
                            <ul class="ficha-empleados-form__error-list">
                                <template x-for="conflict in conflicts" :key="conflict.sheet + '-' + conflict.id">
                                    <li x-text="conflict.detail"></li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Aprobación del cruce con MT-GH-04: ya no bloquea, pero queda auditado quién aprobó --}}
                    <div class="form-field form-grid__full" x-show="hasConflicts || approvalOpenOnLoad" x-cloak>
                        <label class="ficha-empleados-terminate-modal__force" for="novedades_conflict_ack">
                            <input
                                type="checkbox"
                                id="novedades_conflict_ack"
                                name="novedades_conflict_ack"
                                value="1"
                                class="form-check"
                                x-model="acknowledged"
                            >
                            <span class="ficha-empleados-terminate-modal__force-copy">
                                <span class="ficha-empleados-terminate-modal__force-title">Apruebo la desvinculación con novedades vigentes</span>
                                <span class="ficha-empleados-terminate-modal__force-hint">Marque esta casilla si el retiro se cruza con vacaciones, incapacidad o permiso y confirma el caso con GH/Nómina. La aprobación queda registrada en auditoría con su usuario.</span>
                            </span>
                        </label>
                    </div>

                    <div class="form-field form-grid__full" x-show="hasConflicts || approvalOpenOnLoad" x-cloak>
                        <label class="form-label" for="novedades_conflict_reason">Motivo de la aprobación <span class="text-danger">*</span></label>
                        <textarea
                            id="novedades_conflict_reason"
                            name="novedades_conflict_reason"
                            class="form-input supply-textarea"
                            rows="2"
                            maxlength="500"
                            placeholder="Explique por qué se desvincula con novedades vigentes"
                            x-model="reason"
                        >{{ old('novedades_conflict_reason') }}</textarea>
                        <p class="form-hint">Se guarda en auditoría junto con las novedades detectadas.</p>
                    </div>
                </div>

                <div class="ficha-empleados-terminate-modal__actions">
                    <button type="button" class="btn btn--secondary" x-on:click="$dispatch('close-modal', 'ficha-terminate')">Cancelar</button>
                    <button type="submit" class="btn btn--primary">Confirmar desvinculación</button>
                </div>
            </form>
        </div>
    </x-modal>
@endif
