@php
    $fromKey = (string) request('from', 'mis_solicitudes');
    $backUrl = match ($fromKey) {
        'processing' => route('purchase-requests.processing.index', ['module' => $module]),
        'approval' => route('purchase-requests.approval.index', ['module' => $module]),
        default => route('purchase-requests.index', ['module' => $module]),
    };
    $backLabel = match ($fromKey) {
        'processing' => 'Volver a bandeja de compras',
        'approval' => 'Volver a pendientes',
        default => 'Volver a mis solicitudes',
    };
    $estadoPill = match ($purchaseRequest->estado) {
        'aprobado' => 'status-pill--success',
        'rechazado' => 'status-pill--danger',
        default => 'status-pill--info',
    };
    $estadoLabel = match ($purchaseRequest->estado) {
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        default => 'Pendiente',
    };
    $canProcess = auth()->user()?->can('process', $purchaseRequest) ?? false;
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section purchase-requests-page purchase-requests-page--detail">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a href="{{ $backUrl }}" class="pur-req-detail__back">
                        <x-lucide-arrow-left width="16" height="16" aria-hidden="true" />
                        {{ $backLabel }}
                    </a>
                    <div class="pur-req-detail__toolbar-actions">
                        <a
                            href="{{ route('purchase-requests.export.pdf', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                            class="btn btn--secondary btn--sm"
                        >Descargar PDF</a>
                        <x-export-excel
                            route="{{ route('purchase-requests.export.excel', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                            label="Exportar Excel"
                            class="btn btn--secondary btn--sm"
                        />
                        @can('resubmit', $purchaseRequest)
                            <a
                                href="{{ route('purchase-requests.edit', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                class="btn btn--secondary btn--sm purchase-request-resubmit-btn"
                                title="Reabrir y editar"
                                aria-label="Reabrir y editar"
                            >
                                <x-ri-issues-reopen-fill width="18" height="18" aria-hidden="true" />
                            </a>
                        @endcan
                    </div>
                </div>

                <h2 class="page-title">Solicitud de compra {{ $purchaseRequest->folio() }}</h2>
                <p class="page-subtitle">Detalle FO-AD-44 · seguimiento de autorizacion y compras.</p>
                <div class="pur-req-detail__pills">
                    <span class="status-pill {{ $estadoPill }}">{{ $estadoLabel }}</span>
                    @if ($purchaseRequest->urgente)
                        <span class="status-pill status-pill--warning">Urgente</span>
                    @endif
                    @if ($purchaseRequest->estado_compras)
                        <span class="status-pill status-pill--compras-{{ $purchaseRequest->estado_compras }}">
                            Compras: {{ $purchaseRequest->estadoComprasLabel() }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="pur-req-detail-layout">
                <div class="pur-req-detail-layout__main">
                    <div class="pur-req-form__meta">
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Fecha de solicitud</span>
                            <span class="pur-req-form__meta-value">
                                <x-date-table :value="$purchaseRequest->fecha_solicitud ?? $purchaseRequest->created_at" />
                            </span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Solicitante</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->user?->name ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Area</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->areaLabel() ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Solicitud para</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->solicitud_para }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Director aprobador</span>
                            <span class="pur-req-form__meta-value">{{ $purchaseRequest->aprobador?->name ?? '—' }}</span>
                        </div>
                        <div class="pur-req-form__meta-item">
                            <span class="pur-req-form__meta-label">Fecha de aprobacion</span>
                            <span class="pur-req-form__meta-value">
                                @if ($purchaseRequest->fecha_aprobacion)
                                    <x-date-table :value="$purchaseRequest->fecha_aprobacion" />
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                        @if ($purchaseRequest->estado_compras)
                            <div class="pur-req-form__meta-item">
                                <span class="pur-req-form__meta-label">Estado compras</span>
                                <span class="pur-req-form__meta-value">{{ $purchaseRequest->estadoComprasLabel() }}</span>
                            </div>
                        @endif
                    </div>

                    @if ($purchaseRequest->solicitud_para === 'Cliente')
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-building-2 width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Datos del cliente</h3>
                                    <p class="pur-req-form__section-desc">Informacion asociada a la solicitud para cliente.</p>
                                </div>
                            </header>
                            <div class="pur-req-form__meta">
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Razon social</span>
                                    <span class="pur-req-form__meta-value">{{ $purchaseRequest->razon_social ?? '—' }}</span>
                                </div>
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Proyecto nuevo</span>
                                    <span class="pur-req-form__meta-value">{{ $purchaseRequest->proyecto_nuevo ? 'Si' : 'No' }}</span>
                                </div>
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Asume el cliente</span>
                                    <span class="pur-req-form__meta-value">{{ $purchaseRequest->asume_cliente ? 'Si' : 'No' }}</span>
                                </div>
                            </div>
                        </section>
                    @endif

                    @if ($purchaseRequest->comentarios_director)
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-message-square-text width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Comentarios del director</h3>
                                    <p class="pur-req-form__section-desc">Observacion registrada en la autorizacion.</p>
                                </div>
                            </header>
                            <p class="pur-req-detail__block-body">{{ $purchaseRequest->comentarios_director }}</p>
                        </section>
                    @endif

                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step" aria-hidden="true">
                                <x-lucide-package width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="pur-req-form__section-title">Productos solicitados</h3>
                                <p class="pur-req-form__section-desc">{{ $purchaseRequest->items->count() }} linea(s) en esta solicitud.</p>
                            </div>
                        </header>

                        <div class="data-table-wrap">
                            <table class="supply-table purchase-request-items-table">
                                <thead>
                                    <tr>
                                        <th class="col-num">#</th>
                                        <th class="col-foto">Foto</th>
                                        <th class="col-qty">Cantidad</th>
                                        <th>Descripcion</th>
                                        <th>Referencia</th>
                                        <th>Utilizacion</th>
                                        <th>Ubicacion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($purchaseRequest->items as $item)
                                        <tr>
                                            <td class="text-center">{{ $item->orden ?? $loop->iteration }}</td>
                                            <td class="text-center purchase-item-photo-cell">
                                                @if ($item->fotoUrl())
                                                    <a href="{{ $item->fotoUrl() }}" target="_blank" rel="noopener" class="purchase-item-photo-link" title="Ver foto ampliada">
                                                        <img
                                                            src="{{ $item->fotoUrl() }}"
                                                            alt="Foto del producto"
                                                            class="purchase-item-photo-thumb"
                                                            loading="lazy"
                                                        >
                                                    </a>
                                                @else
                                                    <span class="text-muted text-small">Sin foto</span>
                                                @endif
                                            </td>
                                            <td class="text-center">{{ $item->cantidad }}</td>
                                            <td class="pur-req-detail__item-name">{{ $item->descripcion }}</td>
                                            <td>{{ $item->referencia }}</td>
                                            <td>{{ $item->utilizacion }}</td>
                                            <td>{{ $item->ubicacion }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    @if ($purchaseRequest->attachments->isNotEmpty())
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-paperclip width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Adjuntos</h3>
                                    <p class="pur-req-form__section-desc">Documentos de soporte de la solicitud.</p>
                                </div>
                            </header>

                            <ul class="pur-req-detail__attachment-list">
                                @foreach ($purchaseRequest->attachments as $attachment)
                                    <li class="pur-req-detail__attachment-item">
                                        <div class="pur-req-detail__attachment-info">
                                            <x-lucide-file width="16" height="16" aria-hidden="true" />
                                            <div>
                                                <p class="pur-req-detail__attachment-name">{{ $attachment->original_name }}</p>
                                                <p class="pur-req-detail__attachment-size">{{ $attachment->sizeLabel() }}</p>
                                            </div>
                                        </div>
                                        <a
                                            href="{{ route('purchase-requests.attachments.download', [
                                                'module' => $module,
                                                'purchase_request' => $purchaseRequest->id,
                                                'attachment' => $attachment->id,
                                            ]) }}"
                                            class="btn btn--secondary btn--sm"
                                        >Descargar</a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    <section class="pur-req-form__section" id="purchase-request-comments">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step" aria-hidden="true">
                                <x-lucide-messages-square width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="pur-req-form__section-title">Comentarios de la solicitud</h3>
                                <p class="pur-req-form__section-desc">
                                    Notas posteriores al envio. Quien puede ver la solicitud tambien puede responder.
                                    @if (! $purchaseRequest->puedeComentar())
                                        Los comentarios quedan cerrados cuando Compras marca la solicitud como <strong>completada</strong>.
                                    @endif
                                </p>
                            </div>
                        </header>

                        <div class="pur-req-chat">
                            @forelse ($purchaseRequest->comments as $comment)
                                <article class="pur-req-chat__message">
                                    <div class="pur-req-chat__avatar" aria-hidden="true">
                                        {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="pur-req-chat__bubble">
                                        <div class="pur-req-chat__head">
                                            <strong>{{ $comment->user?->name ?? 'Usuario' }}</strong>
                                            <span class="text-caption"><x-date-table :value="$comment->created_at" datetime /></span>
                                        </div>
                                        <p class="pur-req-chat__body">{{ $comment->body }}</p>
                                    </div>
                                </article>
                            @empty
                                <p class="pur-req-form__section-desc">Aun no hay comentarios.</p>
                            @endforelse
                        </div>

                        @if ($purchaseRequest->puedeComentar() && auth()->user()?->can('comment', $purchaseRequest))
                            <form
                                method="POST"
                                action="{{ route('purchase-requests.comments.store', ['module' => $module, 'purchase_request' => $purchaseRequest->id, 'from' => request('from')]) }}"
                                class="pur-req-chat__composer"
                            >
                                @csrf
                                @if (request('from'))
                                    <input type="hidden" name="from" value="{{ request('from') }}">
                                @endif
                                <div class="form-field">
                                    <label class="form-label" for="purchase-request-comment-body">Nuevo comentario</label>
                                    <textarea
                                        id="purchase-request-comment-body"
                                        name="body"
                                        class="form-textarea"
                                        rows="3"
                                        maxlength="5000"
                                        required
                                        placeholder="Ej. El item Cascos de seguridad ya no es necesario."
                                    >{{ old('body') }}</textarea>
                                    <x-input-error :messages="$errors->get('body')" />
                                </div>
                                <button type="submit" class="btn btn--primary btn--sm">Agregar comentario</button>
                            </form>
                        @elseif (! $purchaseRequest->puedeComentar())
                            <p class="text-muted text-small purchase-request-comments__closed">
                                Esta solicitud ya esta completada en Compras; no se pueden agregar mas comentarios.
                            </p>
                        @endif
                    </section>

                    <section class="pur-req-form__section">
                        <header class="pur-req-form__section-head">
                            <span class="pur-req-form__section-step" aria-hidden="true">
                                <x-lucide-mail width="16" height="16" />
                            </span>
                            <div>
                                <h3 class="pur-req-form__section-title">Registro de correos de esta solicitud</h3>
                                <p class="pur-req-form__section-desc">Historial de notificaciones enviadas.</p>
                            </div>
                        </header>

                        @if ($purchaseRequest->mailLogs->isEmpty())
                            <p class="pur-req-form__section-desc">No hay correos registrados para esta solicitud.</p>
                        @else
                            <div class="data-table-wrap">
                                <table class="supply-table">
                                    <thead>
                                        <tr>
                                            <th>Fecha / hora</th>
                                            <th>Tipo</th>
                                            <th>Destinatario</th>
                                            <th>Estado</th>
                                            <th>Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($purchaseRequest->mailLogs as $mailLog)
                                            <tr>
                                                <td>
                                                    @if ($mailLog->sent_at)
                                                        <x-date-table :value="$mailLog->sent_at" datetime />
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>{{ $mailLog->typeLabel() }}</td>
                                                <td>{{ $mailLog->recipient_email }}</td>
                                                <td>
                                                    @php
                                                        $mailStatusClass = $mailLog->status === \App\Models\PurchaseRequestMailLog::STATUS_ENVIADO
                                                            ? 'status-pill--success'
                                                            : 'status-pill--danger';
                                                    @endphp
                                                    <span class="status-pill {{ $mailStatusClass }}">{{ $mailLog->statusLabel() }}</span>
                                                </td>
                                                <td>{{ $mailLog->detail ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    @if ($purchaseRequest->comentarios_compras)
                        <section class="pur-req-form__section">
                            <header class="pur-req-form__section-head">
                                <span class="pur-req-form__section-step" aria-hidden="true">
                                    <x-lucide-clipboard-list width="16" height="16" />
                                </span>
                                <div>
                                    <h3 class="pur-req-form__section-title">Comentarios de compras</h3>
                                    <p class="pur-req-form__section-desc">Notas del procesamiento en bandeja.</p>
                                </div>
                            </header>
                            <p class="pur-req-detail__block-body">{{ $purchaseRequest->comentarios_compras }}</p>
                            @if ($purchaseRequest->procesadoComprasPor)
                                <p class="pur-req-detail__meta-note">
                                    Procesado por: {{ $purchaseRequest->procesadoComprasPor->name }}
                                    @if ($purchaseRequest->procesado_compras_at)
                                        · <x-date-table :value="$purchaseRequest->procesado_compras_at" datetime />
                                    @endif
                                </p>
                            @endif
                        </section>
                    @endif
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Resumen</h3>
                            <p class="panel-text">Datos rapidos de seguimiento.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Estado: {{ $estadoLabel }}</li>
                                <li class="pur-req-form-guide__item">Solicitante: {{ $purchaseRequest->user?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Director: {{ $purchaseRequest->aprobador?->name ?? '—' }}</li>
                                <li class="pur-req-form-guide__item">Productos: {{ $purchaseRequest->items->count() }}</li>
                                @if ($purchaseRequest->estado_compras)
                                    <li class="pur-req-form-guide__item">Compras: {{ $purchaseRequest->estadoComprasLabel() }}</li>
                                @endif
                            </ul>
                        </div>
                    </div>

                    @if ($fromKey === 'processing' && $canProcess)
                        <div class="panel">
                            <div class="panel__header">
                                <h3 class="panel-title">Accion compras</h3>
                                <p class="panel-text">Actualiza el estado operativo en bandeja.</p>
                            </div>
                            <div class="panel__body">
                                <a
                                    href="{{ route('purchase-requests.processing.purchase', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                                    class="btn btn--primary"
                                >Procesar solicitud</a>
                            </div>
                        </div>
                    @endif

                    @include('modules.purchase-requests.partials.approval-form')

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Recordatorio</h3>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">PDF y Excel exportan el formato FO-AD-44.</li>
                                <li class="pur-req-form-guide__item">Los comentarios quedan abiertos hasta completado en Compras.</li>
                                <li class="pur-req-form-guide__item">Si fue rechazada, puedes reabrir y reenviar al director.</li>
                            </ul>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
