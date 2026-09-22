<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('components.layouts.partials.head')
    @livewireStyles
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/sortable@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen bg-gray-50 text-gray-900 font-sans antialiased overflow-hidden">
    {{ $slot }}

    @persist('toast')
        <flux:toast position="top end" />
    @endpersist
    @livewireScriptConfig
    @fluxScripts
</body>
</html>
