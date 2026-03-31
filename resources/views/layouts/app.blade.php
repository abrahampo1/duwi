<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['fa', 'he']) ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'DUWI' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    @if(app()->getLocale() === 'fa')
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500&display=swap" rel="stylesheet">
    @elseif(app()->getLocale() === 'he')
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500&display=swap" rel="stylesheet">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-white text-black antialiased">
    <div class="min-h-full">
        @auth
        <nav class="border-b border-black/10" x-data="{ open: false }">
            <div class="mx-auto max-w-6xl px-6">
                <div class="flex h-14 items-center justify-between">
                    <div class="flex items-center gap-10">
                        <a href="{{ route('dashboard') }}" class="font-serif text-lg tracking-tight">DUWI</a>
                        <div class="hidden sm:flex items-center gap-6">
                            <a href="{{ route('dashboard') }}" class="text-xs uppercase tracking-[0.15em] {{ request()->routeIs('dashboard') ? 'text-black' : 'text-black/40 hover:text-black' }}">
                                {{ __('Dashboard') }}
                            </a>
                            <a href="{{ route('bots.index') }}" class="text-xs uppercase tracking-[0.15em] {{ request()->routeIs('bots.*') ? 'text-black' : 'text-black/40 hover:text-black' }}">
                                {{ __('Bots') }}
                            </a>
                            @if(Auth::user()->is_admin)
                            <a href="{{ route('admin.dashboard') }}" class="text-xs uppercase tracking-[0.15em] {{ request()->routeIs('admin.*') ? 'text-black' : 'text-black/40 hover:text-black' }}">
                                Admin
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="hidden sm:flex items-center gap-5">
                        <x-language-switcher />
                        <a href="{{ route('account.settings') }}" class="text-xs {{ request()->routeIs('account.*') ? 'text-black' : 'text-black/40 hover:text-black' }}">{{ Auth::user()->name }}</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs uppercase tracking-[0.15em] text-black/40 hover:text-black">
                                {{ __('Salir') }}
                            </button>
                        </form>
                    </div>
                    <button @click="open = !open" class="sm:hidden text-black/40 hover:text-black">
                        <svg x-show="!open" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 9h16.5m-16.5 6.75h16.5"/></svg>
                        <svg x-show="open" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <!-- Mobile menu -->
            <div x-show="open" x-cloak x-transition class="sm:hidden border-t border-black/10">
                <div class="mx-auto max-w-6xl px-6 py-4 space-y-3">
                    <a href="{{ route('dashboard') }}" class="block text-xs uppercase tracking-[0.15em] {{ request()->routeIs('dashboard') ? 'text-black' : 'text-black/40' }}">{{ __('Dashboard') }}</a>
                    <a href="{{ route('bots.index') }}" class="block text-xs uppercase tracking-[0.15em] {{ request()->routeIs('bots.*') ? 'text-black' : 'text-black/40' }}">{{ __('Bots') }}</a>
                    @if(Auth::user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}" class="block text-xs uppercase tracking-[0.15em] {{ request()->routeIs('admin.*') ? 'text-black' : 'text-black/40' }}">Admin</a>
                    @endif
                    <div class="border-t border-black/5 pt-3 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <a href="{{ route('account.settings') }}" class="text-xs {{ request()->routeIs('account.*') ? 'text-black' : 'text-black/40' }}">{{ Auth::user()->name }}</a>
                            <x-language-switcher />
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs uppercase tracking-[0.15em] text-black/40 hover:text-black">{{ __('Salir') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
        @endauth

        @if(session('success'))
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-6">
            <p class="text-xs text-black/60 border-s-2 border-black ps-3">{{ session('success') }}</p>
        </div>
        @endif

        @if(session('error'))
        <div class="mx-auto max-w-6xl px-4 sm:px-6 pt-6">
            <p class="text-xs text-red-600 border-s-2 border-red-600 ps-3">{{ session('error') }}</p>
        </div>
        @endif

        <main class="mx-auto max-w-6xl px-4 sm:px-6 py-8 sm:py-12">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
    @livewireScripts
</body>
</html>
