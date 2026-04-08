    <!-- Related Articles -->
    @if($relatedArticles->count() > 0)
    <section class="border-t border-gray-200 bg-gray-50 py-20">
        <div class="container mx-auto px-4 lg:px-0">
            <h2 class="font-['Montserrat'] font-bold text-3xl md:text-4xl mb-12 text-gray-900">Leia também</h2>

            <div class="grid md:grid-cols-3 gap-10">
                @foreach($relatedArticles as $related)
                <article class="group">
                    <a href="{{ $related->url }}" class="block">
                        @if($related->featured_image)
                        <div class="aspect-[16/10] mb-5 overflow-hidden bg-gray-200 rounded-sm">
                            <img src="{{ $related->featured_image }}" alt="{{ $related->title }}"
                                loading="lazy" decoding="async"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        </div>
                        @endif

                        <div class="text-xs uppercase font-medium tracking-wider text-blue-800 mb-3">
                            {{ $related->category->name }}
                        </div>

                        <h3
                            class="font-['Montserrat'] font-bold text-xl md:text-2xl mb-3 leading-tight group-hover:text-blue-800 transition text-gray-900">
                            {{ $related->title }}
                        </h3>

                        <p class="text-gray-600 mb-4 line-clamp-2">
                            {{ Str::limit($related->excerpt, 120) }}
                        </p>

                        <div class="text-sm text-gray-500">
                            {{ $related->reading_time }} min de leitura
                        </div>
                    </a>
                </article>
                @endforeach
            </div>


        </div>
    </section>
    @endif
