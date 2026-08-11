@if ($paginator->hasPages())
    <nav class="sj-pagination" role="navigation" aria-label="Navegacion de paginas">
        <p class="sj-pagination__meta">
            Mostrando
            @if ($paginator->firstItem())
                <span class="sj-pagination__strong">{{ $paginator->firstItem() }}</span>
                a
                <span class="sj-pagination__strong">{{ $paginator->lastItem() }}</span>
            @else
                <span class="sj-pagination__strong">{{ $paginator->count() }}</span>
            @endif
            de
            <span class="sj-pagination__strong">{{ $paginator->total() }}</span>
            resultados
        </p>

        <ul class="sj-pagination__list">
            @if ($paginator->onFirstPage())
                <li>
                    <span class="sj-pagination__item sj-pagination__item--disabled" aria-disabled="true" aria-label="Anterior">
                        <svg class="sj-pagination__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            @else
                <li>
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="sj-pagination__item" aria-label="Anterior">
                        <svg class="sj-pagination__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="sj-pagination__item sj-pagination__item--ellipsis" aria-hidden="true">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li>
                                <span class="sj-pagination__item sj-pagination__item--current" aria-current="page">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $url }}" class="sj-pagination__item">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li>
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="sj-pagination__item" aria-label="Siguiente">
                        <svg class="sj-pagination__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </li>
            @else
                <li>
                    <span class="sj-pagination__item sj-pagination__item--disabled" aria-disabled="true" aria-label="Siguiente">
                        <svg class="sj-pagination__icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
