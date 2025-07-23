<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased flex flex-col min-h-screen">
        <!-- Main Content with Background Image -->
        <div class="flex-1 flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative"
             style="background-image: url('{{ Vite::asset('resources/images/bg.jpg') }}'); background-size: cover; background-position: center; background-attachment: fixed; background-repeat: no-repeat;">
            
            <!-- Dark Overlay -->
            <div class="absolute inset-0 bg-black/60"></div>
            
            <!-- Content -->
            <div class="relative z-10">
                <a href="/">
                    <x-application-logo class="w-64 h-auto mx-auto drop-shadow-2xl" />
                </a>
            </div>

            <div class="relative z-10 w-full sm:max-w-md mt-8 px-8 py-8 login-card">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
