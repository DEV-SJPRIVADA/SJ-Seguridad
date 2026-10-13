{{-- Consulta de psicofísicos / psicosensométricos (MT-ST-04) desde la ficha (solo lectura). --}}
@if ($canViewEmployeePsicofisicos ?? false)
    <x-modal name="ficha-employee-psicofisicos" maxWidth="5xl" :show="false" focusable>
        <div class="modal-card ficha-empleados-consult-modal">
            <div class="ficha-empleados-consult-modal__header">
                <div class="ficha-empleados-consult-modal__heading">
                    <span class="ficha-empleados-consult-modal__heading-icon" aria-hidden="true">
                        <x-lucide-heart-pulse width="18" height="18" aria-hidden="true" />
                    </span>
                    <div class="ficha-empleados-consult-modal__heading-copy">
                        <div class="ficha-empleados-consult-modal__title-row">
                            <h3 class="ficha-empleados-consult-modal__title">Psicofísicos del empleado</h3>
                            <span class="ficha-empleados-consult-modal__count" title="Total de registros">
                                {{ number_format($employeePsicofisicos->count()) }}
                            </span>
                        </div>
                        <p class="ficha-empleados-consult-modal__lead">
                            {{ $entry->hired_full_name }} — {{ $entry->hired_document }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-consult-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'ficha-employee-psicofisicos')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            {{-- Scroll horizontal + vertical: columnas psicofísico y psicosensométrico --}}
            <div class="ficha-empleados-consult-modal__table-wrap" role="region" aria-label="Listado de psicofísicos" tabindex="0">
                <table class="data-table ficha-empleados-consult-modal__table ficha-empleados-consult-modal__table--wide">
                    <thead>
                        <tr>
                            <th>Arma</th>
                            <th>Fecha examen</th>
                            <th>Vencimiento</th>
                            <th>Apto</th>
                            <th>Estado psicofísico</th>
                            <th>Observaciones</th>
                            <th>Examen vial</th>
                            <th>Venc. vial</th>
                            <th>Estado vial</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeePsicofisicos as $registro)
                            @php
                                $estado1Class = match ($registro->estado_1) {
                                    \App\Models\MtSt04Registro::ESTADO_VIGENTE => 'status-pill status-pill--success',
                                    \App\Models\MtSt04Registro::ESTADO_VENCERA => 'status-pill status-pill--warning',
                                    \App\Models\MtSt04Registro::ESTADO_VENCIDO => 'status-pill status-pill--danger',
                                    default => 'status-pill status-pill--muted',
                                };
                                $estado2Class = match ($registro->estado_2) {
                                    \App\Models\MtSt04Registro::ESTADO_VIGENTE => 'status-pill status-pill--success',
                                    \App\Models\MtSt04Registro::ESTADO_VENCERA => 'status-pill status-pill--warning',
                                    \App\Models\MtSt04Registro::ESTADO_VENCIDO => 'status-pill status-pill--danger',
                                    default => 'status-pill status-pill--muted',
                                };
                            @endphp
                            <tr>
                                <td class="ficha-empleados-consult-modal__cell--primary">{{ $registro->arma ?: '—' }}</td>
                                <td><x-date-table :value="$registro->fecha_examen_1" /></td>
                                <td><x-date-table :value="$registro->fecha_vencimiento_1" /></td>
                                <td>{{ $registro->apto ?: '—' }}</td>
                                <td>
                                    @if ($registro->estado_1)
                                        <span class="{{ $estado1Class }}">{{ $registro->estado_1 }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="ficha-empleados-consult-modal__cell--wrap">{{ $registro->observaciones_1 ?: '—' }}</td>
                                <td><x-date-table :value="$registro->fecha_examen_2" /></td>
                                <td><x-date-table :value="$registro->fecha_vencimiento_2" /></td>
                                <td>
                                    @if ($registro->estado_2)
                                        <span class="{{ $estado2Class }}">{{ $registro->estado_2 }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="ficha-empleados-consult-modal__empty">
                                    Este empleado no tiene registros de psicofísicos (MT-ST-04).
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-modal>
@endif
