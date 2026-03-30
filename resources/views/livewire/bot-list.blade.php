<div wire:poll.10s="refreshBots">
    <div class="flex items-end justify-between mb-12">
        <div>
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">{{ __('Bots') }}</p>
            <h1 class="font-serif text-3xl">{{ __('Mis bots') }}</h1>
        </div>
        <a href="{{ route('bots.create') }}" class="border border-black px-4 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
            {{ __('Nuevo bot') }}
        </a>
    </div>

    @if($notification)
    <div class="mb-6" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition.opacity>
        <p class="text-xs {{ $notificationType === 'error' ? 'text-red-600 border-s-2 border-red-600' : 'text-black/60 border-s-2 border-black' }} ps-3">{{ $notification }}</p>
    </div>
    @endif

    @if($bots->isEmpty())
    <div class="border border-dashed border-black/10 py-20 text-center">
        <p class="font-serif text-xl text-black/30 mb-2">{{ __('Sin bots') }}</p>
        <p class="text-xs text-black/25 mb-6">{{ __('Clona un repositorio de GitHub o sube un ZIP') }}</p>
        <a href="{{ route('bots.create') }}" class="border border-black px-5 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
            {{ __('Crear bot') }}
        </a>
    </div>
    @else
    <div class="border-t border-black/10">
        @foreach($bots as $bot)
        <div class="flex items-center justify-between py-5 border-b border-black/10" wire:key="bot-{{ $bot->id }}">
            <div class="flex items-center gap-5">
                @if($bot->status === 'running')
                    <span class="h-1.5 w-1.5 rounded-full bg-black"></span>
                @elseif($bot->status === 'error')
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @elseif($bot->status === 'deploying')
                    <span class="h-1.5 w-1.5 rounded-full bg-black/40 animate-pulse"></span>
                @else
                    <span class="h-1.5 w-1.5 rounded-full bg-black/15"></span>
                @endif
                <a href="{{ route('bots.show', $bot) }}" class="text-sm hover:text-black/50 transition-colors">{{ $bot->name }}</a>
                <span class="text-[10px] uppercase tracking-[0.15em] text-black/20">{{ $bot->deploy_method }}</span>
                <span class="text-[10px] text-black/20 font-mono">{{ $bot->entry_file }}</span>
                @if($bot->latestDeployment)
                    <span class="text-[10px] font-mono
                        {{ match($bot->latestDeployment->status) {
                            'success' => 'text-green-600',
                            'failed', 'rolled_back' => 'text-red-500',
                            'running', 'verifying', 'pending' => 'text-black/40',
                            default => 'text-black/20',
                        } }}">{{ $bot->latestDeployment->status }}{{ $bot->latestDeployment->shortCommit() ? ' · ' . $bot->latestDeployment->shortCommit() : '' }}</span>
                @endif
            </div>

            <div class="flex items-center gap-3">
                @if($bot->status === 'running')
                    <button wire:click="stopBot({{ $bot->id }})" wire:loading.attr="disabled" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black disabled:opacity-30">
                        <span wire:loading.remove wire:target="stopBot({{ $bot->id }})">Stop</span>
                        <span wire:loading wire:target="stopBot({{ $bot->id }})">...</span>
                    </button>
                    <button wire:click="restartBot({{ $bot->id }})" wire:loading.attr="disabled" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black disabled:opacity-30">
                        <span wire:loading.remove wire:target="restartBot({{ $bot->id }})">Restart</span>
                        <span wire:loading wire:target="restartBot({{ $bot->id }})">...</span>
                    </button>
                @elseif($bot->status !== 'deploying')
                    <button wire:click="startBot({{ $bot->id }})" wire:loading.attr="disabled" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black disabled:opacity-30">
                        <span wire:loading.remove wire:target="startBot({{ $bot->id }})">Start</span>
                        <span wire:loading wire:target="startBot({{ $bot->id }})">...</span>
                    </button>
                @endif
                <a href="{{ route('bots.show', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&rarr;</a>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
