<x-app-layout>
    <div
        class="page-section"
        x-data="notifConfigPage({
            modules: @js($moduleGroups),
            fallback: @js($fallbackRecipient),
            initialModule: @js($initialModule),
            initialTypeId: @js($initialTypeId),
        })"
    >
        <div class="app-container notif-config-page">
            @if (session('status'))
                <div class="alert alert--success notif-config-page__alert" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger notif-config-page__alert" role="alert">
                    @foreach ($errors->all() as $error)
                        <p style="margin: 0;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <header class="notif-config-page__hero">
                <div>
                    <p class="eyebrow">Administracion</p>
                    <h2 class="panel-title">Configuracion de notificaciones</h2>
                    <p class="panel-text">Defina quien recibe cada correo automatico del sistema.</p>
                </div>
                <p class="notif-config-page__fallback">
                    <span class="notif-config-page__fallback-label">Respaldo si no hay destinatarios</span>
                    <strong>{{ $fallbackRecipient }}</strong>
                </p>
            </header>

            <div class="notif-config-kpis">
                <article class="notif-config-kpi">
                    <span class="notif-config-kpi__label">Modulos</span>
                    <span class="notif-config-kpi__value">{{ $stats['modules'] }}</span>
                </article>
                <article class="notif-config-kpi">
                    <span class="notif-config-kpi__label">Avisos</span>
                    <span class="notif-config-kpi__value">{{ $stats['types'] }}</span>
                </article>
                <article class="notif-config-kpi notif-config-kpi--success">
                    <span class="notif-config-kpi__label">Configurados</span>
                    <span class="notif-config-kpi__value">{{ $stats['configured_types'] }}</span>
                </article>
                <article class="notif-config-kpi {{ $stats['empty_types'] > 0 ? 'notif-config-kpi--warning' : '' }}">
                    <span class="notif-config-kpi__label">Sin destinatarios</span>
                    <span class="notif-config-kpi__value">{{ $stats['empty_types'] }}</span>
                </article>
            </div>

            <div class="notif-config-toolbar">
                <div class="notif-config-toolbar__search">
                    <label class="sr-only" for="notif-search">Buscar aviso</label>
                    <input
                        id="notif-search"
                        type="search"
                        class="form-input"
                        placeholder="Buscar aviso o modulo..."
                        x-model="search"
                        autocomplete="off"
                    >
                </div>
                <div class="notif-config-toolbar__filters">
                    <select class="form-select" x-model="statusFilter" aria-label="Filtrar por estado">
                        <option value="all">Todos los estados</option>
                        <option value="configured">Con destinatarios</option>
                        <option value="empty">Sin destinatarios</option>
                    </select>
                </div>
            </div>

            {{-- Grid de modulos --}}
            <div class="notif-config-modules-grid" x-show="screen === 'grid'" x-cloak>
                <template x-if="filteredModules.length === 0">
                    <div class="panel">
                        <div class="panel__body">
                            <p class="text-muted">No hay avisos que coincidan con la busqueda.</p>
                        </div>
                    </div>
                </template>
                <template x-for="group in filteredModules" :key="group.module">
                    <button
                        type="button"
                        class="notif-config-module-card"
                        @click="openModule(group.module)"
                    >
                        <span class="notif-config-module-card__icon" aria-hidden="true">
                            <x-lucide-mail width="22" height="22" aria-hidden="true" />
                        </span>
                        <span class="notif-config-module-card__title" x-text="group.module_label"></span>
                        <span class="notif-config-module-card__meta">
                            <span x-text="visibleTypeCount(group) + ' aviso(s)'"></span>
                        </span>
                        <span
                            class="notif-config-module-card__badge"
                            :class="group.empty_count > 0 ? 'notif-config-module-card__badge--warning' : 'notif-config-module-card__badge--ok'"
                            x-text="group.empty_count > 0 ? group.empty_count + ' sin correo' : 'Listo'"
                        ></span>
                    </button>
                </template>
            </div>

            {{-- Detalle modulo --}}
            <div class="notif-config-detail" x-show="screen === 'detail'" x-cloak>
                <button type="button" class="notif-config-detail__back" @click="backToGrid()">
                    <x-lucide-arrow-left width="18" height="18" aria-hidden="true" />
                    Volver a modulos
                </button>

                <div class="notif-config-detail__layout" x-show="activeModule">
                    <aside class="notif-config-detail__nav panel">
                        <div class="panel__header">
                            <h3 class="panel-title" x-text="activeModule?.module_label"></h3>
                            <p class="panel-text">Seleccione un aviso para editar destinatarios.</p>
                        </div>
                        <nav class="notif-config-type-nav" aria-label="Avisos del modulo">
                            <template x-for="type in filteredTypesForModule" :key="type.id">
                                <button
                                    type="button"
                                    class="notif-config-type-nav__item"
                                    :class="{ 'notif-config-type-nav__item--active': selectedTypeId === type.id }"
                                    @click="selectType(type.id)"
                                    :id="'notification-type-' + type.id"
                                >
                                    <span class="notif-config-type-nav__label" x-text="type.label"></span>
                                    <span
                                        class="notif-config-type-nav__badge"
                                        :class="type.emails.length === 0 ? 'notif-config-type-nav__badge--empty' : 'notif-config-type-nav__badge--ok'"
                                        x-text="type.emails.length === 0 ? 'Respaldo' : type.emails.length"
                                    ></span>
                                </button>
                            </template>
                        </nav>
                    </aside>

                    <section class="notif-config-detail__panel panel" x-show="activeType">
                        <div class="panel__header">
                            <h3 class="panel-title" x-text="activeType?.label"></h3>
                            <p class="panel-text" x-show="activeType?.description" x-text="activeType?.description"></p>
                        </div>
                        <div class="panel__body">
                            <div class="notif-config-recipients-block">
                                <h4 class="notif-config-recipients-block__title">Destinatarios</h4>
                                <template x-if="activeType && activeType.emails.length === 0">
                                    <p class="notif-config-empty">
                                        Sin destinatarios configurados. Al enviar este aviso se usara el correo de respaldo del sistema.
                                    </p>
                                </template>
                                <div class="notif-config-chips" x-show="activeType && activeType.emails.length > 0">
                                    <template x-for="assignedEmail in activeType.emails" :key="assignedEmail.id">
                                        <form
                                            method="POST"
                                            :action="detachUrl(activeType.id, assignedEmail.id)"
                                            class="notif-config-chip-form"
                                            @submit="if (! confirm('Quitar este correo del aviso?')) { $event.preventDefault(); }"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <span class="notif-config-chip">
                                                <span class="notif-config-chip__email" x-text="assignedEmail.name"></span>
                                                <button type="submit" class="notif-config-chip__remove" aria-label="Quitar correo">&times;</button>
                                            </span>
                                        </form>
                                    </template>
                                </div>
                            </div>

                            <form
                                x-show="activeType"
                                method="POST"
                                :action="attachUrl(activeType?.id)"
                                class="notif-config-add-form"
                            >
                                @csrf
                                <h4 class="notif-config-add-form__title">Agregar correo</h4>
                                <div class="notif-config-add-form__row">
                                    <div class="form-field" style="margin: 0;">
                                        <label class="form-label" :for="'notif-email-' + activeType?.id">Correo destinatario</label>
                                        <input
                                            :id="'notif-email-' + activeType?.id"
                                            name="email"
                                            type="email"
                                            class="form-input"
                                            placeholder="nombre@empresa.com"
                                            required
                                            autocomplete="email"
                                            list="notif-suggested-emails"
                                            value="{{ old('email') }}"
                                        >
                                    </div>
                                    <button type="submit" class="btn btn--primary">Agregar</button>
                                </div>
                                @if ($suggestedEmails !== [])
                                    <p class="notif-config-add-form__hint">
                                        Correos usados en otros avisos:
                                        {{ implode(' · ', array_slice($suggestedEmails, 0, 5)) }}@if (count($suggestedEmails) > 5) … @endif
                                    </p>
                                @endif
                            </form>
                        </div>
                    </section>
                </div>
            </div>

            @if ($moduleGroups === [])
                <div class="panel">
                    <div class="panel__body">
                        <p class="text-muted">No hay avisos configurables.</p>
                    </div>
                </div>
            @endif

            <datalist id="notif-suggested-emails">
                @foreach ($suggestedEmails as $email)
                    <option value="{{ $email }}"></option>
                @endforeach
            </datalist>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('notifConfigPage', (config) => ({
                    screen: 'grid',
                    selectedModule: null,
                    selectedTypeId: null,
                    search: '',
                    statusFilter: 'all',
                    modules: config.modules ?? [],
                    fallback: config.fallback ?? '',

                    init() {
                        const module = config.initialModule;
                        const typeId = config.initialTypeId ? Number(config.initialTypeId) : null;

                        if (module) {
                            this.openModule(module, typeId);
                            return;
                        }

                        const hash = window.location.hash;
                        if (hash.startsWith('#notification-type-')) {
                            const id = Number(hash.replace('#notification-type-', ''));
                            if (! Number.isNaN(id)) {
                                this.openTypeById(id);
                            }
                        }
                    },

                    get activeModule() {
                        return this.modules.find((group) => group.module === this.selectedModule) ?? null;
                    },

                    get activeType() {
                        return this.activeModule?.types.find((type) => type.id === this.selectedTypeId) ?? null;
                    },

                    get filteredModules() {
                        const query = this.search.trim().toLowerCase();

                        return this.modules
                            .map((group) => {
                                const types = group.types.filter((type) => this.typeMatchesFilters(type, group, query));

                                return {
                                    ...group,
                                    types,
                                    type_count: types.length,
                                    empty_count: types.filter((type) => type.emails.length === 0).length,
                                };
                            })
                            .filter((group) => group.types.length > 0);
                    },

                    get filteredTypesForModule() {
                        if (! this.activeModule) {
                            return [];
                        }

                        const query = this.search.trim().toLowerCase();

                        return this.activeModule.types.filter((type) => this.typeMatchesFilters(type, this.activeModule, query));
                    },

                    typeMatchesFilters(type, group, query) {
                        if (query !== '') {
                            const haystack = [
                                type.label,
                                type.description ?? '',
                                group.module_label,
                            ].join(' ').toLowerCase();

                            if (! haystack.includes(query)) {
                                return false;
                            }
                        }

                        if (this.statusFilter === 'configured' && type.emails.length === 0) {
                            return false;
                        }

                        if (this.statusFilter === 'empty' && type.emails.length > 0) {
                            return false;
                        }

                        return true;
                    },

                    visibleTypeCount(group) {
                        const query = this.search.trim().toLowerCase();

                        return group.types.filter((type) => this.typeMatchesFilters(type, group, query)).length;
                    },

                    openModule(moduleKey, typeId = null) {
                        this.selectedModule = moduleKey;
                        this.screen = 'detail';

                        const group = this.modules.find((item) => item.module === moduleKey);
                        if (! group || group.types.length === 0) {
                            return;
                        }

                        const visible = group.types.filter((type) => this.typeMatchesFilters(type, group, this.search.trim().toLowerCase()));
                        const pool = visible.length > 0 ? visible : group.types;

                        if (typeId && pool.some((type) => type.id === typeId)) {
                            this.selectedTypeId = typeId;
                        } else {
                            this.selectedTypeId = pool[0].id;
                        }

                        this.scrollToSelectedType();
                    },

                    openTypeById(typeId) {
                        for (const group of this.modules) {
                            const match = group.types.find((type) => type.id === typeId);
                            if (match) {
                                this.openModule(group.module, typeId);
                                return;
                            }
                        }
                    },

                    selectType(typeId) {
                        this.selectedTypeId = typeId;
                    },

                    backToGrid() {
                        this.screen = 'grid';
                        this.selectedModule = null;
                        this.selectedTypeId = null;
                        window.history.replaceState({}, '', '{{ route('admin.notifications.index') }}');
                    },

                    scrollToSelectedType() {
                        this.$nextTick(() => {
                            if (this.selectedTypeId) {
                                document.getElementById('notification-type-' + this.selectedTypeId)?.scrollIntoView({ block: 'nearest' });
                            }
                        });
                    },

                    attachUrl(typeId) {
                        return @json(url('/admin/notificaciones/tipos')) + '/' + typeId + '/correos';
                    },

                    detachUrl(typeId, emailId) {
                        return @json(url('/admin/notificaciones/tipos')) + '/' + typeId + '/correos/' + emailId;
                    },
                }));
            });
        </script>
    @endpush
</x-app-layout>
