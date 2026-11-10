{{-- Historial de versiones: permite consultar una anterior y ver qué cambió. --}}
@php
    /** @var list<array<string, mixed>> $versionHistory */
    $consultQuery = $consultQuery ?? null;
    $activeVersionId = (int) ($activeVersionId ?? 0);
@endphp

<div class="panel planeacion-version-history">
    <div class="panel__header">
        <h3 class="panel-title">Historial de versiones</h3>
        <p class="panel-text">Consulte una versión anterior y revise los cambios respecto a la previa.</p>
    </div>
    <div class="panel__body">
        @if ($versionHistory === [])
            <p class="planeacion-version-history__empty">Aún no hay versiones registradas.</p>
        @else
            <ol class="planeacion-version-history__list">
                @foreach ($versionHistory as $row)
                    <li class="planeacion-version-history__item @if ($activeVersionId === (int) $row['id']) planeacion-version-history__item--active @endif">
                        <div class="planeacion-version-history__head">
                            <p class="planeacion-version-history__title">
                                v{{ $row['version_number'] }} · {{ $row['status_label'] }}
                            </p>
                            @if (is_array($consultQuery))
                                <a
                                    href="{{ route('calidad.planeacion.indicadores.show', array_merge($consultQuery, ['version' => $row['id']])) }}"
                                    class="req-manage-filters__icon-btn req-manage-filters__icon-btn--ghost"
                                    title="Consultar v{{ $row['version_number'] }}"
                                    aria-label="Consultar versión {{ $row['version_number'] }}"
                                >
                                    <x-lucide-eye width="18" height="18" aria-hidden="true" />
                                </a>
                            @endif
                        </div>
                        <p class="planeacion-version-history__meta">
                            {{ $row['name'] }}
                            @if ($row['published_at'])
                                <span>· Publicada {{ $row['published_at'] }}</span>
                            @endif
                        </p>
                        <ul class="planeacion-version-history__changes">
                            @foreach ($row['changes'] as $change)
                                <li>{{ $change }}</li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>
</div>
