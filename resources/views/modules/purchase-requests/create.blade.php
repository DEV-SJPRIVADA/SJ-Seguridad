<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    @php
        $attachmentMimes = config('purchase-requests.attachments.mimes', []);
        $attachmentAccept = collect($attachmentMimes)->map(fn ($ext) => '.'.$ext)->implode(',');
        $attachmentMaxFiles = (int) config('purchase-requests.attachments.max_files', 5);
        $attachmentMaxMb = (int) config('purchase-requests.attachments.max_kilobytes', 10240) / 1024;
    @endphp

    <div class="page-section purchase-requests-page purchase-requests-page--form">
        <div class="app-container">
            <div class="page-header-inner purchase-requests-page__intro">
                <h2 class="page-title">Nueva solicitud de compra</h2>
                <p class="page-subtitle">
                    Completa los datos generales, asigna un director aprobador y registra los productos. Al enviar, el director recibe correo para autorizar.
                </p>
            </div>

            @if (session('warning'))
                <div class="alert alert--warning" role="alert">{{ session('warning') }}</div>
            @endif

            <div class="pur-req-form-layout">
                <div class="pur-req-form-layout__main">
                    <form
                        action="{{ route('purchase-requests.store', ['module' => $module]) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="purchase-request-form"
                        class="form-stack"
                    >
                        @csrf

                        <div class="pur-req-form">
                            <div class="pur-req-form__meta">
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Formato</span>
                                    <span class="pur-req-form__meta-value">FO-AD-44</span>
                                </div>
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Área de trabajo</span>
                                    <span class="pur-req-form__meta-value">{{ strtoupper((string) $module) }}</span>
                                </div>
                                <div class="pur-req-form__meta-item">
                                    <span class="pur-req-form__meta-label">Estado</span>
                                    <span class="pur-req-form__meta-value">Nueva solicitud</span>
                                </div>
                            </div>

                            {{-- Sección 1: datos generales FO-AD-44 --}}
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
                                            :value="old('area_key', $module)"
                                            placeholder="Seleccione área"
                                            searchPlaceholder="Buscar área…"
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

                                <label class="pur-req-form__check">
                                    <input type="checkbox" name="urgente" id="urgente" value="1" class="form-check" @checked(old('urgente'))>
                                    <span>Marcar como urgente</span>
                                </label>
                            </section>

                            {{-- Sección 2: solo visible si solicitud_para = Cliente --}}
                            <section
                                id="purchase-cliente-fields"
                                class="pur-req-form__section"
                                @if(old('solicitud_para', 'Interno') !== 'Cliente') hidden @endif
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
                                    <input type="text" name="razon_social" id="razon_social" class="form-input" value="{{ old('razon_social') }}" data-cliente-required="true">
                                    <x-input-error :messages="$errors->get('razon_social')" />
                                </div>

                                <div class="form-grid form-grid--three" style="margin-top: 1rem;">
                                    <div class="form-field">
                                        <label class="form-label">Proyecto nuevo</label>
                                        <div class="pur-req-form__radios">
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="proyecto_nuevo" value="1" @checked(old('proyecto_nuevo') === '1' || old('proyecto_nuevo') === 1)>
                                                Sí
                                            </label>
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="proyecto_nuevo" value="0" @checked(old('proyecto_nuevo', '0') === '0' || old('proyecto_nuevo') === 0 || old('proyecto_nuevo') === null)>
                                                No
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label">Asume el cliente</label>
                                        <div class="pur-req-form__radios">
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="asume_cliente" value="1" @checked(old('asume_cliente') === '1' || old('asume_cliente') === 1)>
                                                Sí
                                            </label>
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="asume_cliente" value="0" @checked(old('asume_cliente', '0') === '0' || old('asume_cliente') === 0 || old('asume_cliente') === null)>
                                                No
                                            </label>
                                        </div>
                                    </div>

                                    <div class="form-field">
                                        <label class="form-label">Reinversión</label>
                                        <div class="pur-req-form__radios">
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="reinversion" value="1" @checked(old('reinversion') === '1' || old('reinversion') === 1)>
                                                Sí
                                            </label>
                                            <label class="pur-req-form__radio">
                                                <input type="radio" name="reinversion" value="0" @checked(old('reinversion', '0') === '0' || old('reinversion') === 0 || old('reinversion') === null)>
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
                                        <p class="pur-req-form__section-desc">Agrega líneas manualmente o carga la plantilla Excel. Cada producto usa tarjeta con más espacio para textos.</p>
                                    </div>
                                </header>

                                <div class="pur-req-form__toolbar">
                                    <div class="purchase-items-bulk pur-req-form__bulk">
                                        <a
                                            href="{{ route('purchase-requests.items.import-template', ['module' => $module]) }}"
                                            class="btn btn--secondary btn--sm"
                                        >
                                            <x-selfhst-microsoft-excel-2013 width="15" height="15" aria-hidden="true" />
                                            Descargar plantilla
                                        </a>
                                        <label class="btn btn--secondary btn--sm" for="purchase-items-import-file">
                                            <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                            Cargar Excel
                                        </label>
                                        <input
                                            type="file"
                                            id="purchase-items-import-file"
                                            class="sr-only"
                                            accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                            data-purchase-items-import-url="{{ route('purchase-requests.items.import', ['module' => $module]) }}"
                                        >
                                        <button type="button" class="btn btn--secondary btn--sm" id="purchase-add-item-btn">
                                            + Agregar producto
                                        </button>
                                    </div>
                                </div>
                                <p id="purchase-items-import-status" class="form-hint" hidden></p>

                                <div id="purchase-items-container" class="purchase-item-cards">
                                    @php
                                        $oldItems = old('items');
                                        $createItems = is_array($oldItems) && count($oldItems) > 0
                                            ? array_values($oldItems)
                                            : [['cantidad' => 1]];
                                    @endphp
                                    @foreach ($createItems as $index => $item)
                                        @include('modules.purchase-requests.partials.item-card', [
                                            'index' => $index,
                                            'item' => is_array($item) ? $item : [],
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
                                            Documentos de soporte (cotizacion, orden, evidencia). Opcional.
                                            Máximo {{ $attachmentMaxFiles }} archivos, {{ $attachmentMaxMb }} MB cada uno.
                                        </p>
                                    </div>
                                </header>

                                <div class="purchase-attachments-picker">
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
                                        <span id="purchase-attachments-count" class="purchase-attachments-picker__status">Sin archivos seleccionados</span>
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
                                Revisa director, productos y urgencia antes de enviar. No podrás editar salvo rechazo del director.
                            </p>
                            <div class="pur-req-form-actions__group">
                                <a href="{{ route('purchase-requests.index', ['module' => $module]) }}" class="btn btn--secondary">Cancelar</a>
                                <button type="submit" class="btn btn--primary">Enviar solicitud</button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="pur-req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de enviar</h3>
                            <p class="panel-text">Reduce devoluciones con estos puntos.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="pur-req-form-guide__list">
                                <li class="pur-req-form-guide__item">Elige el director correcto: solo él puede autorizar.</li>
                                <li class="pur-req-form-guide__item">Interno vs Cliente: si es Cliente, completa razón social.</li>
                                <li class="pur-req-form-guide__item">Cada línea necesita cantidad, descripción, referencia, uso y ubicación.</li>
                                <li class="pur-req-form-guide__item">Usa la plantilla Excel si vas a cargar muchos productos.</li>
                                <li class="pur-req-form-guide__item">Marca urgente solo cuando el impacto lo justifique.</li>
                                <li class="pur-req-form-guide__item">Adjuntos son opcionales; ayudan a Compras al comprar.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Qué pasa después</h3>
                        </div>
                        <div class="panel__body">
                            <ol class="pur-req-form-flow">
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">1</span>
                                    <span>El director aprueba o rechaza (plataforma o correo).</span>
                                </li>
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">2</span>
                                    <span>Si aprueba, entra a la bandeja de Compras.</span>
                                </li>
                                <li class="pur-req-form-flow__item">
                                    <span class="pur-req-form-flow__step">3</span>
                                    <span>Sigue el avance desde Mis solicitudes.</span>
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
