<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page development-requests-page--form">
        <div class="app-container">
            <div class="page-header-inner development-requests-page__intro">
                <h2 class="page-title">Editar solicitud</h2>
                <p class="page-subtitle">
                    {{ $developmentRequest->code ?: 'Sin codigo (borrador / devuelta)' }}
                    — {{ $developmentRequest->estadoLabel() }}
                </p>
            </div>

            <div class="dev-req-form-layout">
                <div class="dev-req-form-layout__main">
                    <form
                        action="{{ route('development-requests.update', ['module' => $module, 'development_request' => $developmentRequest]) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="form-stack"
                    >
                        @csrf
                        @method('PATCH')
                        @include('modules.development-requests.partials.form-fields', [
                            'developmentRequest' => $developmentRequest,
                            'defaultRequester' => [],
                        ])

                        <div class="dev-req-form-actions">
                            <p class="dev-req-form-actions__note">
                                Guarda los cambios o envía nuevamente para aprobación / radicación.
                            </p>
                            <div class="dev-req-form-actions__group">
                                <a
                                    href="{{ route('development-requests.my-requests', ['module' => $module]) }}"
                                    class="btn btn--secondary"
                                >Cancelar</a>
                                <button type="submit" name="action" value="draft" class="btn btn--secondary">
                                    Guardar
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn--primary">
                                    Enviar / radicar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="dev-req-form-aside">
                    {{-- Checklist para corregir una solicitud devuelta. --}}
                    <x-form-aside-guide
                        title="Al editar"
                        subtitle="Revisa estos puntos antes de reenviar."
                        icon="list-checks"
                        :items="[
                            'Corrige los campos que motivaron la devolución.',
                            'Mantén alcance y criterios de aceptación alineados.',
                            'Actualiza anexos si aportan evidencia nueva.',
                            'Verifica líder y prioridad antes de radicar.',
                        ]"
                    />
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
