@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navegación de páginas" class="flex items-center justify-between px-4 py-3 bg-white sm:px-6">
        <!-- Vista móvil -->
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-xs font-bold text-slate-400 bg-white border border-slate-200 rounded-xl cursor-default select-none">
                    Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    Anterior
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors">
                    Siguiente
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-bold text-slate-400 bg-white border border-slate-200 rounded-xl cursor-default select-none">
                    Siguiente
                </span>
            @endif
        </div>

        <!-- Vista escritorio -->
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-[10px] text-slate-500 font-black uppercase tracking-wider">
                    Mostrando
                    <span class="font-black text-slate-900">{{ $paginator->firstItem() }}</span>
                    a
                    <span class="font-black text-slate-900">{{ $paginator->lastItem() }}</span>
                    de
                    <span class="font-black text-slate-900">{{ $paginator->total() }}</span>
                    resultados
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex space-x-1">
                    {{-- Anterior --}}
                    @if ($paginator->onFirstPage())
                        <span class="relative inline-flex items-center p-2 text-xs font-bold text-slate-300 bg-white border border-slate-200 rounded-xl cursor-default select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center p-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors focus:z-10 focus:outline-none focus:ring-1 focus:ring-black">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    @endif

                    {{-- Elementos de la paginación --}}
                    @foreach ($elements as $element)
                        {{-- Separador de tres puntos (...) --}}
                        @if (is_string($element))
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-bold text-slate-400 bg-white cursor-default select-none">
                                {{ $element }}
                            </span>
                        @endif

                        {{-- Lista de enlaces --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="relative inline-flex items-center px-3.5 py-2 text-xs font-extrabold text-white bg-black rounded-xl border border-black cursor-default select-none">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-3.5 py-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors focus:z-10 focus:outline-none focus:ring-1 focus:ring-black">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Siguiente --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center p-2 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition-colors focus:z-10 focus:outline-none focus:ring-1 focus:ring-black">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    @else
                        <span class="relative inline-flex items-center p-2 text-xs font-bold text-slate-300 bg-white border border-slate-200 rounded-xl cursor-default select-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
