<div class="panel">
    <div class="panel__header">
        <h3 class="panel-title">Encargados de seleccion</h3>
        <p class="panel-text">
            Usuarios activos del area <strong>Gestion humana</strong>. Active el permiso para que aparezcan en el select
            <strong>Reclutador</strong> al gestionar requisiciones.
        </p>
    </div>

    @if ($errors->has('selection_officer'))
        <div class="alert alert--error" role="alert">{{ $errors->first('selection_officer') }}</div>
    @endif

    <div class="panel__body">
        <div class="data-table-wrap">
            <table class="data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Correo</th>
                        <th style="width:120px;">Encargado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($gestionHumanaUsers as $ghUser)
                        @php
                            $enabled = $selectionOfficerAccess->canActAsSelectionOfficer($ghUser);
                        @endphp
                        <tr>
                            <td>{{ $ghUser->name }}</td>
                            <td>{{ $ghUser->email }}</td>
                            <td>
                                <form method="POST"
                                      action="{{ route('requisitions.selection-officers.update', ['module' => $moduleKey, 'user' => $ghUser]) }}"
                                      class="requisition-selection-officer-toggle-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="enabled" value="{{ $enabled ? '1' : '0' }}">
                                    <label class="toggle-switch">
                                        <input type="checkbox"
                                               @checked($enabled)
                                               aria-label="Encargado de seleccion activo para {{ $ghUser->name }}"
                                               onchange="this.form.querySelector('[name=enabled]').value = this.checked ? '1' : '0'; this.form.submit();">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No hay usuarios activos asignados al area Gestion humana.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
