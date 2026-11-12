{{-- Documentos del grupo con selector de pestañas y vista previa (plataforma). --}}
@php
    $initialDoc = (string) ($batch->quotations->first()?->getKey() ?? '');
@endphp

<section class="pur-review-panel pur-review-panel--docs" x-data="{ doc: @js($initialDoc) }">
    <header class="pur-review-panel__head">
        <div>
            <h3 class="pur-review-panel__title">Cotizaciones del grupo</h3>
            <p class="pur-review-panel__sub">Revise cada documento antes de elegir la ganadora.</p>
        </div>
        <span class="status-pill status-pill--info">{{ $batch->count() }} documento(s)</span>
    </header>

    <div class="pur-review-docs">
        <div class="pur-review-docs__tabs" role="tablist" aria-label="Documentos del grupo">
            @foreach ($batch->quotations as $quotation)
                <button
                    type="button"
                    role="tab"
                    id="quot-tab-{{ $quotation->id }}"
                    class="pur-review-docs__tab"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    x-bind:class="{ 'pur-review-docs__tab--active': doc === '{{ $quotation->id }}' }"
                    x-bind:aria-selected="doc === '{{ $quotation->id }}' ? 'true' : 'false'"
                    x-on:click="doc = '{{ $quotation->id }}'"
                    aria-controls="quot-pane-{{ $quotation->id }}"
                >
                    <span class="pur-review-docs__ordinal">{{ $loop->iteration }}</span>
                    <span class="pur-review-docs__tab-body">
                        <span class="pur-review-docs__tab-name">{{ $quotation->descripcionVisible() }}</span>
                        <span class="pur-review-docs__tab-meta">
                            {{ $quotation->esPdf() ? 'PDF' : 'Imagen' }} · {{ $quotation->sizeLabel() }}
                        </span>
                    </span>
                    <span class="status-pill {{ $quotation->estadoPillClass() }}">{{ $quotation->estadoLabel() }}</span>
                </button>
            @endforeach
        </div>

        @foreach ($batch->quotations as $quotation)
            @php $fileUrl = $fileUrls[$quotation->id] ?? null; @endphp

            <div
                class="pur-review-docs__pane"
                id="quot-pane-{{ $quotation->id }}"
                role="tabpanel"
                aria-labelledby="quot-tab-{{ $quotation->id }}"
                x-show="doc === '{{ $quotation->id }}'"
                x-cloak
            >
                <div class="pur-review-docs__toolbar">
                    <p class="pur-review-docs__file">
                        <x-lucide-paperclip width="15" height="15" aria-hidden="true" />
                        <span class="pur-review-docs__file-name">{{ $quotation->original_name }}</span>
                        <span class="pur-review-docs__file-meta">
                            {{ $quotation->sizeLabel() }} · cargada por {{ $quotation->uploadedBy?->name ?? 'Compras' }}
                        </span>
                    </p>

                    @if ($fileUrl)
                        <a
                            href="{{ $fileUrl }}"
                            class="pur-review-docs__open"
                            target="_blank"
                            rel="noopener"
                        >
                            <x-lucide-external-link width="15" height="15" aria-hidden="true" />
                            Abrir en otra pestaña
                        </a>
                    @endif
                </div>

                <div class="pur-review-docs__stage">
                    @if (! $fileUrl)
                        <div class="pur-review-docs__empty">
                            <x-lucide-triangle-alert width="22" height="22" aria-hidden="true" />
                            <p>El archivo no está disponible. Contacte a Compras para volver a cargarlo.</p>
                        </div>
                    @elseif ($quotation->esPdf())
                        <iframe
                            src="{{ $fileUrl }}"
                            title="Cotización {{ $quotation->descripcionVisible() }}"
                            loading="lazy"
                        ></iframe>
                    @elseif ($quotation->esImagen())
                        <img
                            src="{{ $fileUrl }}"
                            alt="Cotización {{ $quotation->descripcionVisible() }}"
                            loading="lazy"
                        >
                    @else
                        <div class="pur-review-docs__empty">
                            <x-lucide-file-text width="22" height="22" aria-hidden="true" />
                            <p>Este archivo no se puede previsualizar. Ábralo con el enlace superior.</p>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
