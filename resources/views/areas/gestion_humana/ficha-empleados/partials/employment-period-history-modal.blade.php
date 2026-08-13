{{-- Variables: $employmentHistory, $canGenerateLetters, $supportedLetterCauses --}}
@if ($employmentHistory->isNotEmpty())
    <x-modal name="ficha-employment-history" maxWidth="2xl">
        <div class="modal-card ficha-empleados-history-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-icon name="history" :size="20" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Historial de vinculos</h3>
                        <p class="ficha-empleados-masivos-modal__lead">Contratos anteriores y vinculo actual del empleado.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'ficha-employment-history')"
                >
                    <x-lucide-icon name="x" :size="18" />
                </button>
            </div>

            <div class="data-table-wrap ficha-empleados-periods-table">
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Estado</th>
                            <th>Requisicion</th>
                            <th>Ingreso</th>
                            <th>Cargo</th>
                            <th>Cliente</th>
                            <th>Ultimo dia</th>
                            <th>Desvinculacion</th>
                            <th>Causal</th>
                            <th>Recontratable</th>
                            @if ($canGenerateLetters ?? false)
                                <th>Cartas</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employmentHistory as $period)
                            <tr>
                                <td>{{ $period->sequence }}</td>
                                <td>
                                    <span class="status-pill {{ $period->isActive() ? 'status-pill--req-contratado' : 'status-pill--req-cancelada' }}">
                                        {{ $period->isActive() ? 'Activo' : 'Cerrado' }}
                                    </span>
                                </td>
                                <td>{{ $period->requisition?->code ?? '—' }}</td>
                                <td><x-date-table :value="$period->hire_date" /></td>
                                <td>{{ $period->position_name ?: '—' }}</td>
                                <td>{{ $period->work_center_name ?: '—' }}</td>
                                <td><x-date-table :value="$period->last_work_day" /></td>
                                <td><x-date-table :value="$period->termination_date" /></td>
                                <td>{{ $period->termination_cause_name ?: '—' }}</td>
                                <td>
                                    @if ($period->isActive())
                                        —
                                    @elseif ($period->is_rehireable)
                                        Si
                                    @else
                                        No
                                    @endif
                                </td>
                                @if ($canGenerateLetters ?? false)
                                    <td>
                                        @if (
                                            ! $period->isActive()
                                            && in_array((string) $period->termination_cause_code, $supportedLetterCauses ?? [], true)
                                        )
                                            @include('areas.gestion_humana.ficha-empleados.partials.termination-letter-actions', [
                                                'period' => $period,
                                                'canGenerateLetters' => true,
                                                'compact' => true,
                                            ])
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </x-modal>
@endif
