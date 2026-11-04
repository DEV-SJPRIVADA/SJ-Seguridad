{{-- Modal: solicitar anulación de la OC (Compras elige director). --}}
<x-modal name="purchase-order-request-cancellation" maxWidth="lg" :show="$show ?? false" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-ban width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Solicitar anulación</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Si la orden quedó mal emitida. El director que elija debe aprobarla. Mientras tanto no se puede evaluar la entrega.
                        @if ($order->anulacion_respuesta && $order->anulacion_resuelta_at)
                            Última solicitud rechazada: {{ $order->anulacion_respuesta }}
                        @endif
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                title="Cerrar"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'purchase-order-request-cancellation')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->has('anulacion_aprobador_id') || $errors->has('motivo_anulacion'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                <ul class="mb-0">
                    @foreach ($errors->get('anulacion_aprobador_id') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    @foreach ($errors->get('motivo_anulacion') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('purchase-requests.orders.cancellation.request', ['module' => $module, 'purchase_order' => $order->getKey()]) }}"
            class="ficha-empleados-masivos-modal__body form-stack"
        >
            @csrf
            <div class="form-field">
                <label class="form-label" for="anulacion_aprobador_id">Director que aprueba</label>
                <x-searchable-select
                    id="anulacion_aprobador_id"
                    name="anulacion_aprobador_id"
                    :options="$approverOptions"
                    :value="old('anulacion_aprobador_id', '')"
                    placeholder="Seleccione un director"
                    searchPlaceholder="Buscar director…"
                    required
                />
            </div>
            <div class="form-field">
                <label class="form-label" for="motivo_anulacion">Motivo de la anulación</label>
                <textarea
                    id="motivo_anulacion"
                    name="motivo_anulacion"
                    class="form-textarea"
                    rows="4"
                    maxlength="5000"
                    required
                    placeholder="Indique por qué se debe anular la orden…"
                >{{ old('motivo_anulacion') }}</textarea>
            </div>
            <div class="ficha-empleados-masivos-modal__footer">
                <button
                    type="button"
                    class="btn btn--ghost"
                    x-on:click="$dispatch('close-modal', 'purchase-order-request-cancellation')"
                >
                    Cancelar
                </button>
                <button type="submit" class="btn btn--secondary">
                    <x-lucide-ban width="16" height="16" aria-hidden="true" />
                    Solicitar anulación
                </button>
            </div>
        </form>
    </div>
</x-modal>
