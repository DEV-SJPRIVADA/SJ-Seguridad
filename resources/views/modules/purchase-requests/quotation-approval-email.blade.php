<x-guest-layout>
    {{-- Aprobación de un grupo de cotizaciones desde el enlace firmado del correo (sin login). --}}
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Aprobación de cotizaciones</h1>

                @if ($result)
                    <div class="alert alert--{{ $result === 'aprobada' ? 'success' : 'warning' }}">
                        El grupo quedó <strong>{{ $result }}</strong>. Compras recibirá un correo con la decisión.
                    </div>
                @elseif ($alreadyResolved)
                    <div class="alert alert--info">
                        Este grupo ya no está pendiente. Estado: <strong>{{ $batch->estadoLabel() }}</strong>.
                    </div>
                @else
                    <p class="req-approval-letter__lead">
                        Compras solicita su <strong>aprobación</strong> para el grupo <strong>{{ $batch->label }}</strong>.
                        Elija una cotización ganadora o rechace el grupo completo.
                    </p>
                @endif

                @include('modules.purchase-requests.partials.quotation-approval-summary', ['batch' => $batch, 'fileUrls' => $fileUrls])

                @if ($decideUrl)
                    @include('modules.purchase-requests.partials.quotation-approval-decision', ['batch' => $batch, 'decideUrl' => $decideUrl])
                @endif

                <p class="req-approval-letter__hint">
                    Este enlace es personal y tiene vigencia limitada. También puede gestionarlo en Solicitudes de compra → Autorizaciones compras → Cotizaciones.
                </p>

                <p class="req-approval-letter__signoff">
                    Atentamente,<br>
                    <strong>{{ config('app.name') }}</strong>
                </p>
            </article>
        </div>
    </div>
</x-guest-layout>
