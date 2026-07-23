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
                    <th>Captura activa</th>
                    <th>Accion</th>
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
                        <td>
                            @if ($captureEnabled)
                                <span class="status-pill status-pill--req-contratado">Activo</span>
                            @else
                                <span class="status-pill status-pill--req-cancelada">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            @if ($isManager)
                                <span class="text-small text-muted">Siempre activo</span>
                            @else
                                <form method="POST"
                                      action="{{ route('indicadores.admin.capturadores.update', $operacionesUser) }}"
                                      class="indicadores-inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="enabled" value="{{ $captureEnabled ? '0' : '1' }}">
                                    <button type="submit" class="btn btn--secondary btn--sm">
                                        {{ $captureEnabled ? 'Inactivar' : 'Activar' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">No hay usuarios activos asignados al area Operaciones.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
