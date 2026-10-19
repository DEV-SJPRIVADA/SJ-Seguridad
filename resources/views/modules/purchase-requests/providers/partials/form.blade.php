<x-app-layout>
    <x-slot name="header">
        @include('modules.purchase-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section req-manage-page purchase-providers-page">
        <div class="app-container">
            <div class="page-header-inner purchase-requests-page__intro">
                <div class="pur-req-list__toolbar">
                    <div>
                        <h2 class="page-title">{{ $pageTitle }}</h2>
                        <p class="page-subtitle">Complete los datos del proveedor. Los textos se guardan en mayúsculas.</p>
                    </div>
                    <div class="pur-req-detail__toolbar-actions">
                        <a href="{{ $cancelUrl }}" class="btn btn--ghost btn--sm">Volver al catálogo</a>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel__body">
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

                    <form method="POST" action="{{ $formAction }}" class="form-stack">
                        @csrf
                        @if ($provider->exists)
                            @method('PATCH')
                        @endif

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
                            </div>

                            <div class="form-field">
                                <label class="form-label" for="provider_telefono">Teléfono</label>
                                <input
                                    id="provider_telefono"
                                    type="text"
                                    name="telefono"
                                    class="form-input"
                                    value="{{ old('telefono', $provider->telefono) }}"
                                    maxlength="50"
                                >
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
                            </div>

                            <div class="form-field">
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
                                <label class="form-label" for="provider_ciudad">Ciudad</label>
                                <input
                                    id="provider_ciudad"
                                    type="text"
                                    name="ciudad"
                                    class="form-input"
                                    value="{{ old('ciudad', $provider->ciudad) }}"
                                    maxlength="120"
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

                            <div class="form-field">
                                <span class="form-label">Clasificación</span>
                                <label class="cursos-registros-page__checkbox-label" for="provider_es_cliente">
                                    <input type="hidden" name="es_cliente" value="0">
                                    <input
                                        id="provider_es_cliente"
                                        type="checkbox"
                                        name="es_cliente"
                                        value="1"
                                        @checked((bool) old('es_cliente', $provider->es_cliente))
                                    >
                                    Es cliente
                                </label>
                                <label class="cursos-registros-page__checkbox-label" for="provider_es_proveedor">
                                    <input type="hidden" name="es_proveedor" value="0">
                                    <input
                                        id="provider_es_proveedor"
                                        type="checkbox"
                                        name="es_proveedor"
                                        value="1"
                                        @checked((bool) old('es_proveedor', $provider->exists ? $provider->es_proveedor : true))
                                    >
                                    Es proveedor
                                </label>
                            </div>
                        </div>

                        <div class="form-actions">
                            <div class="form-actions__group form-actions__group--end">
                                <a href="{{ $cancelUrl }}" class="btn btn--ghost">Cancelar</a>
                                <button type="submit" class="btn btn--primary">
                                    {{ $provider->exists ? 'Guardar cambios' : 'Crear proveedor' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
