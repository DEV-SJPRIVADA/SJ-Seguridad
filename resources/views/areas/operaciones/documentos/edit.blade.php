<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Editar documento</h3>
                </div>
                <div class="panel__body">
                    <form method="POST" action="{{ route('indicadores.admin.documents.update', $document) }}" class="form-stack">
                        @csrf
                        @method('PATCH')
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">
                            <div>
                                <label class="form-label">Titulo</label>
                                <input type="text" name="title" class="supply-input" required value="{{ old('title', $document->title) }}">
                            </div>
                            <div>
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="supply-input" required value="{{ old('slug', $document->slug) }}">
                            </div>
                            <div>
                                <label class="form-label">Alcance</label>
                                <select name="scope" class="supply-input supply-select">
                                    @foreach (['system', 'indicator', 'dashboard'] as $scope)
                                        <option value="{{ $scope }}" @selected(old('scope', $document->scope) === $scope)>{{ $scope }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Indicador (opcional)</label>
                                <select name="indicator_id" class="supply-input supply-select">
                                    <option value="">-</option>
                                    @foreach ($indicators as $indicator)
                                        <option value="{{ $indicator->id }}" @selected((string) old('indicator_id', $document->indicator_id) === (string) $indicator->id)>{{ $indicator->code }} - {{ $indicator->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Activo</label>
                                <select name="is_active" class="supply-input supply-select">
                                    <option value="1" @selected(old('is_active', $document->is_active ? '1' : '0') == '1')>Si</option>
                                    <option value="0" @selected(old('is_active', $document->is_active ? '1' : '0') == '0')>No</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Motivo del cambio</label>
                                <input type="text" name="reason" class="supply-input" required value="{{ old('reason') }}">
                            </div>
                        </div>
                        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                            <a href="{{ route('indicadores.admin.documents.show', $document) }}" class="btn btn--secondary">Cancelar</a>
                            <button type="submit" class="btn btn--primary">Guardar cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
