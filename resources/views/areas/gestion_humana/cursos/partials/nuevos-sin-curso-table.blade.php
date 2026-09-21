{{-- Variables: $pendingRows (Collection of EmployeeCursoPending) --}}
<div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
    @include('partials.data-table-loader')
    <table
        class="data-table js-datatable"
        data-dt-responsive="false"
        data-dt-compact="true"
        data-dt-body-scroll="true"
        data-order='[[2, "desc"]]'
        style="width:100%"
    >
        <thead>
            <tr>
                <th>CEDULA</th>
                <th>NOMBRE COMPLETO</th>
                <th>ENCOLADO</th>
                <th data-orderable="false">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pendingRows as $pending)
                <tr>
                    <td>{{ $pending->document_number }}</td>
                    <td>{{ $pending->full_name ?: ($pending->employeeFichaProfile?->full_name ?: '—') }}</td>
                    <td data-order="{{ optional($pending->enqueued_at)?->timestamp ?? 0 }}">
                        {{ optional($pending->enqueued_at)?->format('Y-m-d H:i') ?: '—' }}
                    </td>
                    <td class="table-actions">
                        <div class="cursos-registros-page__row-actions">
                            <button
                                type="button"
                                class="btn btn--primary btn--sm"
                                title="Agregar curso"
                                aria-label="Agregar curso"
                                x-on:click="openCreateFromPending({
                                    document_number: @js($pending->document_number),
                                    full_name: @js($pending->full_name ?: ($pending->employeeFichaProfile?->full_name ?: '')),
                                })"
                            >
                                Agregar curso
                            </button>
                            <button
                                type="button"
                                class="btn btn--secondary btn--sm"
                                title="No aplica / omitir"
                                aria-label="No aplica / omitir"
                                x-on:click="openOmit(@js([
                                    'id' => $pending->id,
                                    'document_number' => $pending->document_number,
                                    'full_name' => $pending->full_name ?: ($pending->employeeFichaProfile?->full_name ?: ''),
                                    'omit_url' => route('gestion-humana.cursos.registros.pendientes.omit', $pending),
                                ]))"
                            >
                                Omitir
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="panel-text">No hay personas nuevas sin curso en la cola.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
