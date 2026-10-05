{{-- Consulta de acreditaciones del empleado desde la ficha (solo lectura). --}}
@if ($canViewEmployeeAcreditaciones ?? false)
    <x-modal name="ficha-employee-acreditaciones" maxWidth="5xl" :show="false" focusable>
        <div class="modal-card ficha-empleados-consult-modal">
            <div class="ficha-empleados-consult-modal__header">
                <div class="ficha-empleados-consult-modal__heading">
                    <span class="ficha-empleados-consult-modal__heading-icon" aria-hidden="true">
                        <x-lucide-badge-check width="18" height="18" aria-hidden="true" />
                    </span>
                    <div class="ficha-empleados-consult-modal__heading-copy">
                        <div class="ficha-empleados-consult-modal__title-row">
                            <h3 class="ficha-empleados-consult-modal__title">Acreditaciones del empleado</h3>
                            <span class="ficha-empleados-consult-modal__count" title="Total de registros">
                                {{ number_format($employeeAcreditaciones->count()) }}
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
                    x-on:click="$dispatch('close-modal', 'ficha-employee-acreditaciones')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            {{-- Scroll horizontal + vertical: columnas finales (observaciones) visibles --}}
            <div class="ficha-empleados-consult-modal__table-wrap" role="region" aria-label="Listado de acreditaciones" tabindex="0">
                <table class="data-table ficha-empleados-consult-modal__table ficha-empleados-consult-modal__table--wide">
                    <thead>
                        <tr>
                            <th>Cargo APO</th>
                            <th>Cargo</th>
                            <th>Estado</th>
                            <th>Vigencia</th>
                            <th>Fecha solicitud</th>
                            <th>Renovación</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeAcreditaciones as $acreditacion)
                            @php
                                $estadoClass = match ($acreditacion->estado) {
                                    \App\Models\AcreditacionAcreditado::ESTADO_ACREDITADO => 'status-pill status-pill--success',
                                    \App\Models\AcreditacionAcreditado::ESTADO_POR_VENCER => 'status-pill status-pill--warning',
                                    \App\Models\AcreditacionAcreditado::ESTADO_EN_PROCESO => 'status-pill status-pill--info',
                                    default => 'status-pill status-pill--muted',
                                };
                            @endphp
                            <tr>
                                <td class="ficha-empleados-consult-modal__cell--primary">{{ $acreditacion->cargo_apo ?: '—' }}</td>
                                <td>{{ $acreditacion->cargo ?: '—' }}</td>
                                <td>
                                    @if ($acreditacion->estado)
                                        <span class="{{ $estadoClass }}">{{ $acreditacion->estado }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td><x-date-table :value="$acreditacion->vigencia_acr" /></td>
                                <td><x-date-table :value="$acreditacion->fecha_solicitud" /></td>
                                <td>{{ $acreditacion->renovacion ?: '—' }}</td>
                                <td class="ficha-empleados-consult-modal__cell--wrap">{{ $acreditacion->observaciones ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="ficha-empleados-consult-modal__empty">
                                    Este empleado no tiene acreditaciones registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-modal>
@endif
