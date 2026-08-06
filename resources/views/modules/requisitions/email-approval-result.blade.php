<x-guest-layout>
    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Decision registrada</h1>

                <div class="alert {{ $action === 'approve' ? 'alert--success' : 'alert--warning' }} ficha-empleados-page__alert">
                    @if ($action === 'approve')
                        La requisicion <strong>{{ $requisition->code }}</strong> fue <strong>autorizada</strong> correctamente. Gestion humana puede continuar el proceso.
                    @else
                        La requisicion <strong>{{ $requisition->code }}</strong> fue <strong>rechazada</strong>.
                    @endif
                </div>

                @include('modules.requisitions.partials.management-approval-details', [
                    'requisition' => $requisition,
                    'statusLabels' => $statusLabels,
                ])

                <p class="req-approval-letter__hint">
                    @if ($action === 'reject')
                        El solicitante recibira un correo con el resultado.
                    @endif
                    Puede cerrar esta ventana.
                </p>

                <p class="req-approval-letter__signoff">
                    Atentamente,<br>
                    <strong>{{ config('app.name') }}</strong>
                </p>
            </article>
        </div>
    </div>
</x-guest-layout>
