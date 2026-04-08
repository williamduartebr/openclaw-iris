<div class="bg-gray-100 border-b border-gray-200">
    <div class="mx-auto container px-4 lg:px-0 py-2 whitespace-nowrap overflow-x-auto">
        <nav class="text-xs md:text-sm" aria-label="Breadcrumb">
            <ol class="list-none p-0 inline-flex items-center gap-2" itemscope
                itemtype="https://schema.org/BreadcrumbList">
                <li class="flex items-center" itemprop="itemListElement" itemscope
                    itemtype="https://schema.org/ListItem">
                    <a href="/" class="text-blue-600 hover:underline" itemprop="item">
                        <span itemprop="name">Início</span>
                    </a>
                    <meta itemprop="position" content="1" />
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mx-1 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </li>
                <li class="flex items-center" itemprop="itemListElement" itemscope
                    itemtype="https://schema.org/ListItem">
                    <a href="/artigos" class="text-blue-600 hover:underline"
                        itemprop="item">
                        <span itemprop="name">Artigos</span>
                    </a>
                    <meta itemprop="position" content="2" />
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mx-1 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </li>
                <li class="flex items-center" itemprop="itemListElement" itemscope
                    itemtype="https://schema.org/ListItem">
                    <a href="/artigos/{{ $article->category->slug }}" class="text-blue-600 hover:underline"
                        itemprop="item">
                        <span itemprop="name">{{ $article->category->name }}</span>
                    </a>
                    <meta itemprop="position" content="3" />
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mx-1 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </li>
                <li class="flex items-center" itemprop="itemListElement" itemscope
                    itemtype="https://schema.org/ListItem">
                    <span class="text-gray-700" itemprop="name">{{ Str::limit($article->title, 40) }}</span>
                    <meta itemprop="position" content="4" />
                </li>
            </ol>
        </nav>
    </div>
</div>
