@extends('shared::layouts.app')

@section('content')
<main id="main-content">
    <section class="bg-white py-24 md:py-32">
        <div class="container mx-auto px-4 lg:px-0 text-center max-w-lg">
            <svg class="mx-auto h-20 w-20 text-gray-400 mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>

            <h1 class="text-3xl font-extrabold text-gray-900 mb-4 font-urbanist">Inscrição cancelada</h1>
            <p class="text-lg text-gray-600 mb-8">
                Você foi removido da nossa newsletter com sucesso. Sentiremos sua falta!
            </p>

            <a href="/"
               class="inline-flex items-center gap-2 bg-brand-blue text-white font-bold px-6 py-3 rounded-lg hover:bg-brand-dark transition-colors">
                Voltar ao início
            </a>
        </div>
    </section>
</main>
@endsection
