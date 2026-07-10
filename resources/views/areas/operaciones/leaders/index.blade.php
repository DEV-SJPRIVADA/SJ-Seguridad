<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            @can('operations.manage')
                <div class="panel" style="margin-bottom:1.5rem;">
                    <div class="panel__header">
                        <h3 class="panel-title">Nuevo jefe de operaciones</h3>
                    </div>
                    <div class="panel__body">
                        <form method="POST" action="{{ route('indicadores.leaders.store') }}" class="form-stack">
                            @csrf
                            <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem;">
                                <div>
                                    <label class="form-label">Nombre</label>
                                    <input type="text" name="name" class="supply-input" required value="{{ old('name') }}">
                                </div>
                                <div>
                                    <label class="form-label">Codigo</label>
                                    <input type="text" name="code" class="supply-input" required maxlength="50" value="{{ old('code') }}">
                                </div>
                                <div style="display:flex; align-items:center; gap:0.5rem; padding-top:1.5rem;">
                                    <input type="checkbox" name="is_active" value="1" checked id="leader-active">
                                    <label for="leader-active">Activo</label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn--primary">Crear jefe</button>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Jefes de operaciones</h3>
                    <p class="panel-text">Catalogo administrable de jefes para la captura de indicadores.</p>
                </div>
                <div class="panel__body">
                    <table class="supply-table js-datatable">
                        <thead>
                            <tr>
                                <th>Codigo</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($leaders as $leader)
                                <tr>
                                    <td>{{ $leader->code }}</td>
                                    <td>{{ $leader->name }}</td>
                                    <td>
                                        <span class="status-pill {{ $leader->is_active ? 'status-pill--req-contratado' : 'status-pill--req-cancelada' }}">
                                            {{ $leader->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td style="display:flex; gap:0.5rem; justify-content:center; flex-wrap:wrap;">
                                        @if ($leader->is_active)
                                            <a href="{{ route('indicadores.leaders.show', $leader) }}" class="btn btn--secondary btn--sm">Dashboard</a>
                                        @endif
                                        @can('operations.manage')
                                            <form method="POST" action="{{ route('indicadores.leaders.update', $leader) }}" style="display:flex; gap:0.35rem; flex-wrap:wrap; justify-content:center;">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="name" value="{{ $leader->name }}">
                                                <input type="hidden" name="code" value="{{ $leader->code }}">
                                                <input type="hidden" name="is_active" value="{{ $leader->is_active ? 0 : 1 }}">
                                                <button type="submit" class="btn btn--secondary btn--sm">
                                                    {{ $leader->is_active ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{ $leaders->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
