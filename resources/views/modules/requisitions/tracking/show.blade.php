<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Detalle de requisicion</h1>

                <div class="alert alert--info ficha-empleados-page__alert">
                    Estado actual:
                    <strong>{{ $statusLabels[$requisition->status] ?? $requisition->status }}</strong>.
                </div>

                @include('modules.requisitions.partials.management-approval-details', [
                    'requisition' => $requisition,
                    'statusLabels' => $statusLabels,
                ])

                @include('modules.requisitions.partials.management-approval-decision', [
                    'requisition' => $requisition,
                    'statusLabels' => $statusLabels,
                ])

                <div class="req-approval-letter__alt-actions">
                    <a href="{{ route('requisitions.tracking', ['module' => $moduleKey]) }}" class="req-approval-letter__back">
                        Volver a Mis requisiciones
                    </a>
                </div>
            </article>
        </div>
    </div>
</x-app-layout>
