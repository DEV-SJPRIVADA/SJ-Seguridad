{{-- Variables: $pendingRows (Collection of EmployeeCursoPending) --}}
<div class="data-table-wrap req-manage-shell__table cursos-registros-page__table-wrap data-table-wrap--booting">
    @include('partials.data-table-loader')
    <table
        class="data-table js-datatable"
        data-dt-responsive="false"
        data-dt-compact="true"
        data-dt-body-scroll="true"
        data-order='[[3, "desc"]]'
        style="width:100%"
    >
        <thead>
            <tr>
                <th>Cédula</th>
                <th>Nombre completo</th>
                <th>Cargo</th>
                <th>Encolado</th>
                <th data-orderable="false">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pendingRows as $pending)
                @php
                    $cargoFicha = trim((string) ($pending->employeeFichaProfile?->position_name ?? ''));
                @endphp
                <tr>
                    <td>{{ $pending->document_number }}</td>
                    <td>{{ $pending->full_name ?: ($pending->employeeFichaProfile?->full_name ?: '—') }}</td>
                    <td>{{ $cargoFicha !== '' ? $cargoFicha : '—' }}</td>
                    <td data-order="{{ optional($pending->enqueued_at)?->timestamp ?? 0 }}">
                        {{ optional($pending->enqueued_at)?->format('Y-m-d H:i') ?: '—' }}
                    </td>
                    <td class="table-actions">
                        <div class="cursos-registros-page__row-actions">
                            <button
                                type="button"
                                class="cursos-catalogo-page__icon-btn"
                                title="Agregar curso"
                                aria-label="Agregar curso"
                                data-document-number="{{ $pending->document_number }}"
                                data-full-name="{{ $pending->full_name ?: ($pending->employeeFichaProfile?->full_name ?: '') }}"
                                x-on:click="openCreateFromPending({
                                    document_number: $el.dataset.documentNumber || '',
                                    full_name: $el.dataset.fullName || '',
                                })"
                            >
                                <x-lucide-plus width="16" height="16" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                title="No aplica / omitir"
                                aria-label="No aplica / omitir"
                                data-omit-id="{{ $pending->id }}"
                                data-document-number="{{ $pending->document_number }}"
                                data-full-name="{{ $pending->full_name ?: ($pending->employeeFichaProfile?->full_name ?: '') }}"
                                data-omit-url="{{ route('gestion-humana.cursos.registros.pendientes.omit', $pending) }}"
                                x-on:click="openOmit({
                                    id: Number($el.dataset.omitId || 0),
                                    document_number: $el.dataset.documentNumber || '',
                                    full_name: $el.dataset.fullName || '',
                                    omit_url: $el.dataset.omitUrl || '',
                                })"
                            >
                                <x-lucide-ban width="16" height="16" aria-hidden="true" />
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="panel-text">No hay personas nuevas sin curso en la cola.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
