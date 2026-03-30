@extends('layouts.app')

@section('content')
<div class="mb-10">
    <a href="{{ route('bots.index') }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&larr; {{ __('Bots') }}</a>
</div>

<!-- Header -->
<div class="flex items-start justify-between mb-12">
    <div>
        <div class="flex items-center gap-4">
            @if($bot->status === 'running')
                <span class="h-2 w-2 rounded-full bg-black"></span>
            @elseif($bot->status === 'error')
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
            @elseif($bot->status === 'deploying')
                <span class="h-2 w-2 rounded-full bg-black/40 animate-pulse"></span>
            @else
                <span class="h-2 w-2 rounded-full bg-black/15"></span>
            @endif
            <h1 class="font-serif text-3xl">{{ $bot->name }}</h1>
        </div>
        @if($bot->description)
            <p class="mt-2 text-sm text-black/40 font-light ms-6">{{ $bot->description }}</p>
        @endif
    </div>

    <div class="flex items-center gap-3">
        @if($bot->status === 'running')
            <form method="POST" action="{{ route('bots.stop', $bot) }}" class="inline">
                @csrf
                <button class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">Stop</button>
            </form>
            <form method="POST" action="{{ route('bots.restart', $bot) }}" class="inline">
                @csrf
                <button class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">Restart</button>
            </form>
        @else
            <form method="POST" action="{{ route('bots.start', $bot) }}" class="inline">
                @csrf
                <button class="border border-black px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">Start</button>
            </form>
        @endif

        @if($bot->deploy_method === 'github')
            <form method="POST" action="{{ route('bots.redeploy', $bot) }}" class="inline">
                @csrf
                <button class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">Pull</button>
            </form>
            @if($bot->deploy_key)
            <form method="POST" action="{{ route('bots.configure-git', $bot) }}" class="inline">
                @csrf
                <button class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">Git SSH</button>
            </form>
            @endif
        @endif

        <form method="POST" action="{{ route('bots.install-deps', $bot) }}" class="inline">
            @csrf
            <button class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">npm i</button>
        </form>

        <a href="{{ route('bots.edit', $bot) }}" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">{{ __('Editar') }}</a>

        <form method="POST" action="{{ route('bots.destroy', $bot) }}" class="inline" onsubmit="return confirm('{{ __('Eliminar este bot?') }}')">
            @csrf
            @method('DELETE')
            <button class="border border-red-200 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-red-400 hover:border-red-500 hover:text-red-600 transition-colors">{{ __('Eliminar') }}</button>
        </form>
    </div>
</div>

<!-- Info -->
<div class="grid grid-cols-3 gap-px bg-black/10 border border-black/10 mb-12">
    <div class="bg-white p-5">
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mb-1">{{ __('Deploy') }}</p>
        <p class="text-sm">{{ $bot->deploy_method === 'github' ? 'GitHub' : 'ZIP' }}</p>
        @if($bot->repo_url)
            <p class="text-[10px] text-black/30 font-mono mt-1 truncate">{{ $bot->repo_url }}</p>
        @endif
        @if($bot->deploy_method === 'github')
            <p class="text-[10px] mt-1 {{ $bot->deploy_key ? 'text-black/40' : 'text-black/20' }}">Key: {{ $bot->deploy_key ? __('configurada') : __('no') }}</p>
        @endif
    </div>
    <div class="bg-white p-5">
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mb-1">{{ __('Entrada') }}</p>
        <p class="text-sm font-mono">{{ $bot->entry_file }}</p>
    </div>
    <div class="bg-white p-5">
        <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mb-1">{{ __('Ultimo inicio') }}</p>
        <p class="text-sm">{{ $bot->last_started_at ? $bot->last_started_at->format('d/m/Y H:i') : '---' }}</p>
        @if($bot->pid)
            <p class="text-[10px] text-black/25 font-mono mt-1">PID {{ $bot->pid }}</p>
        @endif
    </div>
</div>

<!-- Console -->
<div class="mb-12">
    <div class="flex items-center justify-between mb-4">
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Consola') }}</p>
        <a href="{{ route('bots.console', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&rarr; {{ __('Completa') }}</a>
    </div>
    <div class="bg-black/[0.02] border border-black/10 p-5 max-h-64 overflow-y-auto" id="console-output">
        <pre class="text-xs font-mono text-black/50 whitespace-pre-wrap leading-relaxed">{{ $consoleOutput ?: __('Sin output. Inicia el bot.') }}</pre>
    </div>
</div>

<!-- Logs -->
<div>
    <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-4">{{ __('Registro de actividad') }}</p>
    <div class="border-t border-black/10 max-h-52 overflow-y-auto">
        @forelse($logs as $log)
        <div class="flex items-start gap-4 py-3 border-b border-black/5">
            @if($log->type === 'stderr')
                <span class="h-1 w-1 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></span>
            @else
                <span class="h-1 w-1 rounded-full bg-black/20 mt-1.5 flex-shrink-0"></span>
            @endif
            <p class="text-xs {{ $log->type === 'stderr' ? 'text-red-500' : 'text-black/40' }} flex-1">{{ $log->content }}</p>
            <span class="text-[10px] text-black/20 flex-shrink-0">{{ $log->created_at->format('H:i:s') }}</span>
        </div>
        @empty
        <p class="py-6 text-xs text-black/20 text-center">{{ __('Sin registros') }}</p>
        @endforelse
    </div>
</div>

@if($bot->isRunning())
<script>
setInterval(function() {
    fetch('{{ route("bots.logs", $bot) }}')
        .then(r => r.json())
        .then(data => {
            if (data.output) {
                document.querySelector('#console-output pre').textContent = data.output;
            }
        });
}, 5000);
</script>
@endif
@endsection
