<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.partials.ficha-empleados-subnav', ['subTabs' => $subTabs])
    </x-slot>

    {{-- Carta rápida: solo campos de plantilla de contratación; no mueve a En ficha --}}
    <div
        class="page-section ficha-empleados-page ficha-empleados-page--form"
        x-data="{
            scrollToTop() {
                const main = document.querySelector('.app-main');
                if (main) {
                    main.scrollTo({ top: 0, behavior: 'smooth' });
                }
                window.scrollTo({ top: 0, behavior: 'smooth' });

                const top = document.getElementById('ficha-carta-contratacion-top');
                if (top) {
                    top.focus({ preventScroll: true });
                }
            },
        }"
    >
        <div class="app-container">
            <div
                id="ficha-carta-contratacion-top"
                class="ficha-empleados-page__workspace-header ficha-empleados-page__workspace-header--form"
                tabindex="-1"
            >
                <div class="panel-heading-row ficha-empleados-page__title-row block-spaced-sm">
                    <div class="ficha-empleados-page__title-copy">
                        <h2 class="panel-title panel-title--page">
                            Carta de contratación — {{ $fichaEntry->hired_full_name }}
                        </h2>
                        <p class="panel-text">
                            Complete solo los datos de la carta. El empleado permanece en Pendientes
                            @if ($fichaEntry->isRehirePending())
                                (reingreso)
                            @endif
                            ; después puede completar la ficha con
                            @if ($fichaEntry->isRehirePending())
                                Gestionar reingreso
                            @else
                                Gestionar Empleado
                            @endif
                            .
                        </p>
                    </div>

                    <div class="ficha-empleados-page__title-actions" role="toolbar" aria-label="Acciones de carta rápida">
                        <a
                            href="{{ route('gestion-humana.ficha-empleados.employees.index', ['estado' => 'pendientes']) }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Volver a Pendientes"
                            aria-label="Volver a Pendientes"
                        >
                            <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                        </a>
                        <button
                            type="submit"
                            form="ficha-carta-contratacion-form"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            title="Generar y descargar carta"
                            aria-label="Generar y descargar carta"
                        >
                            <x-lucide-file-text width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert--danger ficha-empleados-page__alert">
                    <ul class="ficha-empleados-form__error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (count($templates) === 0)
                <div class="alert alert--danger ficha-empleados-page__alert">
                    No hay plantillas de contratación con archivo. Súbalas en el tablero Plantillas Word.
                </div>
            @endif

            <form
                method="POST"
                action="{{ route('gestion-humana.ficha-empleados.employees.contratacion.quick.generate', $fichaEntry) }}"
                class="panel ficha-empleados-form"
                id="ficha-carta-contratacion-form"
            >
                @csrf

                <div class="panel__body panel__body--compact">
                    @if ($requisitionReference)
                        @include('areas.gestion_humana.ficha-empleados.partials.ficha-requisition-reference', [
                            'reference' => $requisitionReference,
                        ])
                    @endif

                    <section class="ficha-empleados-form__section">
                        <header class="ficha-empleados-form__section-head">
                            <h3 class="ficha-empleados-form__section-title">Datos para la carta</h3>
                            <p class="ficha-empleados-form__section-lead">
                                Campos usados por las plantillas de contratación. La ciudad de requisición sale de la RQ (solo lectura).
                            </p>
                        </header>
                        <div class="form-grid form-grid--two ficha-empleados-form__grid">
                            <div class="form-field form-grid__full">
                                <label class="form-label" for="full_name">Nombre completo <span class="text-danger">*</span></label>
                                <input
                                    id="full_name"
                                    name="full_name"
                                    class="form-input"
                                    value="{{ old('full_name', $profile->full_name ?: $fichaEntry->hired_full_name) }}"
                                    required
                                    maxlength="255"
                                >
                                <x-input-error :messages="$errors->get('full_name')" />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="document_number">Documento <span class="text-danger">*</span></label>
                                <input
                                    id="document_number"
                                    name="document_number"
                                    class="form-input"
                                    value="{{ old('document_number', $profile->document_number ?: $fichaEntry->hired_document) }}"
                                    required
                                    maxlength="30"
                                >
                                <x-input-error :messages="$errors->get('document_number')" />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="birth_place">Lugar de nacimiento <span class="text-danger">*</span></label>
                                <input
                                    id="birth_place"
                                    name="birth_place"
                                    class="form-input"
                                    value="{{ old('birth_place', $profile->birth_place) }}"
                                    required
                                    maxlength="255"
                                >
                                <x-input-error :messages="$errors->get('birth_place')" />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="birth_date">Fecha de nacimiento <span class="text-danger">*</span></label>
                                <input
                                    id="birth_date"
                                    type="date"
                                    name="birth_date"
                                    class="form-input"
                                    value="{{ old('birth_date', optional($profile->birth_date)->format('Y-m-d')) }}"
                                    required
                                >
                                <x-input-error :messages="$errors->get('birth_date')" />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="phone">Teléfono <span class="text-danger">*</span></label>
                                <input
                                    id="phone"
                                    name="phone"
                                    class="form-input"
                                    value="{{ old('phone', $profile->phone) }}"
                                    required
                                    maxlength="30"
                                >
                                <x-input-error :messages="$errors->get('phone')" />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="email">Correo <span class="text-danger">*</span></label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    class="form-input"
                                    value="{{ old('email', $profile->email) }}"
                                    required
                                    maxlength="100"
                                >
                                <x-input-error :messages="$errors->get('email')" />
                            </div>
                            <div class="form-field form-grid__full">
                                <label class="form-label" for="address">Dirección <span class="text-danger">*</span></label>
                                <input
                                    id="address"
                                    name="address"
                                    class="form-input"
                                    value="{{ old('address', $profile->address) }}"
                                    required
                                    maxlength="255"
                                >
                                <x-input-error :messages="$errors->get('address')" />
                            </div>
                            @include('areas.gestion_humana.ficha-empleados.partials.ficha-catalog-select', [
                                'id' => 'residence_city_code',
                                'name' => 'residence_city_code',
                                'label' => 'Ciudad de residencia',
                                'catalogKey' => 'city',
                                'catalogs' => $catalogs,
                                'value' => old('residence_city_code', $profile->residence_city_code),
                                'required' => true,
                            ])
                            <div class="form-field">
                                <label class="form-label" for="ciudad_requisicion_readonly">Ciudad requisición</label>
                                <input
                                    id="ciudad_requisicion_readonly"
                                    class="form-input"
                                    value="{{ $ciudadRequisicion !== '' ? $ciudadRequisicion : '—' }}"
                                    readonly
                                    tabindex="0"
                                >
                                <p class="form-hint">Variable ${CIUDAD_REQUISICION} (no editable aquí).</p>
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="position_name">Cargo <span class="text-danger">*</span></label>
                                <input
                                    id="position_name"
                                    name="position_name"
                                    class="form-input"
                                    value="{{ old('position_name', $profile->position_name ?: ($requisitionReference['position_name'] ?? '')) }}"
                                    required
                                    maxlength="150"
                                >
                                <x-input-error :messages="$errors->get('position_name')" />
                            </div>
                            @if ($profile->position_code)
                                <input type="hidden" name="position_code" value="{{ old('position_code', $profile->position_code) }}">
                            @endif
                            <div class="form-field">
                                <label class="form-label" for="salary">Salario <span class="text-danger">*</span></label>
                                <div class="currency-input-wrap ficha-empleados-form__currency">
                                    <input
                                        id="salary"
                                        name="salary"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="off"
                                        class="form-input js-ficha-currency"
                                        value="{{ old('salary', $profile->salary !== null ? (int) $profile->salary : '') }}"
                                        data-initial-value="{{ old('salary', $profile->salary !== null ? (int) $profile->salary : '') }}"
                                        required
                                    >
                                </div>
                                <x-input-error :messages="$errors->get('salary')" />
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="hire_date">Fecha de ingreso <span class="text-danger">*</span></label>
                                <input
                                    id="hire_date"
                                    type="date"
                                    name="hire_date"
                                    class="form-input"
                                    value="{{ old('hire_date', optional($profile->hire_date)->format('Y-m-d') ?: ($requisitionReference['hiring_date'] ?? '')) }}"
                                    required
                                >
                                <x-input-error :messages="$errors->get('hire_date')" />
                            </div>
                        </div>
                    </section>

                    <section class="ficha-empleados-form__section">
                        <header class="ficha-empleados-form__section-head">
                            <h3 class="ficha-empleados-form__section-title">Plantillas y firmante</h3>
                            <p class="ficha-empleados-form__section-lead">Seleccione al menos una plantilla de contratación y el firmante.</p>
                        </header>
                        <div class="form-stack">
                            @forelse ($templates as $template)
                                <div class="form-field">
                                    <label>
                                        <input
                                            type="checkbox"
                                            class="form-check"
                                            name="template_ids[]"
                                            value="{{ $template['id'] }}"
                                            @checked(collect(old('template_ids', []))->contains($template['id']))
                                        >
                                        <span>{{ $template['label'] }}</span>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">Sin plantillas disponibles.</p>
                            @endforelse
                            <x-input-error :messages="$errors->get('template_ids')" />

                            <div class="form-field">
                                <label class="form-label" for="signatory_id">Firmante <span class="text-danger">*</span></label>
                                <x-searchable-select
                                    id="signatory_id"
                                    name="signatory_id"
                                    :options="$firmaOptions"
                                    :value="old('signatory_id', '')"
                                    placeholder="Seleccione firmante"
                                    :required="true"
                                />
                                <x-input-error :messages="$errors->get('signatory_id')" />
                            </div>
                        </div>
                    </section>
                </div>
            </form>

            {{-- Volver al inicio del scroll tras recorrer el formulario --}}
            <div class="ficha-empleados-page__scroll-top">
                <button
                    type="button"
                    class="ficha-empleados-page__scroll-top-btn"
                    title="Ir al inicio"
                    aria-label="Ir al inicio de la carta de contratación"
                    x-on:click="scrollToTop()"
                >
                    <x-lucide-arrow-up-to-line width="18" height="18" aria-hidden="true" />
                    <span>Ir al inicio</span>
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
        @include('areas.gestion_humana.ficha-empleados.partials.ficha-form-scripts')
    @endpush
</x-app-layout>
