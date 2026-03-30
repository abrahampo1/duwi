@extends('layouts.guest')

@section('content')
<h2 class="font-serif text-2xl mb-8">{{ __('Registro') }}</h2>

<form method="POST" action="{{ route('register') }}" class="space-y-5">
    @csrf

    <div>
        <label for="name" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Nombre') }}</label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
            class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0 placeholder:text-black/20">
        @error('name')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">Email</label>
        <input type="email" name="email" id="email" value="{{ old('email') }}" required
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

    <div>
        <label for="password_confirmation" class="block text-[10px] uppercase tracking-[0.15em] text-black/40 mb-2">{{ __('Confirmar contrasena') }}</label>
        <input type="password" name="password_confirmation" id="password_confirmation" required
            class="w-full border-0 border-b border-black/15 bg-transparent px-0 py-2 text-sm focus:border-black focus:ring-0">
    </div>

    <div class="pt-4">
        <button type="submit" class="w-full border border-black py-3 text-xs uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
            {{ __('Crear cuenta') }}
        </button>
    </div>
</form>

<p class="mt-8 text-center text-xs text-black/30">
    <a href="{{ route('login') }}" class="text-black/50 hover:text-black">{{ __('Ya tengo cuenta') }}</a>
</p>
@endsection
