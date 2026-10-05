{{-- Consulta de cursos del empleado desde la ficha (solo lectura). --}}
@if ($canViewEmployeeCursos ?? false)
    <x-modal name="ficha-employee-cursos" maxWidth="4xl" :show="false" focusable>
        <div class="modal-card ficha-empleados-consult-modal">
            <div class="ficha-empleados-consult-modal__header">
                <div class="ficha-empleados-consult-modal__heading">
                    <span class="ficha-empleados-consult-modal__heading-icon" aria-hidden="true">
                        <x-lucide-graduation-cap width="18" height="18" aria-hidden="true" />
                    </span>
                    <div class="ficha-empleados-consult-modal__heading-copy">
                        <div class="ficha-empleados-consult-modal__title-row">
                            <h3 class="ficha-empleados-consult-modal__title">Cursos del empleado</h3>
                            <span class="ficha-empleados-consult-modal__count" title="Total de registros">
                                {{ number_format($employeeCursos->count()) }}
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
                    x-on:click="$dispatch('close-modal', 'ficha-employee-cursos')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            {{-- Scroll horizontal + vertical para ver todas las columnas --}}
            <div class="ficha-empleados-consult-modal__table-wrap" role="region" aria-label="Listado de cursos" tabindex="0">
                <table class="data-table ficha-empleados-consult-modal__table">
                    <thead>
                        <tr>
                            <th>Tipo de curso</th>
                            <th>Fecha</th>
                            <th>N.º curso</th>
                            <th>Vigencia</th>
                            <th>Estado</th>
                            <th>Documento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employeeCursos as $curso)
                            @php
                                $vigencia = $curso->computeVigencia();
                                $vigenciaClass = match ($vigencia) {
                                    \App\Models\EmployeeCurso::VIGENCIA_VIGENTE => 'status-pill status-pill--success',
                                    \App\Models\EmployeeCurso::VIGENCIA_ACTUALIZAR => 'status-pill status-pill--warning',
                                    default => 'status-pill status-pill--danger',
                                };
                            @endphp
                            <tr>
                                <td class="ficha-empleados-consult-modal__cell--primary">{{ $curso->cursoTipo?->tipo_curso ?: '—' }}</td>
                                <td><x-date-table :value="$curso->fecha_expedicion" /></td>
                                <td>{{ $curso->numero_curso ?: '—' }}</td>
                                <td><span class="{{ $vigenciaClass }}">{{ $vigencia }}</span></td>
                                <td>{{ $curso->estado ?: '—' }}</td>
                                <td>
                                    @if ($curso->hasDocument())
                                        <a
                                            class="cursos-catalogo-page__icon-btn"
                                            href="{{ route('gestion-humana.ficha-empleados.employees.cursos.document', [$entry, $curso]) }}"
                                            title="Descargar documento"
                                            aria-label="Descargar documento del curso"
                                        >
                                            <x-lucide-download width="16" height="16" aria-hidden="true" />
                                        </a>
                                    @else
                                        <span class="text-muted">Sin archivo</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="ficha-empleados-consult-modal__empty">
                                    Este empleado no tiene cursos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-modal>
@endif
