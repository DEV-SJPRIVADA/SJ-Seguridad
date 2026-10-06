{{-- Variables: $employmentHistory, $canGenerateLetters, $canShowClosedLetterActions, $canManage, $activePeriod, $canGenerateContratacionLetters --}}
@if ($employmentHistory->isNotEmpty())
    <x-modal name="ficha-employment-history" maxWidth="2xl">
        <div class="modal-card ficha-empleados-history-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-history width="18" height="18" aria-hidden="true" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Historial de vínculos</h3>
                        <p class="ficha-empleados-masivos-modal__lead">Contratos anteriores y vínculo actual del empleado.</p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'ficha-employment-history')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="data-table-wrap ficha-empleados-periods-table">
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Estado</th>
                            <th>Requisición</th>
                            <th>Ingreso</th>
                            <th>Cargo</th>
                            <th>Cliente</th>
                            <th>Último día</th>
                            <th>Desvinculación</th>
                            <th>Causal</th>
                            <th>Recontratable</th>
                            @if (($canGenerateLetters ?? false) || ($canShowClosedLetterActions ?? false))
                                <th>Cartas</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($employmentHistory as $period)
                            <tr>
                                <td>{{ $period->sequence }}</td>
                                <td>{{ $period->isActive() ? 'Activo' : 'Cerrado' }}</td>
                                <td>{{ $period->requisition?->code ?? '—' }}</td>
                                <td>{{ optional($period->hire_date)->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $period->position_name ?? '—' }}</td>
                                <td>{{ $period->client_name ?? '—' }}</td>
                                <td>{{ optional($period->last_work_day)->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ optional($period->termination_date)->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $period->termination_cause_name ?? $period->termination_cause_code ?? '—' }}</td>
                                <td>
                                    @if ($period->is_rehireable === null)
                                        —
                                    @elseif ($period->is_rehireable)
                                        Sí
                                    @else
                                        No
                                    @endif
                                </td>
                                @if (($canGenerateLetters ?? false) || ($canShowClosedLetterActions ?? false))
                                    <td>
                                        @if (! $period->isActive())
                                            @include('areas.gestion_humana.ficha-empleados.partials.termination-letter-actions', [
                                                'period' => $period,
                                                'canGenerateLetters' => $canGenerateLetters ?? false,
                                                'canShowClosedLetterActions' => true,
                                                'canManage' => $canManage ?? false,
                                                'activePeriod' => $activePeriod ?? null,
                                                'canGenerateContratacionLetters' => $canGenerateContratacionLetters ?? false,
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
