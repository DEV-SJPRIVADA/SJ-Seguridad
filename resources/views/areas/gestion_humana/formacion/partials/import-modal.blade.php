{{-- Variables: $canEdit, $importTemplateUrl, $importUrl, $importAnioOptions, $importMesOptions, $show --}}
@php
    $show = (bool) ($show ?? false);
    $defaultMode = (string) old('mode', 'period');
    $defaultAnio = (string) old('anio', now()->format('Y'));
    $defaultMes = (string) old('mes', now()->format('n'));
@endphp
<x-modal name="formacion-import" maxWidth="md" :show="$show" focusable>
    <div
        class="modal-card ficha-empleados-masivos-modal formacion-import-modal"
        x-data="{ mode: @js($defaultMode) }"
    >
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Importar formaciones</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Elija reemplazar <strong>todo</strong> o solo un <strong>mes</strong> (ej. cargar octubre sin tocar septiembre).
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                title="Cerrar"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'formacion-import')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->has('import_file') || $errors->has('confirm_replace') || $errors->has('mode') || $errors->has('anio') || $errors->has('mes'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                {{ $errors->first('import_file')
                    ?: ($errors->first('confirm_replace')
                        ?: ($errors->first('mode')
                            ?: ($errors->first('anio') ?: $errors->first('mes')))) }}
            </div>
        @endif

        <div class="ficha-empleados-masivos-modal__cards">
            <section class="ficha-empleados-masivos-modal__card ficha-empleados-masivos-modal__card--import">
                <div class="ficha-empleados-masivos-modal__card-head">
                    <span class="ficha-empleados-masivos-modal__card-icon ficha-empleados-masivos-modal__card-icon--import" aria-hidden="true">
                        <x-lucide-replace width="18" height="18" aria-hidden="true" />
                    </span>
                    <div>
                        <h4 class="ficha-empleados-masivos-modal__card-title">Plantilla e importar</h4>
                        <p class="ficha-empleados-masivos-modal__card-note">
                            Excel con columnas: Número de ID, Nombre completo, Fecha de inicio del curso, Nombre completo del curso, Calificación, Nombre de la categoría.
                            Formatos: .xlsx, .xls o .csv (máx. 50 MB). En modo mes, <strong>todas</strong> las fechas del archivo deben ser de ese año/mes; si no, se rechaza el import.
                        </p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__import">
                    <form
                        method="POST"
                        action="{{ $importUrl }}"
                        enctype="multipart/form-data"
                        class="ficha-empleados-masivos-modal__import-form"
                        data-formacion-import-form
                    >
                        @csrf

                        {{-- Modo: opciones apiladas (evita fieldset nativo que se veía roto sin CSS compilado) --}}
                        <div class="formacion-import-modal__mode">
                            <p class="formacion-import-modal__mode-legend" id="formacion-import-mode-label">Tipo de carga</p>
                            <div
                                class="formacion-import-modal__mode-grid"
                                role="radiogroup"
                                aria-labelledby="formacion-import-mode-label"
                            >
                                <label
                                    class="formacion-import-modal__mode-card"
                                    x-bind:class="{ 'is-active': mode === 'period' }"
                                >
                                    <input
                                        type="radio"
                                        name="mode"
                                        value="period"
                                        class="formacion-import-modal__mode-input"
                                        x-model="mode"
                                        @checked($defaultMode === 'period')
                                    >
                                    <span class="formacion-import-modal__mode-card-body">
                                        <span class="formacion-import-modal__mode-card-title">Solo un mes</span>
                                        <span class="formacion-import-modal__mode-card-desc">
                                            Borra ese año/mes e inserta el Excel. El resto de meses no se toca.
                                        </span>
                                    </span>
                                </label>
                                <label
                                    class="formacion-import-modal__mode-card"
                                    x-bind:class="{ 'is-active': mode === 'all' }"
                                >
                                    <input
                                        type="radio"
                                        name="mode"
                                        value="all"
                                        class="formacion-import-modal__mode-input"
                                        x-model="mode"
                                        @checked($defaultMode === 'all')
                                    >
                                    <span class="formacion-import-modal__mode-card-body">
                                        <span class="formacion-import-modal__mode-card-title">Reemplazar todo</span>
                                        <span class="formacion-import-modal__mode-card-desc">
                                            Elimina todos los registros actuales y deja solo los del archivo.
                                        </span>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="cursos-registros-page__filters" style="margin-bottom: 0.75rem;" x-show="mode === 'period'" x-cloak>
                            <div class="form-field">
                                <label class="form-label" for="formacion_import_anio">Año</label>
                                <x-searchable-select
                                    id="formacion_import_anio"
                                    name="anio"
                                    :options="$importAnioOptions"
                                    :value="$defaultAnio"
                                    placeholder="Año"
                                    :required="false"
                                />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="formacion_import_mes">Mes</label>
                                <x-searchable-select
                                    id="formacion_import_mes"
                                    name="mes"
                                    :options="$importMesOptions"
                                    :value="$defaultMes"
                                    placeholder="Mes"
                                    :required="false"
                                />
                            </div>
                        </div>

                        <input
                            type="file"
                            id="formacion-import-file"
                            name="import_file"
                            accept=".xlsx,.xls,.csv"
                            class="ficha-empleados-masivos-modal__file-input"
                            required
                            hidden
                            data-formacion-import-file
                        >

                        <div class="formacion-import-modal__toolbar">
                            <a
                                href="{{ $importTemplateUrl }}"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Descargar plantilla vacía"
                                aria-label="Descargar plantilla vacía"
                            >
                                <x-selfhst-microsoft-excel-2013 width="18" height="18" aria-hidden="true" />
                            </a>
                            <span class="ficha-empleados-masivos-modal__file-name formacion-import-modal__file-name" data-formacion-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions formacion-import-modal__actions">
                                <label
                                    for="formacion-import-file"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Elegir archivo"
                                    aria-label="Elegir archivo"
                                    data-formacion-import-choose
                                >
                                    <x-lucide-file-up width="18" height="18" aria-hidden="true" />
                                </label>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Importar"
                                    aria-label="Importar"
                                    data-formacion-import-submit
                                    disabled
                                >
                                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                                </button>
                            </div>
                        </div>

                        <label class="formacion-import-modal__confirm">
                            <input
                                type="checkbox"
                                name="confirm_replace"
                                value="1"
                                class="formacion-import-modal__checkbox"
                                data-formacion-import-confirm
                                @checked(old('confirm_replace'))
                            >
                            <span class="formacion-import-modal__confirm-text" x-show="mode === 'period'" x-cloak>
                                Confirmo que se borrarán solo los registros del año y mes seleccionados
                            </span>
                            <span class="formacion-import-modal__confirm-text" x-show="mode === 'all'" x-cloak>
                                Confirmo que se borrarán todos los registros actuales
                            </span>
                        </label>
                    </form>
                </div>
            </section>
        </div>

        <div class="ficha-empleados-masivos-modal__loading" data-formacion-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text" x-show="mode === 'period'" x-cloak>
                    Validando filas y reemplazando solo el mes seleccionado. No cierre esta ventana.
                </p>
                <p class="ficha-empleados-masivos-modal__loading-text" x-show="mode === 'all'" x-cloak>
                    Validando filas y reemplazando el dataset completo. No cierre esta ventana.
                </p>
            </div>
        </div>
    </div>
</x-modal>
