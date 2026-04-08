<x-mail::message>
# Comentário recebido

Olá, **{{ $comment->user->name }}**!

Recebemos seu comentário no artigo **{{ $comment->article->title }}**.

<x-mail::panel>
**Seu comentário:**

{!! nl2br(e($comment->content)) !!}
</x-mail::panel>

Ele já está visível para a comunidade. Qualquer resposta será notificada.

<x-mail::button :url="url($comment->article->url)">
Ver artigo
</x-mail::button>
</x-mail::message>
