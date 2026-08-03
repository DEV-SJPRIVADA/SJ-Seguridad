@can('approve', $purchaseRequest)
    <div class="block-spaced-lg">
        <article class="req-approval-letter">
            <h4 class="req-approval-letter__title">Autorizacion de solicitud</h4>

            <p class="req-approval-letter__lead">
                Revise los datos y registre su decision. Al aprobar, la solicitud pasa a la bandeja de Compras.
            </p>

            <form
                method="POST"
                action="{{ route('purchase-requests.approval.update', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                class="req-approval-letter__form"
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

                <div class="req-approval-letter__actions">
                    <button type="submit" name="estado" value="aprobado" class="btn btn--primary">
                        Aprobar solicitud
                    </button>
                    <button type="submit" name="estado" value="rechazado" class="btn btn--danger">
                        Rechazar
                    </button>
                </div>

                <div class="req-approval-letter__alt-actions">
                    <a href="{{ route('purchase-requests.approval.index', ['module' => $module]) }}" class="req-approval-letter__back">
                        Volver al listado de pendientes
                    </a>
                </div>
            </form>
        </article>
    </div>
@endcan
