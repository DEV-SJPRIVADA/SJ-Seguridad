<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => config('access.areas.'.$module, 'Matrices')])
    </x-slot>

    <div class="page-section matrices-page">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced">{{ session('status') }}</div>
            @endif

            <header class="matrices-hero">
                <div class="matrices-hero__copy">
                    <p class="matrices-hero__eyebrow">Matrices dinámicas</p>
                    <h1 class="matrices-hero__title">Formularios propios de {{ config('access.areas.'.$module, 'tu área') }}</h1>
                    <p class="matrices-hero__text">
                        Crea matrices con los campos que necesites, compártelas con áreas o personas y consulta
                        los registros en un listado filtrable con exportación a Excel.
                    </p>
                </div>

                @if ($canCreate)
                    <a href="{{ route('matrices.create', ['module' => $module]) }}" class="btn btn--primary">
                        Nueva matriz
                    </a>
                @endif
            </header>

            @if ($rows->isEmpty())
                <div class="panel matrices-empty">
                    <div class="matrices-empty__icon" aria-hidden="true">
                        <x-lucide-layout-grid width="28" height="28" />
                    </div>
                    <h2 class="matrices-empty__title">Aún no hay matrices para mostrar</h2>
                    <p class="matrices-empty__text">
                        @if ($canCreate)
                            Crea la primera matriz de tu área y compártela para que otros usuarios la diligencien.
                        @else
                            Cuando alguien comparta una matriz contigo aparecerá en esta lista.
                        @endif
                    </p>
                    @if ($canCreate)
                        <a href="{{ route('matrices.create', ['module' => $module]) }}" class="btn btn--primary btn--sm">
                            Crear matriz
                        </a>
                    @endif
                </div>
            @else
                <div class="matrices-grid">
                    @foreach ($rows as $row)
                        @php
                            $matrix = $row['matrix'];
                            $access = $row['access'];
                        @endphp

                        <article class="matrices-card">
                            <header class="matrices-card__header">
                                <div class="matrices-card__heading">
                                    <span class="matrices-card__code">{{ $matrix->code ?? '—' }}</span>
                                    <h2 class="matrices-card__title">{{ $matrix->name }}</h2>
                                </div>
                                <span class="matrices-status matrices-status--{{ $matrix->status }}">
                                    {{ $matrix->statusLabel() }}
                                </span>
                            </header>

                            @if ($matrix->description)
                                <p class="matrices-card__description">{{ \Illuminate\Support\Str::limit($matrix->description, 180) }}</p>
                            @endif

                            <dl class="matrices-card__meta">
                                <div>
                                    <dt>Área</dt>
                                    <dd>{{ config('access.areas.'.$matrix->area_key, 'Sin área') }}</dd>
                                </div>
                                <div>
                                    <dt>Creador</dt>
                                    <dd>{{ $matrix->owner?->name ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt>Registros</dt>
                                    <dd>{{ number_format($row['records_count'], 0, ',', '.') }}</dd>
                                </div>
                                <div>
                                    <dt>Versión</dt>
                                    <dd>{{ $matrix->currentVersion?->version_number ?? '—' }}</dd>
                                </div>
                            </dl>

                            <div class="matrices-card__chips">
                                @if ($row['is_owner'])
                                    <span class="matrices-chip matrices-chip--owner">Creador</span>
                                @endif
                                @if ($access->canRegister)
                                    <span class="matrices-chip matrices-chip--ok">Registrar</span>
                                @endif
                                @if ($access->canEditOthers)
                                    <span class="matrices-chip">Edita todo</span>
                                @elseif ($access->canViewOthers)
                                    <span class="matrices-chip">Ve todo</span>
                                @else
                                    <span class="matrices-chip matrices-chip--muted">Solo lo propio</span>
                                @endif
                            </div>

                            <footer class="matrices-card__actions">
                                @if ($access->canRegister && ! $matrix->isArchived())
                                    <a href="{{ route('matrices.submissions.create', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--primary btn--sm">
                                        Registrar
                                    </a>
                                @endif
                                <a href="{{ route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary btn--sm">
                                    Registros
                                </a>

                                @if ($row['can_manage'])
                                    <a href="{{ route('matrices.share.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary btn--sm">
                                        Compartir
                                    </a>
                                    <a href="{{ route('matrices.edit', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary btn--sm">
                                        Definición
                                    </a>

                                    @if ($matrix->isDraft())
                                        <form method="POST" action="{{ route('matrices.publish', ['module' => $module, 'matrix' => $matrix->id]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn--info btn--sm">Publicar</button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('matrices.archive', ['module' => $module, 'matrix' => $matrix->id]) }}" onsubmit="return confirm('¿Archivar la matriz «{{ $matrix->name }}»? Los registros se conservan.');">
                                        @csrf
                                        <button type="submit" class="btn btn--danger btn--sm">Archivar</button>
                                    </form>
                                @endif
                            </footer>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
