<x-mail::message>
# Novo comentário recebido

Um novo comentário foi postado no artigo **{{ $comment->article->title }}**.

<x-mail::panel>
- **Usuário:** {{ $comment->user->name }} ({{ $comment->user->email }})
- **Comentário:**

{!! nl2br(e($comment->content)) !!}
</x-mail::panel>

<x-mail::button :url="url('/admin/tickets')">
Gerenciar comentários
</x-mail::button>
</x-mail::message>
