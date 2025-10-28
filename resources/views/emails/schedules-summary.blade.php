@component('mail::message')
# 📆 Novos agendamentos registrados

{!! nl2br(e($msg)) !!}

@endcomponent
