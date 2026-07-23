<div class="indicadores-subpanel">
    <h4 class="indicadores-subpanel__title">Capturadores de indicadores</h4>
    <p class="indicadores-subpanel__text">
        Usuarios activos del area <strong>Operaciones</strong>. Active la captura para permitir el ingreso de datos en las fichas FT-OP.
        Los administradores de indicadores (<code>operations.manage</code>) siempre pueden capturar y consolidar.
    </p>

    @if ($errors->has('capturador'))
        <div class="alert alert--error" role="alert">{{ $errors->first('capturador') }}</div>
    @endif

    <div class="indicadores-table-wrap">
        <table class="supply-table indicadores-table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th class="indicadores-table__col-captura">Captura</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($operacionesUsers as $operacionesUser)
                    @php
                        $isManager = $operacionesUser->can('operations.manage');
                        $captureEnabled = $captureAccessService->canCaptureIndicators($operacionesUser);
                    @endphp
                    <tr>
                        <td>{{ $operacionesUser->name }}</td>
                        <td>{{ $operacionesUser->email }}</td>
                        <td>
                            @if ($isManager)
                                <span class="status-pill status-pill--info">Administrador</span>
                            @else
                                <span class="text-small text-muted">Capturador</span>
                            @endif
                        </td>
                        <td class="indicadores-table__col-captura">
                            @if ($isManager)
                                <label class="toggle-switch indicadores-capturador-toggle indicadores-capturador-toggle--locked"
                                       title="Siempre activo">
                                    <input type="checkbox" checked disabled aria-label="Captura siempre activa para {{ $operacionesUser->name }}">
                                    <span class="toggle-slider"></span>
                                </label>
                            @else
                                <form method="POST"
                                      action="{{ route('indicadores.admin.capturadores.update', $operacionesUser) }}"
                                      class="indicadores-capturador-toggle-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="enabled" value="{{ $captureEnabled ? '1' : '0' }}">
                                    <label class="toggle-switch indicadores-capturador-toggle">
                                        <input type="checkbox"
                                               @checked($captureEnabled)
                                               aria-label="Captura activa para {{ $operacionesUser->name }}"
                                               onchange="this.form.querySelector('[name=enabled]').value = this.checked ? '1' : '0'; this.form.submit();">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4">No hay usuarios activos asignados al area Operaciones.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
