<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $settings['app_name'] ?? config('app.name', 'Laravel') }}</title>

        @if(!empty($settings['favicon_path']))
            <link rel="icon" href="{{ asset('storage/'.$settings['favicon_path']) }}">
        @endif

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <style>:root { --color-primary: {{ $settings['primary_color'] ?? '#4f46e5' }}; }</style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="relative min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4 overflow-hidden">

            {{-- Background: uploaded image (if any) --}}
            @if(!empty($settings['login_bg_path']))
                <div class="absolute inset-0 bg-cover bg-center"
                     style="background-image: url('{{ asset('storage/'.$settings['login_bg_path']) }}');"></div>
            @endif

            {{-- Gradient overlay (sits above the image, below the content) --}}
            <div class="absolute inset-0"
                 style="background: linear-gradient(135deg, var(--color-primary), #0f172a);
                        opacity: {{ !empty($settings['login_bg_path']) ? '0.82' : '1' }};"></div>

            {{-- Content --}}
            <div class="relative z-10 flex flex-col items-center mb-6">
                @if(!empty($settings['logo_path']))
                    <img src="{{ asset('storage/'.$settings['logo_path']) }}" class="w-16 h-16 rounded-xl object-contain bg-white p-1 shadow-lg">
                @else
                    <div class="w-16 h-16 rounded-xl flex items-center justify-center text-white text-2xl font-bold bg-white/20 backdrop-blur">
                        {{ substr($settings['app_short_name'] ?? 'P', 0, 1) }}
                    </div>
                @endif
                <h1 class="mt-3 text-white text-lg font-semibold text-center drop-shadow">{{ $settings['app_name'] ?? config('app.name') }}</h1>
                @if(!empty($settings['organization']))<p class="text-white/80 text-sm drop-shadow">{{ $settings['organization'] }}</p>@endif
            </div>

            <div class="relative z-10 w-full sm:max-w-md px-6 py-6 bg-white/95 backdrop-blur shadow-2xl overflow-hidden rounded-2xl">
                {{ $slot }}
            </div>

            <p class="relative z-10 mt-6 text-white/70 text-xs text-center drop-shadow">{{ $settings['footer_text'] ?? '' }}</p>
        </div>
    </body>
</html>
