{{--
    Formulario MT-GI-11 (alta, edición y "nueva versión").

    Variables:
    - $solicitud: MtGi11Solicitud|null — null en alta y en nueva versión.
    - $canEdit: bool — habilita los campos.
    - $formOptions: opciones de los cinco catálogos.
    - $defaults: valores iniciales (defaults del alta o precarga de nueva versión).
    - $action: URL de envío (store o update).
    - $sourceSolicitud: solicitud origen cuando es «nueva versión».

    Layout: shell canónico de formularios del sistema (req-form-layout) con
    secciones numeradas y guía lateral (x-form-aside-guide).
--}}
@php
    use App\Support\DisplayDate;

    $isNew = $solicitud === null;
    $esNuevaVersion = isset($sourceSolicitud) && $sourceSolicitud !== null;

    // Valor del campo: en edición manda el registro; en alta, los defaults o la precarga.
    $values = function (string $key, string $fallback = '') use ($solicitud, $defaults): string {
        if ($solicitud !== null) {
            $raw = $solicitud->getAttribute($key);
            if ($raw instanceof \DateTimeInterface) {
                return $raw->format('Y-m-d');
            }

            return (string) ($raw ?? '');
        }

        return (string) ($defaults[$key] ?? $fallback);
    };

    $title = $isNew
        ? ($esNuevaVersion ? 'Nueva versión de '.$sourceSolicitud->consecutivo : 'Nueva solicitud MT-GI-11')
        : 'Solicitud '.$solicitud->consecutivo;

    $subtitle = $isNew
        ? 'Complete las secciones. Los textos se guardan en MAYÚSCULAS.'
        : 'Registrada por '.$solicitud->solicitanteLabel().' el '.DisplayDate::dateTime($solicitud->created_at).'.';

    $totalAdjuntos = $solicitud?->adjuntos->count() ?? 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        @include('areas.calidad.mt_gi_11.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section mt-gi-11-page mt-gi-11-form-page">
        <div class="app-container">
            {{-- Barra superior: volver + título + estado del registro --}}
            <div class="mt-gi-11-form-page__head">
                <div class="mt-gi-11-form-page__heading">
                    <a
                        href="{{ route('calidad.mt-gi-11.matriz') }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver al listado"
                        aria-label="Volver al listado"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                    <div>
                        <h2 class="page-title">{{ $title }}</h2>
                        <p class="page-subtitle">{{ $subtitle }}</p>
                    </div>
                </div>

                @if (! $isNew)
                    <div class="mt-gi-11-form-page__pills">
                        <span class="status-pill status-pill--{{ $solicitud->estado?->pillClass() ?? 'muted' }}">
                            {{ $solicitud->estado?->name ?? 'Sin estado' }}
                        </span>
                        <span class="status-pill status-pill--muted">{{ $totalAdjuntos }} adjunto(s)</span>
                        @unless ($canEdit)
                            <span class="status-pill status-pill--muted">Solo lectura</span>
                        @endunless
                    </div>
                @endif
            </div>

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

            @if ($esNuevaVersion && $sourceSolicitud)
                {{-- Aviso: la versión nueva no hereda justificación ni adjuntos. --}}
                <div class="mt-gi-11-form-page__notice">
                    <span class="mt-gi-11-form-page__notice-icon" aria-hidden="true">
                        <x-lucide-copy width="18" height="18" />
                    </span>
                    <div>
                        <p class="mt-gi-11-form-page__notice-title">Copia de {{ $sourceSolicitud->consecutivo }}</p>
                        <p class="mt-gi-11-form-page__notice-text">
                            Ya se cargaron los datos del documento con el mismo consecutivo y la versión actualizada en {{ $values('version_actualizada') }}.
                            La fecha de solicitud se asigna con el día de hoy; la justificación y los adjuntos los escribe usted.
                        </p>
                    </div>
                </div>
            @endif

            <div class="req-form-layout">
                <div class="req-form-layout__main">
                    <form
                        id="mt-gi-11-form"
                        method="POST"
                        action="{{ $action }}"
                        enctype="multipart/form-data"
                        class="req-form"
                    >
                        @csrf
                        @if (! $isNew)
                            @method('PATCH')
                        @endif

                        {{-- Metadatos del formato: la fecha de solicitud es automática --}}
                        <div class="req-form__meta req-form__meta--edit">
                            <div class="req-form__meta-item">
                                <span class="req-form__meta-label">Formato</span>
                                <span class="req-form__meta-value">MT-GI-11</span>
                            </div>
                            <div class="req-form__meta-item">
                                <span class="req-form__meta-label">Fecha de solicitud</span>
                                <span class="req-form__meta-value">
                                    {{ $solicitud?->fecha_solicitud ? DisplayDate::date($solicitud->fecha_solicitud) : DisplayDate::date(now()) }}
                                    @if ($isNew)
                                        <span class="req-form__meta-hint">Se asigna automáticamente</span>
                                    @endif
                                </span>
                            </div>
                            <div class="req-form__meta-item">
                                <span class="req-form__meta-label">Consecutivo</span>
                                <span class="req-form__meta-value">{{ $values('consecutivo') ?: 'Se define abajo' }}</span>
                            </div>
                            <div class="req-form__meta-item">
                                <span class="req-form__meta-label">Solicitado por</span>
                                <span class="req-form__meta-value">
                                    {{ $solicitud?->solicitanteLabel() ?? (auth()->user()?->name ?? '—') }}
                                </span>
                            </div>
                        </div>

                        <fieldset class="req-form__fieldset" @disabled(! $canEdit)>
                            {{-- 1. Documento: qué se quiere estandarizar --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">1</span>
                                    <div>
                                        <h4 class="req-form__section-title">Documento</h4>
                                        <p class="req-form__section-desc">Identifique el documento y el proceso al que pertenece.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field">
                                        <x-input-label for="proceso_id" value="Proceso *" />
                                        <x-searchable-select
                                            id="proceso_id"
                                            name="proceso_id"
                                            :options="$formOptions['procesos']"
                                            :value="old('proceso_id', $values('proceso_id'))"
                                            placeholder="Seleccione el proceso…"
                                            search-placeholder="Buscar proceso…"
                                            :required="true"
                                            :disabled="! $canEdit"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="tipo_documento_id" value="Tipo de documento *" />
                                        <x-searchable-select
                                            id="tipo_documento_id"
                                            name="tipo_documento_id"
                                            :options="$formOptions['tipos_documento']"
                                            :value="old('tipo_documento_id', $values('tipo_documento_id'))"
                                            placeholder="Seleccione el tipo…"
                                            :required="true"
                                            :disabled="! $canEdit"
                                        />
                                    </div>
                                    <div class="form-field mt-gi-11-form-page__field--wide">
                                        <x-input-label for="nombre_documento" value="Nombre del documento *" />
                                        <input
                                            id="nombre_documento"
                                            name="nombre_documento"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            value="{{ old('nombre_documento', $values('nombre_documento')) }}"
                                            placeholder="Ej. Procedimiento de gestión documental"
                                            required
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="codigo_actual" value="Código actual" />
                                        <input
                                            id="codigo_actual"
                                            name="codigo_actual"
                                            type="text"
                                            class="form-input"
                                            maxlength="60"
                                            value="{{ old('codigo_actual', $values('codigo_actual')) }}"
                                            placeholder="Ej. PR-GI-01"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="version_actual" value="Versión actual" />
                                        <input
                                            id="version_actual"
                                            name="version_actual"
                                            type="text"
                                            class="form-input"
                                            maxlength="30"
                                            value="{{ old('version_actual', $values('version_actual')) }}"
                                            placeholder="Ej. 03"
                                            autocomplete="off"
                                        >
                                    </div>
                                </div>
                            </section>

                            {{-- 2. Solicitud: tipo, consecutivo y justificación --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">2</span>
                                    <div>
                                        <h4 class="req-form__section-title">Solicitud</h4>
                                        <p class="req-form__section-desc">Qué se solicita y por qué. El consecutivo puede repetirse entre versiones.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field">
                                        <x-input-label for="consecutivo" value="Consecutivo de solicitud *" />
                                        <input
                                            id="consecutivo"
                                            name="consecutivo"
                                            type="text"
                                            class="form-input"
                                            maxlength="60"
                                            value="{{ old('consecutivo', $values('consecutivo')) }}"
                                            placeholder="Ej. MT-GI-11-001"
                                            required
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="tipo_solicitud_id" value="Tipo de solicitud *" />
                                        <x-searchable-select
                                            id="tipo_solicitud_id"
                                            name="tipo_solicitud_id"
                                            :options="$formOptions['tipos_solicitud']"
                                            :value="old('tipo_solicitud_id', $values('tipo_solicitud_id'))"
                                            placeholder="Seleccione el tipo…"
                                            :required="true"
                                            :disabled="! $canEdit"
                                        />
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="estado_id" value="Estado *" />
                                        <x-searchable-select
                                            id="estado_id"
                                            name="estado_id"
                                            :options="$formOptions['estados']"
                                            :value="old('estado_id', $values('estado_id'))"
                                            placeholder="Seleccione el estado…"
                                            :required="true"
                                            :disabled="! $canEdit"
                                        />
                                    </div>
                                    <div class="form-field mt-gi-11-form-page__field--full">
                                        <x-input-label for="justificacion" value="Justificación de la solicitud *" />
                                        <textarea
                                            id="justificacion"
                                            name="justificacion"
                                            class="form-input mt-gi-11-form-page__textarea"
                                            rows="5"
                                            maxlength="2000"
                                            placeholder="Explique el motivo de la solicitud: cambio normativo, hallazgo de auditoría, actualización de alcance…"
                                            required
                                        >{{ old('justificacion', $values('justificacion')) }}</textarea>
                                        <p class="req-form__hint">Máximo 2000 caracteres.</p>
                                    </div>
                                </div>
                            </section>

                            {{-- 3. Responsable del proceso --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">3</span>
                                    <div>
                                        <h4 class="req-form__section-title">Responsable del proceso</h4>
                                        <p class="req-form__section-desc">Quién responde por el documento ante Calidad.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--two">
                                    <div class="form-field">
                                        <x-input-label for="responsable_proceso" value="Nombre del responsable *" />
                                        <input
                                            id="responsable_proceso"
                                            name="responsable_proceso"
                                            type="text"
                                            class="form-input"
                                            maxlength="255"
                                            value="{{ old('responsable_proceso', $values('responsable_proceso')) }}"
                                            placeholder="Nombre completo"
                                            required
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="cargo_responsable_id" value="Cargo del responsable *" />
                                        <x-searchable-select
                                            id="cargo_responsable_id"
                                            name="cargo_responsable_id"
                                            :options="$formOptions['cargos_responsable']"
                                            :value="old('cargo_responsable_id', $values('cargo_responsable_id'))"
                                            placeholder="Seleccione el cargo…"
                                            search-placeholder="Buscar cargo…"
                                            :required="true"
                                            :disabled="! $canEdit"
                                        />
                                    </div>
                                </div>
                            </section>

                            {{-- 4. Estandarización: se puede completar después --}}
                            <section class="req-form__section">
                                <header class="req-form__section-head">
                                    <span class="req-form__section-step">4</span>
                                    <div>
                                        <h4 class="req-form__section-title">Estandarización</h4>
                                        <p class="req-form__section-desc">Opcional al crear. Se completa cuando Calidad estandariza el documento.</p>
                                    </div>
                                </header>

                                <div class="form-grid form-grid--three">
                                    <div class="form-field">
                                        <x-input-label for="codigo_estandarizacion" value="Código de estandarización" />
                                        <input
                                            id="codigo_estandarizacion"
                                            name="codigo_estandarizacion"
                                            type="text"
                                            class="form-input"
                                            maxlength="60"
                                            value="{{ old('codigo_estandarizacion', $values('codigo_estandarizacion')) }}"
                                            placeholder="Ej. PR-GI-01-V2"
                                            autocomplete="off"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="fecha_estandarizacion" value="Fecha de estandarización" />
                                        <input
                                            id="fecha_estandarizacion"
                                            name="fecha_estandarizacion"
                                            type="date"
                                            class="form-input"
                                            value="{{ old('fecha_estandarizacion', $values('fecha_estandarizacion')) }}"
                                        >
                                    </div>
                                    <div class="form-field">
                                        <x-input-label for="version_actualizada" value="Versión actualizada" />
                                        <input
                                            id="version_actualizada"
                                            name="version_actualizada"
                                            type="number"
                                            min="0"
                                            max="9999"
                                            step="1"
                                            class="form-input"
                                            value="{{ old('version_actualizada', $values('version_actualizada')) }}"
                                            placeholder="Ej. 4"
                                        >
                                    </div>
                                </div>
                            </section>

                            @if ($canEdit)
                                {{-- 5. Adjuntos: en alta se suben aquí; en edición se listan aparte --}}
                                <section class="req-form__section">
                                    <header class="req-form__section-head">
                                        <span class="req-form__section-step">5</span>
                                        <div>
                                            <h4 class="req-form__section-title">Datos adjuntos</h4>
                                            <p class="req-form__section-desc">PDF, JPG o PNG. Máximo 10 archivos de 10 MB por envío.</p>
                                        </div>
                                    </header>

                                    <div class="mt-gi-11-form-page__upload">
                                        {{-- Input nativo oculto: se dispara desde la etiqueta estilizada. --}}
                                        <input
                                            id="attachments"
                                            name="attachments[]"
                                            type="file"
                                            class="mt-gi-11-form-page__upload-input"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            data-mt-gi-11-upload
                                            multiple
                                        >
                                        <div class="mt-gi-11-form-page__upload-row">
                                            <label class="mt-gi-11-form-page__upload-label" for="attachments">
                                                <x-lucide-paperclip width="18" height="18" aria-hidden="true" />
                                                <span>Seleccionar archivos</span>
                                            </label>
                                            <span class="mt-gi-11-form-page__upload-file" data-mt-gi-11-upload-name>
                                                Sin archivos seleccionados
                                            </span>
                                        </div>
                                        <p class="mt-gi-11-form-page__upload-hint">
                                            Puede seleccionar varios a la vez. El límite es por envío, no por solicitud.
                                        </p>
                                    </div>
                                </section>
                            @endif
                        </fieldset>

                        @if ($canEdit)
                            {{-- Barra de acciones: enviar el formulario desde el pie --}}
                            <div class="req-form-actions">
                                <p class="req-form-actions__note">
                                    @if ($isNew)
                                        Revise documento, justificación y responsable antes de guardar.
                                    @else
                                        Revise estado y evidencia antes de guardar los cambios.
                                    @endif
                                </p>
                                <div class="req-form-actions__group">
                                    <a
                                        href="{{ route('calidad.mt-gi-11.matriz') }}"
                                        class="btn btn--secondary btn--sm"
                                    >
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn--primary btn--sm">
                                        <x-lucide-save width="16" height="16" aria-hidden="true" />
                                        {{ $isNew ? 'Guardar solicitud' : 'Guardar cambios' }}
                                    </button>
                                </div>
                            </div>
                        @endif
                    </form>

                    @if (! $isNew)
                        {{-- Adjuntos existentes con previsualización y descarga --}}
                        <section id="adjuntos" class="panel mt-gi-11-form-page__attachments">
                            <div class="panel__header panel__header--compact">
                                <h3 class="panel-title">Datos adjuntos ({{ $totalAdjuntos }})</h3>
                            </div>
                            <div class="panel__body">
                                @if ($solicitud->adjuntos->isEmpty())
                                    <p class="panel-text">Sin archivos adjuntos.</p>
                                @else
                                    <ul class="mt-gi-11-form-page__attachments-list">
                                        @foreach ($solicitud->adjuntos as $adjunto)
                                            <li class="mt-gi-11-form-page__attachment">
                                                <span class="mt-gi-11-form-page__attachment-icon" aria-hidden="true">
                                                    <x-lucide-paperclip width="18" height="18" />
                                                </span>
                                                <div class="mt-gi-11-form-page__attachment-meta">
                                                    <p class="mt-gi-11-form-page__attachment-name" title="{{ $adjunto->original_name }}">{{ $adjunto->original_name }}</p>
                                                    <p class="mt-gi-11-form-page__attachment-sub">
                                                        {{ $adjunto->sizeLabel() }} · {{ DisplayDate::dateTime($adjunto->created_at) }}
                                                    </p>
                                                </div>
                                                <div class="cursos-catalogo-page__row-actions">
                                                    @if ($adjunto->isPreviewable())
                                                        <a
                                                            href="{{ route('calidad.mt-gi-11.matriz.adjuntos.preview', [$solicitud, $adjunto]) }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="cursos-catalogo-page__icon-btn"
                                                            title="Previsualizar"
                                                            aria-label="Previsualizar"
                                                        >
                                                            <x-lucide-eye width="16" height="16" aria-hidden="true" />
                                                        </a>
                                                    @endif
                                                    <a
                                                        href="{{ route('calidad.mt-gi-11.matriz.adjuntos.download', [$solicitud, $adjunto]) }}"
                                                        class="cursos-catalogo-page__icon-btn"
                                                        title="Descargar"
                                                        aria-label="Descargar"
                                                    >
                                                        <x-lucide-download width="16" height="16" aria-hidden="true" />
                                                    </a>
                                                    @if ($canEdit)
                                                        <form
                                                            method="POST"
                                                            action="{{ route('calidad.mt-gi-11.matriz.adjuntos.destroy', [$solicitud, $adjunto]) }}"
                                                            onsubmit="return confirm('¿Eliminar el adjunto {{ $adjunto->original_name }}?');"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar" aria-label="Eliminar">
                                                                <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </section>
                    @endif
                </div>

                {{-- Guía lateral: checklist y efecto del guardado --}}
                <aside class="req-form-aside">
                    <x-form-aside-guide
                        :title="$isNew ? 'Antes de crear' : 'Antes de guardar'"
                        subtitle="Revise estos puntos para evitar devoluciones."
                        icon="list-checks"
                        :items="[
                            'El proceso y el tipo de documento salen de Catálogos.',
                            'La fecha de solicitud se asigna automáticamente con el día de hoy.',
                            'El consecutivo puede repetirse: identifica la versión, no la fila.',
                            'La justificación es obligatoria y admite hasta 2000 caracteres.',
                            'La fecha de estandarización no puede ser anterior a la solicitud.',
                            'Adjuntos: PDF, JPG o PNG, máximo 10 por envío.',
                        ]"
                    />

                    <x-form-aside-guide title="Qué pasa después" icon="route">
                        <li class="form-aside-guide__item">
                            @if ($isNew)
                                La solicitud entra a la matriz con su autor como dueño. Solo usted y Calidad la verán.
                            @else
                                Los cambios quedan en la matriz y en el log de auditoría, con fecha y usuario.
                            @endif
                        </li>
                        <li class="form-aside-guide__item">
                            Para una nueva versión del mismo documento use
                            <strong>Nueva versión</strong> desde la matriz: conserva el consecutivo.
                        </li>
                    </x-form-aside-guide>

                    @if (! $isNew)
                        <x-form-aside-guide title="Registro" icon="info">
                            <li class="form-aside-guide__item">
                                ID <strong>{{ $solicitud->id }}</strong> — es la llave de esta solicitud.
                            </li>
                            <li class="form-aside-guide__item">
                                Creada el {{ DisplayDate::dateTime($solicitud->created_at) }}
                                @if ($solicitud->updated_at && $solicitud->updated_at->ne($solicitud->created_at))
                                    · actualizada el {{ DisplayDate::dateTime($solicitud->updated_at) }}
                                @endif
                            </li>
                            <li class="form-aside-guide__item">
                                Alcance: {{ $viewAll ? 've todas las solicitudes del tablero' : 'solo ve sus propias solicitudes' }}.
                            </li>
                        </x-form-aside-guide>
                    @endif
                </aside>
            </div>
        </div>
    </div>

    @if ($canEdit)
        <script>
            // Muestra el nombre de los archivos elegidos junto al botón estilizado.
            document.addEventListener('change', function (event) {
                const input = event.target;

                if (! input.matches('[data-mt-gi-11-upload]')) {
                    return;
                }

                const labelEl = document.querySelector('[data-mt-gi-11-upload-name]');
                if (! labelEl) {
                    return;
                }

                const files = Array.from(input.files || []);
                const total = files.length;

                if (total === 0) {
                    labelEl.textContent = 'Sin archivos seleccionados';

                    return;
                }

                const names = files.map((file) => file.name).join(', ');
                labelEl.textContent = total === 1 ? names : `${total} archivos: ${names}`;
            });
        </script>
    @endif
</x-app-layout>
