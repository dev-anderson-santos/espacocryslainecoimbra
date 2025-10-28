@component('mail::message')
# 📅 Novos Agendamentos Registrados

{!! nl2br(e($msg)) !!}

@component('mail::button', ['url' => config('app.url')])
Acessar o Sistema
@endcomponent

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
