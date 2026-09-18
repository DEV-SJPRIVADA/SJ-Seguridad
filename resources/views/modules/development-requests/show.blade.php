<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page">
        <div class="app-container development-requests-page__stack">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">{{ $developmentRequest->code ?: 'Sin codigo' }} — {{ $developmentRequest->title }}</h3>
                    <p class="panel-text">{{ $developmentRequest->estadoLabel() }} · {{ $developmentRequest->tipoLabel() }} · {{ $developmentRequest->prioridadLabel() }}</p>
                </div>
                <div class="panel__body">
                    <div class="dashboard-stat-grid">
                        <div><strong>Area:</strong> {{ $developmentRequest->areaLabel() }}</div>
                        <div><strong>Solicitante:</strong> {{ $developmentRequest->requester_name }}</div>
                        <div><strong>Correo:</strong> {{ $developmentRequest->requester_email }}</div>
                        <div><strong>Lider:</strong> {{ $developmentRequest->leader?->name }}</div>
                        @if ($developmentRequest->assignedProgrammer)
                            <div><strong>Programador:</strong> {{ $developmentRequest->assignedProgrammer->name }}</div>
                        @endif
                    </div>

                    <h4 class="panel-title" style="margin-top:1rem;">Descripcion</h4>
                    <p class="panel-text" style="white-space:pre-wrap;">{{ $developmentRequest->description }}</p>

                    <h4 class="panel-title">Problema actual</h4>
                    <p class="panel-text" style="white-space:pre-wrap;">{{ $developmentRequest->current_process_problem }}</p>

                    <h4 class="panel-title">Paso a paso deseado</h4>
                    <p class="panel-text" style="white-space:pre-wrap;">{{ $developmentRequest->desired_steps }}</p>

                    <h4 class="panel-title">Criterio de aceptacion</h4>
                    <p class="panel-text" style="white-space:pre-wrap;">{{ $developmentRequest->acceptance_criteria }}</p>

                    @if ($developmentRequest->attachments->isNotEmpty())
                        <h4 class="panel-title">Anexos</h4>
                        <ul>
                            @foreach ($developmentRequest->attachments as $att)
                                <li>
                                    <a href="{{ route('development-requests.attachments.download', ['module' => $module, 'development_request' => $developmentRequest, 'attachment' => $att]) }}">
                                        {{ $att->original_name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @if ($canLeaderDecide)
                <div class="panel">
                    <div class="panel__header"><h3 class="panel-title">Decision del lider</h3></div>
                    <div class="panel__body">
                        <form method="POST" action="{{ route('development-requests.leader.update', ['module' => $module, 'development_request' => $developmentRequest]) }}">
                            @csrf
                            @method('PATCH')
                            <div class="form-field bottom-spaced">
                                <label class="form-label" for="notes">Observacion (obligatoria si rechaza)</label>
                                <textarea name="notes" id="notes" class="form-input" rows="2">{{ old('notes') }}</textarea>
                                <x-input-error :messages="$errors->get('notes')" />
                            </div>
                            <div style="display:flex;gap:.5rem;">
                                <button type="submit" name="decision" value="approve" class="btn btn--primary">Aprobar y radicar</button>
                                <button type="submit" name="decision" value="reject" class="btn btn--secondary">Rechazar</button>
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
                    <div class="panel__header">
                        <h3 class="panel-title">Gestion TIC</h3>
                        <p class="panel-text">Bloque interno, asignacion y cambio de estado.</p>
                    </div>
                    <div class="panel__body">
                        <form method="POST" action="{{ route('development-requests.tic.transition', ['module' => $module, 'development_request' => $developmentRequest]) }}">
                            @csrf
                            @method('PATCH')

                            <div class="form-grid bottom-spaced">
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
                            </div>

                            <div class="form-field bottom-spaced" style="display:flex;gap:1.5rem;flex-wrap:wrap;">
                                <label><input type="checkbox" name="requires_fo_ge_12" value="1" @checked(old('requires_fo_ge_12', $developmentRequest->requires_fo_ge_12))> Requiere FO-GE-12</label>
                                <label><input type="checkbox" name="requires_extended_analysis" value="1" @checked(old('requires_extended_analysis', $developmentRequest->requires_extended_analysis))> Analisis extendido</label>
                            </div>

                            <div class="form-field bottom-spaced">
                                <label class="form-label" for="tic_risks">Riesgos</label>
                                <textarea name="tic_risks" id="tic_risks" class="form-input" rows="2">{{ old('tic_risks', $developmentRequest->tic_risks) }}</textarea>
                            </div>
                            <div class="form-field bottom-spaced">
                                <label class="form-label" for="tic_analysis_notes">Notas de analisis</label>
                                <textarea name="tic_analysis_notes" id="tic_analysis_notes" class="form-input" rows="2">{{ old('tic_analysis_notes', $developmentRequest->tic_analysis_notes) }}</textarea>
                            </div>
                            <div class="form-field bottom-spaced">
                                <label class="form-label" for="closure_notes">Notas de cierre</label>
                                <textarea name="closure_notes" id="closure_notes" class="form-input" rows="2">{{ old('closure_notes', $developmentRequest->closure_notes) }}</textarea>
                            </div>
                            <div class="form-field bottom-spaced">
                                <label class="form-label" for="comment">Comentario de transicion</label>
                                <textarea name="comment" id="comment" class="form-input" rows="2" placeholder="Obligatorio al devolver o rechazar">{{ old('comment') }}</textarea>
                                <x-input-error :messages="$errors->get('comment')" />
                            </div>

                            <button type="submit" class="btn btn--primary">Actualizar estado</button>
                        </form>
                    </div>
                </div>
            @elseif ($developmentRequest->tic_viability || $developmentRequest->assignedProgrammer || $developmentRequest->tic_analysis_notes)
                <div class="panel">
                    <div class="panel__header"><h3 class="panel-title">Bloque TIC</h3></div>
                    <div class="panel__body">
                        <div class="dashboard-stat-grid">
                            @if ($developmentRequest->assignedProgrammer)
                                <div><strong>Programador:</strong> {{ $developmentRequest->assignedProgrammer->name }}</div>
                            @endif
                            @if ($developmentRequest->tic_viability)
                                <div><strong>Viabilidad:</strong> {{ $developmentRequest->tic_viability }}</div>
                            @endif
                            @if ($developmentRequest->tic_confirmed_priority)
                                <div><strong>Prioridad TIC:</strong> {{ \App\Models\DevelopmentRequest::prioridadesLabels()[$developmentRequest->tic_confirmed_priority] ?? $developmentRequest->tic_confirmed_priority }}</div>
                            @endif
                            @if ($developmentRequest->tic_complexity)
                                <div><strong>Complejidad:</strong> {{ $developmentRequest->tic_complexity }}</div>
                            @endif
                            @if ($developmentRequest->tic_estimated_date)
                                <div><strong>Fecha estimada:</strong> {{ $developmentRequest->tic_estimated_date->format('Y-m-d') }}</div>
                            @endif
                        </div>
                        @if ($developmentRequest->tic_analysis_notes)
                            <p class="panel-text" style="white-space:pre-wrap;margin-top:1rem;">{{ $developmentRequest->tic_analysis_notes }}</p>
                        @endif
                    </div>
                </div>
            @endif

            @if ($canUat)
                <div class="panel">
                    <div class="panel__header"><h3 class="panel-title">Pruebas de aceptacion (UAT)</h3></div>
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
                                <textarea name="uat_notes" id="uat_notes" class="form-input" rows="2">{{ old('uat_notes') }}</textarea>
                                <x-input-error :messages="$errors->get('uat_notes')" />
                            </div>
                            <button type="submit" class="btn btn--primary">Registrar UAT</button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="panel">
                <div class="panel__header"><h3 class="panel-title">Historial de estados</h3></div>
                <div class="panel__body">
                    <ul>
                        @forelse ($developmentRequest->statusLogs as $log)
                            <li>
                                {{ $log->created_at?->format('Y-m-d H:i') }} —
                                {{ $log->from_status ?: '—' }} → {{ $log->to_status }}
                                @if ($log->user) ({{ $log->user->name }}) @endif
                                @if ($log->comment) — {{ $log->comment }} @endif
                            </li>
                        @empty
                            <li>Sin historial.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="panel development-requests-page__panel" id="conversation">
                <div class="panel__header">
                    <h3 class="panel-title">Conversacion</h3>
                    <p class="panel-text">Los participantes reciben correo al publicar un mensaje.</p>
                </div>
                <div class="panel__body">
                    @forelse ($developmentRequest->messages as $message)
                        <div class="bottom-spaced">
                            <strong>{{ $message->user?->name }}</strong>
                            <span class="text-caption">{{ $message->created_at?->format('Y-m-d H:i') }}</span>
                            <p class="panel-text" style="white-space:pre-wrap;">{{ $message->body }}</p>
                        </div>
                    @empty
                        <p class="panel-text">Aun no hay mensajes.</p>
                    @endforelse

                    @if ($canComment)
                        <form method="POST" action="{{ route('development-requests.messages.store', ['module' => $module, 'development_request' => $developmentRequest, 'from' => $from]) }}" class="top-spaced">
                            @csrf
                            <div class="form-field bottom-spaced">
                                <label class="form-label" for="body">Nuevo mensaje</label>
                                <textarea name="body" id="body" class="form-input" rows="3" required maxlength="5000">{{ old('body') }}</textarea>
                                <x-input-error :messages="$errors->get('body')" />
                            </div>
                            <button type="submit" class="btn btn--primary">Enviar mensaje</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
