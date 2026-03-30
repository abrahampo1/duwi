<div>
    <div class="mb-10">
        <a href="{{ route('bots.index') }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&larr; {{ __('Bots') }}</a>
    </div>

    @if($notification)
    <div class="mb-6" x-data="{ show: true }" x-init="setTimeout(() => show = false, 4000)" x-show="show" x-transition.opacity>
        <p class="text-xs {{ $notificationType === 'error' ? 'text-red-600 border-s-2 border-red-600' : 'text-black/60 border-s-2 border-black' }} ps-3">{{ $notification }}</p>
    </div>
    @endif

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
                <button wire:click="stopBot" wire:loading.attr="disabled" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="stopBot">Stop</span>
                    <span wire:loading wire:target="stopBot">...</span>
                </button>
                <button wire:click="restartBot" wire:loading.attr="disabled" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="restartBot">Restart</span>
                    <span wire:loading wire:target="restartBot">...</span>
                </button>
            @elseif($bot->status !== 'deploying')
                <button wire:click="startBot" wire:loading.attr="disabled" class="border border-black px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="startBot">Start</span>
                    <span wire:loading wire:target="startBot">...</span>
                </button>
            @endif

            @if($bot->deploy_method === 'github')
                <button wire:click="redeployBot" wire:loading.attr="disabled" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="redeployBot">Pull</span>
                    <span wire:loading wire:target="redeployBot">...</span>
                </button>
                @if($bot->deploy_key)
                <button wire:click="configureGitSsh" wire:loading.attr="disabled" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="configureGitSsh">Git SSH</span>
                    <span wire:loading wire:target="configureGitSsh">...</span>
                </button>
                @endif
            @endif

            <button wire:click="installDeps" wire:loading.attr="disabled" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                <span wire:loading.remove wire:target="installDeps">npm i</span>
                <span wire:loading wire:target="installDeps">...</span>
            </button>

            <a href="{{ route('bots.edit', $bot) }}" class="border border-black/15 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">{{ __('Editar') }}</a>

            <button wire:click="deleteBot" wire:confirm="{{ __('Eliminar este bot?') }}" class="border border-red-200 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-red-400 hover:border-red-500 hover:text-red-600 transition-colors">{{ __('Eliminar') }}</button>
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

    <!-- Webhook / Auto-deploy -->
    @if($bot->deploy_method === 'github')
    <div class="border border-black/10 p-6 mb-12">
        <div class="flex items-center justify-between mb-4">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Auto-deploy') }}</p>
            <button wire:click="toggleAutoDeploy" wire:loading.attr="disabled"
                class="border px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] transition-colors disabled:opacity-30
                {{ $bot->auto_deploy ? 'border-black bg-black text-white hover:bg-transparent hover:text-black' : 'border-black/15 text-black/40 hover:border-black hover:text-black' }}">
                <span wire:loading.remove wire:target="toggleAutoDeploy">{{ $bot->auto_deploy ? __('Activado') : __('Desactivado') }}</span>
                <span wire:loading wire:target="toggleAutoDeploy">...</span>
            </button>
        </div>

        @if($bot->auto_deploy && $bot->webhook_secret)
        <div class="space-y-4">
            <div>
                <p class="text-[10px] uppercase tracking-[0.15em] text-black/30 mb-2">{{ __('Webhook URL') }}</p>
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $bot->getWebhookUrl() }}"
                        class="flex-1 border-0 border-b border-black/10 bg-transparent px-0 py-1 text-xs font-mono text-black/50 focus:ring-0">
                    <button onclick="navigator.clipboard.writeText('{{ $bot->getWebhookUrl() }}')"
                        class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">{{ __('Copiar') }}</button>
                </div>
            </div>

            <div x-data="{ show: false }">
                <p class="text-[10px] uppercase tracking-[0.15em] text-black/30 mb-2">{{ __('Webhook Secret') }}</p>
                <div class="flex items-center gap-2">
                    <input :type="show ? 'text' : 'password'" readonly value="{{ $bot->webhook_secret }}"
                        class="flex-1 border-0 border-b border-black/10 bg-transparent px-0 py-1 text-xs font-mono text-black/50 focus:ring-0">
                    <button @click="show = !show" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black" x-text="show ? '{{ __('Ocultar') }}' : '{{ __('Mostrar') }}'"></button>
                    <button onclick="navigator.clipboard.writeText('{{ $bot->webhook_secret }}')"
                        class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">{{ __('Copiar') }}</button>
                    <button wire:click="regenerateWebhookSecret" wire:confirm="{{ __('Regenerar secret? El anterior dejara de funcionar.') }}"
                        class="text-[10px] uppercase tracking-[0.15em] text-red-400 hover:text-red-600">{{ __('Regenerar') }}</button>
                </div>
            </div>

            @if($bot->last_webhook_at)
            <p class="text-[10px] text-black/25">{{ __('Ultimo webhook') }}: {{ $bot->last_webhook_at->format('d/m/Y H:i') }}</p>
            @endif

            <p class="text-[10px] text-black/25 leading-relaxed">{{ __('Pega la URL y el secret en GitHub → Settings → Webhooks. Content type: application/json.') }}</p>
        </div>
        @endif
    </div>
    @endif

    <!-- Console -->
    <div class="mb-12" wire:poll.3s="refreshConsole">
        <div class="flex items-center justify-between mb-4">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Consola') }}</p>
            <a href="{{ route('bots.console', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&rarr; {{ __('Completa') }}</a>
        </div>
        <div class="bg-black/[0.02] border border-black/10 p-5 max-h-64 overflow-y-auto"
             x-data="{ atBottom: true }"
             x-on:scroll="atBottom = ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 50)"
             x-effect="if(atBottom) $nextTick(() => $el.scrollTop = $el.scrollHeight)">
            <pre class="text-xs font-mono text-black/50 whitespace-pre-wrap leading-relaxed">{{ $consoleOutput ?: __('Sin output. Inicia el bot.') }}</pre>
        </div>
    </div>

    <!-- Logs -->
    <div>
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-4">{{ __('Registro de actividad') }}</p>
        <div class="border-t border-black/10 max-h-52 overflow-y-auto">
            @forelse($logs as $log)
            <div class="flex items-start gap-4 py-3 border-b border-black/5" wire:key="log-{{ $log->id }}">
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
</div>
