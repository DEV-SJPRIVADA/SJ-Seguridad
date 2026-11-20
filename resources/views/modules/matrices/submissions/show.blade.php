<x-app-layout>
    <x-slot name="header">
        @include('modules.matrices.partials.subnav', ['tabs' => $tabs, 'label' => $matrix->name])
    </x-slot>

    @php
        $groupedFields = $fields->groupBy(fn ($field) => $field->section ?: 'General');
    @endphp

    <div class="page-section matrices-page matrices-detail">
        <div class="app-container">
            @if (session('status'))
                <div class="alert alert--success bottom-spaced" role="status">{{ session('status') }}</div>
            @endif

            <header class="matrices-hero matrices-hero--compact">
                <div class="matrices-hero__copy">
                    <p class="matrices-hero__eyebrow">{{ $matrix->name }}</p>
                    <h1 class="matrices-hero__title">{{ $submission->folio }}</h1>
                    <div class="matrices-detail__meta">
                        <span class="matrices-status matrices-status--{{ $submission->status }}">{{ $submission->statusLabel() }}</span>
                        <span class="matrices-muted">Registrado por {{ $submission->creator?->name ?? '—' }}</span>
                        <span class="matrices-muted">{{ $submission->created_at?->format('d/m/Y H:i') }}</span>
                        <span class="matrices-muted">Versión {{ $submission->version?->version_number ?? '—' }}</span>
                    </div>
                </div>

                <div class="matrices-hero__actions">
                    <a href="{{ route('matrices.submissions.index', ['module' => $module, 'matrix' => $matrix->id]) }}" class="btn btn--secondary">Volver</a>

                    @if ($canEdit)
                        <a href="{{ route('matrices.submissions.edit', ['module' => $module, 'matrix' => $matrix->id, 'submission' => $submission->id]) }}" class="btn btn--primary">Editar</a>
                    @endif

                    @if ($canArchive)
                        <form method="POST" action="{{ route('matrices.submissions.archive', ['module' => $module, 'matrix' => $matrix->id, 'submission' => $submission->id]) }}" onsubmit="return confirm('¿Archivar el registro {{ $submission->folio }}? Se conserva para consulta.');">
                            @csrf
                            <button type="submit" class="btn btn--danger">Archivar</button>
                        </form>
                    @endif
                </div>
            </header>

            @foreach ($groupedFields as $section => $sectionFields)
                <section class="panel matrices-detail__section">
                    <div class="panel__header panel__header--compact">
                        <h2 class="matrices-panel-title">{{ $section }}</h2>
                    </div>
                    <div class="panel__body">
                        <dl class="matrices-detail__grid">
                            @foreach ($sectionFields as $field)
                                @php
                                    $value = $values->get($field->id);
                                    $fieldAttachments = $attachments->get($field->id) ?? collect();
                                @endphp

                                <div class="matrices-detail__item matrices-detail__item--{{ in_array((int) $field->col_span, [4, 6], true) ? (int) $field->col_span : 12 }}">
                                    <dt>{{ $field->label }}</dt>
                                    <dd>
                                        @if ($field->type === 'file')
                                            @if ($fieldAttachments->isEmpty())
                                                <span class="matrices-muted">Sin adjuntos</span>
                                            @else
                                                <ul class="matrices-attachments">
                                                    @foreach ($fieldAttachments as $attachment)
                                                        <li class="matrices-attachments__item">
                                                            <span class="matrices-attachments__name">{{ $attachment->original_name }}</span>
                                                            <span class="matrices-muted">{{ $attachment->humanSize() }}</span>
                                                            @if ($attachment->isPreviewable())
                                                                <a href="{{ route('matrices.attachments.preview', ['module' => $module, 'matrix' => $matrix->id, 'attachment' => $attachment->id]) }}" target="_blank" rel="noopener" class="matrices-link">Ver</a>
                                                            @endif
                                                            <a href="{{ route('matrices.attachments.download', ['module' => $module, 'matrix' => $matrix->id, 'attachment' => $attachment->id]) }}" class="matrices-link">Descargar</a>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        @elseif ($field->type === 'signature')
                                            @if ($fieldAttachments->isEmpty())
                                                <span class="matrices-muted">Sin firma</span>
                                            @else
                                                <a href="{{ route('matrices.attachments.preview', ['module' => $module, 'matrix' => $matrix->id, 'attachment' => $fieldAttachments->first()->id]) }}" target="_blank" rel="noopener">
                                                    <img
                                                        src="{{ route('matrices.attachments.preview', ['module' => $module, 'matrix' => $matrix->id, 'attachment' => $fieldAttachments->first()->id]) }}"
                                                        alt="Firma"
                                                        class="matrices-signature__image matrices-signature__image--read"
                                                    >
                                                </a>
                                            @endif
                                        @elseif ($field->type === 'checkbox')
                                            @php
                                                $labels = collect($value?->value_json ?? [])
                                                    ->map(fn ($key) => $value?->optionLabel((string) $key) ?: (string) $key)
                                                    ->filter();
                                            @endphp
                                            {{ $labels->isEmpty() ? '—' : $labels->implode(', ') }}
                                        @else
                                            {{ $value?->displayValue() ?: '—' }}
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-app-layout>
