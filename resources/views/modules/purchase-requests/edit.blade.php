<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $oldItems = old('items');
        $formItems = is_array($oldItems) && $oldItems !== []
            ? $oldItems
            : $purchaseRequest->items->map(fn ($item) => [
                'cantidad' => $item->cantidad,
                'descripcion' => $item->descripcion,
                'referencia' => $item->referencia,
                'utilizacion' => $item->utilizacion,
                'ubicacion' => $item->ubicacion,
                'existing_foto_path' => $item->foto_path,
            ])->values()->all();

        $attachmentMimes = config('purchase-requests.attachments.mimes', []);
        $attachmentAccept = collect($attachmentMimes)->map(fn ($ext) => '.'.$ext)->implode(',');
        $attachmentMaxFiles = (int) config('purchase-requests.attachments.max_files', 5);
        $attachmentMaxMb = (int) config('purchase-requests.attachments.max_kilobytes', 10240) / 1024;
        $keptIds = old('keep_attachment_ids');
        $existingAttachments = $keptIds === null
            ? $purchaseRequest->attachments
            : $purchaseRequest->attachments->whereIn('id', collect($keptIds)->map(fn ($id) => (int) $id)->all());
        $backUrl = route('purchase-requests.index', ['module' => $module]);
    @endphp

    <div class="page-section purchase-requests-page purchase-requests-page--form">
        <div class="app-container">
            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $backUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver a mis solicitudes"
                        aria-label="Volver a mis solicitudes"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>
                <h2 class="page-title">Reabrir solicitud {{ $purchaseRequest->folio() }}</h2>
                <p class="page-subtitle">
                    Corrige lo necesario y reenvia al director para una nueva autorizacion.
                </p>
            </div>

            @if ($purchaseRequest->comentarios_director)
                <div class="alert alert--warning" role="alert">
                    <strong>Motivo del rechazo:</strong> {{ $purchaseRequest->comentarios_director }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert--warning" role="alert">{{ session('warning') }}</div>
            @endif

            <div class="pur-req-form-layout">
                <div class="pur-req-form-layout__main">
                    <form
                        action="{{ route('purchase-requests.update', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="purchase-request-form"
                        class="form-stack"
                    >
                        @csrf
                        @method('PATCH')

                        <div class="pur-req-form">
                            <div class="pur-req-form__meta">
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Formato</span>
                                    <span class="pur-req-form__meta-value">FO-AD-44</span>
                                </div>
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Folio</span>
                                    <span class="pur-req-form__meta-value">{{ $purchaseRequest->folio() }}</span>
                                </div>
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Estado</span>
                                    <span class="pur-req-form__meta-value">Rechazada — reenvío</span>
                                </div>
                            </div>

                            <section class="pur-req-form__section">
                                <header class="pur-req-form__section-head">
                                    <span class="pur-req-form__section-step">1</span>
                                    <div>
                                        <h3 class="pur-req-form__section-title">Datos generales</h3>
                                        <p class="pur-req-form__section-desc">Área, fecha, destinatario y director que autoriza.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field">
                                        <label class="form-label" for="area_key">Área</label>
                                        <x-searchable-select
                                            id="area_key"
                                            name="area_key"
                                            :options="config('access.areas', [])"
                                            :value="old('area_key', $purchaseRequest->area_key)"
                                            placeholder="Seleccione área"
                                            searchPlaceholder="Buscar área…"
                                            :required="true"
                                            :allowClear="false"
                                        />
                                        <x-input-error :messages="$errors->get('area_key')" />
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="fecha_solicitud">Fecha de solicitud</label>
                                        <input type="date" name="fecha_solicitud" id="fecha_solicitud" class="form-input" value="{{ old('fecha_solicitud', optional($purchaseRequest->fecha_solicitud)->toDateString()) }}" required>
                                        <x-input-error :messages="$errors->get('fecha_solicitud')" />
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="solicitud_para">Solicitud para</label>
                                        <x-searchable-select
                                            id="solicitud_para"
                                            name="solicitud_para"
                                            :options="[
                                                ['value' => 'Interno', 'label' => 'Interno'],
                                                ['value' => 'Cliente', 'label' => 'Cliente'],
                                            ]"
                                            :value="old('solicitud_para', $purchaseRequest->solicitud_para)"
                                            placeholder="Seleccione…"
                                            :required="true"
                                            :allowClear="false"
                                        />
                                        <x-input-error :messages="$errors->get('solicitud_para')" />
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label" for="aprobador_id">Director aprobador</label>
                                        <x-searchable-select
                                            id="aprobador_id"
                                            name="aprobador_id"
                                            :options="collect($directores)->map(fn($d) => ['value' => (string) $d->id, 'label' => $d->name])->all()"
                                            :value="old('aprobador_id', $purchaseRequest->aprobador_id)"
                                            placeholder="Seleccione director"
                                            searchPlaceholder="Buscar director…"
                                            :required="true"
                                        />
                                        <x-input-error :messages="$errors->get('aprobador_id')" />
                                    </div>
                                </div>

                                <label class="pur-req-form__check">
                                    <input type="checkbox" name="urgente" id="urgente" value="1" class="form-check" @checked(old('urgente', $purchaseRequest->urgente))>
                                    <span>Marcar como urgente</span>
                                </label>
                            </section>

                            {{-- Sección 2: solo visible si solicitud_para = Cliente --}}
                            <section
                                id="purchase-cliente-fields"
                                class="pur-req-form__section"
                                @if(old('solicitud_para', $purchaseRequest->solicitud_para) !== 'Cliente') hidden @endif
                            >
                                <header class="pur-req-form__section-head">
                                    <span class="pur-req-form__section-step">2</span>
                                    <div>
                                        <h3 class="pur-req-form__section-title">Datos del cliente</h3>
                                        <p class="pur-req-form__section-desc">Solo aplica cuando la solicitud es para Cliente.</p>
                                    </div>
                                </header>

                                <div class="form-field">
                                    <label class="form-label" for="razon_social">Razón social</label>
                                    <input type="text" name="razon_social" id="razon_social" class="form-input" value="{{ old('razon_social', $purchaseRequest->razon_social) }}" data-cliente-required="true">
                                    <x-input-error :messages="$errors->get('razon_social')" />
                                </div>

                                <div class="form-grid form-grid--three" style="margin-top: 1rem;">
                                    <div class="form-field">
                                        <label class="form-label">Proyecto nuevo</label>
                                        <div class="pur-req-form__radios">
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="proyecto_nuevo" value="1" @checked(old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo ? '1' : '0') === '1' || old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo) === 1)>
                                                Sí
                                            </label>
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="proyecto_nuevo" value="0" @checked(old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo ? '1' : '0') === '0' || old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo) === 0 || old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo) === null)>
                                                No
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label">Asume el cliente</label>
                                        <div class="pur-req-form__radios">
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="asume_cliente" value="1" @checked(old('asume_cliente', $purchaseRequest->asume_cliente ? '1' : '0') === '1' || old('asume_cliente', $purchaseRequest->asume_cliente) === 1)>
                                                Sí
                                            </label>
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="asume_cliente" value="0" @checked(old('asume_cliente', $purchaseRequest->asume_cliente ? '1' : '0') === '0' || old('asume_cliente', $purchaseRequest->asume_cliente) === 0 || old('asume_cliente', $purchaseRequest->asume_cliente) === null)>
                                                No
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label">Reinversión</label>
                                        <div class="pur-req-form__radios">
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="reinversion" value="1" @checked(old('reinversion', $purchaseRequest->reinversion ? '1' : '0') === '1' || old('reinversion', $purchaseRequest->reinversion) === 1)>
                                                Sí
                                            </label>
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="reinversion" value="0" @checked(old('reinversion', $purchaseRequest->reinversion ? '1' : '0') === '0' || old('reinversion', $purchaseRequest->reinversion) === 0 || old('reinversion', $purchaseRequest->reinversion) === null)>
                                                No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="pur-req-form__section pur-req-form__section--products">
                                <header class="pur-req-form__section-head">
                                    <span class="pur-req-form__section-step">3</span>
                                    <div>
                                        <h3 class="pur-req-form__section-title">Productos solicitados</h3>
                                        <p class="pur-req-form__section-desc">Ajusta líneas o agrega productos antes de reenviar. Cada producto usa tarjeta con más espacio para textos.</p>
                                    </div>
                                </header>

                                <div class="pur-req-form__toolbar">
                                    <div class="purchase-items-bulk pur-req-form__bulk">
                                        <button
                                            type="button"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                            id="purchase-add-item-btn"
                                            title="Agregar producto"
                                            aria-label="Agregar producto"
                                        >
                                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>

                                <div id="purchase-items-container" class="purchase-item-cards">
                                    @foreach ($formItems as $index => $item)
                                        @include('modules.purchase-requests.partials.item-card', [
                                            'index' => $index,
                                            'item' => $item,
                                        ])
                                    @endforeach
                                </div>
                                <p class="form-hint">La foto es opcional en cada línea. Formatos: JPG, PNG, WEBP o GIF (máx. 5 MB).</p>
                                <x-input-error :messages="$errors->get('items')" />
                            </section>

                            <section class="pur-req-form__section">
                                <header class="pur-req-form__section-head">
                                    <span class="pur-req-form__section-step">4</span>
                                    <div>
                                        <h3 class="pur-req-form__section-title">Adjuntos</h3>
                                        <p class="pur-req-form__section-desc">
                                            Documentos de soporte. Opcional. Máximo {{ $attachmentMaxFiles }} archivos, {{ $attachmentMaxMb }} MB cada uno.
                                            Quitar un archivo de la lista lo elimina al reenviar.
                                        </p>
                                    </div>
                                </header>

                                <div class="purchase-attachments-picker">
                                    @if ($existingAttachments->isNotEmpty())
                                        <div class="purchase-attachments-existing-wrapper">
                                            <span class="text-small text-muted" style="font-weight: 500; margin-bottom: 0.35rem; display: block;">Archivos adjuntos actuales:</span>
                                            <ul id="purchase-attachments-existing" class="purchase-attachments-list" style="margin-bottom: 0.75rem;">
                                                @foreach ($existingAttachments as $attachment)
                                                    <li data-existing-attachment class="purchase-attachment-item">
                                                        <input type="hidden" name="keep_attachment_ids[]" value="{{ $attachment->id }}">
                                                        <div class="purchase-attachment-item__info">
                                                            <x-lucide-paperclip width="15" height="15" class="text-muted" aria-hidden="true" />
                                                            <span class="purchase-attachment-item__name">{{ $attachment->original_name }}</span>
                                                            <span class="purchase-attachment-item__size">({{ $attachment->sizeLabel() }})</span>
                                                        </div>
                                                        <button
                                                            type="button"
                                                            class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger purchase-attachment-item__remove"
                                                            data-remove-attachment
                                                            title="Quitar archivo"
                                                            aria-label="Quitar archivo"
                                                        >
                                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @else
                                        <ul id="purchase-attachments-existing" class="purchase-attachments-list" style="margin-bottom: 0.75rem;"></ul>
                                    @endif

                                    <input
                                        type="file"
                                        name="attachments[]"
                                        id="purchase-attachments"
                                        class="purchase-attachments-picker__input"
                                        multiple
                                        accept="{{ $attachmentAccept }}"
                                    >
                                    <label class="pur-req-form__upload" for="purchase-attachments">
                                        <span class="pur-req-form__upload-icon" aria-hidden="true">
                                            <x-lucide-paperclip width="20" height="20" />
                                        </span>
                                        <span class="pur-req-form__upload-copy">
                                            <span class="pur-req-form__upload-title">Elegir archivos</span>
                                            <span class="pur-req-form__upload-hint">PDF, Word, Excel, PowerPoint, JPG, PNG y WEBP</span>
                                        </span>
                                    </label>
                                    <div class="purchase-attachments-picker__actions">
                                        <span id="purchase-attachments-count" class="purchase-attachments-picker__status">Sin archivos nuevos seleccionados</span>
                                    </div>
                                    <ul id="purchase-attachments-selected" class="purchase-attachments-list"></ul>
                                </div>
                                <x-input-error :messages="$errors->get('attachments')" />
                                @foreach ($errors->getMessages() as $errorKey => $errorMessages)
                                    @continue(! str_starts_with($errorKey, 'attachments.'))
                                    <x-input-error :messages="$errorMessages" />
                                @endforeach
                            </section>
                        </div>

                        <div class="pur-req-form-actions">
                            <p class="pur-req-form-actions__note">
                                Al reenviar, la solicitud vuelve a pendiente y el director recibe una nueva notificacion.
                            </p>
                            <div class="pur-req-form-actions__group">
                                <a
                                    href="{{ $backUrl }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cancelar"
                                    aria-label="Cancelar"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Reenviar solicitud al director"
                                    aria-label="Reenviar solicitud al director"
                                >
                                    <x-lucide-send width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de reenviar</h3>
                            <p class="panel-text">Corrige el motivo del rechazo.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Revisa el comentario del director arriba.</li>
                                <li class="pur-req-form-guide__item">Confirma director, productos y urgencia.</li>
                                <li class="pur-req-form-guide__item">Si es Cliente, completa razon social.</li>
                                <li class="pur-req-form-guide__item">Puedes quitar o agregar adjuntos antes de enviar.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Que pasa despues</h3>
                        </div>
                        <div class="panel__body">
                            <ol class="pur-req-form-flow">
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">1</span>
                                    <span>La solicitud vuelve a pendiente.</span>
                                </li>
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">2</span>
                                    <span>El director recibe la nueva notificacion.</span>
                                </li>
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">3</span>
                                    <span>Si aprueba, entra de nuevo a bandeja de Compras.</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/purchase-request-form.js')
    @endpush

    @include('modules.purchase-requests.partials.form-photo-styles')
</x-app-layout>
