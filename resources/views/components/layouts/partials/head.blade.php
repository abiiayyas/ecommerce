<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="{{ asset('img/logo3.png') }}" type="image/png">
<link rel="apple-touch-icon" href="{{ asset('img/logo3.png') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

{{-- General SEO --}}
<link rel="canonical" href="{{ url()->current() }}">
<meta name="og:site_name" content="{{ config('app.name') }}">

@yield('seo')
@vite(['resources/css/app.css', 'resources/js/app.js'])
{{-- @fluxAppearance --}}
