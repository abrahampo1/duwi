@extends('layouts.app')

@section('content')
<div class="mb-16">
    <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">{{ __('Dashboard') }}</p>
    <h1 class="font-serif text-3xl">{{ __('Bienvenido, :name', ['name' => Auth::user()->name]) }}</h1>
</div>

<!-- Stats -->
<div class="grid grid-cols-4 gap-px bg-black/10 border border-black/10 mb-16">
    <div class="bg-white p-6 text-center">
        <p class="font-serif text-3xl">{{ $stats['total'] }}</p>
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Total') }}</p>
    </div>
    <div class="bg-white p-6 text-center">
        <p class="font-serif text-3xl">{{ $stats['running'] }}</p>
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Online') }}</p>
    </div>
    <div class="bg-white p-6 text-center">
        <p class="font-serif text-3xl">{{ $stats['stopped'] }}</p>
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Offline') }}</p>
    </div>
    <div class="bg-white p-6 text-center">
        <p class="font-serif text-3xl">{{ $stats['error'] }}</p>
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Error') }}</p>
    </div>
</div>

<!-- Bot List -->
<div class="flex items-center justify-between mb-8">
    <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Tus bots') }}</p>
    <a href="{{ route('bots.create') }}" class="border border-black px-4 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
        {{ __('Nuevo bot') }}
    </a>
</div>

@if($bots->isEmpty())
<div class="border border-dashed border-black/10 py-20 text-center">
    <p class="font-serif text-xl text-black/30 mb-2">{{ __('Sin bots') }}</p>
    <p class="text-xs text-black/25 mb-6">{{ __('Crea tu primer bot de Discord para empezar') }}</p>
    <a href="{{ route('bots.create') }}" class="border border-black px-5 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
        {{ __('Crear bot') }}
    </a>
</div>
@else
<div class="border-t border-black/10">
    @foreach($bots as $bot)
    <a href="{{ route('bots.show', $bot) }}" class="flex items-center justify-between py-5 border-b border-black/10 group">
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-3">
                @if($bot->status === 'running')
                    <span class="h-1.5 w-1.5 rounded-full bg-black"></span>
                @elseif($bot->status === 'error')
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @else
                    <span class="h-1.5 w-1.5 rounded-full bg-black/15"></span>
                @endif
                <span class="text-sm group-hover:text-black/60 transition-colors">{{ $bot->name }}</span>
            </div>
            @if($bot->description)
                <span class="text-xs text-black/25 hidden sm:inline">{{ Str::limit($bot->description, 50) }}</span>
            @endif
        </div>
        <div class="flex items-center gap-6">
            <span class="text-[10px] uppercase tracking-[0.15em] text-black/25">{{ $bot->deploy_method === 'github' ? 'GitHub' : 'ZIP' }}</span>
            <span class="text-[10px] text-black/25 font-mono">{{ $bot->entry_file }}</span>
            <span class="text-xs text-black/20">&rarr;</span>
        </div>
    </a>
    @endforeach
</div>
@endif
@endsection
