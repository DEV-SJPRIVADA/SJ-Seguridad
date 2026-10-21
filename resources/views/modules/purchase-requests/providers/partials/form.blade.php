<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.catalogos-subnav', ['subTabs' => $subTabs])
    </x-slot>
    <div class="page-section purchase-requests-page purchase-requests-page--form purchase-providers-page">
        <div class="app-container">
            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-detail__toolbar">
                    <a
                        href="{{ $cancelUrl }}"
                        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                        title="Volver al catálogo"
                        aria-label="Volver al catálogo"
                    >
                        <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    </a>
                </div>
                <h2 class="page-title">{{ $pageTitle }}</h2>
                <p class="page-subtitle">
                    Catálogo de proveedores de Compras. Los textos se guardan en mayúsculas, excepto correo, teléfono, identificación y ciudad.
                </p>
            </div>

            @if ($errors->any())
                <div class="alert alert--danger" role="alert">
                    <p class="alert__title">Revise los datos del formulario:</p>
                    <ul class="form-error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $formAction }}" class="pur-req-form">
                @csrf
                @if ($provider->exists)
                    @method('PATCH')
                @endif

                <div class="pur-req-form__meta">
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Registro</span>
                        <span class="pur-req-form__meta-value">{{ $provider->exists ? 'Edición' : 'Nuevo' }}</span>
                    </div>
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Área</span>
                        <span class="pur-req-form__meta-value">COMPRAS</span>
                    </div>
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Formato</span>
                        <span class="pur-req-form__meta-value">VINE01 / catálogo</span>
                    </div>
                    <div class="pur-req-form__meta-item">
                        <span class="pur-req-form__meta-label">Estado</span>
                        <span class="pur-req-form__meta-value">{{ \App\Models\PurchaseProvider::estadoLabels()[old('estado', $provider->estado ?: \App\Models\PurchaseProvider::ESTADO_ACTIVO)] ?? 'Activo' }}</span>
                    </div>
                </div>

                <section class="pur-req-form__section">
                    <header class="pur-req-form__section-head">
                        <span class="pur-req-form__section-step">1</span>
                        <div>
                            <h3 class="pur-req-form__section-title">Identificación y contacto</h3>
                            <p class="pur-req-form__section-desc">Datos con los que se emite la Orden de Compra FO-AD-07.</p>
                        </div>
                    </header>

                    <div class="cursos-registros-page__form-grid">
                        <div class="form-field">
                            <label class="form-label" for="provider_identificacion">Identificación *</label>
                            <input
                                id="provider_identificacion"
                                type="text"
                                name="identificacion"
                                class="form-input"
                                value="{{ old('identificacion', $provider->identificacion) }}"
                                maxlength="50"
                                required
                                autocomplete="off"
                            >
                            <x-input-error :messages="$errors->get('identificacion')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_nombre">Nombre / razón social *</label>
                            <input
                                id="provider_nombre"
                                type="text"
                                name="nombre"
                                class="form-input"
                                value="{{ old('nombre', $provider->nombre) }}"
                                maxlength="255"
                                required
                            >
                            <x-input-error :messages="$errors->get('nombre')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_telefono">Teléfono</label>
                            <input
                                id="provider_telefono"
                                type="tel"
                                name="telefono"
                                class="form-input"
                                value="{{ old('telefono', $provider->telefono) }}"
                                maxlength="15"
                                inputmode="numeric"
                                pattern="[0-9]{7,15}"
                                title="Solo números, entre 7 y 15 dígitos."
                                autocomplete="off"
                            >
                            <x-input-error :messages="$errors->get('telefono')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_correo">Correo</label>
                            <input
                                id="provider_correo"
                                type="email"
                                name="correo"
                                class="form-input"
                                value="{{ old('correo', $provider->correo) }}"
                                maxlength="255"
                                autocomplete="off"
                            >
                            <x-input-error :messages="$errors->get('correo')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_contacto">Contacto</label>
                            <input
                                id="provider_contacto"
                                type="text"
                                name="contacto"
                                class="form-input"
                                value="{{ old('contacto', $provider->contacto) }}"
                                maxlength="255"
                            >
                            <x-input-error :messages="$errors->get('contacto')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_ciudad">Ciudad</label>
                            <x-searchable-select
                                id="provider_ciudad"
                                name="ciudad"
                                :options="$ciudades"
                                :value="old('ciudad', $provider->ciudad)"
                                placeholder="Seleccione una ciudad"
                                search-placeholder="Buscar ciudad…"
                            />
                            <x-input-error :messages="$errors->get('ciudad')" />
                        </div>
                    </div>
                </section>

                <section class="pur-req-form__section">
                    <header class="pur-req-form__section-head">
                        <span class="pur-req-form__section-step">2</span>
                        <div>
                            <h3 class="pur-req-form__section-title">Condiciones comerciales</h3>
                            <p class="pur-req-form__section-desc">Dirección, forma de pago y días de pago.</p>
                        </div>
                    </header>

                    <div class="cursos-registros-page__form-grid">
                        <div class="form-field cursos-registros-page__form-span">
                            <label class="form-label" for="provider_direccion">Dirección</label>
                            <input
                                id="provider_direccion"
                                type="text"
                                name="direccion"
                                class="form-input"
                                value="{{ old('direccion', $provider->direccion) }}"
                                maxlength="500"
                            >
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_forma_pago">Forma de pago</label>
                            <input
                                id="provider_forma_pago"
                                type="text"
                                name="forma_pago"
                                class="form-input"
                                value="{{ old('forma_pago', $provider->forma_pago) }}"
                                maxlength="120"
                                placeholder="Ej: TRANSFERENCIA"
                            >
                            <x-input-error :messages="$errors->get('forma_pago')" />
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_dias_pago">Días de pago</label>
                            <input
                                id="provider_dias_pago"
                                type="number"
                                name="dias_pago"
                                class="form-input"
                                value="{{ old('dias_pago', $provider->dias_pago) }}"
                                min="0"
                                max="3650"
                                step="1"
                                placeholder="Ej: 30"
                            >
                        </div>

                        <div class="form-field">
                            <label class="form-label" for="provider_estado">Estado *</label>
                            <x-searchable-select
                                id="provider_estado"
                                name="estado"
                                :options="$estados"
                                :value="old('estado', $provider->estado ?: \App\Models\PurchaseProvider::ESTADO_ACTIVO)"
                                search-placeholder="Buscar estado…"
                                :allow-clear="false"
                                required
                            />
                        </div>
                    </div>
                </section>

                <div class="pur-req-form-actions">
                    <p class="pur-req-form-actions__note">
                        El correo y la ciudad alimentan la Orden de Compra y las notificaciones. Verifique los datos antes de guardar.
                    </p>
                    <div class="pur-req-form-actions__group">
                        <a
                            href="{{ $cancelUrl }}"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                            title="Cancelar"
                            aria-label="Cancelar"
                        >
                            <x-lucide-x width="18" height="18" aria-hidden="true" />
                        </a>
                        <button
                            type="submit"
                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                            title="{{ $provider->exists ? 'Guardar cambios' : 'Crear proveedor' }}"
                            aria-label="{{ $provider->exists ? 'Guardar cambios' : 'Crear proveedor' }}"
                        >
                            <x-lucide-save width="18" height="18" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
