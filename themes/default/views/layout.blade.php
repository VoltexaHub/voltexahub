<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vx-user" content="{{ auth()->check() ? '1' : '0' }}">
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
    @stack('head')
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
                <nav class="flex items-center gap-4 text-[0.875rem] ml-auto">
                    @auth
                        <a href="{{ route('messages.index') }}" class="relative vx-muted hover:vx-heading" title="Messages" aria-label="Messages">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            @if(($unreadMessages ?? 0) > 0)
                                <span class="absolute -top-1 -right-1.5 min-w-[1.05rem] h-[1.05rem] px-1 text-[10px] font-mono font-medium bg-[color:var(--accent)] text-white rounded-full flex items-center justify-center">{{ $unreadMessages }}</span>
                            @endif
                        </a>
                        <a href="{{ route('notifications.index') }}" class="relative vx-muted hover:vx-heading" title="Notifications" aria-label="Notifications">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 10-12 0v3a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/>
                            </svg>
                            @if(($unreadNotifications ?? 0) > 0)
                                <span class="absolute -top-1 -right-1.5 min-w-[1.05rem] h-[1.05rem] px-1 text-[10px] font-mono font-medium bg-[color:var(--accent)] text-white rounded-full flex items-center justify-center">{{ $unreadNotifications }}</span>
                            @endif
                        </a>

                        <details class="relative vx-user-menu">
                            <summary class="list-none cursor-pointer flex items-center gap-2 rounded-md py-1 pr-1.5 pl-1 hover:bg-[color:var(--surface-mute)] transition">
                                <img src="{{ auth()->user()->avatar_url }}" alt="" class="w-7 h-7 rounded-full border vx-hairline" />
                                <span class="vx-muted hidden sm:inline">{{ auth()->user()->name }}</span>
                                <svg class="w-3 h-3 vx-subtle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
                            </summary>
                            <div class="absolute right-0 mt-2 w-56 vx-card shadow-sm overflow-hidden py-1 z-30">
                                <a href="{{ route('users.show', auth()->user()) }}" class="block px-4 py-2 text-sm hover:bg-[color:var(--surface-mute)]">
                                    <span class="vx-heading block">{{ auth()->user()->name }}</span>
                                    <span class="vx-meta normal-case tracking-normal">View profile</span>
                                </a>
                                <div class="border-t vx-hairline my-1"></div>
                                <a href="{{ route('bookmarks.index') }}" class="block px-4 py-1.5 text-sm vx-muted hover:bg-[color:var(--surface-mute)] hover:vx-heading">Bookmarks</a>
                                <a href="{{ route('blocks.index') }}" class="block px-4 py-1.5 text-sm vx-muted hover:bg-[color:var(--surface-mute)] hover:vx-heading">Blocked users</a>
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-1.5 text-sm vx-muted hover:bg-[color:var(--surface-mute)] hover:vx-heading">Settings</a>
                                @if(auth()->user()->is_admin)
                                    <div class="border-t vx-hairline my-1"></div>
                                    <a href="{{ route('admin.dashboard') }}" class="block px-4 py-1.5 text-sm hover:bg-[color:var(--surface-mute)]" style="color:var(--accent)">Admin panel</a>
                                @endif
                                <div class="border-t vx-hairline my-1"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-1.5 text-sm vx-muted hover:bg-[color:var(--surface-mute)] hover:vx-heading">Log out</button>
                                </form>
                            </div>
                        </details>
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

        @if($announcement ?? null)
            @php
                $tone = $announcement['tone'] ?? 'info';
                $toneStyles = match($tone) {
                    'warning' => 'background:#fef3c7;color:#78350f;border-color:#fcd34d;',
                    'notice'  => 'background:var(--surface-mute);color:var(--text);border-color:var(--border);',
                    default   => 'background:color-mix(in oklch, var(--accent) 10%, transparent);color:var(--accent-hover);border-color:color-mix(in oklch, var(--accent) 30%, transparent);',
                };
            @endphp
            <div class="vx-announcement border-b text-sm" style="{{ $toneStyles }}" data-announcement-version="{{ $announcement['version'] }}">
                <div class="max-w-5xl mx-auto px-5 py-2.5 flex items-start gap-4">
                    <span class="vx-meta shrink-0 pt-0.5" style="color:inherit;opacity:0.7">{{ strtoupper($tone) }}</span>
                    <p class="flex-1 leading-relaxed">{{ $announcement['message'] }}</p>
                    <button type="button" class="vx-announcement-dismiss shrink-0 text-xl leading-none opacity-70 hover:opacity-100" title="Dismiss" aria-label="Dismiss announcement">×</button>
                </div>
            </div>
        @endif

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
            if (btn) {
                btn.addEventListener('click', function () {
                    var root = document.documentElement;
                    var next = root.classList.contains('dark') ? 'light' : 'dark';
                    root.classList.toggle('dark', next === 'dark');
                    try { localStorage.setItem('theme', next); } catch (e) {}
                });
            }

            // Close the user menu when clicking outside it.
            document.addEventListener('click', function (e) {
                document.querySelectorAll('details.vx-user-menu[open]').forEach(function (d) {
                    if (!d.contains(e.target)) d.removeAttribute('open');
                });
            });

            // Announcement dismiss, keyed by version so new announcements re-appear.
            var bar = document.querySelector('.vx-announcement');
            if (bar) {
                var v = bar.getAttribute('data-announcement-version') || '0';
                var key = 'vx-announcement-dismissed-' + v;
                try { if (localStorage.getItem(key)) bar.remove(); } catch (e) {}
                var close = bar.querySelector('.vx-announcement-dismiss');
                if (close) close.addEventListener('click', function () {
                    try { localStorage.setItem(key, '1'); } catch (e) {}
                    bar.remove();
                });
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
