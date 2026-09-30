{{-- Variables: $sheetLabel (string) --}}
@php
    $sheetLabel = $sheetLabel ?? 'la hoja';
@endphp

<x-modal name="rn-historial" maxWidth="2xl" focusable>
    <div class="modal-card ficha-empleados-masivos-modal rn-novedad-modal rn-historial-modal">
        <div class="ficha-empleados-masivos-modal__header rn-novedad-modal__header">
            <div class="ficha-empleados-masivos-modal__heading">
                <span class="ficha-empleados-masivos-modal__heading-icon rn-novedad-modal__icon" aria-hidden="true">
                    <x-lucide-history width="18" height="18" aria-hidden="true" />
                </span>
                <div>
                    <p class="rn-novedad-modal__eyebrow">Auditoría</p>
                    <h3 class="ficha-empleados-masivos-modal__title">Historial</h3>
                    <p class="ficha-empleados-masivos-modal__lead">
                        Eventos registrados en <strong>{{ $sheetLabel }}</strong>.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="ficha-empleados-masivos-modal__close"
                aria-label="Cerrar"
                x-on:click="$dispatch('close-modal', 'rn-historial')"
            >
                <x-lucide-x width="18" height="18" aria-hidden="true" />
            </button>
        </div>

        <div class="rn-historial-modal__body">
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

            <div
                class="rn-historial-modal__state rn-historial-modal__state--empty"
                x-show="! historialLoading && historialItems.length === 0"
                x-cloak
            >
                <span class="rn-historial-modal__empty-icon" aria-hidden="true">
                    <x-lucide-inbox width="22" height="22" />
                </span>
                <p class="rn-historial-modal__state-title">Sin eventos</p>
                <p class="rn-historial-modal__state-desc">Aún no hay actividad registrada para este alcance.</p>
            </div>

            <ol
                class="rn-historial-modal__timeline"
                x-show="! historialLoading && historialItems.length > 0"
                x-cloak
            >
                <template x-for="item in historialItems" :key="item.id">
                    <li class="rn-historial-modal__item">
                        <span
                            class="rn-historial-modal__dot"
                            x-bind:class="historialActionClass(item, 'dot')"
                            aria-hidden="true"
                        ></span>
                        <div class="rn-historial-modal__card">
                            <div class="rn-historial-modal__card-top">
                                <time class="rn-historial-modal__when" x-text="item.created_at_display"></time>
                                <span
                                    class="rn-historial-modal__badge"
                                    x-text="historialActionLabel(item)"
                                    x-bind:class="historialActionClass(item)"
                                ></span>
                            </div>
                            <p class="rn-historial-modal__summary" x-text="item.summary"></p>
                            <div class="rn-historial-modal__actor">
                                <span class="rn-historial-modal__actor-icon" aria-hidden="true">
                                    <x-lucide-user-round width="14" height="14" />
                                </span>
                                <span x-text="item.user_name || 'Sistema'"></span>
                            </div>
                            <p
                                class="rn-historial-modal__reason"
                                x-show="item.reason"
                                x-cloak
                                x-text="item.reason"
                            ></p>
                        </div>
                    </li>
                </template>
            </ol>
        </div>
    </div>
</x-modal>
