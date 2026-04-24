<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="page-title">{{ __('Panel de trabajo') }}</h2>
            <p class="page-subtitle">Selecciona un modulo autorizado en el panel lateral izquierdo para cargar sus tableros y opciones de trabajo.</p>
        </div>
    </x-slot>

    <div class="page-section page-section--spacious">
        <div class="app-container">
            <div class="panel">
                <div class="panel__body dashboard-empty-state">
                    @if ($selectedModule)
                        <p class="text-caption">Modulo seleccionado</p>
                        <h3 class="page-title title-spaced">{{ $selectedModule['label'] }}</h3>
                        <p class="page-subtitle block-spaced">
                            Este modulo esta visible para tu usuario porque tiene permisos de modulo o tableros asignados.
                        </p>
                        @if ($selectedBoard)
                            <div class="notice notice--success block-spaced">
                                Tablero activo: {{ $selectedBoard['label'] }}
                            </div>
                        @elseif ($selectedModule['boards']->isNotEmpty())
                            <p class="text-small text-muted block-spaced">
                                Selecciona un tablero autorizado en las pestanas superiores para iniciar el trabajo.
                            </p>
                        @else
                            <p class="text-small text-muted block-spaced">
                                Este modulo no tiene tableros habilitados para tu usuario.
                            </p>
                        @endif

                        <div class="dashboard-empty-state__grid block-spaced-lg">
                            <article class="card card--muted">
                                <h4 class="panel-title">Visualizacion</h4>
                                <p class="text-small text-muted block-spaced">
                                    @if ($selectedModule['can_view'] || $selectedModule['boards']->isNotEmpty())
                                        Tu usuario puede consultar la informacion de este modulo.
                                    @else
                                        Tu usuario no tiene permiso de visualizacion para este modulo.
                                    @endif
                                </p>
                            </article>

                            <article class="card card--muted">
                                <h4 class="panel-title">Modificacion</h4>
                                <p class="text-small text-muted block-spaced">
                                    @if ($selectedModule['can_manage'])
                                        Tu usuario puede modificar y operar funciones dentro de este modulo.
                                    @else
                                        Tu usuario solo tiene acceso de consulta dentro de este modulo.
                                    @endif
                                </p>
                            </article>
                        </div>
                    @else
                        <p class="text-caption">SJ Seguridad</p>
                        <h3 class="page-title title-spaced">Ningun modulo seleccionado</h3>
                        <p class="page-subtitle block-spaced">
                            El espacio de trabajo permanece vacio hasta que selecciones un modulo autorizado. Al ingresar a un modulo, el sistema mostrara sus tableros o menus horizontales segun tus permisos.
                        </p>

                        <div class="dashboard-empty-state__grid block-spaced-lg">
                            <article class="card card--muted">
                                <h4 class="panel-title">Como funciona</h4>
                                <p class="text-small text-muted block-spaced">
                                    El menu lateral izquierdo lista los modulos habilitados para tu usuario. Cada modulo puede exponer uno o varios tableros en la franja horizontal superior.
                                </p>
                            </article>

                            <article class="card card--muted">
                                <h4 class="panel-title">Acceso autorizado</h4>
                                <p class="text-small text-muted block-spaced">
                                    Los tableros y vistas solo aparecen cuando tu rol o permisos directos los habilitan. El `super-admin` puede activar y administrar toda la estructura.
                                </p>
                            </article>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
