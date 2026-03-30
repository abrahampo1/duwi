<div>
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
                <button wire:click="restartBot" wire:loading.attr="disabled" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="restartBot">Restart</span>
                    <span wire:loading wire:target="restartBot">...</span>
                </button>
                <button wire:click="stopBot" wire:loading.attr="disabled" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="stopBot">Stop</span>
                    <span wire:loading wire:target="stopBot">...</span>
                </button>
            @elseif($bot->status !== 'deploying')
                <button wire:click="startBot" wire:loading.attr="disabled" class="border border-black px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="startBot">Start</span>
                    <span wire:loading wire:target="startBot">...</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Console -->
    <div wire:poll.2s="refreshConsole"
         class="bg-black/[0.02] border border-black/10 min-h-[500px] max-h-[70vh] overflow-y-auto p-6"
         x-data="{ atBottom: true }"
         x-on:scroll="atBottom = ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 50)"
         x-effect="if(atBottom) $nextTick(() => $el.scrollTop = $el.scrollHeight)">
        <pre class="text-xs font-mono text-black/50 whitespace-pre-wrap leading-relaxed">{{ $consoleOutput ?: '# ' . __('Esperando output...') }}</pre>
    </div>

    <!-- Logs -->
    <div class="mt-10">
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-4">{{ __('Registro de actividad') }}</p>
        <div class="border-t border-black/10 max-h-52 overflow-y-auto">
            @forelse($logs as $log)
            <div class="flex items-start gap-4 py-2.5 border-b border-black/5" wire:key="log-{{ $log->id }}">
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
</div>
