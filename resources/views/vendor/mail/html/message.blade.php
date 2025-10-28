@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
{{-- <img src="{{ asset(config('adminlte.logo_img')) }}" class="logo" alt="{{ config('app.name') }}"> --}}
<img src="https://laravel.com/img/notification-logo.png" class="logo" alt="Laravel Logo">
@endcomponent
@endslot

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
@slot('subcopy')
@component('mail::subcopy')
{{ $subcopy }}
@endcomponent
@endslot
@endisset

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. @lang('All rights reserved.')
@endcomponent
@endslot
@endcomponent
