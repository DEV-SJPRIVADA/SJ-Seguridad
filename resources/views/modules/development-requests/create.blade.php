<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page development-requests-page--form">
        <div class="app-container">
            <div class="page-header-inner development-requests-page__intro">
                <h2 class="page-title">Nueva solicitud de desarrollo</h2>
                <p class="page-subtitle">
                    Formato FO-TIC-23. Completa las secciones en orden. Si eres el líder seleccionado, al enviar se radica de inmediato.
                </p>
            </div>

            <div class="dev-req-form-layout">
                <div class="dev-req-form-layout__main">
                    <form
                        action="{{ route('development-requests.store', ['module' => $module]) }}"
                        method="POST"
                        enctype="multipart/form-data"
                        class="form-stack"
                    >
                        @csrf
                        @include('modules.development-requests.partials.form-fields')

                        <div class="dev-req-form-actions">
                            <p class="dev-req-form-actions__note">
                                Puedes guardar borrador y continuar después, o enviar para aprobación / radicación.
                            </p>
                            <div class="dev-req-form-actions__group">
                                <a
                                    href="{{ route('development-requests.my-requests', ['module' => $module]) }}"
                                    class="btn btn--secondary"
                                >Cancelar</a>
                                <button type="submit" name="action" value="draft" class="btn btn--secondary">
                                    Guardar borrador
                                </button>
                                <button type="submit" name="action" value="submit" class="btn btn--primary">
                                    Enviar / radicar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <aside class="dev-req-form-aside">
                    {{-- Checklist previo al envío de la solicitud de desarrollo. --}}
                    <x-form-aside-guide
                        title="Antes de enviar"
                        subtitle="Reduce devoluciones con estos puntos."
                        icon="list-checks"
                        :items="[
                            'Prioridad alineada con el impacto real del negocio.',
                            'Título concreto: qué se pide y para qué área.',
                            'Describe el proceso actual y el problema, no solo la solución.',
                            'Pasos esperados del sistema en orden operativo.',
                            'Alcance SI / NO claro para evitar ambigüedades.',
                            'Criterio de aceptación verificable al entregar.',
                            'Si hay urgencia, justifica la fecha deseada.',
                        ]"
                    />

                    {{-- Ruta de la solicitud dentro de TIC. --}}
                    <x-form-aside-guide
                        title="Qué pasa después"
                        icon="route"
                        numbered
                        :items="[
                            'El líder aprueba o devuelve la solicitud.',
                            'TIC la analiza, prioriza y planifica el desarrollo.',
                            'Puedes seguir el avance y el chat desde Mis solicitudes.',
                        ]"
                    />
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
