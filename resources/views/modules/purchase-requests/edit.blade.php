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
    @endphp

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Reabrir solicitud {{ $purchaseRequest->folio() }}</h3>
                    <p class="panel-text">Modifique los datos necesarios y reenvie la solicitud al director para una nueva autorizacion.</p>
                </div>

                <div class="panel__body">
                    @if ($purchaseRequest->comentarios_director)
                        <div class="alert alert--warning bottom-spaced" role="alert">
                            <strong>Motivo del rechazo:</strong> {{ $purchaseRequest->comentarios_director }}
                        </div>
                    @endif

                    @if (session('warning'))
                        <div class="alert alert--warning bottom-spaced" role="alert">{{ session('warning') }}</div>
                    @endif

                    <form action="{{ route('purchase-requests.update', ['module' => $module, 'purchase_request' => $purchaseRequest->id]) }}" method="POST" enctype="multipart/form-data" id="purchase-request-form">
                        @csrf
                        @method('PATCH')

                        <div class="dashboard-stat-grid bottom-spaced">
                            <div class="form-field">
                                <label class="form-label" for="area_key">Area</label>
                                <x-searchable-select
                                    id="area_key"
                                    name="area_key"
                                    :options="config('access.areas', [])"
                                    :value="old('area_key', $purchaseRequest->area_key)"
                                    placeholder="Seleccione area"
                                    searchPlaceholder="Buscar area…"
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

                        <div class="form-field block-spaced">
                            <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="urgente" id="urgente" value="1" @checked(old('urgente', $purchaseRequest->urgente))>
                                Marcar como urgente
                            </label>
                        </div>

                        <div id="purchase-cliente-fields" class="card card--muted block-spaced" @if(old('solicitud_para', $purchaseRequest->solicitud_para) !== 'Cliente') hidden @endif>
                            <h4 class="form-label">Datos del cliente</h4>
                            <div class="dashboard-stat-grid" style="margin-top: 0.75rem;">
                                <div class="form-field">
                                    <label class="form-label" for="razon_social">Razon social</label>
                                    <input type="text" name="razon_social" id="razon_social" class="form-input" value="{{ old('razon_social', $purchaseRequest->razon_social) }}" data-cliente-required="true">
                                    <x-input-error :messages="$errors->get('razon_social')" />
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Proyecto nuevo</label>
                                    <div style="display: flex; gap: 1rem;">
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="proyecto_nuevo" value="1" @checked(old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo ? '1' : '0') === '1' || old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo) === 1)>
                                            Si
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="proyecto_nuevo" value="0" @checked(old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo ? '1' : '0') === '0' || old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo) === 0 || old('proyecto_nuevo', $purchaseRequest->proyecto_nuevo) === null)>
                                            No
                                        </label>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Asume el cliente</label>
                                    <div style="display: flex; gap: 1rem;">
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="asume_cliente" value="1" @checked(old('asume_cliente', $purchaseRequest->asume_cliente ? '1' : '0') === '1' || old('asume_cliente', $purchaseRequest->asume_cliente) === 1)>
                                            Si
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="asume_cliente" value="0" @checked(old('asume_cliente', $purchaseRequest->asume_cliente ? '1' : '0') === '0' || old('asume_cliente', $purchaseRequest->asume_cliente) === 0 || old('asume_cliente', $purchaseRequest->asume_cliente) === null)>
                                            No
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="block-spaced">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                                <h4 class="form-label" style="margin: 0;">Productos solicitados</h4>
                                <button type="button" class="btn btn--secondary btn--sm" id="purchase-add-item-btn">
                                    + Agregar producto
                                </button>
                            </div>

                            <table class="supply-table purchase-items-table">
                                <thead>
                                    <tr>
                                        <th style="width: 90px;">Cantidad</th>
                                        <th style="width: 120px;">Foto</th>
                                        <th>Descripcion</th>
                                        <th>Referencia</th>
                                        <th>Utilizacion</th>
                                        <th>Ubicacion</th>
                                        <th style="width: 90px;">Accion</th>
                                    </tr>
                                </thead>
                                <tbody id="purchase-items-container">
                                    @foreach ($formItems as $index => $item)
                                        <tr data-purchase-item-row>
                                            <td>
                                                <input type="number" name="items[{{ $index }}][cantidad]" class="supply-input" min="1" value="{{ old('items.'.$index.'.cantidad', $item['cantidad'] ?? 1) }}" required>
                                            </td>
                                            @include('modules.purchase-requests.partials.item-foto-field', [
                                                'index' => $index,
                                                'existingFotoPath' => $item['existing_foto_path'] ?? null,
                                            ])
                                            <td>
                                                <input type="text" name="items[{{ $index }}][descripcion]" class="supply-input" value="{{ old('items.'.$index.'.descripcion', $item['descripcion'] ?? '') }}" placeholder="Descripcion del producto" required>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][referencia]" class="supply-input" value="{{ old('items.'.$index.'.referencia', $item['referencia'] ?? '') }}" placeholder="Marca-Modelo / codigo" required>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][utilizacion]" class="supply-input" value="{{ old('items.'.$index.'.utilizacion', $item['utilizacion'] ?? '') }}" placeholder="para quién / qué uso" required>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $index }}][ubicacion]" class="supply-input" value="{{ old('items.'.$index.'.ubicacion', $item['ubicacion'] ?? '') }}" placeholder="Ubicacion / sede" required>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn--secondary btn--sm" data-remove-item>Quitar</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="form-hint" style="margin-top: 0.5rem;">La foto es opcional en cada linea. Formatos: JPG, PNG, WEBP o GIF (max. 5 MB).</p>
                            <x-input-error :messages="$errors->get('items')" />
                        </div>

                        @php
                            $attachmentMimes = config('purchase-requests.attachments.mimes', []);
                            $attachmentAccept = collect($attachmentMimes)->map(fn ($ext) => '.'.$ext)->implode(',');
                            $attachmentMaxFiles = (int) config('purchase-requests.attachments.max_files', 5);
                            $attachmentMaxMb = (int) config('purchase-requests.attachments.max_kilobytes', 10240) / 1024;
                            $keptIds = old('keep_attachment_ids');
                            $existingAttachments = $keptIds === null
                                ? $purchaseRequest->attachments
                                : $purchaseRequest->attachments->whereIn('id', collect($keptIds)->map(fn ($id) => (int) $id)->all());
                        @endphp
                        <div class="block-spaced">
                            <label class="form-label" for="purchase-attachments">Adjuntos</label>
                            <p class="form-hint">
                                Documentos de soporte (cotizacion, orden, evidencia). Opcional.
                                Maximo {{ $attachmentMaxFiles }} archivos, {{ $attachmentMaxMb }} MB cada uno.
                                Tipos: PDF, Word, Excel, PowerPoint, JPG, PNG y WEBP. Quitar un archivo de la lista lo elimina al reenviar.
                            </p>
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
                                                    <button type="button" class="btn btn--secondary btn--sm purchase-attachment-item__remove" data-remove-attachment title="Quitar archivo">
                                                        <x-lucide-trash-2 width="13" height="13" aria-hidden="true" />
                                                        <span>Quitar</span>
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
                                <div class="purchase-attachments-picker__actions">
                                    <label for="purchase-attachments" class="btn btn--secondary btn--sm purchase-attachments-picker__btn">
                                        <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                        <span>Elegir archivos</span>
                                    </label>
                                    <span id="purchase-attachments-count" class="purchase-attachments-picker__status">Sin archivos nuevos seleccionados</span>
                                </div>
                                <ul id="purchase-attachments-selected" class="purchase-attachments-list"></ul>
                            </div>
                            <x-input-error :messages="$errors->get('attachments')" />
                            @foreach ($errors->getMessages() as $errorKey => $errorMessages)
                                @continue(! str_starts_with($errorKey, 'attachments.'))
                                <x-input-error :messages="$errorMessages" />
                            @endforeach
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; gap: 0.75rem; flex-wrap: wrap;">
                            <a href="{{ route('purchase-requests.index', ['module' => $module]) }}" class="btn btn--secondary">
                                Cancelar
                            </a>
                            <button type="submit" class="btn btn--primary">
                                Reenviar solicitud al director
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        @vite('resources/js/purchase-request-form.js')
    @endpush

    @include('modules.purchase-requests.partials.form-photo-styles')
</x-app-layout>
