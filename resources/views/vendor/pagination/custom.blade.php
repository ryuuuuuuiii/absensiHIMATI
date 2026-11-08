@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="himati-pagination">
        <div class="himati-pagination-info">
            Menampilkan
            <span class="font-semibold">{{ $paginator->firstItem() }}</span>
            sampai
            <span class="font-semibold">{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-semibold">{{ $paginator->total() }}</span>
            data
        </div>

        <ul class="himati-pagination-list">
            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" title="Sebelumnya"><i class="fas fa-chevron-left"></i></span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" title="Sebelumnya"><i class="fas fa-chevron-left"></i></a>
                </li>
            @endif

            {{-- Elemen Nomor Halaman --}}
            @foreach ($elements as $element)
                {{-- Separator Titik Tiga --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link dots">{{ $element }}</span>
                    </li>
                @endif

                {{-- Array Nomor Halaman --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Tombol Selanjutnya --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" title="Selanjutnya"><i class="fas fa-chevron-right"></i></a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link" title="Selanjutnya"><i class="fas fa-chevron-right"></i></span>
                </li>
            @endif
        </ul>
    </nav>
@endif
