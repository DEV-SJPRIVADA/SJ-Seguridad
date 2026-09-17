<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section">
        <div class="app-container">
            <div class="panel">
                <div class="panel__header">
                    <h3 class="panel-title">Nueva solicitud de desarrollo (FO-TIC-23)</h3>
                    <p class="panel-text">Diligencie los campos obligatorios. Si usted es el lider seleccionado, al enviar se radica de inmediato.</p>
                </div>
                <div class="panel__body">
                    <form
                        action="{{ route('development-requests.store', ['module' => $module]) }}"
                        method="POST"
                        enctype="multipart/form-data"
                    >
                        @csrf
                        @include('modules.development-requests.partials.form-fields')
                        <div class="cursos-registros-page__form-actions" style="display:flex;gap:.5rem;flex-wrap:wrap;">
                            <button type="submit" name="action" value="draft" class="btn btn--secondary">Guardar borrador</button>
                            <button type="submit" name="action" value="submit" class="btn btn--primary">Enviar / radicar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
