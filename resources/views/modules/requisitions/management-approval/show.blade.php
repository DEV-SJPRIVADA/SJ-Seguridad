<x-app-layout>
    <x-slot name="header">
        @include('modules.requisitions.partials.subnav', ['moduleLabel' => $moduleLabel, 'subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <article class="req-approval-letter">
                <h1 class="req-approval-letter__title">Autorizacion de requisicion (cargo nuevo)</h1>

                <p class="req-approval-letter__lead">
                    Se registro una solicitud de personal que <strong>requiere autorizacion de gerencia</strong> antes de que Gestion humana continúe el proceso.
                </p>

                <div class="req-approval-letter__panel">
                    <p><strong>Codigo:</strong> {{ $requisition->code }}</p>
                    <p><strong>Motivo:</strong> {{ $requisition->requestReason?->name ?? '—' }}</p>
                    <p><strong>Cargo:</strong> {{ $requisition->position?->name ?? '—' }}</p>
                    <p><strong>Solicitante:</strong> {{ $requisition->requester?->name ?? '—' }}</p>
                    <p><strong>Area solicitante:</strong> {{ config('access.areas.' . $requisition->requesting_area_key) ?? $requisition->requesting_area_key }}</p>
                    <p><strong>Estado:</strong> {{ $statusLabels[$requisition->status] ?? $requisition->status }}</p>
                    @if ($requisition->request_date)
                        <p><strong>Fecha solicitud:</strong> {{ $requisition->request_date->format('d/m/Y') }}</p>
                    @endif
                </div>

                <div class="req-approval-letter__panel req-approval-letter__panel--muted">
                    <p><strong>Cliente:</strong> {{ $requisition->client?->name ?? '—' }}</p>
                    <p><strong>Ciudad:</strong> {{ $requisition->city?->name ?? '—' }}</p>
                    @if ($requisition->clientType?->name)
                        <p><strong>Tipo de cliente:</strong> {{ $requisition->clientType->name }}</p>
                    @endif
                    @if ($requisition->programmingType?->name)
                        <p><strong>Programacion:</strong> {{ $requisition->programmingType->name }}</p>
                    @endif
                    @if ($requisition->uniform?->name)
                        <p><strong>Dotacion:</strong> {{ $requisition->uniform->name }}</p>
                    @endif
                    <p><strong>Perfil requerido:</strong> {{ $requisition->required_profile }}</p>
                    @if (filled($requisition->service_structure))
                        <p><strong>Estructura del servicio:</strong> {{ $requisition->service_structure }}</p>
                    @endif
                    @if ($requisition->requester_observation)
                        <p><strong>Observacion del solicitante:</strong> {{ $requisition->requester_observation }}</p>
                    @endif
                </div>

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

                    <div class="req-approval-letter__alt-actions">
                        <a href="{{ route('requisitions.management-approval.index', ['module' => $moduleKey]) }}" class="req-approval-letter__back">
                            Volver al listado de pendientes
                        </a>
                    </div>
                </form>

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
