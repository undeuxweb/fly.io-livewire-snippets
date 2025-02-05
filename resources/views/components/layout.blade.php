<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')

        @livewireStyles
    </head>
    <body class="flex items-center justify-center min-h-screen">
        {{ $slot }}
        @livewireScripts
        @stack('scripts')
    </body>
</html>
