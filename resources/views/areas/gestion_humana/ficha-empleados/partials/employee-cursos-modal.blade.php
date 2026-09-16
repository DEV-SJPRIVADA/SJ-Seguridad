{{-- Variables: $entry, $employeeCursos, $canViewEmployeeCursos --}}
@if ($canViewEmployeeCursos ?? false)
    <x-modal name="ficha-employee-cursos" maxWidth="2xl" :show="false" focusable>
        <div class="modal-card ficha-empleados-masivos-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-graduation-cap width="20" height="20" aria-hidden="true" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Cursos del empleado</h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            {{ $entry->hired_full_name }} — {{ $entry->hired_document }}
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'ficha-employee-cursos')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="table-responsive">
                <table class="data-table" style="width:100%">
                    <thead>
                        <tr>
                            <th>TIPO CURSO</th>
                            <th>FECHA</th>
                            <th>No.CURSO</th>
                            <th>VIGENCIA</th>
                            <th>ESTADO</th>
                            <th>DOCUMENTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeCursos as $curso)
                            @php
                                $vigencia = $curso->computeVigencia();
                                $vigenciaClass = $vigencia === 'VIGENTE'
                                    ? 'status-pill status-pill--success'
                                    : 'status-pill status-pill--danger';
                            @endphp
                            <tr>
                                <td>{{ $curso->cursoTipo?->tipo_curso ?: '—' }}</td>
                                <td>{{ optional($curso->fecha_expedicion)?->format('Y-m-d') }}</td>
                                <td>{{ $curso->numero_curso }}</td>
                                <td><span class="{{ $vigenciaClass }}">{{ $vigencia }}</span></td>
                                <td>{{ $curso->estado ?: '—' }}</td>
                                <td>
                                    @if ($curso->hasDocument())
                                        <a
                                            class="btn btn--secondary btn--sm"
                                            href="{{ route('gestion-humana.ficha-empleados.employees.cursos.document', [$entry, $curso]) }}"
                                        >Descargar</a>
                                    @else
                                        <span class="text-muted">Sin archivo</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Este empleado no tiene cursos registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-modal>
@endif
