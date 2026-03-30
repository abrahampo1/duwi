@extends('layouts.guest')

@section('content')
<h2 class="font-serif text-2xl mb-8">{{ __('Entrar') }}</h2>

<form method="POST" action="{{ route('login') }}" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
            class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20"
            placeholder="tu@email.com">
        @error('email')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Contrasena') }}</label>
        <input type="password" name="password" id="password" required
            class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
        @error('password')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex items-center pt-1">
        <input type="checkbox" name="remember" id="remember" class="h-3 w-3 border-black/20 text-black focus:ring-0 rounded-none">
        <label for="remember" class="ms-2 text-xs text-black/40">{{ __('Recordarme') }}</label>
    </div>

    <div class="pt-4">
        <button type="submit" class="w-full border border-black py-3 text-xs uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
            {{ __('Entrar') }}
        </button>
    </div>
</form>

<p class="mt-8 text-center text-xs text-black/20">
    {{ __('Contacta al administrador para crear una cuenta') }}
</p>
@endsection
