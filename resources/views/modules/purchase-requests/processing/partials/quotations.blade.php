{{--
    Cotizaciones de la solicitud (Compras), agrupadas por lote.
    Cada lote representa una OC futura y admite hasta 3 cotizaciones. El director elige
    una ganadora por lote. Sin cotizaciones la OC se emite igual; con un lote pendiente se bloquea.
--}}
@php
    $hayPendiente = $purchaseRequest->tieneCotizacionPendienteAprobacion();
    $puedeGestionar = $purchaseRequest->puedeEmitirOrden();
    $maxPorLote = $quotationMaxPerBatch ?? 3;
    $totalCotizaciones = $quotationBatches->sum(fn ($batch) => $batch->count());
@endphp

<section class="pur-req-form__section" id="cotizaciones">
    <header class="pur-req-form__section-head">
        <span class="pur-req-form__section-step" aria-hidden="true">
            <x-lucide-file-check-2 width="16" height="16" />
        </span>
        <div>
            <h3 class="pur-req-form__section-title">Cotizaciones</h3>
            <p class="pur-req-form__section-desc">
                Opcional. Cada grupo representa una orden de compra futura: cargue de 1 a {{ $maxPorLote }} cotizaciones
                (PDF, JPG o PNG), envíelas a aprobación de un director y, si elige una ganadora, podrá vincularla a la OC.
                Sin cotizaciones la orden se emite normalmente.
            </p>
        </div>
    </header>

    @if ($hayPendiente)
        <div class="alert alert--warning" role="status">
            <p class="alert__title">Hay grupos de cotizaciones pendientes de aprobación.</p>
            <p class="mb-0">La orden de compra se podrá emitir cuando el director resuelva cada grupo pendiente.</p>
        </div>
    @endif

    @if ($errors->has('files') || $errors->has('files.*') || $errors->has('lote_label') || $errors->has('aprobador_id') || $errors->has('purchase_quotation_id'))
        <div class="alert alert--danger" role="alert">
            <ul class="form-error-list mb-0">
                @foreach (['lote_label', 'files', 'files.*', 'descriptions.*', 'aprobador_id', 'purchase_quotation_id'] as $campo)
                    @foreach ($errors->get($campo) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                @endforeach
            </ul>
        </div>
    @endif

    @if ($puedeGestionar)
        <button
            type="button"
            class="btn btn--primary btn--sm btn-fit-content"
            x-data
            x-on:click.prevent="$dispatch('open-modal', 'quotation-batch-new')"
        >
            <x-lucide-folder-plus width="16" height="16" aria-hidden="true" />
            Cargar cotización
        </button>
    @endif

    @if ($totalCotizaciones === 0)
        <p class="text-muted" style="margin-top: 0.85rem;">Esta solicitud todavía no tiene cotizaciones cargadas.</p>
    @else
        <div class="pur-req-quotation-batches">
            @foreach ($quotationBatches as $batch)
                <article class="pur-req-quotation-batch">
                    <header class="pur-req-quotation-batch__head">
                        <div>
                            <h4 class="pur-req-quotation-batch__title">{{ $batch->label }}</h4>
                            <p class="pur-req-quotation-batch__meta">
                                {{ $batch->count() }} cotización(es)
                                @if ($batch->solicitadaAt())
                                    · Solicitado {{ $batch->solicitadaAt()->format('d/m/Y H:i') }}
                                @endif
                                @if ($batch->aprobador())
                                    · Aprobador: {{ $batch->aprobador()->name }}
                                @endif
                            </p>
                        </div>
                        <div class="pur-req-quotation-batch__actions">
                            <span class="status-pill {{ $batch->estadoPillClass() }}">{{ $batch->estadoLabel() }}</span>

                            @if ($puedeGestionar && $batch->puedeAgregarCotizacion($maxPorLote))
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Agregar cotización al grupo"
                                    aria-label="Agregar cotización al grupo"
                                    x-data
                                    x-on:click.prevent="$dispatch('open-modal', 'quotation-batch-add-{{ $batch->key }}')"
                                >
                                    <x-lucide-plus width="16" height="16" aria-hidden="true" />
                                </button>
                            @endif

                            @if ($puedeGestionar && $batch->puedeSolicitarAprobacion())
                                <button
                                    type="button"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Solicitar aprobación del grupo"
                                    aria-label="Solicitar aprobación del grupo"
                                    x-data
                                    x-on:click.prevent="$dispatch('open-modal', 'quotation-batch-request-{{ $batch->key }}')"
                                >
                                    <x-lucide-send width="16" height="16" aria-hidden="true" />
                                </button>
                            @endif

                            @if ($puedeGestionar && $batch->puedeRetirarSolicitud())
                                <form
                                    method="POST"
                                    action="{{ route('purchase-requests.quotations.approval.withdraw', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'lote' => $batch->key]) }}"
                                    style="display:inline"
                                    onsubmit="return confirm('¿Retirar la solicitud de aprobación de este grupo?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                        title="Retirar solicitud de aprobación"
                                        aria-label="Retirar solicitud de aprobación"
                                    >
                                        <x-lucide-undo-2 width="16" height="16" aria-hidden="true" />
                                    </button>
                                </form>
                            @endif
                        </div>
                    </header>

                    @if ($batch->seleccionada())
                        <p class="pur-req-quotation-batch__selected">
                            Cotización ganadora: <strong>{{ $batch->seleccionada()->descripcionVisible() }}</strong>
                            @if ($batch->seleccionada()->purchaseOrder)
                                · vinculada a OC N.º {{ $batch->seleccionada()->purchaseOrder->numeroOc }}
                            @endif
                        </p>
                    @endif

                    @if (filled($batch->comentario()))
                        <p class="pur-req-quotation-batch__comment">Decisión del aprobador: {{ $batch->comentario() }}</p>
                    @endif

                    <div class="data-table-wrap pur-req-form__table-wrap">
                        <table class="supply-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Cotización</th>
                                    <th>Archivo</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($batch->quotations as $quotation)
                                    <tr>
                                        <td class="text-center">{{ $loop->iteration }}</td>
                                        <td>
                                            {{ $quotation->descripcionVisible() }}
                                            @if ($quotation->uploadedBy)
                                                <br><span class="text-muted">Cargada por {{ $quotation->uploadedBy->name }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $quotation->original_name }}</td>
                                        <td>
                                            <span class="status-pill {{ $quotation->estadoPillClass() }}">{{ $quotation->estadoLabel() }}</span>
                                            @if ($quotation->purchaseOrder)
                                                <br><span class="text-muted">OC N.º {{ $quotation->purchaseOrder->numeroOc }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="table-actions">
                                                <a
                                                    href="{{ route('purchase-requests.quotations.file', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'purchase_quotation' => $quotation->id]) }}"
                                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                                    title="Ver archivo"
                                                    aria-label="Ver archivo"
                                                    target="_blank"
                                                    rel="noopener"
                                                >
                                                    <x-lucide-eye width="16" height="16" aria-hidden="true" />
                                                </a>

                                                @if ($puedeGestionar && $quotation->puedeEliminar())
                                                    <form
                                                        method="POST"
                                                        action="{{ route('purchase-requests.quotations.destroy', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'purchase_quotation' => $quotation->id]) }}"
                                                        style="display:inline"
                                                        onsubmit="return confirm('¿Eliminar esta cotización?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--danger"
                                                            title="Eliminar cotización"
                                                            aria-label="Eliminar cotización"
                                                        >
                                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

@if ($puedeGestionar)
    {{-- Nuevo grupo: referencia + hasta 3 archivos. --}}
    <x-modal name="quotation-batch-new" maxWidth="lg" :show="false" focusable>
        <div class="modal-card ficha-empleados-masivos-modal">
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-folder-plus width="18" height="18" aria-hidden="true" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Cargar cotización</h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            Cada grupo de cotizaciones representa una OC futura. Use una referencia por grupo
                            (por ejemplo, “OC 1 · Asociado A”) y cargue de 1 a {{ $maxPorLote }} archivos;
                            el director elegirá una cotización ganadora.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                    title="Cerrar"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'quotation-batch-new')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <form
                method="POST"
                action="{{ route('purchase-requests.quotations.store', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                enctype="multipart/form-data"
                class="ficha-empleados-masivos-modal__body form-stack"
            >
                @csrf
                <div class="form-field">
                    <label class="form-label" for="quotation_batch_label">Referencia del grupo *</label>
                    <input
                        id="quotation_batch_label"
                        type="text"
                        name="lote_label"
                        class="form-input"
                        maxlength="120"
                        value="{{ old('lote_label') }}"
                        placeholder="Ej: OC 1 · Asociado A"
                        required
                    >
                    <p class="form-help">Identifica la orden de compra futura a la que pertenecen estas cotizaciones.</p>
                </div>

                @for ($i = 1; $i <= $maxPorLote; $i++)
                    <div class="cursos-registros-page__form-grid">
                        <div class="form-field">
                            <label class="form-label" for="quotation_file_{{ $i }}">Cotización {{ $i }}{{ $i === 1 ? ' *' : ' (opcional)' }}</label>
                            <input
                                id="quotation_file_{{ $i }}"
                                type="file"
                                name="files[{{ $i }}]"
                                class="form-input"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                @required($i === 1)
                            >
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="quotation_description_{{ $i }}">Descripción {{ $i }}</label>
                            <input
                                id="quotation_description_{{ $i }}"
                                type="text"
                                name="descriptions[{{ $i }}]"
                                class="form-input"
                                maxlength="500"
                                value="{{ old('descriptions.'.$i) }}"
                                placeholder="Ej: Proveedor A"
                            >
                        </div>
                    </div>
                @endfor

                <p class="form-help">PDF, JPG o PNG. Máximo 10 MB por archivo.</p>

                <div class="ficha-empleados-masivos-modal__footer">
                    <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'quotation-batch-new')">Cancelar</button>
                    <button type="submit" class="btn btn--primary btn--sm btn-fit-content">
                        <x-lucide-upload width="16" height="16" aria-hidden="true" />
                        Cargar grupo
                    </button>
                </div>
            </form>
        </div>
    </x-modal>

    @foreach ($quotationBatches as $batch)
        @if ($batch->puedeAgregarCotizacion($maxPorLote))
            <x-modal name="quotation-batch-add-{{ $batch->key }}" maxWidth="lg" :show="false" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-plus width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title">Agregar cotización</h3>
                                <p class="ficha-empleados-masivos-modal__lead">{{ $batch->label }} · {{ $batch->count() }} de {{ $maxPorLote }} cotizaciones.</p>
                            </div>
                        </div>
                        <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Cerrar" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'quotation-batch-add-{{ $batch->key }}')">
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('purchase-requests.quotations.store', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                        enctype="multipart/form-data"
                        class="ficha-empleados-masivos-modal__body form-stack"
                    >
                        @csrf
                        <input type="hidden" name="lote" value="{{ $batch->key }}">

                        <div class="form-field">
                            <label class="form-label" for="quotation_add_file_{{ $batch->key }}">Archivo *</label>
                            <input
                                id="quotation_add_file_{{ $batch->key }}"
                                type="file"
                                name="files[1]"
                                class="form-input"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                required
                            >
                            <p class="form-help">PDF, JPG o PNG. Máximo 10 MB.</p>
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="quotation_add_description_{{ $batch->key }}">Descripción (opcional)</label>
                            <input
                                id="quotation_add_description_{{ $batch->key }}"
                                type="text"
                                name="descriptions[1]"
                                class="form-input"
                                maxlength="500"
                                placeholder="Ej: Proveedor B"
                            >
                        </div>

                        <div class="ficha-empleados-masivos-modal__footer">
                            <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'quotation-batch-add-{{ $batch->key }}')">Cancelar</button>
                            <button type="submit" class="btn btn--primary btn--sm btn-fit-content">
                                <x-lucide-upload width="16" height="16" aria-hidden="true" />
                                Agregar cotización
                            </button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endif

        @if ($batch->puedeSolicitarAprobacion())
            <x-modal name="quotation-batch-request-{{ $batch->key }}" maxWidth="lg" :show="false" focusable>
                <div class="modal-card ficha-empleados-masivos-modal">
                    <div class="ficha-empleados-masivos-modal__header">
                        <div class="ficha-empleados-masivos-modal__heading">
                            <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                                <x-lucide-file-check-2 width="18" height="18" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="ficha-empleados-masivos-modal__title">Solicitar aprobación del grupo</h3>
                                <p class="ficha-empleados-masivos-modal__lead">
                                    {{ $batch->label }} · {{ $batch->count() }} cotización(es).
                                    El director decide por correo o en Autorizaciones compras → Cotizaciones.
                                    Mientras esté pendiente no se podrá emitir la OC.
                                </p>
                            </div>
                        </div>
                        <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost" title="Cerrar" aria-label="Cerrar" x-on:click="$dispatch('close-modal', 'quotation-batch-request-{{ $batch->key }}')">
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('purchase-requests.quotations.approval.request', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'lote' => $batch->key]) }}"
                        class="ficha-empleados-masivos-modal__body form-stack"
                    >
                        @csrf
                        <div class="form-field">
                            <label class="form-label" for="quotation_aprobador_{{ $batch->key }}">Aprobador (director o administrador)</label>
                            <x-searchable-select
                                id="quotation_aprobador_{{ $batch->key }}"
                                name="aprobador_id"
                                :options="$approverOptions"
                                :value="old('aprobador_id', '')"
                                placeholder="Seleccione el aprobador"
                                searchPlaceholder="Buscar…"
                                required
                            />
                        </div>
                        <div class="ficha-empleados-masivos-modal__footer">
                            <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'quotation-batch-request-{{ $batch->key }}')">Cancelar</button>
                            <button type="submit" class="btn btn--primary btn--sm btn-fit-content">
                                <x-lucide-send width="16" height="16" aria-hidden="true" />
                                Solicitar aprobación
                            </button>
                        </div>
                    </form>
                </div>
            </x-modal>
        @endif
    @endforeach
@endif
