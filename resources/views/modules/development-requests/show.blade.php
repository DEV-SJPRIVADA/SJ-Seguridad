@php
    $fromKey = (string) ($from ?? 'mis_solicitudes');
    $backUrl = match ($fromKey) {
        'tic_queue' => route('development-requests.tic-queue', ['module' => $module]),
        'leader_approval' => route('development-requests.leader-approval', ['module' => $module]),
        default => route('development-requests.my-requests', ['module' => $module]),
    };
    $backLabel = match ($fromKey) {
        'tic_queue' => 'Volver a bandeja TIC',
        'leader_approval' => 'Volver a aprobación líder',
        default => 'Volver a mis solicitudes',
    };
    $statusPill = match ($developmentRequest->status) {
        \App\Models\DevelopmentRequest::STATUS_ENTREGADO,
        \App\Models\DevelopmentRequest::STATUS_CERRADO => 'status-pill--success',
        \App\Models\DevelopmentRequest::STATUS_RECHAZADO,
        \App\Models\DevelopmentRequest::STATUS_DEVUELTO => 'status-pill--danger',
        \App\Models\DevelopmentRequest::STATUS_PENDIENTE_APROBACION_LIDER,
        \App\Models\DevelopmentRequest::STATUS_EN_PRUEBAS => 'status-pill--warning',
        \App\Models\DevelopmentRequest::STATUS_BORRADOR => 'status-pill--muted',
        default => 'status-pill--info',
    };
    $detailBlocks = [
        ['title' => 'Descripción general', 'value' => $developmentRequest->description],
        ['title' => 'Cómo se hace hoy y cuál es el problema', 'value' => $developmentRequest->current_process_problem],
        ['title' => 'Qué necesita que haga el sistema', 'value' => $developmentRequest->desired_steps],
        ['title' => 'Quién usará esto', 'value' => $developmentRequest->users_description],
        ['title' => 'Qué no se debería permitir', 'value' => $developmentRequest->restrictions],
        ['title' => 'Alcance: qué SÍ incluye', 'value' => $developmentRequest->scope_in],
        ['title' => 'Fuera de alcance', 'value' => $developmentRequest->scope_out],
        ['title' => 'Criterio de aceptación', 'value' => $developmentRequest->acceptance_criteria],
        ['title' => 'Reportes o indicadores', 'value' => $developmentRequest->reports],
        ['title' => 'Justificación de urgencia / fecha', 'value' => $developmentRequest->desired_date_justification],
        ['title' => 'Notas adicionales', 'value' => $developmentRequest->notes],
    ];
    $hasAsideActions = $canLeaderDecide || ($canProcessTic && count($allowedTransitions) > 0) || $canUat;
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page development-requests-page--detail">
        <div class="app-container development-requests-page__stack">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner development-requests-page__intro">
                <div class="dev-req-detail__toolbar">
                    <a href="{{ $backUrl }}" class="dev-req-detail__back">
                        <x-lucide-arrow-left width="16" height="16" aria-hidden="true" />
                        {{ $backLabel }}
                    </a>
                    @if (in_array($developmentRequest->status, ['borrador', 'devuelto'], true)
                        && (int) $developmentRequest->created_by === (int) auth()->id())
                        <a
                            href="{{ route('development-requests.edit', ['module' => $module, 'development_request' => $developmentRequest]) }}"
                            class="btn btn--secondary btn--sm"
                        >Editar</a>
                    @endif
                </div>
                <h2 class="page-title">{{ $developmentRequest->code ?: 'Sin codigo' }}</h2>
                <p class="page-subtitle">{{ $developmentRequest->title }}</p>
                <div class="dev-req-detail__pills">
                    <span class="status-pill {{ $statusPill }}">{{ $developmentRequest->estadoLabel() }}</span>
                    <span class="status-pill status-pill--muted">{{ $developmentRequest->tipoLabel() }}</span>
                    <span class="status-pill status-pill--info">{{ $developmentRequest->prioridadLabel() }}</span>
                </div>
            </div>

            <div class="dev-req-detail-layout">
                <aside class="dev-req-detail-layout__rail dev-req-detail-layout__rail--history" aria-label="Historial y conversación">
                    <section class="dev-req-form__section dev-req-detail__history-panel">
                        <header class="dev-req-form__section-head">
                            <span class="dev-req-form__section-step" aria-hidden="true">
                                <x-lucide-history width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="dev-req-form__section-title">Historial de estados</h3>
                                <p class="dev-req-form__section-desc">Transiciones con usuario y comentario.</p>
                            </div>
                        </header>

                        <ol class="dev-req-timeline dev-req-timeline--scroll">
                            @forelse ($developmentRequest->statusLogs as $log)
                                <li class="dev-req-timeline__item">
                                    <div class="dev-req-timeline__marker" aria-hidden="true"></div>
                                    <div class="dev-req-timeline__content">
                                        <p class="dev-req-timeline__title">
                                            {{ $log->from_status ? (\App\Models\DevelopmentRequest::estadosLabels()[$log->from_status] ?? $log->from_status) : 'Inicio' }}
                                            →
                                            {{ \App\Models\DevelopmentRequest::estadosLabels()[$log->to_status] ?? $log->to_status }}
                                        </p>
                                        <p class="dev-req-timeline__meta">
                                            <x-date-table :value="$log->created_at" datetime />
                                            @if ($log->user) · {{ $log->user->name }} @endif
                                        </p>
                                        @if ($log->comment)
                                            <p class="dev-req-timeline__comment">{{ $log->comment }}</p>
                                        @endif
                                    </div>
                                </li>
                            @empty
                                <li class="dev-req-timeline__empty">Sin historial.</li>
                            @endforelse
                        </ol>
                    </section>

                    <section class="dev-req-form__section development-requests-page__panel" id="conversation">
                        <header class="dev-req-form__section-head">
                            <span class="dev-req-form__section-step" aria-hidden="true">
                                <x-lucide-messages-square width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="dev-req-form__section-title">Conversacion</h3>
                                <p class="dev-req-form__section-desc">Los participantes reciben correo al publicar un mensaje.</p>
                            </div>
                        </header>

                        <div class="dev-req-chat">
                            @forelse ($developmentRequest->messages as $message)
                                <article class="dev-req-chat__message">
                                    <div class="dev-req-chat__avatar" aria-hidden="true">
                                        {{ strtoupper(substr($message->user?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="dev-req-chat__bubble">
                                        <div class="dev-req-chat__head">
                                            <strong>{{ $message->user?->name ?? 'Usuario' }}</strong>
                                            <span class="text-caption"><x-date-table :value="$message->created_at" datetime /></span>
                                        </div>
                                        <p class="dev-req-chat__body">{{ $message->body }}</p>
                                    </div>
                                </article>
                            @empty
                                <p class="dev-req-form__section-desc">Aun no hay mensajes.</p>
                            @endforelse
                        </div>

                        @if ($canComment)
                            <form
                                method="POST"
                                action="{{ route('development-requests.messages.store', ['module' => $module, 'development_request' => $developmentRequest, 'from' => $from]) }}"
                                class="dev-req-chat__composer"
                            >
                                @csrf
                                <div class="form-field">
                                    <label class="form-label" for="body">Nuevo mensaje</label>
                                    <textarea name="body" id="body" class="form-textarea" rows="3" required maxlength="5000">{{ old('body') }}</textarea>
                                    <x-input-error :messages="$errors->get('body')" />
                                </div>
                                <button type="submit" class="btn btn--primary">Enviar mensaje</button>
                            </form>
                        @endif
                    </section>
                </aside>

                <div class="dev-req-detail-layout__main">
                    <div class="dev-req-form__meta">
                        <div class="dev-req-form__meta-item">
                            <span class="dev-req-form__meta-label">Área</span>
                            <span class="dev-req-form__meta-value">{{ $developmentRequest->areaLabel() }}</span>
                        </div>
                        <div class="dev-req-form__meta-item">
                            <span class="dev-req-form__meta-label">Solicitante</span>
                            <span class="dev-req-form__meta-value">{{ $developmentRequest->requester_name }}</span>
                        </div>
                        <div class="dev-req-form__meta-item">
                            <span class="dev-req-form__meta-label">Correo</span>
                            <span class="dev-req-form__meta-value">{{ $developmentRequest->requester_email }}</span>
                        </div>
                        <div class="dev-req-form__meta-item">
                            <span class="dev-req-form__meta-label">Lider</span>
                            <span class="dev-req-form__meta-value">{{ $developmentRequest->leader?->name ?: '—' }}</span>
                        </div>
                        @if ($developmentRequest->requester_position)
                            <div class="dev-req-form__meta-item">
                                <span class="dev-req-form__meta-label">Cargo</span>
                                <span class="dev-req-form__meta-value">{{ $developmentRequest->requester_position }}</span>
                            </div>
                        @endif
                        @if ($developmentRequest->requester_phone)
                            <div class="dev-req-form__meta-item">
                                <span class="dev-req-form__meta-label">Teléfono</span>
                                <span class="dev-req-form__meta-value">{{ $developmentRequest->requester_phone }}</span>
                            </div>
                        @endif
                        @if ($developmentRequest->proceso_sede)
                            <div class="dev-req-form__meta-item">
                                <span class="dev-req-form__meta-label">Proceso / sede</span>
                                <span class="dev-req-form__meta-value">{{ $developmentRequest->proceso_sede }}</span>
                            </div>
                        @endif
                        @if ($developmentRequest->associated_norm)
                            <div class="dev-req-form__meta-item">
                                <span class="dev-req-form__meta-label">Norma / formato</span>
                                <span class="dev-req-form__meta-value">{{ $developmentRequest->associated_norm }}</span>
                            </div>
                        @endif
                        @if ($developmentRequest->desired_date)
                            <div class="dev-req-form__meta-item">
                                <span class="dev-req-form__meta-label">Fecha deseada</span>
                                <span class="dev-req-form__meta-value"><x-date-table :value="$developmentRequest->desired_date" /></span>
                            </div>
                        @endif
                        @if ($developmentRequest->assignedProgrammer)
                            <div class="dev-req-form__meta-item">
                                <span class="dev-req-form__meta-label">Programador</span>
                                <span class="dev-req-form__meta-value">{{ $developmentRequest->assignedProgrammer->name }}</span>
                            </div>
                        @endif
                    </div>

                    <section class="dev-req-form__section">
                        <header class="dev-req-form__section-head">
                            <span class="dev-req-form__section-step" aria-hidden="true">
                                <x-lucide-file-text width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="dev-req-form__section-title">Detalle FO-TIC-23</h3>
                                <p class="dev-req-form__section-desc">Contenido funcional de la solicitud.</p>
                            </div>
                        </header>

                        <div class="dev-req-detail__blocks">
                            @foreach ($detailBlocks as $block)
                                @continue(blank($block['value']))
                                <article class="dev-req-detail__block">
                                    <h4 class="dev-req-detail__block-title">{{ $block['title'] }}</h4>
                                    <p class="dev-req-detail__block-body">{{ $block['value'] }}</p>
                                </article>
                            @endforeach
                        </div>

                        @if ($developmentRequest->attachments->isNotEmpty())
                            <div class="dev-req-form__attachments">
                                <p class="form-label">Anexos</p>
                                <ul class="dev-req-form__attachment-list">
                                    @foreach ($developmentRequest->attachments as $att)
                                        <li class="dev-req-form__attachment-item">
                                            <x-lucide-file width="16" height="16" aria-hidden="true" />
                                            <a href="{{ route('development-requests.attachments.download', ['module' => $module, 'development_request' => $developmentRequest, 'attachment' => $att]) }}">
                                                {{ $att->original_name }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </section>

                    @if ($developmentRequest->tic_viability || $developmentRequest->assignedProgrammer || $developmentRequest->tic_analysis_notes || $developmentRequest->tic_risks || $developmentRequest->closure_notes)
                        <section class="dev-req-form__section">
                            <header class="dev-req-form__section-head">
                                <span class="dev-req-form__section-step" aria-hidden="true">
                                    <x-lucide-cpu width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="dev-req-form__section-title">Bloque TIC</h3>
                                    <p class="dev-req-form__section-desc">Información interna de análisis y planificación.</p>
                                </div>
                            </header>

                            <div class="dev-req-form__meta">
                                @if ($developmentRequest->assignedProgrammer)
                                    <div class="dev-req-form__meta-item">
                                        <span class="dev-req-form__meta-label">Programador</span>
                                        <span class="dev-req-form__meta-value">{{ $developmentRequest->assignedProgrammer->name }}</span>
                                    </div>
                                @endif
                                @if ($developmentRequest->tic_viability)
                                    <div class="dev-req-form__meta-item">
                                        <span class="dev-req-form__meta-label">Viabilidad</span>
                                        <span class="dev-req-form__meta-value">{{ ucfirst($developmentRequest->tic_viability) }}</span>
                                    </div>
                                @endif
                                @if ($developmentRequest->tic_confirmed_priority)
                                    <div class="dev-req-form__meta-item">
                                        <span class="dev-req-form__meta-label">Prioridad TIC</span>
                                        <span class="dev-req-form__meta-value">{{ \App\Models\DevelopmentRequest::prioridadesLabels()[$developmentRequest->tic_confirmed_priority] ?? $developmentRequest->tic_confirmed_priority }}</span>
                                    </div>
                                @endif
                                @if ($developmentRequest->tic_complexity)
                                    <div class="dev-req-form__meta-item">
                                        <span class="dev-req-form__meta-label">Complejidad</span>
                                        <span class="dev-req-form__meta-value">{{ ucfirst($developmentRequest->tic_complexity) }}</span>
                                    </div>
                                @endif
                                @if ($developmentRequest->tic_estimated_date)
                                    <div class="dev-req-form__meta-item">
                                        <span class="dev-req-form__meta-label">Fecha estimada</span>
                                        <span class="dev-req-form__meta-value"><x-date-table :value="$developmentRequest->tic_estimated_date" /></span>
                                    </div>
                                @endif
                                <div class="dev-req-form__meta-item">
                                    <span class="dev-req-form__meta-label">FO-GE-12</span>
                                    <span class="dev-req-form__meta-value">{{ $developmentRequest->requires_fo_ge_12 ? 'Si' : 'No' }}</span>
                                </div>
                                <div class="dev-req-form__meta-item">
                                    <span class="dev-req-form__meta-label">Analisis extendido</span>
                                    <span class="dev-req-form__meta-value">{{ $developmentRequest->requires_extended_analysis ? 'Si' : 'No' }}</span>
                                </div>
                            </div>

                            @foreach ([
                                'Riesgos' => $developmentRequest->tic_risks,
                                'Notas de análisis' => $developmentRequest->tic_analysis_notes,
                                'Notas de cierre' => $developmentRequest->closure_notes,
                            ] as $label => $value)
                                @continue(blank($value))
                                <article class="dev-req-detail__block">
                                    <h4 class="dev-req-detail__block-title">{{ $label }}</h4>
                                    <p class="dev-req-detail__block-body">{{ $value }}</p>
                                </article>
                            @endforeach
                        </section>
                    @endif
                </div>

                <aside class="dev-req-detail-layout__rail dev-req-detail-layout__rail--side" aria-label="Acciones y conversación">
                    <div class="dev-req-detail-layout__actions">
                    @if (! $hasAsideActions)
                        <section class="dev-req-aside-card">
                            <header class="dev-req-aside-card__head">
                                <span class="dev-req-aside-card__icon" aria-hidden="true">
                                    <x-lucide-clipboard-list width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="dev-req-aside-card__title">Resumen</h3>
                                    <p class="dev-req-aside-card__desc">Datos rapidos de seguimiento</p>
                                </div>
                            </header>
                            <dl class="dev-req-aside-card__facts">
                                <div class="dev-req-aside-card__fact">
                                    <dt>Estado</dt>
                                    <dd>
                                        <span class="status-pill {{ $statusPill }}">{{ $developmentRequest->estadoLabel() }}</span>
                                    </dd>
                                </div>
                                <div class="dev-req-aside-card__fact">
                                    <dt>Prioridad</dt>
                                    <dd>{{ $developmentRequest->prioridadLabel() }}</dd>
                                </div>
                                <div class="dev-req-aside-card__fact">
                                    <dt>Área</dt>
                                    <dd>{{ $developmentRequest->areaLabel() }}</dd>
                                </div>
                                @if ($developmentRequest->assignedProgrammer)
                                    <div class="dev-req-aside-card__fact">
                                        <dt>Programador</dt>
                                        <dd>{{ $developmentRequest->assignedProgrammer->name }}</dd>
                                    </div>
                                @endif
                            </dl>
                        </section>
                    @endif

                    @if ($canLeaderDecide)
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Decisión del líder</h3>
                                <p class="panel-text">Aprueba para radicar o rechaza con observacion.</p>
                            </div>
                            <div class="panel__body">
                                <form method="POST" action="{{ route('development-requests.leader.update', ['module' => $module, 'development_request' => $developmentRequest]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-field bottom-spaced">
                                        <label class="form-label" for="notes">Observación</label>
                                        <textarea name="notes" id="notes" class="form-textarea" rows="3" placeholder="Obligatoria si rechaza">{{ old('notes') }}</textarea>
                                        <x-input-error :messages="$errors->get('notes')" />
                                    </div>
                                    <div class="dev-req-form-actions__group">
                                        <button type="submit" name="decision" value="approve" class="btn btn--primary btn--sm">Aprobar y radicar</button>
                                        <button type="submit" name="decision" value="reject" class="btn btn--secondary btn--sm">Rechazar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if ($canProcessTic && count($allowedTransitions) > 0)
                        @php
                            $statusOptions = collect($allowedTransitions)->map(fn ($s) => [
                                'value' => $s,
                                'label' => \App\Models\DevelopmentRequest::estadosLabels()[$s] ?? $s,
                            ])->values()->all();
                            $viabilityOptions = [
                                ['value' => 'aceptado', 'label' => 'Aceptado'],
                                ['value' => 'devuelto', 'label' => 'Devuelto'],
                                ['value' => 'rechazado', 'label' => 'Rechazado'],
                            ];
                            $complexityOptions = [
                                ['value' => 'baja', 'label' => 'Baja'],
                                ['value' => 'media', 'label' => 'Media'],
                                ['value' => 'alta', 'label' => 'Alta'],
                            ];
                        @endphp
                        <div class="panel">
                            <form
                                method="POST"
                                action="{{ route('development-requests.tic.transition', ['module' => $module, 'development_request' => $developmentRequest]) }}"
                                class="dev-req-tic-form"
                                id="dev-req-tic-form"
                            >
                                @csrf
                                @method('PATCH')

                                <div class="panel__header panel__header--with-action">
                                    <div>
                                        <h3 class="panel-title">Gestión TIC</h3>
                                        <p class="panel-text">Transicion, asignacion y bloque interno.</p>
                                    </div>
                                    <button
                                        type="submit"
                                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                        title="Actualizar estado"
                                        aria-label="Actualizar estado"
                                    >
                                        <x-lucide-refresh-cw width="18" height="18" aria-hidden="true" />
                                    </button>
                                </div>

                                <div class="panel__body">
                                    <div class="form-field">
                                        <label class="form-label">Nuevo estado</label>
                                        <x-searchable-select
                                            name="to_status"
                                            :options="$statusOptions"
                                            :value="old('to_status', $allowedTransitions[0] ?? '')"
                                            placeholder="Seleccione estado"
                                            :required="true"
                                        />
                                        <x-input-error :messages="$errors->get('to_status')" />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label">Programador asignado</label>
                                        <x-searchable-select
                                            name="assigned_programmer_id"
                                            :options="$programmerOptions"
                                            :value="old('assigned_programmer_id', $developmentRequest->assigned_programmer_id)"
                                            placeholder="Opcional"
                                            :allowClear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label">Viabilidad TIC</label>
                                        <x-searchable-select
                                            name="tic_viability"
                                            :options="$viabilityOptions"
                                            :value="old('tic_viability', $developmentRequest->tic_viability)"
                                            placeholder="Opcional"
                                            :allowClear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label">Prioridad confirmada</label>
                                        <x-searchable-select
                                            name="tic_confirmed_priority"
                                            :options="$prioridadOptions"
                                            :value="old('tic_confirmed_priority', $developmentRequest->tic_confirmed_priority)"
                                            placeholder="Opcional"
                                            :allowClear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label">Complejidad</label>
                                        <x-searchable-select
                                            name="tic_complexity"
                                            :options="$complexityOptions"
                                            :value="old('tic_complexity', $developmentRequest->tic_complexity)"
                                            placeholder="Opcional"
                                            :allowClear="true"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="tic_estimated_date">Fecha estimada</label>
                                        <input type="date" name="tic_estimated_date" id="tic_estimated_date" class="form-input"
                                               value="{{ old('tic_estimated_date', optional($developmentRequest->tic_estimated_date)->format('Y-m-d')) }}">
                                    </div>

                                    <div class="dev-req-tic-form__checks">
                                        <label class="dev-req-tic-form__check">
                                            <input type="checkbox" name="requires_fo_ge_12" value="1" class="form-check" @checked(old('requires_fo_ge_12', $developmentRequest->requires_fo_ge_12))>
                                            Requiere FO-GE-12
                                        </label>
                                        <label class="dev-req-tic-form__check">
                                            <input type="checkbox" name="requires_extended_analysis" value="1" class="form-check" @checked(old('requires_extended_analysis', $developmentRequest->requires_extended_analysis))>
                                            Analisis extendido
                                        </label>
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="tic_risks">Riesgos</label>
                                        <textarea name="tic_risks" id="tic_risks" class="form-textarea" rows="2">{{ old('tic_risks', $developmentRequest->tic_risks) }}</textarea>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="tic_analysis_notes">Notas de análisis</label>
                                        <textarea name="tic_analysis_notes" id="tic_analysis_notes" class="form-textarea" rows="2">{{ old('tic_analysis_notes', $developmentRequest->tic_analysis_notes) }}</textarea>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="closure_notes">Notas de cierre</label>
                                        <textarea name="closure_notes" id="closure_notes" class="form-textarea" rows="2">{{ old('closure_notes', $developmentRequest->closure_notes) }}</textarea>
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="comment">Comentario de transicion</label>
                                        <textarea name="comment" id="comment" class="form-textarea" rows="2" placeholder="Obligatorio al devolver o rechazar">{{ old('comment') }}</textarea>
                                        <x-input-error :messages="$errors->get('comment')" />
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    @if ($canUat)
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">UAT</h3>
                                <p class="panel-text">Prueba de aceptación del solicitante.</p>
                            </div>
                            <div class="panel__body">
                                <form method="POST" action="{{ route('development-requests.uat.update', ['module' => $module, 'development_request' => $developmentRequest]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="form-field bottom-spaced">
                                        <label class="form-label">Resultado</label>
                                        <x-searchable-select
                                            name="uat_result"
                                            :options="[['value' => 'si', 'label' => 'Aceptado'], ['value' => 'no', 'label' => 'No aceptado']]"
                                            :value="old('uat_result')"
                                            placeholder="Seleccione"
                                            :required="true"
                                        />
                                        <x-input-error :messages="$errors->get('uat_result')" />
                                    </div>
                                    <div class="form-field bottom-spaced">
                                        <label class="form-label" for="uat_notes">Observaciones</label>
                                        <textarea name="uat_notes" id="uat_notes" class="form-textarea" rows="3">{{ old('uat_notes') }}</textarea>
                                        <x-input-error :messages="$errors->get('uat_notes')" />
                                    </div>
                                    <button type="submit" class="btn btn--primary">Registrar UAT</button>
                                </form>
                            </div>
                        </div>
                    @endif

                    <section class="dev-req-aside-card dev-req-aside-card--tips">
                        <header class="dev-req-aside-card__head">
                            <span class="dev-req-aside-card__icon dev-req-aside-card__icon--tips" aria-hidden="true">
                                <x-lucide-lightbulb width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="dev-req-aside-card__title">Recordatorio</h3>
                                <p class="dev-req-aside-card__desc">Buenas practicas al gestionar la solicitud</p>
                            </div>
                        </header>
                        <ol class="dev-req-aside-card__tips">
                            <li class="dev-req-aside-card__tip">
                                <span class="dev-req-aside-card__tip-num" aria-hidden="true">1</span>
                                <span>Usa el chat para aclaraciones; dispara correo a participantes.</span>
                            </li>
                            <li class="dev-req-aside-card__tip">
                                <span class="dev-req-aside-card__tip-num" aria-hidden="true">2</span>
                                <span>Al devolver o rechazar, deja comentario claro.</span>
                            </li>
                            <li class="dev-req-aside-card__tip">
                                <span class="dev-req-aside-card__tip-num" aria-hidden="true">3</span>
                                <span>Prioridad efectiva = confirmada TIC o sugerida.</span>
                            </li>
                        </ol>
                    </section>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
