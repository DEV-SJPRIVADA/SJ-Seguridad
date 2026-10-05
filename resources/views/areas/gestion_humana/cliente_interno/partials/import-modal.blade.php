{{-- Variables: $importTemplateUrl, $importUrl, $periodCountUrl, $importAnioOptions, $importMesOptions, $show --}}
@php
    $show = (bool) ($show ?? false);
    $defaultAnio = (string) old('anio', now()->format('Y'));
    $defaultMes = (string) old('mes', now()->format('n'));
@endphp
<x-modal name="cliente-interno-import" maxWidth="md" :show="$show" focusable>
    <div class="modal-card ficha-empleados-masivos-modal formacion-import-modal">
        <div class="ficha-empleados-masivos-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                    <x-lucide-upload width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="ficha-empleados-masivos-modal__title">Importar solicitudes</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Reemplaza solo las solicitudes del <strong>año y mes</strong> seleccionados.
                        Las filas del Excel fuera de ese periodo también se insertan (opción B).
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                title="Cerrar"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'cliente-interno-import')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        @if ($errors->has('import_file') || $errors->has('confirm_replace') || $errors->has('anio') || $errors->has('mes'))
            <div class="alert alert--danger ficha-empleados-masivos-modal__alert">
                {{ $errors->first('import_file') ?: ($errors->first('confirm_replace') ?: ($errors->first('anio') ?: $errors->first('mes'))) }}
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
                            Columnas: Fecha de solicitud, Nombre y apellidos, Cédula, Correo electrónico, Solicitud, Fecha de respuesta, Estado, Novedad, Días de respuesta.
                            Formatos: .xlsx, .xls o .csv (máx. 50 MB).
                        </p>
                    </div>
                </div>

                <div class="ficha-empleados-masivos-modal__import">
                    <form
                        method="POST"
                        action="{{ $importUrl }}"
                        enctype="multipart/form-data"
                        class="ficha-empleados-masivos-modal__import-form"
                        data-ci-import-form
                        data-period-count-url="{{ $periodCountUrl }}"
                    >
                        @csrf

                        {{-- Periodo a reemplazar (DELETE solo este año+mes). --}}
                        <div class="cursos-registros-page__filters" style="margin-bottom: 0.75rem;">
                            <div class="form-field">
                                <label class="form-label" for="ci_import_anio">Año del periodo</label>
                                <x-searchable-select
                                    id="ci_import_anio"
                                    name="anio"
                                    :options="$importAnioOptions"
                                    :value="$defaultAnio"
                                    placeholder="Año"
                                    :required="true"
                                />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="ci_import_mes">Mes del periodo</label>
                                <x-searchable-select
                                    id="ci_import_mes"
                                    name="mes"
                                    :options="$importMesOptions"
                                    :value="$defaultMes"
                                    placeholder="Mes"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <p class="ficha-empleados-masivos-modal__card-note" data-ci-import-period-count aria-live="polite">
                            Seleccione año y mes para ver cuántas filas se borrarán.
                        </p>

                        <input
                            type="file"
                            id="ci-import-file"
                            name="import_file"
                            accept=".xlsx,.xls,.csv"
                            class="ficha-empleados-masivos-modal__file-input"
                            required
                            hidden
                            data-ci-import-file
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
                            <span class="ficha-empleados-masivos-modal__file-name formacion-import-modal__file-name" data-ci-import-name>Sin archivo seleccionado</span>
                            <div class="ficha-empleados-masivos-modal__import-actions formacion-import-modal__actions">
                                <label
                                    for="ci-import-file"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Elegir archivo"
                                    aria-label="Elegir archivo"
                                    data-ci-import-choose
                                >
                                    <x-lucide-file-up width="18" height="18" aria-hidden="true" />
                                </label>
                                <button
                                    type="submit"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                    title="Importar (reemplaza el periodo)"
                                    aria-label="Importar"
                                    data-ci-import-submit
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
                                data-ci-import-confirm
                                @checked(old('confirm_replace'))
                            >
                            <span class="formacion-import-modal__confirm-text">
                                Confirmo que se borrarán las solicitudes del periodo seleccionado
                            </span>
                        </label>
                    </form>
                </div>
            </section>
        </div>

        <div class="ficha-empleados-masivos-modal__loading" data-ci-import-loading hidden aria-live="polite" aria-busy="true">
            <div class="ficha-empleados-masivos-modal__loading-card">
                <span class="ficha-empleados-masivos-modal__spinner" aria-hidden="true"></span>
                <p class="ficha-empleados-masivos-modal__loading-title">Importando archivo</p>
                <p class="ficha-empleados-masivos-modal__loading-text">Validando filas y reemplazando el periodo. No cierre esta ventana.</p>
            </div>
        </div>
    </div>
</x-modal>
