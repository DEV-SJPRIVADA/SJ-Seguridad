<div class="indicadores-subpanel block-spaced">
    <h4 class="indicadores-subpanel__title">Tipos de notificacion</h4>
    <p class="indicadores-subpanel__text">
        Asigne que correos del catalogo reciben cada tipo de aviso. Los correos deben existir en la categoria
        <strong>Correos de notificacion</strong>.
    </p>

    @foreach ($notificationTypes as $notificationType)
        <div class="panel block-spaced" style="margin-top: 1rem;">
            <div class="panel__header">
                <h4 class="panel-title" style="font-size: 1rem;">{{ $notificationType['label'] }}</h4>
                @if ($notificationType['description'])
                    <p class="panel-text">{{ $notificationType['description'] }}</p>
                @endif
            </div>
            <div class="panel__body">
                <form method="POST" action="{{ route('requisitions.notification-types.sync', ['module' => $moduleKey]) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="type_slug" value="{{ $notificationType['slug'] }}">

                    @php
                        $assignedIds = $notificationType['email_ids'];
                    @endphp

                    @if ($notificationEmailOptions->isEmpty())
                        <p class="text-muted">Agregue correos en la categoria Correos de notificacion primero.</p>
                    @else
                        <div class="checkbox-grid" style="display: grid; gap: 0.5rem;">
                            @foreach ($notificationEmailOptions as $emailOption)
                                <label class="checkbox-label" style="display: flex; align-items: center; gap: 0.5rem;">
                                    <input
                                        type="checkbox"
                                        name="email_ids[]"
                                        value="{{ $emailOption->id }}"
                                        @checked(in_array($emailOption->id, $assignedIds, true))
                                        @disabled(! $emailOption->is_active && ! in_array($emailOption->id, $assignedIds, true))
                                    >
                                    <span>{{ $emailOption->name }}</span>
                                    @unless ($emailOption->is_active)
                                        <span class="status-pill status-pill--muted">Inactivo</span>
                                    @endunless
                                </label>
                            @endforeach
                        </div>
                        <button type="submit" class="btn btn--primary btn--sm" style="margin-top: 1rem;">Guardar asignacion</button>
                    @endif
                </form>
            </div>
        </div>
    @endforeach
</div>
