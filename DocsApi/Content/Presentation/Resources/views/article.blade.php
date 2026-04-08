@extends('shared::layouts.app')

@push('head')
@include('content::article.partials.head-schemas')
@endpush

@push('styles')
@include('content::article.partials.styles')
@endpush

@section('breadcrumbs')
@include('content::article.partials.breadcrumbs')
@endsection

@section('content')
<main id="main-content">
    @include('content::article.partials.article-main')

    @include('content::article.partials.comments-section')

    {{-- Newsletter --}}
    <div class="max-w-250 mx-auto px-6 my-10">
        <x-shared::newsletter
            :category-slug="$article->category->slug ?? null"
            :category-name="$article->category->name ?? null"
            :source-url="request()->url()"
        />
    </div>

    {{-- Banner de Anúncio 3 (bloqueado em BOFU) --}}
    <div class="max-w-178 mx-auto px-6 my-10">
        <x-shared::ad-slot name="content_bottom" :funnel-stage="$article->category->funnel_stage ?? null" />
    </div>

    @include('content::article.partials.related-articles')
</main>
@endsection

@push('scripts')
@include('content::article.partials.scripts')
@endpush
