<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Autorizacion de requisicion (cargo nuevo)</h1>

                @if ($isPending)
                    <p class="req-approval-letter__lead">
                        Se registro una solicitud de personal que <strong>requiere autorizacion de gerencia</strong> antes de que Gestion humana continúe el proceso.
                    </p>
                @else
                    <div class="alert alert--info ficha-empleados-page__alert">
                        Esta requisicion ya fue gestionada. Estado actual:
                        <strong>{{ $statusLabels[$requisition->status] ?? $requisition->status }}</strong>.
                    </div>
                @endif

                @include('modules.requisitions.partials.management-approval-details', [
                    'requisition' => $requisition,
                    'statusLabels' => $statusLabels,
                ])

                @if (! $isPending)
                    @include('modules.requisitions.partials.management-approval-decision', [
                        'requisition' => $requisition,
                        'statusLabels' => $statusLabels,
                    ])
                @endif

                @if ($isPending)
                    <form
                        method="POST"
                        action="{{ route('requisitions.management-approval.decide', ['module' => $moduleKey, 'requisition' => $requisition]) }}"
                        class="req-approval-letter__form"
                    >
                        @csrf

                        <p class="req-approval-letter__section-label">
                            Revise los datos y registre su decision. Al autorizar, la requisicion pasa a <strong>Solicitada</strong> y Gestion humana puede continuar.
                        </p>

                        <div class="form-field">
                            <x-input-label for="comment" value="Comentario" />
                            <textarea
                                id="comment"
                                name="comment"
                                class="form-textarea"
                                rows="3"
                                placeholder="Obligatorio si rechaza. Opcional al autorizar."
                            >{{ old('comment') }}</textarea>
                            <x-input-error :messages="$errors->get('comment')" />
                            <x-input-error :messages="$errors->get('action')" />
                        </div>

                        <div class="req-approval-letter__actions">
                            <button type="submit" name="action" value="approve" class="btn btn--primary">
                                Autorizar solicitud
                            </button>
                            <button type="submit" name="action" value="reject" class="btn btn--danger">
                                Rechazar
                            </button>
                        </div>
                    </form>
                @endif

                <div class="req-approval-letter__alt-actions">
                    <a href="{{ route('requisitions.management-approval.index', ['module' => $moduleKey]) }}" class="req-approval-letter__back">
                        Volver al listado
                    </a>
                </div>

                <p class="req-approval-letter__hint">
                    Tambien puede gestionar otras solicitudes desde <strong>Requisiciones → Autorizacion gerencia</strong> en el tablero de Gestion humana.
                </p>

                <p class="req-approval-letter__signoff">
                    Atentamente,<br>
                    <strong>{{ config('app.name') }}</strong>
                </p>
            </article>
        </div>
    </div>
</x-app-layout>
