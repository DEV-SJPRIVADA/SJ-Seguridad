<x-app-layout>
    <x-slot name="header">
        @include('areas.gestion_humana.cursos.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section ficha-empleados-catalogs-page cursos-catalogo-page">
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

            <div id="cursos-catalog-selector-screen">
                <div class="page-header-inner ficha-empleados-catalogs-page__head">
                    <h2 class="page-title">Catalogos de cursos</h2>
                    <p class="page-subtitle">Tipos de curso y escuelas usadas en registros e importacion.</p>
                </div>

                <div class="ficha-empleados-catalogs-page__grid">
                    <button type="button" class="ficha-empleados-catalogs-page__card" data-catalog-key="tipos">
                        <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                            <x-lucide-list width="22" height="22" aria-hidden="true" />
                        </span>
                        <span class="ficha-empleados-catalogs-page__card-title">Tipos de curso</span>
                        <span class="ficha-empleados-catalogs-page__card-count">{{ $tipos->count() }} registrados</span>
                    </button>
                    <button type="button" class="ficha-empleados-catalogs-page__card" data-catalog-key="escuelas">
                        <span class="ficha-empleados-catalogs-page__card-icon" aria-hidden="true">
                            <x-lucide-list width="22" height="22" aria-hidden="true" />
                        </span>
                        <span class="ficha-empleados-catalogs-page__card-title">Escuelas</span>
                        <span class="ficha-empleados-catalogs-page__card-count">{{ $escuelas->count() }} registrados</span>
                    </button>
                </div>
            </div>

            <div id="cursos-catalog-management-screen" class="ficha-empleados-catalogs-page__manage" hidden>
                <button type="button" class="ficha-empleados-catalogs-page__back" data-catalog-back>
                    <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    Volver al tablero
                </button>

                <section id="section-tipos" class="ficha-empleados-catalogs-page__section" hidden>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Gestionar: Tipos de curso</h3>
                            <p class="panel-text">
                                TIPO CURSO, CARGO CURSO, FORMATO PARA CURSOS, CURSOS, CARGO ACREDIT.
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
                                        <input id="tipo_curso_new" name="tipo_curso" type="text" class="form-input" maxlength="150" required value="{{ old('tipo_curso') }}" placeholder="Ej. ALTURAS">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cargo_curso_new">CARGO CURSO</label>
                                        <input id="cargo_curso_new" name="cargo_curso" type="text" class="form-input" maxlength="150" value="{{ old('cargo_curso') }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="formato_para_cursos_new">FORMATO PARA CURSOS</label>
                                        <input id="formato_para_cursos_new" name="formato_para_cursos" type="text" class="form-input" maxlength="150" value="{{ old('formato_para_cursos') }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cursos_new">CURSOS</label>
                                        <input id="cursos_new" name="cursos" type="text" class="form-input" maxlength="255" value="{{ old('cursos') }}">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="cargo_acredit_new">CARGO ACREDIT</label>
                                        <input id="cargo_acredit_new" name="cargo_acredit" type="text" class="form-input" maxlength="150" value="{{ old('cargo_acredit') }}">
                                    </div>
                                    <div class="ficha-empleados-catalogs-page__create-actions">
                                        <label class="ficha-empleados-catalogs-page__active-check">
                                            <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', true))>
                                            <span>Activo</span>
                                        </label>
                                        <button
                                            type="submit"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                            title="Agregar tipo"
                                            aria-label="Agregar tipo"
                                        >
                                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="data-table-wrap data-table-wrap--booting">
                                @include('partials.data-table-loader')
                                <table class="data-table js-datatable" data-dt-responsive="false" data-dt-compact="true" style="width:100%">
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
                                                            <summary class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit" title="Editar" aria-label="Editar">
                                                                <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                            </summary>
                                                            <form method="POST" action="{{ route('gestion-humana.cursos.catalogo.update', $tipo) }}" class="ficha-empleados-catalogs-page__create-form cursos-catalogo-page__edit-form">
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
                                                            <form method="POST" action="{{ route('gestion-humana.cursos.catalogo.destroy', $tipo) }}" class="cursos-catalogo-page__delete-form" onsubmit="return confirm('¿Eliminar este tipo de curso?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar" aria-label="Eliminar">
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
                </section>

                <section id="section-escuelas" class="ficha-empleados-catalogs-page__section" hidden>
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Gestionar: Escuelas</h3>
                            <p class="panel-text">Entidades donde se realizan los cursos (CODIGO, NIT, NOMBRE). El codigo es unico.</p>
                        </div>
                        <div class="panel__body section-stack">
                            <form
                                method="POST"
                                action="{{ route('gestion-humana.cursos.catalogo.escuelas.store') }}"
                                class="ficha-empleados-catalogs-page__create-form cursos-catalogo-page__filters"
                            >
                                @csrf
                                <div class="ficha-empleados-catalogs-page__create-row cursos-catalogo-page__filters-row">
                                    <div class="form-field">
                                        <label class="form-label" for="escuela_codigo_new">CODIGO</label>
                                        <input id="escuela_codigo_new" name="codigo" type="text" class="form-input" maxlength="30" required value="{{ old('codigo') }}" placeholder="Ej. 015">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="escuela_nit_new">NIT</label>
                                        <input id="escuela_nit_new" name="nit" type="text" class="form-input" maxlength="30" required value="{{ old('nit') }}" placeholder="Ej. 8050262894">
                                    </div>
                                    <div class="form-field">
                                        <label class="form-label" for="escuela_nombre_new">NOMBRE</label>
                                        <input id="escuela_nombre_new" name="nombre" type="text" class="form-input" maxlength="255" required value="{{ old('nombre') }}" placeholder="Ej. SNIPER">
                                    </div>
                                    <div class="ficha-empleados-catalogs-page__create-actions">
                                        <label class="ficha-empleados-catalogs-page__active-check">
                                            <input type="checkbox" name="is_active" value="1" class="form-check" @checked(old('is_active', true))>
                                            <span>Activo</span>
                                        </label>
                                        <button
                                            type="submit"
                                            class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary"
                                            title="Agregar escuela"
                                            aria-label="Agregar escuela"
                                        >
                                            <x-lucide-plus width="18" height="18" aria-hidden="true" />
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="data-table-wrap data-table-wrap--booting">
                                @include('partials.data-table-loader')
                                <table class="data-table js-datatable cursos-catalogo-page__escuelas-table" data-dt-responsive="false" data-dt-compact="true" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>CODIGO</th>
                                            <th>NIT</th>
                                            <th>NOMBRE</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($escuelas as $escuela)
                                            <tr>
                                                <td>{{ $escuela->codigo }}</td>
                                                <td>{{ $escuela->nit }}</td>
                                                <td>{{ $escuela->nombre }}</td>
                                                <td>
                                                    <span class="status-pill {{ $escuela->is_active ? 'status-pill--success' : 'status-pill--muted' }}">
                                                        {{ $escuela->is_active ? 'Activo' : 'Inactivo' }}
                                                    </span>
                                                </td>
                                                <td class="table-actions">
                                                    <div class="cursos-catalogo-page__row-actions">
                                                        <details class="cursos-catalogo-page__edit-details">
                                                            <summary class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--edit" title="Editar" aria-label="Editar">
                                                                <x-lucide-pencil width="16" height="16" aria-hidden="true" />
                                                            </summary>
                                                            <form method="POST" action="{{ route('gestion-humana.cursos.catalogo.escuelas.update', $escuela) }}" class="ficha-empleados-catalogs-page__create-form cursos-catalogo-page__edit-form">
                                                                @csrf
                                                                @method('PATCH')
                                                                <div class="ficha-empleados-catalogs-page__create-row">
                                                                    <div class="form-field">
                                                                        <label class="form-label">CODIGO</label>
                                                                        <input name="codigo" type="text" class="form-input" maxlength="30" required value="{{ $escuela->codigo }}">
                                                                    </div>
                                                                    <div class="form-field">
                                                                        <label class="form-label">NIT</label>
                                                                        <input name="nit" type="text" class="form-input" maxlength="30" required value="{{ $escuela->nit }}">
                                                                    </div>
                                                                    <div class="form-field">
                                                                        <label class="form-label">NOMBRE</label>
                                                                        <input name="nombre" type="text" class="form-input" maxlength="255" required value="{{ $escuela->nombre }}">
                                                                    </div>
                                                                    <div class="ficha-empleados-catalogs-page__create-actions">
                                                                        <label class="ficha-empleados-catalogs-page__active-check">
                                                                            <input type="checkbox" name="is_active" value="1" class="form-check" @checked($escuela->is_active)>
                                                                            <span>Activo</span>
                                                                        </label>
                                                                        <button type="submit" class="btn btn--primary btn--sm">Guardar</button>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </details>
                                                        <form method="POST" action="{{ route('gestion-humana.cursos.catalogo.escuelas.destroy', $escuela) }}" class="cursos-catalogo-page__delete-form" onsubmit="return confirm('¿Eliminar esta escuela?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Eliminar" aria-label="Eliminar">
                                                                <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-muted">Sin escuelas registradas.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var selectorScreen = document.getElementById('cursos-catalog-selector-screen');
            var manageScreen = document.getElementById('cursos-catalog-management-screen');

            function catalogQueryKey() {
                return new URLSearchParams(window.location.search).get('catalog');
            }

            function setCatalogQuery(key) {
                var url = new URL(window.location.href);
                if (key) {
                    url.searchParams.set('catalog', key);
                } else {
                    url.searchParams.delete('catalog');
                }
                url.hash = '';
                window.history.replaceState({}, '', url.toString());
            }

            function showSection(key) {
                selectorScreen.hidden = true;
                manageScreen.hidden = false;
                document.querySelectorAll('.ficha-empleados-catalogs-page__section').forEach(function (section) {
                    section.hidden = section.id !== 'section-' + key;
                });
                setCatalogQuery(key);
                window.scrollTo(0, 0);
            }

            function showSelector() {
                selectorScreen.hidden = false;
                manageScreen.hidden = true;
                setCatalogQuery(null);
            }

            document.querySelectorAll('[data-catalog-key]').forEach(function (button) {
                button.addEventListener('click', function () {
                    showSection(button.getAttribute('data-catalog-key'));
                });
            });

            document.querySelectorAll('[data-catalog-back]').forEach(function (button) {
                button.addEventListener('click', showSelector);
            });

            var openCatalog = catalogQueryKey();
            if (openCatalog && document.getElementById('section-' + openCatalog)) {
                showSection(openCatalog);
            }
        });
    </script>
</x-app-layout>
