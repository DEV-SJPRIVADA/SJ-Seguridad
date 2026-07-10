<x-app-layout>
    <x-slot name="header">
        @include('areas.operaciones.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Nuevo documento</h3>
                </div>
                <div class="panel__body">
                    <form method="POST" action="{{ route('indicadores.admin.documents.store') }}" class="form-stack">
                        @csrf
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">
                            <div>
                                <label class="form-label">Titulo</label>
                                <input type="text" name="title" class="supply-input" required value="{{ old('title') }}">
                            </div>
                            <div>
                                <label class="form-label">Slug</label>
                                <input type="text" name="slug" class="supply-input" required value="{{ old('slug') }}">
                            </div>
                            <div>
                                <label class="form-label">Alcance</label>
                                <select name="scope" class="supply-input supply-select">
                                    <option value="system">system</option>
                                    <option value="indicator">indicator</option>
                                    <option value="dashboard">dashboard</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Indicador (opcional)</label>
                                <select name="indicator_id" class="supply-input supply-select">
                                    <option value="">-</option>
                                    @foreach ($indicators as $indicator)
                                        <option value="{{ $indicator->id }}" @selected(old('indicator_id') == $indicator->id)>{{ $indicator->code }} - {{ $indicator->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Estado version inicial</label>
                                <select name="initial_status" class="supply-input supply-select">
                                    <option value="draft">draft</option>
                                    <option value="vigente">vigente</option>
                                    <option value="archivado">archivado</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Activo</label>
                                <select name="is_active" class="supply-input supply-select">
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Contenido</label>
                            <textarea name="content" class="supply-textarea" rows="10" required>{{ old('content') }}</textarea>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">
                            <div>
                                <label class="form-label">Resumen del cambio</label>
                                <input type="text" name="change_summary" class="supply-input" required value="{{ old('change_summary') }}">
                            </div>
                            <div>
                                <label class="form-label">Motivo</label>
                                <input type="text" name="change_reason" class="supply-input" required value="{{ old('change_reason') }}">
                            </div>
                        </div>
                        <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                            <a href="{{ route('indicadores.admin.documents.index') }}" class="btn btn--secondary">Cancelar</a>
                            <button type="submit" class="btn btn--primary">Guardar documento</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
