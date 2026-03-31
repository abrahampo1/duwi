<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ in_array(app()->getLocale(), ['fa', 'he']) ? 'rtl' : 'ltr' }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DUWI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    @if(app()->getLocale() === 'fa')
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500&display=swap" rel="stylesheet">
    @elseif(app()->getLocale() === 'he')
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;400;500&display=swap" rel="stylesheet">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white text-black antialiased">

    <!-- Nav -->
    <nav class="border-b border-black/10">
        <div class="mx-auto max-w-6xl px-6">
            <div class="flex h-14 items-center justify-between">
                <span class="font-serif text-lg tracking-tight">DUWI</span>
                <div class="flex items-center gap-5">
                    <x-language-switcher />
                    <a href="{{ route('login') }}" class="text-xs uppercase tracking-[0.15em] text-black/40 hover:text-black">{{ __('Entrar') }}</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <div class="mx-auto max-w-6xl px-6">
        <div class="py-32 sm:py-48 max-w-2xl">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">Don't be upset with internet</p>
            <h1 class="font-serif text-5xl sm:text-7xl leading-[0.9] tracking-tight">
                {{ __('Hostea tus bots de Discord') }}
            </h1>
            <p class="mt-8 text-sm leading-relaxed text-black/50 max-w-md font-light">
                {{ __('Despliega y gestiona tus bots en segundos. Clona desde GitHub o sube un ZIP. Sin complicaciones.') }}
            </p>
            <div class="mt-10">
                <a href="{{ route('login') }}" class="border border-black px-6 py-3 text-xs uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
                    {{ __('Entrar') }}
                </a>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-black/10"></div>

        <!-- Features -->
        <div class="py-24 grid grid-cols-1 sm:grid-cols-3 gap-16">
            <div>
                <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">01</p>
                <h3 class="font-serif text-xl mb-3">GitHub</h3>
                <p class="text-sm text-black/40 font-light leading-relaxed">{{ __('Clona directamente desde cualquier repositorio. Publico o privado con deploy keys.') }}</p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">02</p>
                <h3 class="font-serif text-xl mb-3">ZIP</h3>
                <p class="text-sm text-black/40 font-light leading-relaxed">{{ __('Sube tu proyecto comprimido. Se extrae y configura automaticamente.') }}</p>
            </div>
            <div>
                <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">03</p>
                <h3 class="font-serif text-xl mb-3">{{ __('Control') }}</h3>
                <p class="text-sm text-black/40 font-light leading-relaxed">{{ __('Inicia, detiene y reinicia. Consola en vivo. Variables de entorno seguras.') }}</p>
            </div>
        </div>

        <!-- Divider -->
        <div class="border-t border-black/10"></div>

        <!-- Footer -->
        <div class="py-8 flex items-center justify-between">
            <p class="text-[10px] uppercase tracking-[0.2em] text-black/20">DUWI</p>
            <p class="text-[10px] text-black/20">{{ __('Discord Bot Hosting') }}</p>
        </div>
    </div>
</body>
</html>
