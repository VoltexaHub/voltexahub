<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <script>
        (function () {
            try {
                var m = localStorage.getItem('theme');
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (m === 'dark' || (m !== 'light' && prefersDark)) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    @vite(['resources/css/app.css'])
    @hook('head')
</head>
<body class="font-sans bg-slate-50 dark:bg-slate-950">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white dark:bg-slate-900 border-b border-slate-200/70 dark:border-slate-800/70 sticky top-0 z-20 backdrop-blur supports-[backdrop-filter]:bg-white/80 supports-[backdrop-filter]:dark:bg-slate-900/80">
            <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="text-lg font-semibold vx-heading shrink-0">
                    {{ $activeTheme['name'] ?? 'VoltexaHub' }}
                </a>
                <form method="GET" action="{{ route('search') }}" class="hidden md:block flex-1 max-w-sm">
                    <input name="q" type="search" value="{{ request('q') }}" placeholder="Search..." class="vx-input text-sm" />
                </form>
                <nav class="flex items-center gap-4 text-sm">
                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">Admin</a>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="relative text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100" title="Notifications">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 10-12 0v3a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0" />
                            </svg>
                            @if(($unreadNotifications ?? 0) > 0)
                                <span class="absolute -top-1.5 -right-1.5 min-w-[1.1rem] h-[1.1rem] px-1 text-[10px] font-semibold bg-red-500 text-white rounded-full flex items-center justify-center ring-2 ring-white dark:ring-slate-900">
                                    {{ $unreadNotifications }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('messages.index') }}" class="relative text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100">
                            Messages
                            @if(($unreadMessages ?? 0) > 0)
                                <span class="absolute -top-1.5 -right-2 min-w-[1.1rem] h-[1.1rem] px-1 text-[10px] font-semibold bg-red-500 text-white rounded-full flex items-center justify-center ring-2 ring-white dark:ring-slate-900">
                                    {{ $unreadMessages }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('dashboard') }}" class="text-slate-700 dark:text-slate-200 hover:vx-heading">{{ auth()->user()->name }}</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="vx-muted hover:vx-heading">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-slate-700 dark:text-slate-200 hover:vx-heading">Log in</a>
                        <a href="{{ route('register') }}" class="text-slate-700 dark:text-slate-200 hover:vx-heading">Register</a>
                    @endauth
                    <button id="vx-theme-toggle" type="button" aria-label="Toggle dark mode"
                            class="p-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v2m0 14v2m9-9h-2M5 12H3m15.364-6.364l-1.414 1.414M6.05 17.95l-1.414 1.414m0-13.728l1.414 1.414M17.95 17.95l1.414 1.414M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                </nav>
            </div>
        </header>

        @if(session('flash.success'))
            <div class="bg-emerald-50 dark:bg-emerald-950/40 border-b border-emerald-200/70 dark:border-emerald-900/60 text-emerald-800 dark:text-emerald-200 px-4 py-2 text-sm">
                <div class="max-w-6xl mx-auto">{{ session('flash.success') }}</div>
            </div>
        @endif
        @if(session('flash.error'))
            <div class="bg-red-50 dark:bg-red-950/40 border-b border-red-200/70 dark:border-red-900/60 text-red-800 dark:text-red-200 px-4 py-2 text-sm">
                <div class="max-w-6xl mx-auto">{{ session('flash.error') }}</div>
            </div>
        @endif

        <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-8">
            @hook('before_content')
            @yield('content')
            @hook('after_content')
        </main>

        <footer class="border-t border-slate-200/70 dark:border-slate-800/70 bg-white/50 dark:bg-slate-900/50 py-4 text-center text-xs vx-muted">
            Powered by <a href="https://github.com/VoltexaHub/VoltexaHub" class="hover:underline">VoltexaHub</a>
            · Theme: {{ $activeTheme['name'] ?? 'Default' }}
        </footer>
    </div>

    <script>
        (function () {
            var btn = document.getElementById('vx-theme-toggle');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var root = document.documentElement;
                var next = root.classList.contains('dark') ? 'light' : 'dark';
                root.classList.toggle('dark', next === 'dark');
                try { localStorage.setItem('theme', next); } catch (e) {}
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
