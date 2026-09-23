<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.acreditaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section ficha-empleados-catalogs-page acreditaciones-catalogo-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success ficha-empleados-catalogs-page__alert">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert--danger ficha-empleados-catalogs-page__alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert--danger ficha-empleados-catalogs-page__alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="page-header-inner ficha-empleados-catalogs-page__head">
                <h2 class="page-title">Catálogo de cargos</h2>
                <p class="page-subtitle">
                    CARGO MANAGER, CARGO APO, CARGO INFORME y CARGO ACREDITACIÓN.
                    No elimine la última fila activa de un CARGO APO si hay acreditados que lo usan: desactívela.
                </p>
            </div>

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Gestionar cargos</h3>
                    <p class="panel-text">{{ $cargos->count() }} registros en catálogo</p>
                </div>
                <div class="panel__body section-stack">
                    <form
                        method="POST"
                        action="{{ route('gestion-humana.acreditaciones.catalogo.store') }}"
                        class="ficha-empleados-catalogs-page__create-form cursos-catalogo-page__filters"
                    >
                        @csrf
                        <div class="ficha-empleados-catalogs-page__create-row cursos-catalogo-page__filters-row">
                            <div class="form-field">
                                <label class="form-label" for="cargo_manager_new">CARGO MANAGER</label>
                                <input id="cargo_manager_new" name="cargo_manager" type="text" class="form-input" maxlength="255" required value="{{ old('cargo_manager') }}" placeholder="Ej. GUARDA">
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="cargo_apo_new">CARGO APO</label>
                                <input id="cargo_apo_new" name="cargo_apo" type="text" class="form-input" maxlength="255" required value="{{ old('cargo_apo') }}" placeholder="Ej. VIGILANTE">
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="cargo_informe_new">CARGO INFORME</label>
                                <input id="cargo_informe_new" name="cargo_informe" type="text" class="form-input" maxlength="255" required value="{{ old('cargo_informe') }}" placeholder="Ej. GUARDAS">
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="cargo_acreditacion_new">CARGO ACREDITACIÓN</label>
                                <input id="cargo_acreditacion_new" name="cargo_acreditacion" type="text" class="form-input" maxlength="20" required value="{{ old('cargo_acreditacion') }}" placeholder="Ej. 1">
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="sort_order_new">Orden</label>
                                <input id="sort_order_new" name="sort_order" type="number" class="form-input" min="0" max="999999" value="{{ old('sort_order', 0) }}">
                            </div>
                            <div class="ficha-empleados-catalogs-page__create-actions">
                                <label class="ficha-empleados-catalogs-page__active-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', true))>
                                    <span>Activo</span>
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm">Agregar cargo</button>
                            </div>
                        </div>
                    </form>

                    <div class="data-table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table class="data-table js-datatable" data-dt-responsive="false" data-dt-compact="true" style="width:100%">
                            <thead>
                                <tr>
                                    <th>CARGO MANAGER</th>
                                    <th>CARGO APO</th>
                                    <th>CARGO INFORME</th>
                                    <th>CARGO ACREDITACIÓN</th>
                                    <th>Orden</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($cargos as $cargo)
                                    <tr>
                                        <td>{{ $cargo->cargo_manager }}</td>
                                        <td>{{ $cargo->cargo_apo }}</td>
                                        <td>{{ $cargo->cargo_informe }}</td>
                                        <td>{{ $cargo->cargo_acreditacion }}</td>
                                        <td>{{ $cargo->sort_order }}</td>
                                        <td>
                                            <span class="status-pill {{ $cargo->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                {{ $cargo->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td class="table-actions">
                                            <div class="cursos-catalogo-page__row-actions">
                                                <details class="cursos-catalogo-page__edit-details">
                                                    <summary class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit" title="Editar" aria-label="Editar">
                                                        <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                    </summary>
                                                    <form method="POST" action="{{ route('gestion-humana.acreditaciones.catalogo.update', $cargo) }}" class="ficha-empleados-catalogs-page__create-form cursos-catalogo-page__edit-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="ficha-empleados-catalogs-page__create-row">
                                                            <div class="form-field">
                                                                <label class="form-label">CARGO MANAGER</label>
                                                                <input name="cargo_manager" type="text" class="form-input" maxlength="255" required value="{{ $cargo->cargo_manager }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">CARGO APO</label>
                                                                <input name="cargo_apo" type="text" class="form-input" maxlength="255" required value="{{ $cargo->cargo_apo }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">CARGO INFORME</label>
                                                                <input name="cargo_informe" type="text" class="form-input" maxlength="255" required value="{{ $cargo->cargo_informe }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">CARGO ACREDITACIÓN</label>
                                                                <input name="cargo_acreditacion" type="text" class="form-input" maxlength="20" required value="{{ $cargo->cargo_acreditacion }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">Orden</label>
                                                                <input name="sort_order" type="number" class="form-input" min="0" max="999999" value="{{ $cargo->sort_order }}">
                                                            </div>
                                                            <div class="ficha-empleados-catalogs-page__create-actions">
                                                                <label class="ficha-empleados-catalogs-page__active-check">
                                                                    <input type="checkbox" name="is_active" value="1" class="form-check" @checked($cargo->is_active)>
                                                                    <span>Activo</span>
                                                                </label>
                                                                <button type="submit" class="btn btn--primary btn--sm">Guardar</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </details>
                                                @if ($catalogService->canDelete($cargo))
                                                    <form method="POST" action="{{ route('gestion-humana.acreditaciones.catalogo.destroy', $cargo) }}" class="cursos-catalogo-page__delete-form" onsubmit="return confirm('¿Eliminar este cargo del catálogo?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar" aria-label="Eliminar">
                                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted text-caption" title="{{ $catalogService->deletionBlockReason($cargo) }}">Protegido</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-muted">Sin cargos en el catálogo. Ejecute el seeder o agregue el primero.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
