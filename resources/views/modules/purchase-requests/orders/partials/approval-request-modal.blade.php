{{-- Modal: solicitar aprobación opcional de la OC (Compras). --}}
<x-modal name="purchase-order-request-approval" maxWidth="lg" :show="$show ?? false" focusable>
    <div class="modal-card ficha-empleados-masivos-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-badge-check width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Solicitar aprobación</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Opcional. El aprobador decide por correo o en Autorizaciones compras. Si la rechaza, la orden queda anulada.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                title="Cerrar"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'purchase-order-request-approval')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->has('aprobacion_aprobador_id') || $errors->has('aprobacion_nota'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                <ul class="mb-0">
                    @foreach ($errors->get('aprobacion_aprobador_id') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    @foreach ($errors->get('aprobacion_nota') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('purchase-requests.orders.approval.request', ['module' => $module, 'purchase_order' => $order->getKey()]) }}"
            class="ficha-empleados-masivos-modal__body form-stack"
        >
            @csrf
            <div class="form-field">
                <label class="form-label" for="aprobacion_aprobador_id">Aprobador (director o administrador)</label>
                <x-searchable-select
                    id="aprobacion_aprobador_id"
                    name="aprobacion_aprobador_id"
                    :options="$approverOptions"
                    :value="old('aprobacion_aprobador_id', '')"
                    placeholder="Seleccione el aprobador"
                    searchPlaceholder="Buscar…"
                    required
                />
            </div>
            <div class="form-field">
                <label class="form-label" for="aprobacion_nota">Nota para el aprobador (opcional)</label>
                <textarea
                    id="aprobacion_nota"
                    name="aprobacion_nota"
                    class="form-textarea"
                    rows="3"
                    maxlength="5000"
                    placeholder="Contexto para quien aprueba…"
                >{{ old('aprobacion_nota') }}</textarea>
            </div>
            <div class="ficha-empleados-masivos-modal__footer">
                <button
                    type="button"
                    class="btn btn--ghost"
                    x-on:click="$dispatch('close-modal', 'purchase-order-request-approval')"
                >
                    Cancelar
                </button>
                <button type="submit" class="btn btn--primary">
                    <x-lucide-send width="16" height="16" aria-hidden="true" />
                    Solicitar aprobación
                </button>
            </div>
        </form>
    </div>
</x-modal>
