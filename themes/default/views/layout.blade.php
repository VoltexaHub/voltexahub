<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css'])
    @hook('head')
</head>
<body class="font-sans antialiased bg-gray-100 text-gray-800">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
                <a href="{{ route('home') }}" class="text-lg font-semibold text-gray-900">
                    {{ $activeTheme['name'] ?? 'VoltexaHub' }}
                </a>
                <nav class="flex items-center gap-4 text-sm">
                    @auth
                        @if(auth()->user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Admin</a>
                        @endif
                        <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-900">{{ auth()->user()->name }}</a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-500 hover:text-gray-900">Log out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-700 hover:text-gray-900">Log in</a>
                        <a href="{{ route('register') }}" class="text-gray-700 hover:text-gray-900">Register</a>
                    @endauth
                </nav>
            </div>
        </header>

        @if(session('flash.success'))
            <div class="bg-green-100 border-b border-green-200 text-green-800 px-4 py-2 text-sm">
                <div class="max-w-6xl mx-auto">{{ session('flash.success') }}</div>
            </div>
        @endif
        @if(session('flash.error'))
            <div class="bg-red-100 border-b border-red-200 text-red-800 px-4 py-2 text-sm">
                <div class="max-w-6xl mx-auto">{{ session('flash.error') }}</div>
            </div>
        @endif

        <main class="flex-1 max-w-6xl w-full mx-auto px-4 py-6">
            @hook('before_content')
            @yield('content')
            @hook('after_content')
        </main>

        <footer class="border-t border-gray-200 bg-white py-4 text-center text-xs text-gray-500">
            Powered by <a href="https://github.com/joogiebear/VoltexaHub" class="hover:underline">VoltexaHub</a>
            · Theme: {{ $activeTheme['name'] ?? 'Default' }}
        </footer>
    </div>
    @stack('scripts')
</body>
</html>
