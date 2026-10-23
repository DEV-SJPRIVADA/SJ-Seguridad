{{-- Aprobación opcional de la OC: pedirla a un director o administrador, ver el estado y retirarla. --}}
@if ($order->isAprobada())
    <div class="alert alert--success" role="status">
        <strong>Orden aprobada</strong> por {{ $order->aprobacionAprobador?->name ?? '—' }}
        @if ($order->aprobacion_resuelta_at)
            el {{ $order->aprobacion_resuelta_at->format('d/m/Y H:i') }}
        @endif
        @if ($order->aprobacion_comentario)
            · Comentario: {{ $order->aprobacion_comentario }}
        @endif
    </div>
@elseif ($order->aprobacion_estado === \App\Models\PurchaseOrder::APROBACION_RECHAZADA)
    <div class="alert alert--danger" role="status">
        <strong>Aprobación rechazada</strong> por {{ $order->aprobacionAprobador?->name ?? '—' }}; la orden quedó anulada.
        @if ($order->aprobacion_comentario)
            Motivo: {{ $order->aprobacion_comentario }}
        @endif
    </div>
@elseif ($order->tieneAprobacionPendiente())
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-hourglass width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Aprobación pendiente</h3>
                <p class="pur-req-form__section-desc">
                    Esperando la decisión de <strong>{{ $order->aprobacionAprobador?->name ?? '—' }}</strong>.
                    Pedida por {{ $order->aprobacionSolicitadaPor?->name ?? '—' }}
                    @if ($order->aprobacion_solicitada_at)
                        el {{ $order->aprobacion_solicitada_at->format('d/m/Y H:i') }}
                    @endif
                    @if ($order->aprobacion_nota)
                        · Nota: {{ $order->aprobacion_nota }}
                    @endif
                </p>
            </div>
        </header>

        @if ($canWithdrawApproval)
            <form method="POST" action="{{ route('purchase-requests.orders.approval.withdraw', ['module' => $module, 'purchase_order' => $order->getKey()]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--secondary btn--sm">Retirar solicitud de aprobación</button>
            </form>
        @endif
    </section>
@elseif ($canRequestApproval)
    <section class="pur-req-form__section">
        <header class="pur-req-form__section-head">
            <span class="pur-req-form__section-step" aria-hidden="true">
                <x-lucide-badge-check width="16" height="16" />
            </span>
            <div>
                <h3 class="pur-req-form__section-title">Solicitar aprobación</h3>
                <p class="pur-req-form__section-desc">Opcional. El aprobador decide por correo o en Autorizaciones compras. Si la rechaza, la orden queda anulada.</p>
            </div>
        </header>

        <form method="POST" action="{{ route('purchase-requests.orders.approval.request', ['module' => $module, 'purchase_order' => $order->getKey()]) }}" class="form-stack">
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
                <x-input-error :messages="$errors->get('aprobacion_aprobador_id')" />
            </div>
            <div class="form-field">
                <label class="form-label" for="aprobacion_nota">Nota para el aprobador (opcional)</label>
                <textarea
                    id="aprobacion_nota"
                    name="aprobacion_nota"
                    class="form-textarea"
                    rows="2"
                    maxlength="5000"
                >{{ old('aprobacion_nota') }}</textarea>
                <x-input-error :messages="$errors->get('aprobacion_nota')" />
            </div>
            <button type="submit" class="btn btn--primary btn--sm">Solicitar aprobación</button>
        </form>
    </section>
@endif
