<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Nueva solicitud de compra</h3>
                    <p class="panel-text">Completa los datos generales, asigna un director aprobador y registra los productos solicitados.</p>
                </div>

                <div class="panel__body">
                    @if (session('warning'))
                        <div class="alert alert--warning bottom-spaced" role="alert">{{ session('warning') }}</div>
                    @endif

                    <form action="{{ route('purchase-requests.store', ['module' => $module]) }}" method="POST" enctype="multipart/form-data" id="purchase-request-form">
                        @csrf

                        <div class="dashboard-stat-grid bottom-spaced">
                            <div class="form-field">
                                <label class="form-label" for="area_key">Area</label>
                                <x-searchable-select
                                    id="area_key"
                                    name="area_key"
                                    :options="config('access.areas', [])"
                                    :value="old('area_key', $module)"
                                    placeholder="Seleccione area"
                                    searchPlaceholder="Buscar area…"
                                    :required="true"
                                    :allowClear="false"
                                />
                                <x-input-error :messages="$errors->get('area_key')" />
                            </div>

                            <div class="form-field">
                                <label class="form-label" for="fecha_solicitud">Fecha de solicitud</label>
                                <input type="date" name="fecha_solicitud" id="fecha_solicitud" class="form-input" value="{{ old('fecha_solicitud', now()->toDateString()) }}" required>
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
                                    :value="old('solicitud_para', 'Interno')"
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
                                    :value="old('aprobador_id')"
                                    placeholder="Seleccione director"
                                    searchPlaceholder="Buscar director…"
                                    :required="true"
                                />
                                <x-input-error :messages="$errors->get('aprobador_id')" />
                            </div>
                        </div>

                        <div class="form-field block-spaced">
                            <label class="form-label" style="display: flex; align-items: center; gap: 0.5rem;">
                                <input type="checkbox" name="urgente" id="urgente" value="1" @checked(old('urgente'))>
                                Marcar como urgente
                            </label>
                        </div>

                        <div id="purchase-cliente-fields" class="card card--muted block-spaced" @if(old('solicitud_para', 'Interno') !== 'Cliente') hidden @endif>
                            <h4 class="form-label">Datos del cliente</h4>
                            <div class="dashboard-stat-grid" style="margin-top: 0.75rem;">
                                <div class="form-field">
                                    <label class="form-label" for="razon_social">Razon social</label>
                                    <input type="text" name="razon_social" id="razon_social" class="form-input" value="{{ old('razon_social') }}" data-cliente-required="true">
                                    <x-input-error :messages="$errors->get('razon_social')" />
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Proyecto nuevo</label>
                                    <div style="display: flex; gap: 1rem;">
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="proyecto_nuevo" value="1" @checked(old('proyecto_nuevo') === '1' || old('proyecto_nuevo') === 1)>
                                            Si
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="proyecto_nuevo" value="0" @checked(old('proyecto_nuevo', '0') === '0' || old('proyecto_nuevo') === 0 || old('proyecto_nuevo') === null)>
                                            No
                                        </label>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <label class="form-label">Asume el cliente</label>
                                    <div style="display: flex; gap: 1rem;">
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="asume_cliente" value="1" @checked(old('asume_cliente') === '1' || old('asume_cliente') === 1)>
                                            Si
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.35rem;">
                                            <input type="radio" name="asume_cliente" value="0" @checked(old('asume_cliente', '0') === '0' || old('asume_cliente') === 0 || old('asume_cliente') === null)>
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
                                    <tr data-purchase-item-row>
                                        <td>
                                            <input type="number" name="items[0][cantidad]" class="supply-input" min="1" value="{{ old('items.0.cantidad', 1) }}" required>
                                        </td>
                                        @include('modules.purchase-requests.partials.item-foto-field', ['index' => 0])
                                        <td>
                                            <input type="text" name="items[0][descripcion]" class="supply-input" value="{{ old('items.0.descripcion') }}" placeholder="Descripcion del producto" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][referencia]" class="supply-input" value="{{ old('items.0.referencia') }}" placeholder="Marca-Modelo / codigo" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][utilizacion]" class="supply-input" value="{{ old('items.0.utilizacion') }}" placeholder="para quién / qué uso" required>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][ubicacion]" class="supply-input" value="{{ old('items.0.ubicacion') }}" placeholder="Ubicacion / sede" required>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn--secondary btn--sm" data-remove-item>Quitar</button>
                                        </td>
                                    </tr>
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
                        @endphp
                        <div class="block-spaced">
                            <label class="form-label" for="purchase-attachments">Adjuntos</label>
                            <p class="form-hint">
                                Documentos de soporte (cotizacion, orden, evidencia). Opcional.
                                Maximo {{ $attachmentMaxFiles }} archivos, {{ $attachmentMaxMb }} MB cada uno.
                                Tipos: PDF, Word, Excel, PowerPoint, JPG, PNG y WEBP.
                            </p>
                            <div class="purchase-attachments-picker">
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
                                    <span id="purchase-attachments-count" class="purchase-attachments-picker__status">Sin archivos seleccionados</span>
                                </div>
                                <ul id="purchase-attachments-selected" class="purchase-attachments-list"></ul>
                            </div>
                            <x-input-error :messages="$errors->get('attachments')" />
                            @foreach ($errors->getMessages() as $errorKey => $errorMessages)
                                @continue(! str_starts_with($errorKey, 'attachments.'))
                                <x-input-error :messages="$errorMessages" />
                            @endforeach
                        </div>

                        <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
                            <button type="submit" class="btn btn--primary">
                                Enviar solicitud
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
