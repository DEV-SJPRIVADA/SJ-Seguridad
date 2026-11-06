<x-app-layout>
    @php
        $isEdit = $caso !== null;
        $dateValue = function (string $key) use ($caso) {
            $old = old($key);
            if ($old !== null) {
                return $old;
            }
            $date = $caso?->{$key};

            return $date ? $date->format('Y-m-d') : '';
        };
        $textValue = function (string $key, string $default = '') use ($caso) {
            return old($key, $caso?->{$key} ?? $default);
        };
    @endphp

    <x-slot name="header">
        @include('areas.calidad.servicio_cliente.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Alta y edición FO-GI-22: mismo esquema que Solicitar personal (secciones, guía y acciones). --}}
    <div
        class="page-section servicio-cliente-form-page"
        x-data="{
            tipo: @js(old('tipo_cliente', $caso->tipo_cliente ?? 'externo')),
        }"
    >
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="page-header-inner" style="padding-top: 0; margin-bottom: 1.25rem;">
                <h2 class="page-title">{{ $isEdit ? 'Caso N° '.$caso->consecutivo : 'Nuevo caso FO-GI-22' }}</h2>
                <p class="page-subtitle">
                    @if ($isEdit)
                        Actualice el caso. El plazo de respuesta lo calcula el sistema.
                    @else
                        Complete las secciones. El consecutivo se asigna al guardar y el caso queda en el listado de Calidad.
                    @endif
                </p>
            </div>

            <div class="req-form-layout">
                <div class="req-form-layout__main form-stack">
                    <form
                        id="fo-gi-22-caso-form"
                        method="POST"
                        action="{{ $isEdit ? route('calidad.servicio-cliente.fo-gi-22.update', $caso) : route('calidad.servicio-cliente.fo-gi-22.store') }}"
                        enctype="multipart/form-data"
                        class="form-stack"
                    >
                        @csrf
                        @if ($isEdit)
                            @method('PATCH')
                        @endif

                        <div class="req-form">
                            <div class="req-form__meta req-form__meta--edit">
                                <div class="req-form__meta-item">
                                    <span class="req-form__meta-label">Formato</span>
                                    <span class="req-form__meta-value">FO-GI-22</span>
                                </div>
                                <div class="req-form__meta-item">
                                    <span class="req-form__meta-label">Consecutivo</span>
                                    <span class="req-form__meta-value">{{ $isEdit ? $caso->consecutivo : 'Se asigna al guardar' }}</span>
                                </div>
                                <div class="req-form__meta-item">
                                    <span class="req-form__meta-label">Área</span>
                                    <span class="req-form__meta-value">Calidad</span>
                                </div>
                            </div>

                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">1</span>
                                    <div>
                                        <h4 class="req-form__section-title">Fechas</h4>
                                        <p class="req-form__section-desc">El plazo son 10 días hábiles después de la recepción, sin contar festivos.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--three">
                                    <div class="form-field">
                                        <x-input-label for="fecha_recepcion" value="Fecha de recepción *" />
                                        <input id="fecha_recepcion" name="fecha_recepcion" type="date" class="form-input" required value="{{ $dateValue('fecha_recepcion') }}" @disabled(! $canEdit)>
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="fecha_verificacion" value="Fecha de verificación" />
                                        <input id="fecha_verificacion" name="fecha_verificacion" type="date" class="form-input" value="{{ $dateValue('fecha_verificacion') }}" @disabled(! $canEdit)>
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="fecha_plazo_vista" value="Fecha de plazo de respuesta" />
                                        <input id="fecha_plazo_vista" type="text" class="form-input" readonly value="{{ $isEdit ? optional($caso->fecha_plazo_respuesta)->format('d-m-Y') : '10 días hábiles después de la recepción' }}">
                                    </div>
                                </div>
                            </section>

                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">2</span>
                                    <div>
                                        <h4 class="req-form__section-title">Cliente y ciudad</h4>
                                        <p class="req-form__section-desc">Interno queda fijo en SJSEGURIDAD. Externo se elige de la matriz comercial.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field">
                                        <x-input-label for="tipo_cliente" value="Tipo de cliente *" />
                                        <x-searchable-select id="tipo_cliente" name="tipo_cliente" :options="$options['tipos']" :value="old('tipo_cliente', $caso->tipo_cliente ?? 'externo')" :allow-clear="false" :disabled="! $canEdit" x-on:change="tipo = $event.target.value" />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="city_id" value="Ciudad *" />
                                        <x-searchable-select id="city_id" name="city_id" :options="$options['ciudades']" :value="old('city_id', $caso->city_id ?? '')" placeholder="Selecciona una ciudad" search-placeholder="Buscar ciudad…" :disabled="! $canEdit" />
                                    </div>
                                    <div class="form-field req-form__field-span" x-show="tipo === 'externo'" x-cloak>
                                        <x-input-label for="commercial_client_id" value="Nombre del cliente *" />
                                        <x-searchable-select id="commercial_client_id" name="commercial_client_id" :options="$options['clientes']" :value="old('commercial_client_id', $caso->commercial_client_id ?? '')" placeholder="Selecciona un cliente" search-placeholder="Buscar cliente…" :disabled="! $canEdit" />
                                    </div>
                                    <div class="form-field req-form__field-span" x-show="tipo === 'interno'" x-cloak>
                                        <x-input-label for="cliente_interno_fijo" value="Nombre del cliente" />
                                        <input id="cliente_interno_fijo" type="text" class="form-input" readonly value="SJSEGURIDAD">
                                        <p class="req-form__hint">Personal interno: el nombre no se cambia.</p>
                                    </div>
                                </div>
                            </section>

                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">3</span>
                                    <div>
                                        <h4 class="req-form__section-title">Clasificación</h4>
                                        <p class="req-form__section-desc">Tipología, fuente y categoría salen de Catálogos.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--three">
                                    <div class="form-field">
                                        <x-input-label for="tipologia_id" value="Tipología *" />
                                        <x-searchable-select id="tipologia_id" name="tipologia_id" :options="$options['tipologias']" :value="old('tipologia_id', $caso->tipologia_id ?? '')" placeholder="Selecciona" search-placeholder="Buscar tipología…" :disabled="! $canEdit" />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="fuente_id" value="Fuente *" />
                                        <x-searchable-select id="fuente_id" name="fuente_id" :options="$options['fuentes']" :value="old('fuente_id', $caso->fuente_id ?? '')" placeholder="Selecciona" search-placeholder="Buscar fuente…" :disabled="! $canEdit" />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="categoria_id" value="Categoría *" />
                                        <x-searchable-select id="categoria_id" name="categoria_id" :options="$options['categorias']" :value="old('categoria_id', $caso->categoria_id ?? '')" placeholder="Selecciona" search-placeholder="Buscar categoría…" :disabled="! $canEdit" />
                                    </div>
                                </div>
                            </section>

                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">4</span>
                                    <div>
                                        <h4 class="req-form__section-title">Descripción y evidencia</h4>
                                        <p class="req-form__section-desc">Qué pasó, la corrección inmediata y la acción. Para cerrar hace falta al menos un archivo.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field req-form__field-span">
                                        <x-input-label for="descripcion" value="Descripción de la situación *" />
                                        <textarea id="descripcion" name="descripcion" class="form-input" rows="4" required @disabled(! $canEdit)>{{ $textValue('descripcion') }}</textarea>
                                    </div>
                                    <div class="form-field req-form__field-span">
                                        <x-input-label for="correccion" value="Corrección (acción inmediata) *" />
                                        <textarea id="correccion" name="correccion" class="form-input" rows="3" required @disabled(! $canEdit)>{{ $textValue('correccion') }}</textarea>
                                    </div>
                                    <div class="form-field req-form__field-span">
                                        <x-input-label for="accion_realizar" value="Acción a realizar *" />
                                        <textarea id="accion_realizar" name="accion_realizar" class="form-input" rows="3" required @disabled(! $canEdit)>{{ $textValue('accion_realizar') }}</textarea>
                                    </div>
                                    <div class="form-field req-form__field-span">
                                        <x-input-label for="responsable_seguimiento" value="Responsable de seguimiento" />
                                        <textarea id="responsable_seguimiento" name="responsable_seguimiento" class="form-input" rows="2" @disabled(! $canEdit)>{{ $textValue('responsable_seguimiento') }}</textarea>
                                        <p class="req-form__hint">Texto libre. No sale del catálogo de responsables.</p>
                                    </div>
                                    @if ($canEdit)
                                        <div class="form-field req-form__field-span">
                                            <x-input-label for="attachments" value="Evidencia (PDF, JPG o PNG)" />
                                            {{-- El input nativo queda oculto; el botón usa el mismo selector de Cursos. --}}
                                            <div class="cursos-registros-page__file-picker" x-data="{ fileLabel: '' }">
                                                <input
                                                    id="attachments"
                                                    name="attachments[]"
                                                    type="file"
                                                    class="cursos-registros-page__file-input"
                                                    accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                                    multiple
                                                    @change="fileLabel = [...$event.target.files].map((file) => file.name).join(', ')"
                                                >
                                                <span class="cursos-registros-page__file-name" x-text="fileLabel || 'Sin archivos seleccionados'">Sin archivos seleccionados</span>
                                                <div class="cursos-registros-page__file-actions">
                                                    <label for="attachments" class="btn btn--secondary btn--sm">
                                                        <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                                        Elegir archivos
                                                    </label>
                                                </div>
                                            </div>
                                            <p class="req-form__hint">Obligatoria si la eficacia queda en Cerrado. Máximo 10 archivos por envío, 10 MB cada uno.</p>
                                        </div>
                                    @endif
                                </div>
                            </section>

                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">5</span>
                                    <div>
                                        <h4 class="req-form__section-title">Estado y responsable</h4>
                                        <p class="req-form__section-desc">El responsable sale de Catálogos. La eficacia del seguimiento es fija.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field">
                                        <x-input-label for="responsable_id" value="Responsable *" />
                                        <x-searchable-select id="responsable_id" name="responsable_id" :options="$options['responsables']" :value="old('responsable_id', $caso->responsable_id ?? '')" placeholder="Selecciona" search-placeholder="Buscar responsable…" :disabled="! $canEdit" />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="eficacia" value="Eficacia del seguimiento *" />
                                        <x-searchable-select id="eficacia" name="eficacia" :options="$options['eficacia']" :value="old('eficacia', $caso->eficacia ?? 'abierto')" :allow-clear="false" :disabled="! $canEdit" />
                                    </div>
                                </div>
                            </section>
                        </div>
                    </form>

                    @if ($isEdit)
                        <section class="req-form__section" id="observaciones">
                            <header class="req-form__section-head">
                                <span class="req-form__section-step">6</span>
                                <div>
                                    <h4 class="req-form__section-title">Observaciones</h4>
                                    <p class="req-form__section-desc">Historial de seguimiento. Cada nota se puede corregir o eliminar, y el cambio queda en el log.</p>
                                </div>
                            </header>

                            @if ($caso->observaciones->isEmpty())
                                <p class="req-form__hint">Todavía no hay observaciones de seguimiento.</p>
                            @else
                                <ul class="servicio-cliente-page__timeline">
                                    @foreach ($caso->observaciones as $observacion)
                                        @php
                                            $editingThis = (int) old('observacion_id') === $observacion->id;
                                        @endphp
                                        <li class="servicio-cliente-page__obs" x-data="{ editing: @js($editingThis) }">
                                            <div class="servicio-cliente-page__obs-head">
                                                <p class="servicio-cliente-page__obs-meta">
                                                    <strong>{{ $observacion->created_at?->format('d/m/Y H:i') }}</strong>
                                                    · {{ $observacion->author?->name ?? 'Usuario' }}
                                                </p>
                                                @if ($canEdit)
                                                    <div class="cursos-catalogo-page__row-actions">
                                                        <button
                                                            type="button"
                                                            class="cursos-catalogo-page__icon-btn"
                                                            title="Editar observación"
                                                            aria-label="Editar observación"
                                                            x-on:click="editing = !editing"
                                                        >
                                                            <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                        <form
                                                            method="POST"
                                                            action="{{ route('calidad.servicio-cliente.fo-gi-22.observaciones.destroy', [$caso, $observacion]) }}"
                                                            class="servicio-cliente-page__inline"
                                                            onsubmit="return confirm('¿Eliminar esta observación?');"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button
                                                                type="submit"
                                                                class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                                                title="Eliminar observación"
                                                                aria-label="Eliminar observación"
                                                            >
                                                                <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                            <p x-show="!editing">{{ $observacion->texto }}</p>
                                            @if ($canEdit)
                                                <form
                                                    method="POST"
                                                    action="{{ route('calidad.servicio-cliente.fo-gi-22.observaciones.update', [$caso, $observacion]) }}"
                                                    class="servicio-cliente-page__stack"
                                                    x-show="editing"
                                                    x-cloak
                                                >
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="observacion_id" value="{{ $observacion->id }}">
                                                    <label class="form-label" for="observacion_texto_{{ $observacion->id }}">Texto</label>
                                                    <textarea id="observacion_texto_{{ $observacion->id }}" name="texto" class="form-input" rows="3" required>{{ $editingThis ? old('texto') : $observacion->texto }}</textarea>
                                                    <button type="submit" class="btn btn--primary btn--sm">Guardar observación</button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($canEdit)
                                <form method="POST" action="{{ route('calidad.servicio-cliente.fo-gi-22.observaciones.store', $caso) }}" class="servicio-cliente-page__stack">
                                    @csrf
                                    <x-input-label for="texto" value="Nueva observación *" />
                                    <textarea id="texto" name="texto" class="form-input" rows="3" required>{{ old('texto') }}</textarea>
                                    <button type="submit" class="btn btn--primary btn--sm">Agregar observación</button>
                                </form>
                            @endif
                        </section>

                        <section class="req-form__section" id="adjuntos">
                            <header class="req-form__section-head">
                                <span class="req-form__section-step">7</span>
                                <div>
                                    <h4 class="req-form__section-title">Adjuntos</h4>
                                    <p class="req-form__section-desc">Evidencia del caso. El último archivo de un caso cerrado no se elimina.</p>
                                </div>
                            </header>

                            @if ($caso->adjuntos->isEmpty())
                                <p class="req-form__hint">Sin archivos.</p>
                            @else
                                <ul class="servicio-cliente-page__timeline">
                                    @foreach ($caso->adjuntos as $adjunto)
                                        <li>
                                            <a href="{{ route('calidad.servicio-cliente.fo-gi-22.adjuntos.download', [$caso, $adjunto]) }}">{{ $adjunto->original_name }}</a>
                                            <span class="text-caption">{{ $adjunto->created_at?->format('d/m/Y H:i') }}</span>
                                            @if ($canEdit)
                                                <form method="POST" action="{{ route('calidad.servicio-cliente.fo-gi-22.adjuntos.destroy', [$caso, $adjunto]) }}" class="servicio-cliente-page__inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar adjunto" aria-label="Eliminar adjunto">
                                                        <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                    </button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if ($canEdit)
                                {{-- Nombre elegido, Elegir archivos y Subir archivos en una sola fila. --}}
                                <form method="POST" action="{{ route('calidad.servicio-cliente.fo-gi-22.adjuntos.store', $caso) }}" enctype="multipart/form-data">
                                    @csrf
                                    <x-input-label for="attachments_extra" value="Agregar archivos" />
                                    <div class="servicio-cliente-page__upload-line" x-data="{ fileLabel: '' }">
                                        <input
                                            id="attachments_extra"
                                            name="attachments[]"
                                            type="file"
                                            class="cursos-registros-page__file-input"
                                            accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                            multiple
                                            required
                                            @change="fileLabel = [...$event.target.files].map((file) => file.name).join(', ')"
                                        >
                                        <span class="cursos-registros-page__file-name" x-text="fileLabel || 'Sin archivos seleccionados'">Sin archivos seleccionados</span>
                                        <label for="attachments_extra" class="btn btn--secondary btn--sm">
                                            <x-lucide-upload width="15" height="15" aria-hidden="true" />
                                            Elegir archivos
                                        </label>
                                        <button type="submit" class="btn btn--secondary btn--sm">Subir archivos</button>
                                    </div>
                                </form>
                            @endif
                        </section>
                    @endif

                    @if ($canEdit)
                        {{-- Cancelar y guardar quedan después de observaciones y adjuntos, pero envían el formulario del caso. --}}
                        <div class="req-form-actions">
                            <p class="req-form-actions__note">
                                @if ($isEdit)
                                    Revise eficacia y evidencia antes de guardar. Un caso cerrado no puede quedar sin archivos.
                                @else
                                    Revise cliente, tipología y descripción antes de crear. El consecutivo no se puede cambiar después.
                                @endif
                            </p>
                            <div class="req-form-actions__group">
                                <a
                                    href="{{ route('calidad.servicio-cliente.fo-gi-22.index') }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Cancelar"
                                    aria-label="Cancelar"
                                >
                                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                                </a>
                                <button
                                    type="submit"
                                    form="fo-gi-22-caso-form"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="{{ $isEdit ? 'Guardar cambios' : 'Crear caso' }}"
                                    aria-label="{{ $isEdit ? 'Guardar cambios' : 'Crear caso' }}"
                                >
                                    @if ($isEdit)
                                        <x-lucide-save width="18" height="18" aria-hidden="true" />
                                    @else
                                        <x-lucide-send width="18" height="18" aria-hidden="true" />
                                    @endif
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <aside class="req-form-aside">
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de {{ $isEdit ? 'guardar' : 'crear' }}</h3>
                            <p class="panel-text">Verifique estos puntos para no devolver el caso.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="req-form-guide__list">
                                <li class="req-form-guide__item">Interno: el cliente queda SJSEGURIDAD.</li>
                                <li class="req-form-guide__item">Externo: elija el cliente por nombre en la matriz comercial.</li>
                                <li class="req-form-guide__item">El plazo son 10 días hábiles después de la recepción.</li>
                                <li class="req-form-guide__item">Cerrar exige al menos un PDF, JPG o PNG.</li>
                                <li class="req-form-guide__item">Si falta categoría o responsable, créelos en Catálogos.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Qué pasa después</h3>
                        </div>
                        <div class="panel__body">
                            <p class="panel-text" style="margin: 0; line-height: 1.55;">
                                @if ($isEdit)
                                    El listado y el dashboard toman la eficacia y las fechas guardadas. Las observaciones nuevas quedan en el historial del caso.
                                @else
                                    El caso entra al listado FO-GI-22 con un consecutivo automático. Las observaciones se agregan al abrir el caso.
                                @endif
                            </p>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
