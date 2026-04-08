@extends('shared::layouts.app')

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Artigos Técnicos',
            'description' => 'Categorias de artigos técnicos do Mercado Veículos',
            'url' => url('/artigos'),
            'inLanguage' => 'pt-BR',
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Mercado Veículos'),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('breadcrumbs')
<div class="bg-gray-100 border-b border-gray-200">
    <div class="mx-auto container px-4 lg:px-0 py-2 whitespace-nowrap overflow-x-auto">
        <nav class="text-xs md:text-sm" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex items-center gap-2" itemscope itemtype="https://schema.org/BreadcrumbList">
                <li class="flex items-center" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <a href="/" class="text-blue-600 hover:underline" itemprop="item">
                        <span itemprop="name">Início</span>
                    </a>
                    <meta itemprop="position" content="1" />
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mx-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </li>
                <li class="flex items-center" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span class="text-gray-700" itemprop="name">Artigos</span>
                    <meta itemprop="position" content="2" />
                </li>
            </ol>
        </nav>
    </div>
</div>
@endsection

@section('content')
<main id="main-content">
    <section class="bg-white py-16 md:py-24">
        <div class="container mx-auto px-4 lg:px-0">
            <div class="mb-10 md:mb-14">
                <h1 class="mb-3 font-montserrat text-4xl font-bold text-gray-900 md:text-5xl">Artigos Técnicos</h1>
                <p class="max-w-3xl text-lg text-gray-600">
                    Explore as categorias editoriais e veja quantos artigos publicados existem em cada tema.
                </p>
            </div>

            @if (session('success'))
                <div class="mb-10 rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="font-montserrat text-lg font-bold text-emerald-900">
                                Cadastro concluído
                            </p>
                            <p class="mt-1 text-sm leading-6 text-emerald-800">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                @php
                    $accents = [
                        ['title' => 'group-hover:text-vibrant-blueLight', 'link' => 'text-vibrant-blueLight', 'orb' => 'bg-vibrant-blueLight/20', 'shadow' => 'hover:shadow-vibrant-blueLight/5'],
                        ['title' => 'group-hover:text-orange-600', 'link' => 'text-orange-600', 'orb' => 'bg-orange-100', 'shadow' => 'hover:shadow-orange-500/5'],
                        ['title' => 'group-hover:text-sky-600', 'link' => 'text-sky-600', 'orb' => 'bg-sky-100', 'shadow' => 'hover:shadow-sky-500/5'],
                        ['title' => 'group-hover:text-emerald-600', 'link' => 'text-emerald-600', 'orb' => 'bg-emerald-100', 'shadow' => 'hover:shadow-emerald-500/5'],
                    ];
                @endphp

                @forelse($categories as $index => $category)
                    @php($accent = $accents[$index % count($accents)])
                    <a href="{{ route('content.category.index', ['categorySlug' => $category->slug]) }}"
                       class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-gray-50/50 p-10 transition-all duration-300 hover:shadow-xl {{ $accent['shadow'] }}">
                        <div class="relative z-10">
                            <div class="mb-4 inline-flex rounded-full border border-gray-200 bg-white px-3 py-1 text-sm font-semibold text-gray-600">
                                {{ $category->published_articles_count }} {{ \Illuminate\Support\Str::plural('artigo', $category->published_articles_count) }}
                            </div>

                            <h2 class="mb-3 font-montserrat text-3xl font-bold text-gray-900 transition-colors {{ $accent['title'] }}">
                                {{ $category->name }}
                            </h2>
                            <p class="mb-8 max-w-lg text-lg text-gray-600">
                                {{ $category->description ?: 'Conteúdo técnico atualizado para apoiar sua tomada de decisão no setor automotivo.' }}
                            </p>
                            <span class="flex items-center gap-2 font-bold {{ $accent['link'] }}">
                                Ver categoria
                                <svg class="h-5 w-5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="absolute -right-10 -bottom-10 h-48 w-48 rounded-full {{ $accent['orb'] }} opacity-30 transition-transform duration-500 group-hover:scale-125"></div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-10 text-gray-600">
                        Nenhuma categoria ativa foi encontrada.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Newsletter --}}
    <div class="container mx-auto px-4 lg:px-0 pb-16">
        <x-shared::newsletter />
    </div>
</main>
@endsection
