@extends('shared::layouts.app')

@push('head')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $category->description,
            'url' => url('/artigos/' . $category->slug),
            'inLanguage' => 'pt-BR',
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name', 'Mercado Veículos')
            ]
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
                    <a href="/artigos" class="text-blue-600 hover:underline" itemprop="item">
                        <span itemprop="name">Artigos</span>
                    </a>
                    <meta itemprop="position" content="2" />
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mx-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </li>
                <li class="flex items-center" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                    <span class="text-gray-700" itemprop="name">{{ $category->name }}</span>
                    <meta itemprop="position" content="3" />
                </li>
            </ol>
        </nav>
    </div>
</div>
@endsection

@section('content')
<main id="main-content">
    <!-- Category Header -->
    <header class="bg-white border-b border-gray-100 py-16 md:py-24">
        <div class="container mx-auto px-4 lg:px-0 text-center md:text-left">
            <h1 class="font-['Montserrat'] font-bold text-4xl md:text-5xl lg:text-6xl mb-6 text-gray-900 tracking-tight">
                {{ $category->name }}
            </h1>
            @if($category->description)
            <p class="text-xl md:text-2xl text-gray-500 max-w-3xl leading-relaxed font-roboto font-light">
                {{ $category->description }}
            </p>
            @endif
        </div>
    </header>

    <!-- Banner AdSense — Categorias -->
    <div class="container mx-auto px-4 lg:px-0 pt-0 py-6">
        <x-shared::ad-slot name="content_category" />
    </div>

    <!-- Articles Grid -->
    <section class="py-16 md:py-24 bg-gray-50/50">
        <div class="container mx-auto px-4 lg:px-0">
            @if($articles->count() > 0)
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-x-10 gap-y-16 md:gap-x-14 md:gap-y-24">
                @foreach($articles as $article)
                <article class="group relative flex flex-col h-full transition-all duration-300 hover:bg-slate-100 hover:scale-[1.03] rounded-2xl p-6 -m-6">
                    <a href="{{ $article->url }}" class="block">
                        @if($article->featured_image)
                        <div class="aspect-[16/10] mb-6 overflow-hidden rounded-xl bg-gray-100 shadow-sm border border-gray-100">
                            <img src="{{ $article->featured_image }}" alt="{{ $article->title }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-700 ease-out">
                        </div>
                        @endif

                        <div class="flex items-center gap-2 text-sm text-gray-500 mb-3 font-medium tracking-wide">
                            <time datetime="{{ $article->published_at->toIso8601String() }}">
                                {{ $article->published_at->format('d/m/Y') }}
                            </time>
                            <span>&middot;</span>
                            <span>{{ $article->reading_time }} min</span>
                        </div>

                        <h2 class="font-['Montserrat'] font-bold text-2xl mb-2 text-gray-900 leading-snug group-hover:text-blue-700 transition-colors">
                            {{ $article->title }}
                        </h2>

                        <div class="mb-5">
                            <span class="text-blue-700 font-medium text-sm hover:text-blue-800 transition-colors inline-flex items-center group-hover:underline decoration-2 underline-offset-4">
                                Ler artigo
                                <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </span>
                        </div>

                        <p class="text-gray-600 leading-relaxed line-clamp-3">
                            {{ Str::limit($article->excerpt, 150) }}
                        </p>
                    </a>
                </article>
                @endforeach
            </div>

            <!-- Pagination -->
            @if($articles->hasPages())
            <div class="mt-12">
                {{ $articles->onEachSide(1)->links() }}
            </div>
            @endif
            @else
            <div class="text-center py-16">
                <p class="text-xl text-gray-600">
                    Nenhum artigo publicado ainda nesta categoria.
                </p>
            </div>
            @endif
        </div>
    </section>

    {{-- Newsletter --}}
    <div class="container mx-auto px-4 lg:px-0 pb-16">
        <x-shared::newsletter :category-slug="$category->slug" :category-name="$category->name" />
    </div>
</main>
@endsection
