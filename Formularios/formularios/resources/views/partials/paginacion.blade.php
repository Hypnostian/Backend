@if ($paginator->hasPages())
    <nav aria-label="Paginación">
        <ul class="pagination">
            @if ($paginator->onFirstPage())
                <li class="is-disabled" aria-disabled="true"><span aria-hidden="true">&lsaquo;</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">&lsaquo;</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="is-disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="is-active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}" aria-label="Ir a la página {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Página siguiente">&rsaquo;</a></li>
            @else
                <li class="is-disabled" aria-disabled="true"><span aria-hidden="true">&rsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
