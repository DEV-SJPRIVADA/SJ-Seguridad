{{--
  Historial de auditoría de Exámenes ocupacionales (solo seleccion.edit).
  Variables: $historialUserOptions (list<value,label>).
  Estado Alpine en seleccionExamens(): historial*, applyHistorialFilters(), clearHistorialFilters().
  Reutiliza el chrome .rn-historial-modal (timeline) + lista de cambios antes → después.
--}}
<x-modal name="seleccion-examen-historial" maxWidth="3xl" focusable>
    <div class="modal-card ficha-empleados-masivos-modal rn-novedad-modal rn-historial-modal seleccion-examen-historial">
        <div class="ficha-empleados-masivos-modal__header rn-novedad-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon rn-novedad-modal__icon" aria-hidden="true">
                    <x-lucide-history width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <p class="rn-novedad-modal__eyebrow">Auditoría</p>
                    <h3 class="ficha-empleados-masivos-modal__title">Historial de cambios</h3>
                    <p class="ficha-empleados-masivos-modal__lead" x-text="historialFilterable
                        ? 'Altas, cambios y eliminaciones en Examen ocupacional, por usuario.'
                        : 'Cambios registrados para este examen.'"></p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'seleccion-examen-historial')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        <div class="rn-historial-modal__body">
            {{-- Filtros (solo vista global): usuario, acción, cédula/usuario y rango de fechas --}}
            <form
                class="rn-historial-modal__filters"
                x-show="historialFilterable"
                x-cloak
                x-on:submit.prevent="applyHistorialFilters()"
            >
                <div class="rn-historial-modal__filters-grid seleccion-examen-historial__filters-grid">
                    <div class="rn-historial-modal__filter-field">
                        <label class="rn-historial-modal__filter-label" for="examen-historial-user">Usuario</label>
                        <x-searchable-select
                            id="examen-historial-user"
                            name="historial_user_id"
                            :options="$historialUserOptions"
                            value=""
                            placeholder="Todos"
                            :allow-clear="true"
                            x-on:change="historialFilters.user_id = String($event.detail?.value ?? $event.target?.value ?? '')"
                        />
                    </div>
                    <div class="rn-historial-modal__filter-field">
                        <label class="rn-historial-modal__filter-label" for="examen-historial-action">Acción</label>
                        <x-searchable-select
                            id="examen-historial-action"
                            name="historial_action"
                            :options="[
                                ['value' => 'create', 'label' => 'Alta'],
                                ['value' => 'update', 'label' => 'Actualización'],
                                ['value' => 'delete', 'label' => 'Eliminación'],
                            ]"
                            value=""
                            placeholder="Todas"
                            :allow-clear="true"
                            x-on:change="historialFilters.action = String($event.detail?.value ?? $event.target?.value ?? '')"
                        />
                    </div>
                    <div class="rn-historial-modal__filter-field">
                        <label class="rn-historial-modal__filter-label" for="examen-historial-q">Cédula</label>
                        <input id="examen-historial-q" type="search" class="form-input" placeholder="Cédula o usuario…" x-model="historialFilters.q">
                    </div>
                    <div class="rn-historial-modal__filter-field">
                        <label class="rn-historial-modal__filter-label" for="examen-historial-date-from">Desde</label>
                        <input id="examen-historial-date-from" type="date" class="form-input" x-model="historialFilters.date_from">
                    </div>
                    <div class="rn-historial-modal__filter-field">
                        <label class="rn-historial-modal__filter-label" for="examen-historial-date-to">Hasta</label>
                        <input id="examen-historial-date-to" type="date" class="form-input" x-model="historialFilters.date_to">
                    </div>
                    <div class="rn-historial-modal__filter-actions">
                        <span class="rn-historial-modal__filter-label rn-historial-modal__filter-label--spacer" aria-hidden="true">&nbsp;</span>
                        <div class="rn-historial-modal__filter-actions-row">
                            <button type="submit" class="req-manage-filters__icon-btn req-manage-filters__icon-btn--primary" title="Filtrar historial" aria-label="Filtrar historial">
                                <x-lucide-search width="18" height="18" aria-hidden="true" />
                            </button>
                            <button
                                type="button"
                                class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                title="Limpiar filtros"
                                aria-label="Limpiar filtros del historial"
                                x-on:click.prevent="clearHistorialFilters()"
                            >
                                <x-lucide-filter-x width="18" height="18" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="rn-historial-modal__meta" x-show="! historialLoading" x-cloak>
                <span class="rn-historial-modal__count">
                    <span x-text="historialItems.length"></span>
                    <span x-text="historialItems.length === 1 ? 'evento' : 'eventos'"></span>
                </span>
            </div>

            <div class="rn-historial-modal__state" x-show="historialLoading" x-cloak>
                <span class="rn-historial-modal__spinner" aria-hidden="true"></span>
                <p class="rn-historial-modal__state-title">Cargando historial…</p>
                <p class="rn-historial-modal__state-desc">Consultando eventos de auditoría.</p>
            </div>

            <div class="rn-historial-modal__state rn-historial-modal__state--empty" x-show="! historialLoading && historialItems.length === 0" x-cloak>
                <span class="rn-historial-modal__empty-icon" aria-hidden="true">
                    <x-lucide-inbox width="22" height="22" />
                </span>
                <p class="rn-historial-modal__state-title">Sin eventos</p>
                <p class="rn-historial-modal__state-desc">Aún no hay actividad registrada para este alcance.</p>
            </div>

            <ol class="rn-historial-modal__timeline" x-show="! historialLoading && historialItems.length > 0" x-cloak>
                <template x-for="item in historialItems" :key="item.id">
                    <li class="rn-historial-modal__item">
                        <span class="rn-historial-modal__dot" x-bind:class="historialActionClass(item, 'dot')" aria-hidden="true"></span>
                        <div class="rn-historial-modal__card">
                            <div class="rn-historial-modal__card-top">
                                <time class="rn-historial-modal__when" x-text="item.created_at_display"></time>
                                <span class="rn-historial-modal__badge" x-text="historialActionLabel(item)" x-bind:class="historialActionClass(item)"></span>
                            </div>
                            <p class="rn-historial-modal__summary" x-text="item.summary"></p>
                            <div class="rn-historial-modal__actor">
                                <span class="rn-historial-modal__actor-icon" aria-hidden="true">
                                    <x-lucide-user-round width="14" height="14" />
                                </span>
                                <span x-text="item.user_name || 'Sistema'"></span>
                            </div>

                            {{-- Cambios campo a campo: alta muestra valores iniciales; edición antes → después --}}
                            <ul class="seleccion-examen-historial__changes" x-show="(item.changes || []).length > 0" x-cloak>
                                <template x-for="change in item.changes" :key="item.id + '-' + change.field">
                                    <li class="seleccion-examen-historial__change">
                                        <span class="seleccion-examen-historial__field" x-text="change.label"></span>
                                        <template x-if="item.action !== 'create'">
                                            <span class="seleccion-examen-historial__before" x-text="change.before"></span>
                                        </template>
                                        <template x-if="item.action !== 'create'">
                                            <span class="seleccion-examen-historial__arrow" aria-hidden="true">→</span>
                                        </template>
                                        <span class="seleccion-examen-historial__after" x-text="change.after"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </li>
                </template>
            </ol>
        </div>
    </div>
</x-modal>
