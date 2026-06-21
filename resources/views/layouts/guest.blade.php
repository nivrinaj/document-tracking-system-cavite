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
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <style>:root { --color-primary: {{ $settings['primary_color'] ?? '#4f46e5' }}; }</style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50 dark:bg-gray-900">
        <div class="min-h-screen min-h-[100dvh] flex">

            {{-- ───────── Left: brand panel (hidden on small screens) ───────── --}}
            <div class="relative hidden lg:flex lg:w-1/2 flex-col justify-between p-12 overflow-hidden">
                {{-- background image (optional) --}}
                @if(!empty($settings['login_bg_path']))
                    <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('storage/'.$settings['login_bg_path']) }}');"></div>
                @endif
                {{-- gradient --}}
                <div class="absolute inset-0" style="background: linear-gradient(150deg, var(--color-primary) 0%, #1e293b 70%, #0f172a 100%); opacity: {{ !empty($settings['login_bg_path']) ? '0.88' : '1' }};"></div>
                {{-- decorative shapes --}}
                <div class="absolute -top-24 -right-24 w-96 h-96 rounded-full bg-white/10 blur-2xl"></div>
                <div class="absolute -bottom-32 -left-20 w-96 h-96 rounded-full bg-white/5 blur-2xl"></div>

                {{-- brand top --}}
                <div class="relative z-10 flex items-center gap-3">
                    @if(!empty($settings['logo_path']))
                        <img src="{{ asset('storage/'.$settings['logo_path']) }}" class="w-12 h-12 rounded-xl object-contain bg-white p-1 shadow-lg">
                    @else
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-white text-xl font-bold bg-white/20 backdrop-blur">
                            {{ substr($settings['app_short_name'] ?? 'P', 0, 1) }}
                        </div>
                    @endif
                    <span class="text-white/90 font-semibold tracking-wide">{{ $settings['app_short_name'] ?? 'PGC DTS' }}</span>
                </div>

                {{-- headline --}}
                <div class="relative z-10 max-w-md">
                    <h2 class="text-white text-3xl font-bold leading-tight drop-shadow">{{ $settings['app_name'] ?? config('app.name') }}</h2>
                    @if(!empty($settings['organization']))
                        <p class="mt-2 text-white/80 text-lg">{{ $settings['organization'] }}</p>
                    @endif
                    <div class="mt-8 space-y-3 text-white/85 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </span>
                            QR-tracked documents across every office
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z"/></svg>
                            </span>
                            Real-time status, history &amp; reports
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </span>
                            Secure, accountable, paperless routing
                        </div>
                    </div>
                </div>

                {{-- footer --}}
                <p class="relative z-10 text-white/60 text-xs">{{ $settings['footer_text'] ?? '© '.date('Y').' '.($settings['organization'] ?? '') }}</p>
            </div>

            {{-- ───────── Right: sign-in (desktop: centered card · mobile: branded bottom sheet) ───────── --}}
            <div class="relative flex-1 flex flex-col min-h-screen min-h-[100dvh] lg:justify-center lg:items-center lg:p-12 bg-gray-50 dark:bg-gray-900">

                {{-- Mobile-only branded background (image + gradient overlay) --}}
                <div class="absolute inset-0 lg:hidden overflow-hidden">
                    @if(!empty($settings['login_bg_path']))
                        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('storage/'.$settings['login_bg_path']) }}');"></div>
                    @endif
                    <div class="absolute inset-0" style="background: linear-gradient(150deg, var(--color-primary) 0%, #1e293b 70%, #0f172a 100%); opacity: {{ !empty($settings['login_bg_path']) ? '0.9' : '1' }};"></div>
                </div>

                {{-- Mobile brand header (sits on the gradient, above the sheet) --}}
                <div class="relative z-10 lg:hidden flex flex-col items-center text-center px-6 pt-12 pb-6">
                    @if(!empty($settings['logo_path']))
                        <img src="{{ asset('storage/'.$settings['logo_path']) }}" class="w-16 h-16 rounded-2xl object-contain bg-white p-1.5 shadow-lg">
                    @else
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-2xl font-bold bg-white/20 backdrop-blur">
                            {{ substr($settings['app_short_name'] ?? 'P', 0, 1) }}
                        </div>
                    @endif
                    <h1 class="mt-3 text-white text-xl font-bold drop-shadow">{{ $settings['app_name'] ?? config('app.name') }}</h1>
                    @if(!empty($settings['organization']))
                        <p class="text-white/80 text-sm drop-shadow">{{ $settings['organization'] }}</p>
                    @endif
                </div>

                {{-- Form: bottom sheet on mobile (rounded top, anchored to bottom) · plain centered card on desktop --}}
                <div class="relative z-10 w-full mt-auto lg:mt-0 lg:max-w-sm
                            bg-white dark:bg-gray-800 rounded-t-3xl shadow-2xl px-6 py-8 sm:px-8
                            lg:bg-transparent lg:dark:bg-transparent lg:rounded-none lg:shadow-none lg:p-0">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Welcome back</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sign in to continue to your dashboard.</p>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
