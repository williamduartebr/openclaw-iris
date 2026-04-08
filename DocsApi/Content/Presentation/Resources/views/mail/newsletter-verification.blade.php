<x-mail::message>
# Confirme sua inscrição

Olá{{ $subscriber->name ? ', **'.$subscriber->name.'**' : '' }}!

Recebemos sua solicitação de inscrição na newsletter do **{{ config('app.name') }}**.

Use o código abaixo para confirmar seu e-mail:

<x-mail::panel>
**{{ $code }}**
</x-mail::panel>

Ou clique no botão abaixo para acessar a página de verificação:

<x-mail::button :url="route('newsletter.verify', ['email' => $subscriber->email])">
Verificar e-mail
</x-mail::button>

Se você não solicitou esta inscrição, pode ignorar este e-mail.
</x-mail::message>
