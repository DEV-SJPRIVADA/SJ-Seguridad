@php
    $shareShowTrigger = $shareShowTrigger ?? true;
    $shareShowModal = $shareShowModal ?? true;
    $shareAssignees = $indicator->assignees->map(function ($row): array {
        return [
            'user_id' => (string) $row->user_id,
            'name' => (string) ($row->user?->name ?? 'Usuario #'.$row->user_id),
            'can_consult' => (bool) $row->can_consult,
        ];
    })->values()->all();
    $shareAreaAssignees = $indicator->areaAssignees->map(function ($row): array {
        return [
            'area_key' => (string) $row->area_key,
            'name' => (string) (config('access.areas.'.$row->area_key) ?? $row->area_key),
            'can_consult' => (bool) $row->can_consult,
        ];
    })->values()->all();
@endphp

@if ($shareShowTrigger)
    {{-- Abre el modal de asignaciones; no comparte hasta Guardar. --}}
    <button
        type="button"
        class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
        title="Compartir indicador"
        aria-label="Compartir indicador"
        x-data=""
        x-on:click="$dispatch('open-modal', 'planeacion-share')"
    >
        <x-lucide-users width="18" height="18" aria-hidden="true" />
    </button>
@endif

@if ($shareShowModal)
    {{-- Compartir: todo asignado captura; Consulta abre consolidado del equipo. --}}
    <x-modal name="planeacion-share" maxWidth="3xl" focusable>
        <div
            class="modal-card ficha-empleados-masivos-modal planeacion-share-modal"
            x-data="planeacionShareForm(@js([
                'assignees' => $shareAssignees,
                'areaAssignees' => $shareAreaAssignees,
                'users' => $shareUsers,
                'areas' => $shareAreas ?? [],
            ]))"
        >
            <div class="ficha-empleados-masivos-modal__header">
                <div class="ficha-empleados-masivos-modal__heading">
                    <span class="ficha-empleados-masivos-modal__heading-icon" aria-hidden="true">
                        <x-lucide-users width="18" height="18" aria-hidden="true" />
                    </span>
                    <div>
                        <h3 class="ficha-empleados-masivos-modal__title">Compartir indicador</h3>
                        <p class="ficha-empleados-masivos-modal__lead">
                            Asigne usuarios o áreas completas. Todo asignado diligencia su propia captura; marque Consulta si debe ver el consolidado del equipo.
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    class="ficha-empleados-masivos-modal__close"
                    aria-label="Cerrar"
                    x-on:click="$dispatch('close-modal', 'planeacion-share')"
                >
                    <x-lucide-x width="18" height="18" aria-hidden="true" />
                </button>
            </div>

            <div class="planeacion-share-modal__body">
                <div class="planeacion-share__add">
                    <div class="form-field planeacion-share__user">
                        <label class="form-label" for="share_user_id">Usuario activo</label>
                        <x-searchable-select
                            id="share_user_id"
                            name="share_user_id"
                            :options="$shareUsers"
                            value=""
                            placeholder="Seleccionar usuario"
                            search-placeholder="Buscar usuario…"
                            :allow-clear="true"
                        />
                    </div>
                    <label class="planeacion-share__consult-add">
                        <input type="checkbox" x-model="newCanConsult">
                        <span>Consulta (ve consolidado del equipo)</span>
                    </label>
                    <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar usuario" aria-label="Agregar usuario" @click="addUser()">
                        <x-lucide-plus width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
                <div class="planeacion-share__add">
                    <div class="form-field planeacion-share__user">
                        <label class="form-label" for="share_area_key">Área</label>
                        <x-searchable-select
                            id="share_area_key"
                            name="share_area_key"
                            :options="$shareAreas ?? []"
                            value=""
                            placeholder="Seleccionar área"
                            search-placeholder="Buscar área…"
                            :allow-clear="true"
                        />
                    </div>
                    <label class="planeacion-share__consult-add">
                        <input type="checkbox" x-model="newAreaCanConsult">
                        <span>Consulta (ve consolidado del equipo)</span>
                    </label>
                    <button type="button" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar área" aria-label="Agregar área" @click="addArea()">
                        <x-lucide-plus width="18" height="18" aria-hidden="true" />
                    </button>
                </div>
                <p class="planeacion-share__error" x-show="error" x-cloak x-text="error"></p>

                <form method="POST" action="{{ route('calidad.planeacion.indicadores.share', $indicator) }}" class="planeacion-share__form">
                    @csrf
                    <template x-for="(row, index) in assignees" :key="row.user_id">
                        <input type="hidden" :name="'assignees[' + index + '][user_id]'" :value="row.user_id">
                    </template>
                    <template x-for="(row, index) in areaAssignees" :key="row.area_key">
                        <input type="hidden" :name="'area_assignees[' + index + '][area_key]'" :value="row.area_key">
                    </template>

                    <div class="data-table-wrap planeacion-share-modal__table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Captura</th>
                                    <th>Consulta</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="assignees.length === 0">
                                    <tr>
                                        <td colspan="4">Aún no hay usuarios asignados.</td>
                                    </tr>
                                </template>
                                <template x-for="(row, index) in assignees" :key="'row-' + row.user_id">
                                    <tr>
                                        <td x-text="row.name"></td>
                                        <td>Sí</td>
                                        <td>
                                            <label class="planeacion-share__consult">
                                                <input type="hidden" :name="'assignees[' + index + '][can_consult]'" :value="row.can_consult ? '1' : '0'">
                                                <input type="checkbox" x-model="row.can_consult">
                                                <span>Consulta</span>
                                            </label>
                                        </td>
                                        <td>
                                            <button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Quitar usuario" aria-label="Quitar usuario" @click="removeUser(index)">
                                                <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div class="data-table-wrap planeacion-share-modal__table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Área</th>
                                    <th>Captura</th>
                                    <th>Consulta</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="areaAssignees.length === 0">
                                    <tr>
                                        <td colspan="4">Aún no hay áreas asignadas.</td>
                                    </tr>
                                </template>
                                <template x-for="(row, index) in areaAssignees" :key="'area-' + row.area_key">
                                    <tr>
                                        <td x-text="row.name"></td>
                                        <td>Sí</td>
                                        <td>
                                            <label class="planeacion-share__consult">
                                                <input type="hidden" :name="'area_assignees[' + index + '][can_consult]'" :value="row.can_consult ? '1' : '0'">
                                                <input type="checkbox" x-model="row.can_consult">
                                                <span>Consulta</span>
                                            </label>
                                        </td>
                                        <td>
                                            <button type="button" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Quitar área" aria-label="Quitar área" @click="removeArea(index)">
                                                <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <div class="ficha-empleados-masivos-modal__footer">
                        <button type="button" class="btn btn--ghost" x-on:click="$dispatch('close-modal', 'planeacion-share')">
                            <x-lucide-x width="16" height="16" aria-hidden="true" />
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn--primary">
                            <x-lucide-save width="16" height="16" aria-hidden="true" />
                            Guardar asignaciones
                        </button>
                    </div>
                </form>

                {{-- Suplencias temporales: el suplente captura a nombre del titular dentro del rango. --}}
                <div class="planeacion-share__substitutions">
                    <h4 class="planeacion-share__substitutions-title">Suplencias</h4>
                    <p class="planeacion-share__substitutions-desc">
                        El suplente captura y reemplaza a nombre del titular solo entre las fechas indicadas. No hereda consulta.
                    </p>

                    <form method="POST" action="{{ route('calidad.planeacion.indicadores.substitutions.store', $indicator) }}" class="planeacion-share__substitution-form">
                        @csrf
                        <div class="form-field">
                            <label class="form-label" for="substitution_holder">Titular</label>
                            <x-searchable-select
                                id="substitution_holder"
                                name="holder_user_id"
                                :options="$shareUsers"
                                value=""
                                placeholder="Seleccionar titular"
                                search-placeholder="Buscar titular…"
                                :allow-clear="true"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="substitution_substitute">Suplente</label>
                            <x-searchable-select
                                id="substitution_substitute"
                                name="substitute_user_id"
                                :options="$shareUsers"
                                value=""
                                placeholder="Seleccionar suplente"
                                search-placeholder="Buscar suplente…"
                                :allow-clear="true"
                            />
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="substitution_starts_on">Desde</label>
                            <input id="substitution_starts_on" name="starts_on" type="date" class="form-input" required>
                        </div>
                        <div class="form-field">
                            <label class="form-label" for="substitution_ends_on">Hasta</label>
                            <input id="substitution_ends_on" name="ends_on" type="date" class="form-input" required>
                        </div>
                        <div class="form-field">
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Agregar suplencia" aria-label="Agregar suplencia">
                                <x-lucide-plus width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                    </form>

                    <div class="data-table-wrap planeacion-share-modal__table">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Titular</th>
                                    <th>Suplente</th>
                                    <th>Desde</th>
                                    <th>Hasta</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($substitutions ?? [] as $row)
                                    <tr>
                                        <td>{{ $row->holder?->name ?? '—' }}</td>
                                        <td>{{ $row->substitute?->name ?? '—' }}</td>
                                        <td>{{ $row->starts_on?->format('d/m/Y') }}</td>
                                        <td>{{ $row->ends_on?->format('d/m/Y') }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('calidad.planeacion.indicadores.substitutions.destroy', [$indicator, $row->id]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="cursos-catalogo-page__icon-btn cursos-catalogo-page__icon-btn--danger" title="Quitar suplencia" aria-label="Quitar suplencia">
                                                    <x-lucide-trash-2 width="16" height="16" aria-hidden="true" />
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">Aún no hay suplencias registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </x-modal>

    @once
        <script>
            window.planeacionShareForm = window.planeacionShareForm || function (initial) {
                return {
                    assignees: initial.assignees || [],
                    areaAssignees: initial.areaAssignees || [],
                    users: initial.users || [],
                    areas: initial.areas || [],
                    newCanConsult: false,
                    newAreaCanConsult: false,
                    error: '',
                    addUser() {
                        this.error = '';
                        const input = document.getElementById('share_user_id');
                        const userId = input ? String(input.value || '') : '';
                        if (! userId) {
                            this.error = 'Seleccione un usuario activo.';
                            return;
                        }
                        if (this.assignees.some((row) => String(row.user_id) === userId)) {
                            this.error = 'Ese usuario ya está asignado.';
                            return;
                        }
                        const option = this.users.find((row) => String(row.value) === userId);
                        this.assignees.push({
                            user_id: userId,
                            name: option ? option.label : userId,
                            can_consult: Boolean(this.newCanConsult),
                        });
                        this.newCanConsult = false;
                    },
                    addArea() {
                        this.error = '';
                        const input = document.getElementById('share_area_key');
                        const areaKey = input ? String(input.value || '') : '';
                        if (! areaKey) {
                            this.error = 'Seleccione un área.';
                            return;
                        }
                        if (this.areaAssignees.some((row) => String(row.area_key) === areaKey)) {
                            this.error = 'Esa área ya está asignada.';
                            return;
                        }
                        const option = this.areas.find((row) => String(row.value) === areaKey);
                        this.areaAssignees.push({
                            area_key: areaKey,
                            name: option ? option.label : areaKey,
                            can_consult: Boolean(this.newAreaCanConsult),
                        });
                        this.newAreaCanConsult = false;
                    },
                    removeUser(index) {
                        this.assignees.splice(index, 1);
                    },
                    removeArea(index) {
                        this.areaAssignees.splice(index, 1);
                    },
                };
            };
        </script>
    @endonce
@endif
