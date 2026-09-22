<x-app-layout>
    <x-slot name="header">
        @include('modules.development-requests.partials.subnav', ['subTabs' => $subTabs])
    </x-slot>

    <div class="page-section development-requests-page development-requests-page--form">
        <div class="app-container">
            <div class="page-header-inner development-requests-page__intro">
                <h2 class="page-title">Nueva solicitud de desarrollo</h2>
                <p class="page-subtitle">
                    Formato FO-TIC-23. Completa las secciones en orden. Si eres el lider seleccionado, al enviar se radica de inmediato.
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
                                Puedes guardar borrador y continuar despues, o enviar para aprobacion / radicacion.
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
                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Antes de enviar</h3>
                            <p class="panel-text">Reduce devoluciones con estos puntos.</p>
                        </div>
                        <div class="panel__body">
                            <ul class="dev-req-form-guide__list">
                                <li class="dev-req-form-guide__item">Prioridad alineada con el impacto real del negocio.</li>
                                <li class="dev-req-form-guide__item">Titulo concreto: que se pide y para que area.</li>
                                <li class="dev-req-form-guide__item">Describe el proceso actual y el problema, no solo la solucion.</li>
                                <li class="dev-req-form-guide__item">Pasos esperados del sistema en orden operativo.</li>
                                <li class="dev-req-form-guide__item">Alcance SI / NO claro para evitar ambiguedades.</li>
                                <li class="dev-req-form-guide__item">Criterio de aceptacion verificable al entregar.</li>
                                <li class="dev-req-form-guide__item">Si hay urgencia, justifica la fecha deseada.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel__header">
                            <h3 class="panel-title">Que pasa despues</h3>
                        </div>
                        <div class="panel__body">
                            <ol class="dev-req-form-flow">
                                <li class="dev-req-form-flow__item">
                                    <span class="dev-req-form-flow__step">1</span>
                                    <span>El lider aprueba o devuelve la solicitud.</span>
                                </li>
                                <li class="dev-req-form-flow__item">
                                    <span class="dev-req-form-flow__step">2</span>
                                    <span>TIC la analiza, prioriza y planifica el desarrollo.</span>
                                </li>
                                <li class="dev-req-form-flow__item">
                                    <span class="dev-req-form-flow__step">3</span>
                                    <span>Puedes seguir el avance y el chat desde Mis solicitudes.</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
