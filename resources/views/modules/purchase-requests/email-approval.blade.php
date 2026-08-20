<x-guest-layout>
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Autorizacion de solicitud de compra</h1>

                @if ($alreadyResolved ?? false)
                    <div class="alert alert--info ficha-empleados-page__alert">
                        Esta solicitud ya fue gestionada. Estado actual:
                        <strong>{{ $purchaseRequest->estado === \App\Models\PurchaseRequest::ESTADO_APROBADO ? 'Aprobada' : 'Rechazada' }}</strong>.
                    </div>
                @else
                    <p class="req-approval-letter__lead">
                        Se registro una solicitud de compra que <strong>requiere su autorizacion</strong> como director asignado.
                    </p>
                @endif

                <div class="req-approval-letter__panel">
                    <p><strong>Folio:</strong> {{ $purchaseRequest->folio() }}</p>
                    <p><strong>Solicitante:</strong> {{ $purchaseRequest->user?->name ?? '—' }}</p>
                    <p><strong>Area:</strong> {{ $purchaseRequest->areaLabel() ?? '—' }}</p>
                    <p><strong>Fecha:</strong> {{ optional($purchaseRequest->fecha_solicitud)->format('d/m/Y') ?? '—' }}</p>
                    <p><strong>Solicitud para:</strong> {{ $purchaseRequest->solicitud_para }}</p>
                    @if ($purchaseRequest->urgente)
                        <p><strong>Prioridad:</strong> <span class="status-pill status-pill--warning">Urgente</span></p>
                    @endif
                    <p><strong>Productos:</strong> {{ $purchaseRequest->items->count() }}</p>
                </div>

                <div class="block-spaced">
                    <table class="supply-table">
                        <thead>
                            <tr>
                                <th>Cantidad</th>
                                <th>Descripcion</th>
                                <th>Referencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($purchaseRequest->items as $item)
                                <tr>
                                    <td class="text-center">{{ $item->cantidad }}</td>
                                    <td>{{ $item->descripcion }}</td>
                                    <td>{{ $item->referencia }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if (! ($alreadyResolved ?? false))
                    <div class="req-approval-letter__alt-actions">
                        <a href="{{ $pdfUrl }}" class="req-approval-letter__back" target="_blank" rel="noopener">
                            Ver PDF (FO-AD-44)
                        </a>
                    </div>

                    <form method="POST" action="{{ $decideUrl }}" class="req-approval-letter__form">
                        @csrf

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
                    </form>
                @endif

                <p class="req-approval-letter__hint">
                    Este enlace es personal y tiene vigencia limitada. Tambien puede gestionar pendientes desde el tablero de Solicitudes de compra.
                </p>

                <p class="req-approval-letter__signoff">
                    Atentamente,<br>
                    <strong>{{ config('app.name') }}</strong>
                </p>
            </article>
        </div>
    </div>
</x-guest-layout>
