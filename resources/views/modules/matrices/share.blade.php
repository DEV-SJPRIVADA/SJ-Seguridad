<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => $matrix->name])
    </x-slot>

    <div class="page-section matrices-page matrices-share">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger bottom-spaced" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="matrices-alert-line">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <header class="matrices-hero matrices-hero--compact">
                <div class="matrices-hero__copy">
                    <p class="matrices-hero__eyebrow">Compartir</p>
                    <h1 class="matrices-hero__title">{{ $matrix->name }}</h1>
                    <p class="matrices-hero__text">
                        El creador siempre ve todos los registros. Comparte por área o por usuario y define
                        qué puede hacer cada uno.
                    </p>
                </div>
                <span class="matrices-status matrices-status--{{ $matrix->status }}">{{ $matrix->statusLabel() }}</span>
            </header>

            <div class="matrices-share__grid">
                <section class="panel">
                    <div class="panel__header panel__header--compact">
                        <h2 class="matrices-panel-title">Compartir con un área</h2>
                    </div>
                    <div class="panel__body">
                        <form method="POST" action="{{ route('matrices.share.store', ['module' => $module, 'matrix' => $matrix->id]) }}" class="matrices-share__form">
                            @csrf
                            <input type="hidden" name="kind" value="area">

                            <div class="form-field">
                                <label class="form-label" for="share-area">Área</label>
                                <x-searchable-select
                                    id="share-area"
                                    name="area_key"
                                    :options="collect($areaOptions)->map(fn ($label, $key) => ['value' => $key, 'label' => $label])->values()->all()"
                                    placeholder="Selecciona un área"
                                    searchPlaceholder="Buscar área…"
                                    :required="true"
                                />
                            </div>

                            @include('modules.matrices.partials.capability-toggles', ['prefix' => 'area'])

                            <div class="form-field">
                                <label class="form-label" for="area-expires">Vigencia (opcional)</label>
                                <input id="area-expires" type="date" class="form-input" name="expires_at" min="{{ now()->toDateString() }}">
                            </div>

                            <button type="submit" class="btn btn--primary">Guardar acceso del área</button>
                        </form>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel__header panel__header--compact">
                        <h2 class="matrices-panel-title">Compartir con un usuario</h2>
                    </div>
                    <div class="panel__body">
                        <form method="POST" action="{{ route('matrices.share.store', ['module' => $module, 'matrix' => $matrix->id]) }}" class="matrices-share__form">
                            @csrf
                            <input type="hidden" name="kind" value="user">

                            <div class="form-field">
                                <label class="form-label" for="share-user">Usuario</label>
                                <x-searchable-select
                                    id="share-user"
                                    name="user_id"
                                    :options="collect($userOptions)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()->all()"
                                    placeholder="Selecciona un usuario"
                                    searchPlaceholder="Buscar por nombre…"
                                    :required="true"
                                />
                            </div>

                            @include('modules.matrices.partials.capability-toggles', ['prefix' => 'user'])

                            <div class="form-field">
                                <label class="form-label" for="user-expires">Vigencia (opcional)</label>
                                <input id="user-expires" type="date" class="form-input" name="expires_at" min="{{ now()->toDateString() }}">
                            </div>

                            <button type="submit" class="btn btn--primary">Guardar acceso del usuario</button>
                        </form>
                    </div>
                </section>
            </div>

            <section class="panel">
                <div class="panel__header panel__header--compact">
                    <h2 class="matrices-panel-title">Accesos por área</h2>
                </div>
                <div class="panel__body">
                    @if ($areaAccess->isEmpty())
                        <p class="matrices-muted">Todavía no has compartido esta matriz con ninguna área.</p>
                    @else
                        <div class="matrices-access-list">
                            @foreach ($areaAccess as $grant)
                                <article class="matrices-access-row">
                                    <div class="matrices-access-row__identity">
                                        <strong>{{ $grant->areaLabel() }}</strong>
                                        <span class="matrices-muted">
                                            Otorgado por {{ $grant->grantedBy?->name ?? '—' }}
                                            @if ($grant->expires_at)
                                                · vence {{ $grant->expires_at->format('d/m/Y') }}
                                            @endif
                                            @if ($grant->expires_at && $grant->expires_at->isPast())
                                                · <span class="matrices-chip matrices-chip--danger">Vencido</span>
                                            @endif
                                        </span>
                                    </div>

                                    <form method="POST" action="{{ route('matrices.share.store', ['module' => $module, 'matrix' => $matrix->id]) }}" class="matrices-access-row__form">
                                        @csrf
                                        <input type="hidden" name="kind" value="area">
                                        <input type="hidden" name="area_key" value="{{ $grant->area_key }}">

                                        @include('modules.matrices.partials.capability-checkboxes', [
                                            'prefix' => 'area-'.$grant->id,
                                            'grant' => $grant,
                                        ])

                                        <input type="date" class="form-input form-input--sm" name="expires_at" min="{{ now()->toDateString() }}" value="{{ $grant->expires_at?->toDateString() }}" title="Vigencia">
                                        <button type="submit" class="btn btn--secondary btn--sm">Actualizar</button>
                                    </form>

                                    <form method="POST" action="{{ route('matrices.share.destroy-area', ['module' => $module, 'matrix' => $matrix->id, 'areaAccess' => $grant->id]) }}" onsubmit="return confirm('¿Retirar el acceso del área {{ $grant->areaLabel() }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm">Quitar</button>
                                    </form>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            <section class="panel">
                <div class="panel__header panel__header--compact">
                    <h2 class="matrices-panel-title">Accesos por usuario</h2>
                </div>
                <div class="panel__body">
                    @if ($userAccess->isEmpty())
                        <p class="matrices-muted">Todavía no has compartido esta matriz con usuarios individuales.</p>
                    @else
                        <div class="matrices-access-list">
                            @foreach ($userAccess as $grant)
                                <article class="matrices-access-row">
                                    <div class="matrices-access-row__identity">
                                        <strong>{{ $grant->user?->name ?? 'Usuario eliminado' }}</strong>
                                        <span class="matrices-muted">
                                            {{ config('access.areas.'.$grant->user?->area_key, 'Sin área') }}
                                            · otorgado por {{ $grant->grantedBy?->name ?? '—' }}
                                            @if ($grant->expires_at)
                                                · vence {{ $grant->expires_at->format('d/m/Y') }}
                                            @endif
                                        </span>
                                    </div>

                                    <form method="POST" action="{{ route('matrices.share.store', ['module' => $module, 'matrix' => $matrix->id]) }}" class="matrices-access-row__form">
                                        @csrf
                                        <input type="hidden" name="kind" value="user">
                                        <input type="hidden" name="user_id" value="{{ $grant->user_id }}">

                                        @include('modules.matrices.partials.capability-checkboxes', [
                                            'prefix' => 'user-'.$grant->id,
                                            'grant' => $grant,
                                        ])

                                        <input type="date" class="form-input form-input--sm" name="expires_at" min="{{ now()->toDateString() }}" value="{{ $grant->expires_at?->toDateString() }}" title="Vigencia">
                                        <button type="submit" class="btn btn--secondary btn--sm">Actualizar</button>
                                    </form>

                                    <form method="POST" action="{{ route('matrices.share.destroy-user', ['module' => $module, 'matrix' => $matrix->id, 'userAccess' => $grant->id]) }}" onsubmit="return confirm('¿Retirar el acceso de {{ $grant->user?->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm">Quitar</button>
                                    </form>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
