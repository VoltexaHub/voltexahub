<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link href="https://fonts.bunny.net/css?family=fraunces:400,500,600,700|inter-tight:400,500,600|jetbrains-mono:400,500&display=swap" rel="stylesheet" />
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
<body class="font-sans">
    <div class="min-h-screen flex flex-col">
        <header class="sticky top-0 z-20 border-b vx-hairline bg-[color:var(--bg)]/85 backdrop-blur supports-[backdrop-filter]:bg-[color:var(--bg)]/70">
            <div class="max-w-5xl mx-auto px-5 h-16 flex items-center gap-5">
                <a href="{{ route('home') }}" class="vx-display font-semibold text-[1.35rem] leading-none tracking-tight shrink-0">
                    {{ $activeTheme['name'] ?? 'VoltexaHub' }}<span class="text-[color:var(--accent)]">.</span>
                </a>
                <span class="hidden md:inline-block w-px h-6 bg-[color:var(--border)]"></span>
                <form method="GET" action="{{ route('search') }}" class="hidden md:block flex-1 max-w-md">
                    <input name="q" type="search" value="{{ request('q') }}" placeholder="Search the hub…" class="vx-input text-sm" />
                </form>
                <nav class="flex items-center gap-5 text-[0.875rem] ml-auto">
                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="vx-meta hover:vx-heading">Admin</a>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="relative vx-muted hover:vx-heading" title="Notifications">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 10-12 0v3a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0" />
                            </svg>
                            @if(($unreadNotifications ?? 0) > 0)
                                <span class="absolute -top-1 -right-1.5 min-w-[1.05rem] h-[1.05rem] px-1 text-[10px] font-mono font-medium bg-[color:var(--accent)] text-white rounded-full flex items-center justify-center">
                                    {{ $unreadNotifications }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('messages.index') }}" class="relative vx-muted hover:vx-heading">
                            Messages
                            @if(($unreadMessages ?? 0) > 0)
                                <span class="absolute -top-1 -right-2.5 min-w-[1.05rem] h-[1.05rem] px-1 text-[10px] font-mono font-medium bg-[color:var(--accent)] text-white rounded-full flex items-center justify-center">
                                    {{ $unreadMessages }}
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('dashboard') }}" class="vx-muted hover:vx-heading">{{ auth()->user()->name }}</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="vx-subtle hover:vx-heading">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="vx-muted hover:vx-heading">Log in</a>
                        <a href="{{ route('register') }}" class="vx-btn-primary text-xs py-1.5 px-3">Register</a>
                    @endauth
                    <button id="vx-theme-toggle" type="button" aria-label="Toggle dark mode"
                            class="p-1.5 rounded-md vx-muted hover:vx-heading hover:bg-[color:var(--surface-mute)] transition">
                        <svg class="w-4.5 h-4.5 hidden dark:block" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1.5m0 15V21m9-9h-1.5M4.5 12H3m15.364-6.364l-1.06 1.06M6.697 17.303l-1.06 1.06m0-13.728l1.06 1.06M17.303 17.303l1.06 1.06M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg class="w-4.5 h-4.5 dark:hidden" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                </nav>
            </div>
        </header>

        @if(session('flash.success'))
            <div class="vx-flash vx-flash-success">
                <div class="max-w-5xl mx-auto px-5">{{ session('flash.success') }}</div>
            </div>
        @endif
        @if(session('flash.error'))
            <div class="vx-flash vx-flash-error">
                <div class="max-w-5xl mx-auto px-5">{{ session('flash.error') }}</div>
            </div>
        @endif

        <main class="flex-1 max-w-5xl w-full mx-auto px-5 py-10">
            @hook('before_content')
            @yield('content')
            @hook('after_content')
        </main>

        <footer class="border-t vx-hairline py-6">
            <div class="max-w-5xl mx-auto px-5 flex items-center justify-between">
                <p class="vx-meta">
                    <span class="text-[color:var(--accent)]">/</span>
                    Powered by <a href="https://github.com/VoltexaHub/voltexahub" class="hover:text-[color:var(--accent)]">VoltexaHub</a>
                </p>
                <p class="vx-meta">Theme · {{ $activeTheme['name'] ?? 'Default' }}</p>
            </div>
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
