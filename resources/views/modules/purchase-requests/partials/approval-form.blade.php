@can('approve', $purchaseRequest)
    <div class="panel pur-req-approval-panel">
        <div class="panel__header">
            <h3 class="panel-title">Autorizacion de solicitud</h3>
            <p class="panel-text">Revise los datos y registre su decision. Al aprobar, pasa a la bandeja de Compras.</p>
        </div>
        <div class="panel__body">
            <form
                method="POST"
                action="{{ route('purchase-requests.approval.update', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                class="pur-req-approval-panel__form"
            >
                @csrf
                @method('PATCH')

                <div class="form-field">
                    <x-input-label for="comentarios_director" value="Comentarios (opcional al aprobar)" />
                    <textarea
                        id="comentarios_director"
                        name="comentarios_director"
                        class="form-textarea"
                        rows="3"
                        placeholder="Obligatorio si rechaza. Opcional al aprobar."
                    >{{ old('comentarios_director') }}</textarea>
                    <x-input-error :messages="$errors->get('comentarios_director')" />
                    <x-input-error :messages="$errors->get('estado')" />
                </div>

                <div class="pur-req-form-actions__group">
                    <button type="submit" name="estado" value="aprobado" class="btn btn--primary btn--sm">
                        Aprobar solicitud
                    </button>
                    <button type="submit" name="estado" value="rechazado" class="btn btn--danger btn--sm">
                        Rechazar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endcan
