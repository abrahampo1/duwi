<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}" class="h-full">
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
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-black antialiased">
    <div class="flex min-h-full flex-col justify-center px-6 py-16">
        <div class="mx-auto w-full max-w-sm">
            <div class="text-center mb-12">
                <a href="/" class="font-serif text-2xl tracking-tight">DUWI</a>
                <p class="mt-1 text-[10px] uppercase tracking-[0.2em] text-black/30">Don't be upset with internet</p>
                <div class="mt-3 flex justify-center">
                    <x-language-switcher />
                </div>
            </div>
            @yield('content')
        </div>
    </div>
</body>
</html>
