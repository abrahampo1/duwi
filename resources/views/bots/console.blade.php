@extends('layouts.app')

@section('content')
<div class="mb-10">
    <a href="{{ route('bots.show', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&larr; {{ $bot->name }}</a>
</div>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        @if($bot->status === 'running')
            <span class="h-2 w-2 rounded-full bg-black"></span>
        @else
            <span class="h-2 w-2 rounded-full bg-black/15"></span>
        @endif
        <h1 class="font-serif text-2xl">{{ __('Consola') }}</h1>
        <span class="text-[10px] font-mono text-black/25">{{ $bot->entry_file }}</span>
    </div>
    <div class="flex items-center gap-3">
        @if($bot->status === 'running')
            <form method="POST" action="{{ route('bots.restart', $bot) }}" class="inline">
                @csrf
                <button class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">Restart</button>
            </form>
            <form method="POST" action="{{ route('bots.stop', $bot) }}" class="inline">
                @csrf
                <button class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">Stop</button>
            </form>
        @else
            <form method="POST" action="{{ route('bots.start', $bot) }}" class="inline">
                @csrf
                <button class="border border-black px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">Start</button>
            </form>
        @endif
    </div>
</div>

<!-- Console -->
<div class="bg-black/[0.02] border border-black/10 min-h-[500px] max-h-[70vh] overflow-y-auto p-6" id="console-output">
    <pre class="text-xs font-mono text-black/50 whitespace-pre-wrap leading-relaxed" id="console-text">{{ $consoleOutput ?: '# ' . __('Esperando output...') }}</pre>
</div>

<!-- Logs -->
<div class="mt-10">
    <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-4">{{ __('Registro de actividad') }}</p>
    <div class="border-t border-black/10 max-h-52 overflow-y-auto">
        @forelse($logs as $log)
        <div class="flex items-start gap-4 py-2.5 border-b border-black/5">
            @if($log->type === 'stderr')
                <span class="h-1 w-1 rounded-full bg-red-400 mt-1.5 flex-shrink-0"></span>
            @else
                <span class="h-1 w-1 rounded-full bg-black/20 mt-1.5 flex-shrink-0"></span>
            @endif
            <p class="text-[11px] font-mono {{ $log->type === 'stderr' ? 'text-red-500' : 'text-black/40' }} flex-1">{{ $log->content }}</p>
            <span class="text-[10px] text-black/20 flex-shrink-0">{{ $log->created_at->format('H:i:s') }}</span>
        </div>
        @empty
        <p class="py-6 text-xs text-black/20 text-center">{{ __('Sin registros') }}</p>
        @endforelse
    </div>
</div>

<script>
const consoleEl = document.getElementById('console-output');
const consoleText = document.getElementById('console-text');

function scrollToBottom() {
    consoleEl.scrollTop = consoleEl.scrollHeight;
}

scrollToBottom();

setInterval(function() {
    fetch('{{ route("bots.logs", $bot) }}')
        .then(r => r.json())
        .then(data => {
            if (data.output) {
                const wasAtBottom = consoleEl.scrollTop + consoleEl.clientHeight >= consoleEl.scrollHeight - 50;
                consoleText.textContent = data.output;
                if (wasAtBottom) scrollToBottom();
            }
        });
}, 3000);
</script>
@endsection
