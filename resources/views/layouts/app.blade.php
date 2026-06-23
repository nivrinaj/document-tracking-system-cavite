<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $settings['app_name'] ?? config('app.name') }}</title>
    @if(!empty($settings['favicon_path']))
        <link rel="icon" href="{{ asset('storage/'.$settings['favicon_path']) }}">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @php
        $primary = $settings['primary_color'] ?? '#4f46e5';
        // Lighten/darken a hex color so the whole UI can theme from one color.
        $shade = function (string $hex, int $pct): string {
            $hex = ltrim($hex, '#');
            if (strlen($hex) === 3) { $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
            $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
            $adj = fn ($c) => (int) max(0, min(255, $pct > 0 ? $c + (255 - $c) * $pct / 100 : $c + $c * $pct / 100));
            return sprintf('#%02x%02x%02x', $adj($r), $adj($g), $adj($b));
        };
    @endphp
    <style>
        :root {
            --color-primary: {{ $primary }};
            --color-primary-light: {{ $shade($primary, 35) }};
            --color-primary-dark: {{ $shade($primary, -18) }};
        }
        [x-cloak] { display: none !important; }
    </style>

    {{-- Apply dark mode + saved font size before paint to avoid a flash --}}
    <script>
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
        (function () {
            var fs = parseInt(localStorage.getItem('fontScale') || '16', 10);
            document.documentElement.style.fontSize = fs + 'px';
            window.adjustFont = function (delta) {
                var v = parseInt(localStorage.getItem('fontScale') || '16', 10) + delta;
                v = Math.max(14, Math.min(22, v));
                localStorage.setItem('fontScale', v);
                document.documentElement.style.fontSize = v + 'px';
            };
            window.resetFont = function () {
                localStorage.setItem('fontScale', '16');
                document.documentElement.style.fontSize = '16px';
            };
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-100"
      x-data="{ sidebarOpen: false, dark: document.documentElement.classList.contains('dark') }"
      x-init="$watch('dark', v => { localStorage.setItem('darkMode', v); document.documentElement.classList.toggle('dark', v); })">

    <div class="min-h-screen lg:flex">

        {{-- ===================== Sidebar ===================== --}}
        <aside class="fixed inset-y-0 left-0 z-40 w-64 transform bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-transform duration-200 lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen lg:self-start"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex items-center gap-3 h-16 px-5 border-b border-gray-200 dark:border-gray-700">
                @if(!empty($settings['logo_path']))
                    <img src="{{ asset('storage/'.$settings['logo_path']) }}" alt="Logo" class="h-9 w-9 rounded object-contain">
                @else
                    <div class="h-9 w-9 rounded-lg flex items-center justify-center text-white font-bold" style="background: var(--color-primary)">
                        {{ strtoupper(substr($settings['app_short_name'] ?? 'P', 0, 1)) }}
                    </div>
                @endif
                <div class="leading-tight">
                    <div class="font-semibold text-sm">{{ $settings['app_short_name'] ?? 'PGC-DTS' }}</div>
                    <div class="text-[11px] text-gray-400">Document Tracking</div>
                </div>
            </div>

            <nav class="px-3 py-4 space-y-1 overflow-y-auto h-[calc(100vh-4rem)]">
                <x-nav-item :active="request()->routeIs('dashboard')" :href="route('dashboard')" label="Dashboard">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </x-nav-item>

                @can('documents.view')
                <x-nav-item :active="request()->routeIs('documents.*')" :href="route('documents.index')" label="Document Tracking">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </x-nav-item>
                @endcan

                @if(\App\Models\Conversation::enabled())
                <x-nav-item :active="request()->routeIs('messages.*')" :href="route('messages.index')" label="Messages">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.5C3.5 15.3 3 13.7 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </x-nav-item>
                @endif

                @can('reports.view')
                <x-nav-item :active="request()->routeIs('reports.*')" :href="route('reports.index')" label="Reports">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </x-nav-item>
                @endcan

                <x-nav-item :active="request()->routeIs('logs.*')" :href="route('logs.index')" label="Logs & History">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </x-nav-item>

                @canany(['users.manage', 'divisions.manage', 'roles.manage', 'settings.manage'])
                <div class="pt-4 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Administration</div>
                @endcanany

                @can('users.manage')
                <x-nav-item :active="request()->routeIs('users.*')" :href="route('users.index')" label="Users">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z"/>
                </x-nav-item>
                @endcan

                @can('departments.manage')
                <x-nav-item :active="request()->routeIs('departments.*')" :href="route('departments.index')" label="Departments">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0H5m14 0h2M5 21H3m4-14h2m-2 4h2m6-4h2m-2 4h2M9 21v-4h6v4"/>
                </x-nav-item>
                @endcan

                @can('roles.manage')
                <x-nav-item :active="request()->routeIs('roles.*')" :href="route('roles.index')" label="Roles & Permissions">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </x-nav-item>
                @endcan

                @role('Super Admin')
                <x-nav-item :active="request()->routeIs('document-types.*')" :href="route('document-types.index')" label="Document Types">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z"/>
                </x-nav-item>
                @endrole

                @can('settings.manage')
                <x-nav-item :active="request()->routeIs('settings.*')" :href="route('settings.edit')" label="System Settings">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </x-nav-item>
                @endcan

                @role('Super Admin')
                <div class="pt-4 pb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Help</div>
                <x-nav-item :active="request()->routeIs('documentation.*')" :href="route('documentation.index')" label="Documentation">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </x-nav-item>
                <x-nav-item :active="request()->routeIs('changelog.*')" :href="route('changelog.index')" label="Changelog">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </x-nav-item>
                @endrole

                <div class="px-3 pt-4 mt-2">
                    <a href="{{ auth()->user()->hasRole('Super Admin') ? route('changelog.index') : '#' }}"
                       class="block text-[11px] text-gray-400 {{ auth()->user()->hasRole('Super Admin') ? 'hover:text-[color:var(--color-primary)]' : 'pointer-events-none' }}">
                        Version {{ config('version.number') }}
                    </a>
                </div>
            </nav>
        </aside>

        {{-- Mobile overlay --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
             class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

        {{-- ===================== Main column ===================== --}}
        <div class="flex-1 flex flex-col min-w-0">
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 h-16 flex items-center gap-4 px-4 sm:px-6 bg-white/90 dark:bg-gray-800/90 backdrop-blur border-b border-gray-200 dark:border-gray-700">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 -ml-2 rounded text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="flex-1 min-w-0">
                    @isset($header)
                        <div class="font-semibold text-lg truncate">{{ $header }}</div>
                    @endisset
                </div>

                {{-- Messages --}}
                @if(\App\Models\Conversation::enabled())
                    <a href="{{ route('messages.index') }}" class="relative p-2 rounded-lg {{ request()->routeIs('messages.*') ? 'text-[color:var(--color-primary)] bg-[color:var(--color-primary)]/10' : 'text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700' }}" title="Messages">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4-.8L3 20l1.3-3.5C3.5 15.3 3 13.7 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        <span id="msgBadge" class="absolute -top-0.5 -right-0.5 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5 hidden" style="background: var(--color-primary)"></span>
                    </a>
                @endif

                {{-- Notifications bell --}}
                @php $unreadCount = auth()->user()->unreadNotifications()->count(); @endphp
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open; if (open) window.__notifLoad && window.__notifLoad()" class="relative p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" title="Notifications">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span id="notifBadge" class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] leading-none rounded-full px-1.5 py-0.5 {{ $unreadCount > 0 ? '' : 'hidden' }}">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         x-init="window.__notifLoad && window.__notifLoad()"
                         class="fixed inset-x-3 top-14 sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-80 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg z-50">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                            <span class="font-semibold text-sm">Notifications</span>
                            <form method="POST" action="{{ route('notifications.readAll') }}">@csrf
                                <button class="text-xs link">Mark all read</button>
                            </form>
                        </div>
                        <div id="notifList" class="max-h-80 overflow-y-auto">
                            <p class="px-4 py-8 text-center text-sm text-gray-400">Loading…</p>
                        </div>
                        <a href="{{ route('notifications.index') }}" class="block text-center text-xs link py-2 border-t border-gray-100 dark:border-gray-700">View all notifications</a>
                    </div>
                </div>

                {{-- Font size control (helps users who need bigger text) --}}
                <div class="hidden sm:flex items-center rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden" title="Text size">
                    <button type="button" onclick="adjustFont(-1)" class="px-2 py-1 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Smaller text">A&minus;</button>
                    <button type="button" onclick="resetFont()" class="px-2 py-1 text-sm font-semibold text-gray-600 dark:text-gray-300 border-x border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Reset text size">A</button>
                    <button type="button" onclick="adjustFont(1)" class="px-2 py-1 text-base text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Larger text">A+</button>
                </div>

                {{-- Dark mode toggle --}}
                <button @click="dark = !dark" class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700" title="Toggle dark mode">
                    <svg x-show="!dark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg x-show="dark" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </button>

                {{-- User dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
                        <img src="{{ auth()->user()->avatar_url }}" class="w-8 h-8 rounded-full object-cover" alt="">
                        <span class="hidden sm:block text-sm font-medium">{{ auth()->user()->name }}</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false"
                         class="absolute right-0 mt-2 w-56 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg py-1">
                        <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                            <div class="text-sm font-medium truncate">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</div>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach(auth()->user()->getRoleNames() as $r)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded-full text-white" style="background: var(--color-primary)">{{ $r }}</span>
                                @endforeach
                            </div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">My Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700">Log Out</button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Flash messages --}}
            <div class="px-4 sm:px-6 pt-4 space-y-2">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" class="flex items-start gap-2 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 text-sm">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="flex-1">{{ session('success') }}</span>
                        <button @click="show = false">&times;</button>
                    </div>
                @endif
                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show" class="flex items-start gap-2 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 text-sm">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="flex-1">{{ session('error') }}</span>
                        <button @click="show = false">&times;</button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 text-sm">
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif
            </div>

            {{-- Page content --}}
            <main class="flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>

            <footer class="px-6 py-4 text-center text-xs text-gray-400 border-t border-gray-200 dark:border-gray-700">
                {{ $settings['footer_text'] ?? '' }}
                @if(!empty($settings['support_contact']))
                    <span class="block mt-1">Need help? {{ $settings['support_contact'] }}</span>
                @endif
            </footer>
        </div>
    </div>

    {{-- Themed confirmation dialog (replaces the browser's plain confirm) --}}
    <div id="confirmModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" data-confirm-cancel></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
            <div class="mx-auto w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/></svg>
            </div>
            <h3 class="text-lg font-semibold mb-1">Please confirm</h3>
            <p id="confirmMessage" class="text-sm text-gray-500 dark:text-gray-400 mb-5"></p>
            <div class="flex gap-2">
                <button type="button" data-confirm-cancel class="flex-1 px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium">Cancel</button>
                <button type="button" id="confirmOk" class="flex-1 px-4 py-2 rounded-lg text-white text-sm font-medium hover:opacity-90" style="background: var(--color-primary)">Confirm</button>
            </div>
        </div>
    </div>
    <script>
        (function () {
            const modal = document.getElementById('confirmModal');
            const msgEl = document.getElementById('confirmMessage');
            let pending = null;
            const close = () => { modal.classList.add('hidden'); modal.classList.remove('flex'); pending = null; };
            const open = (form, message) => { pending = form; msgEl.textContent = message; modal.classList.remove('hidden'); modal.classList.add('flex'); };
            // Intercept any form that opts in with data-confirm="..."
            document.addEventListener('submit', function (e) {
                const f = e.target;
                if (f && f.hasAttribute && f.hasAttribute('data-confirm')) {
                    e.preventDefault();
                    open(f, f.getAttribute('data-confirm'));
                }
            }, true);
            document.getElementById('confirmOk').addEventListener('click', function () {
                const f = pending; close();
                if (f) { f.submit(); } // native submit() bypasses the capture listener
            });
            modal.querySelectorAll('[data-confirm-cancel]').forEach(el => el.addEventListener('click', close));
            document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
        })();
    </script>

    {{-- Live notification bell: badge polls every 60s; dropdown loads fresh on open --}}
    <script>
        (function () {
            const badge = document.getElementById('notifBadge');
            const list = document.getElementById('notifList');
            const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';
            const feedUrl = '{{ route('notifications.feed') }}';
            const esc = s => (s || '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

            function render(items) {
                if (!list) return;
                if (!items.length) {
                    list.innerHTML = '<p class="px-4 py-8 text-center text-sm text-gray-400">You\'re all caught up 🎉</p>';
                    return;
                }
                list.innerHTML = items.map(it => `
                    <form method="POST" action="${it.read_url}">
                        <input type="hidden" name="_token" value="${csrf}">
                        <button type="submit" class="w-full text-left px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 border-b border-gray-50 dark:border-gray-700/50">
                            <div class="text-sm">${esc(it.message)}</div>
                            <div class="text-xs text-gray-400 mt-0.5">${esc(it.code)} · ${esc(it.ago)}</div>
                        </button>
                    </form>`).join('');
            }

            function updateBadge(count) {
                if (!badge) return;
                if (count > 0) { badge.textContent = count > 9 ? '9+' : count; badge.classList.remove('hidden'); }
                else { badge.classList.add('hidden'); }
            }

            window.__notifLoad = async function () {
                try {
                    const r = await fetch(feedUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!r.ok) return;
                    const d = await r.json();
                    updateBadge(d.count);
                    render(d.items || []);
                } catch (e) { /* ignore */ }
            };

            setInterval(window.__notifLoad, 60000); // refresh badge + list every 60s
        })();
    </script>

    @if(\App\Models\Conversation::enabled())
    {{-- Live message badge: polls unread count for the navbar chat icon --}}
    <script>
        (function () {
            const url = '{{ route('messages.unreadCount') }}';
            const setBadge = (el, count) => {
                if (!el) return;
                if (count > 0) { el.textContent = count > 9 ? '9+' : count; el.classList.remove('hidden'); }
                else { el.classList.add('hidden'); }
            };
            window.__msgUnread = 0;
            window.__refreshMsgBadge = async function () {
                try {
                    const r = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!r.ok) return;
                    const d = await r.json();
                    window.__msgUnread = d.count || 0;
                    setBadge(document.getElementById('msgBadge'), d.count);
                    setBadge(document.getElementById('msgBubbleBadge'), d.count);
                    window.dispatchEvent(new CustomEvent('msg-unread', { detail: d.count }));
                } catch (e) { /* ignore */ }
            };
            window.__refreshMsgBadge();
            setInterval(() => { if (!document.hidden) window.__refreshMsgBadge(); }, 20000);
        })();
    </script>
    @endif

    @if(\App\Models\Conversation::enabled() && ! request()->routeIs('messages.index'))
        @include('messages._widget')
    @endif

    @stack('scripts')
</body>
</html>
