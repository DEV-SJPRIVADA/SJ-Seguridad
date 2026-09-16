<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.cursos.partials.subnav', ['subTabs' => $subTabs])
        <div class="app-container cursos-catalogo-page__header">
            <div class="panel-heading-row">
                <h2 class="panel-title panel-title--page">Catálogo</h2>
                <p class="panel-text">Gestion humana — tipos de curso</p>
            </div>
        </div>
    </x-slot>

    <div class="page-section ficha-empleados-catalogs-page cursos-catalogo-page">
        <div class="app-container section-stack">
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

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Tipos de curso</h3>
                    <p class="panel-text">
                        Catálogo editable (TIPO CURSO, CARGO CURSO, FORMATO PARA CURSOS, CURSOS, CARGO ACREDIT).
                        No elimine un tipo con registros asociados: desactívelo.
                    </p>
                </div>
                <div class="panel__body section-stack">
                    <form
                        method="POST"
                        action="{{ route('gestion-humana.cursos.catalogo.store') }}"
                        class="ficha-empleados-catalogs-page__create-form cursos-catalogo-page__filters"
                    >
                        @csrf
                        <div class="ficha-empleados-catalogs-page__create-row cursos-catalogo-page__filters-row">
                            <div class="form-field">
                                <label class="form-label" for="tipo_curso_new">TIPO CURSO</label>
                                <input
                                    id="tipo_curso_new"
                                    name="tipo_curso"
                                    type="text"
                                    class="form-input"
                                    maxlength="150"
                                    required
                                    value="{{ old('tipo_curso') }}"
                                    placeholder="Ej. ALTURAS"
                                >
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="cargo_curso_new">CARGO CURSO</label>
                                <input
                                    id="cargo_curso_new"
                                    name="cargo_curso"
                                    type="text"
                                    class="form-input"
                                    maxlength="150"
                                    value="{{ old('cargo_curso') }}"
                                >
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="formato_para_cursos_new">FORMATO PARA CURSOS</label>
                                <input
                                    id="formato_para_cursos_new"
                                    name="formato_para_cursos"
                                    type="text"
                                    class="form-input"
                                    maxlength="150"
                                    value="{{ old('formato_para_cursos') }}"
                                >
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="cursos_new">CURSOS</label>
                                <input
                                    id="cursos_new"
                                    name="cursos"
                                    type="text"
                                    class="form-input"
                                    maxlength="255"
                                    value="{{ old('cursos') }}"
                                >
                            </div>
                            <div class="form-field">
                                <label class="form-label" for="cargo_acredit_new">CARGO ACREDIT</label>
                                <input
                                    id="cargo_acredit_new"
                                    name="cargo_acredit"
                                    type="text"
                                    class="form-input"
                                    maxlength="150"
                                    value="{{ old('cargo_acredit') }}"
                                >
                            </div>
                            <div class="ficha-empleados-catalogs-page__create-actions">
                                <label class="ficha-empleados-catalogs-page__active-check">
                                    <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', true))>
                                    <span>Activo</span>
                                </label>
                                <button type="submit" class="btn btn--primary btn--sm">Agregar tipo</button>
                            </div>
                        </div>
                    </form>

                    <div class="data-table-wrap data-table-wrap--booting">
                        @include('partials.data-table-loader')
                        <table
                            class="data-table js-datatable"
                            data-dt-responsive="false"
                            data-dt-compact="true"
                            style="width:100%"
                        >
                            <thead>
                                <tr>
                                    <th>TIPO CURSO</th>
                                    <th>CARGO CURSO</th>
                                    <th>FORMATO</th>
                                    <th>CURSOS</th>
                                    <th>CARGO ACREDIT</th>
                                    <th>Estado</th>
                                    <th>Registros</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tipos as $tipo)
                                    <tr>
                                        <td>{{ $tipo->tipo_curso }}</td>
                                        <td>{{ $tipo->cargo_curso ?: '—' }}</td>
                                        <td>{{ $tipo->formato_para_cursos ?: '—' }}</td>
                                        <td>{{ $tipo->cursos ?: '—' }}</td>
                                        <td>{{ $tipo->cargo_acredit ?: '—' }}</td>
                                        <td>
                                            <span class="status-pill {{ $tipo->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                {{ $tipo->is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </td>
                                        <td>{{ $tipo->employee_cursos_count }}</td>
                                        <td class="table-actions">
                                            <div class="cursos-catalogo-page__row-actions">
                                                <details class="cursos-catalogo-page__edit-details">
                                                    <summary
                                                        class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit"
                                                        title="Editar"
                                                        aria-label="Editar"
                                                    >
                                                        <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                    </summary>
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.cursos.catalogo.update', $tipo) }}"
                                                        class="ficha-empleados-catalogs-page__create-form cursos-catalogo-page__edit-form"
                                                    >
                                                        @csrf
                                                        @method('PATCH')
                                                        <div class="ficha-empleados-catalogs-page__create-row">
                                                            <div class="form-field">
                                                                <label class="form-label">TIPO CURSO</label>
                                                                <input name="tipo_curso" type="text" class="form-input" maxlength="150" required value="{{ $tipo->tipo_curso }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">CARGO CURSO</label>
                                                                <input name="cargo_curso" type="text" class="form-input" maxlength="150" value="{{ $tipo->cargo_curso }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">FORMATO</label>
                                                                <input name="formato_para_cursos" type="text" class="form-input" maxlength="150" value="{{ $tipo->formato_para_cursos }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">CURSOS</label>
                                                                <input name="cursos" type="text" class="form-input" maxlength="255" value="{{ $tipo->cursos }}">
                                                            </div>
                                                            <div class="form-field">
                                                                <label class="form-label">CARGO ACREDIT</label>
                                                                <input name="cargo_acredit" type="text" class="form-input" maxlength="150" value="{{ $tipo->cargo_acredit }}">
                                                            </div>
                                                            <div class="ficha-empleados-catalogs-page__create-actions">
                                                                <label class="ficha-empleados-catalogs-page__active-check">
                                                                    <input type="checkbox" name="is_active" value="1" class="form-check" @checked($tipo->is_active)>
                                                                    <span>Activo</span>
                                                                </label>
                                                                <button type="submit" class="btn btn--primary btn--sm">Guardar</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </details>
                                                @if ($tipo->employee_cursos_count === 0)
                                                    <form
                                                        method="POST"
                                                        action="{{ route('gestion-humana.cursos.catalogo.destroy', $tipo) }}"
                                                        class="cursos-catalogo-page__delete-form"
                                                        onsubmit="return confirm('¿Eliminar este tipo de curso?')"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        <button
                                                            type="submit"
                                                            class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger"
                                                            title="Eliminar"
                                                            aria-label="Eliminar"
                                                        >
                                                            <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted text-caption" title="Desactive el tipo en su lugar">Con registros</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">Sin tipos de curso.</td>
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
