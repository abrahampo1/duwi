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
    <div class="flex items-start justify-between mb-8">
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
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="border-b border-black/10 mb-8 flex items-center gap-0 -mx-1">
        @php
            $tabs = [
                'general' => __('General'),
                'deploys' => __('Deploys'),
                'console' => __('Consola'),
                'settings' => __('Configuracion'),
                'logs' => __('Registro'),
            ];
        @endphp

        @foreach($tabs as $key => $label)
            <button
                wire:click="setTab('{{ $key }}')"
                class="px-4 py-3 text-[10px] uppercase tracking-[0.2em] transition-colors relative
                    {{ $activeTab === $key
                        ? 'text-black after:absolute after:bottom-0 after:left-0 after:right-0 after:h-px after:bg-black'
                        : 'text-black/30 hover:text-black/60' }}"
            >{{ $label }}</button>
        @endforeach
    </div>

    <!-- ==================== GENERAL TAB ==================== -->
    @if($activeTab === 'general')
    <div>
        <!-- Info Grid -->
        <div class="grid grid-cols-3 gap-px bg-black/10 border border-black/10 mb-8">
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
                @if($currentCommit)
                    <p class="text-[10px] text-black/25 font-mono mt-1">HEAD {{ substr($currentCommit, 0, 7) }}</p>
                @endif
            </div>
            <div class="bg-white p-5">
                <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mb-1">{{ __('Ultimo inicio') }}</p>
                <p class="text-sm">{{ $bot->last_started_at ? $bot->last_started_at->format('d/m/Y H:i') : '---' }}</p>
                @if($bot->pid)
                    <p class="text-[10px] text-black/25 font-mono mt-1">PID {{ $bot->pid }}</p>
                @endif
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="flex items-center gap-3 mb-8">
            @if($bot->deploy_method === 'github')
                <button wire:click="redeployBot" wire:loading.attr="disabled" class="border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="redeployBot">{{ __('Pull & Deploy') }}</span>
                    <span wire:loading wire:target="redeployBot">{{ __('Desplegando...') }}</span>
                </button>
                @if($bot->deploy_key)
                <button wire:click="configureGitSsh" wire:loading.attr="disabled" class="border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="configureGitSsh">Git SSH</span>
                    <span wire:loading wire:target="configureGitSsh">...</span>
                </button>
                @endif
            @endif

            <button wire:click="installDeps" wire:loading.attr="disabled" class="border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                <span wire:loading.remove wire:target="installDeps">npm install</span>
                <span wire:loading wire:target="installDeps">...</span>
            </button>

            <a href="{{ route('bots.edit', $bot) }}" class="border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors">{{ __('Editar') }}</a>
        </div>

        <!-- Last Deploy -->
        @if($deployments->isNotEmpty())
        <div class="border border-black/10 p-5">
            <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mb-3">{{ __('Ultimo deploy') }}</p>
            @php $last = $deployments->first(); @endphp
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    @if($last->status === 'success')
                        <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                    @elseif($last->status === 'failed')
                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    @elseif($last->status === 'rolled_back')
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    @else
                        <span class="h-1.5 w-1.5 rounded-full bg-black/30 animate-pulse"></span>
                    @endif
                    <div>
                        <p class="text-sm">
                            <span class="font-mono text-black/50">{{ $last->shortCommit() }}</span>
                            @if($last->commit_message)
                                <span class="text-black/40 ms-2">{{ Str::limit($last->commit_message, 60) }}</span>
                            @endif
                        </p>
                        <p class="text-[10px] text-black/25 mt-0.5">
                            {{ $last->created_at->format('d/m/Y H:i') }}
                            · {{ $last->triggered_by }}
                            @if($last->duration()) · {{ $last->duration() }} @endif
                        </p>
                    </div>
                </div>
                <button wire:click="setTab('deploys')" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">{{ __('Ver todos') }} &rarr;</button>
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- ==================== DEPLOYS TAB ==================== -->
    @if($activeTab === 'deploys')
    <div>
        <div class="flex items-center justify-between mb-6">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Historial de deploys') }}</p>
            @if($bot->deploy_method === 'github')
            <button wire:click="redeployBot" wire:loading.attr="disabled"
                class="border border-black px-4 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors disabled:opacity-30">
                <span wire:loading.remove wire:target="redeployBot">{{ __('Nuevo deploy') }}</span>
                <span wire:loading wire:target="redeployBot">{{ __('Desplegando...') }}</span>
            </button>
            @endif
        </div>

        @if($currentCommit)
        <div class="bg-black/[0.02] border border-black/10 p-4 mb-6 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase tracking-[0.15em] text-black/30 mb-1">{{ __('Version actual') }}</p>
                <p class="text-sm font-mono text-black/60">{{ substr($currentCommit, 0, 12) }}</p>
            </div>
            <span class="text-[10px] uppercase tracking-[0.15em] text-black/20">HEAD</span>
        </div>
        @endif

        @if($deployments->isEmpty())
        <div class="border border-dashed border-black/10 py-16 text-center">
            <p class="text-xs text-black/25">{{ __('Sin deploys registrados') }}</p>
        </div>
        @else
        <div class="border-t border-black/10">
            @foreach($deployments as $deploy)
            <div class="py-4 border-b border-black/5" wire:key="deploy-{{ $deploy->id }}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4 min-w-0 flex-1">
                        @if($deploy->status === 'success')
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500 flex-shrink-0"></span>
                        @elseif($deploy->status === 'failed')
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                        @elseif($deploy->status === 'rolled_back')
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                        @elseif($deploy->status === 'verifying')
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse flex-shrink-0"></span>
                        @else
                            <span class="h-1.5 w-1.5 rounded-full bg-black/25 {{ in_array($deploy->status, ['pending', 'running']) ? 'animate-pulse' : '' }} flex-shrink-0"></span>
                        @endif

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-3">
                                <span class="font-mono text-sm text-black/60">{{ $deploy->shortCommit() }}</span>
                                <span class="text-[9px] uppercase tracking-[0.15em] px-1.5 py-0.5 border
                                    @if($deploy->status === 'success') border-green-200 text-green-600
                                    @elseif($deploy->status === 'failed') border-red-200 text-red-500
                                    @elseif($deploy->status === 'rolled_back') border-amber-200 text-amber-600
                                    @elseif($deploy->status === 'verifying') border-blue-200 text-blue-500
                                    @else border-black/10 text-black/30
                                    @endif
                                ">{{ $deploy->status }}</span>
                                <span class="text-[9px] uppercase tracking-[0.1em] text-black/20">{{ $deploy->triggered_by }}</span>
                            </div>
                            @if($deploy->commit_message)
                                <p class="text-xs text-black/35 mt-0.5 truncate">{{ $deploy->commit_message }}</p>
                            @endif
                            <p class="text-[10px] text-black/20 mt-0.5">
                                {{ $deploy->created_at->format('d/m/Y H:i:s') }}
                                @if($deploy->duration()) · {{ $deploy->duration() }} @endif
                                @if($deploy->previous_commit) · {{ __('desde') }} {{ substr($deploy->previous_commit, 0, 7) }} @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0 ms-4">
                        @if($deploy->output && $deploy->status !== 'failed')
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="text-[10px] uppercase tracking-[0.15em] text-black/25 hover:text-black">{{ __('Output') }}</button>
                            <div x-show="open" @click.outside="open = false" x-transition
                                class="absolute right-0 top-full mt-1 w-[540px] border border-black/10 shadow-lg z-10">
                                <div class="flex items-center justify-between px-4 py-2 border-b border-black/5 bg-white">
                                    <span class="text-[9px] uppercase tracking-[0.15em] text-black/30">{{ __('Output de Node.js') }}</span>
                                    <button @click="open = false" class="text-[10px] text-black/25 hover:text-black">&times;</button>
                                </div>
                                <div class="bg-black/[0.02] p-4 max-h-72 overflow-y-auto">
                                    <pre class="text-xs font-mono text-black/50 whitespace-pre-wrap leading-relaxed">{{ $deploy->output }}</pre>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($deploy->status === 'success' && $deploy->commit_hash && (!$currentCommit || $deploy->commit_hash !== $currentCommit))
                        <button
                            wire:click="rollbackTo({{ $deploy->id }})"
                            wire:confirm="{{ __('Rollback a :commit? El bot se reiniciara.', ['commit' => $deploy->shortCommit()]) }}"
                            wire:loading.attr="disabled"
                            class="border border-black/15 px-2.5 py-1 text-[10px] uppercase tracking-[0.1em] text-black/30 hover:border-amber-500 hover:text-amber-600 transition-colors disabled:opacity-30">
                            <span wire:loading.remove wire:target="rollbackTo({{ $deploy->id }})">Rollback</span>
                            <span wire:loading wire:target="rollbackTo({{ $deploy->id }})">...</span>
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Failed deploys: show output inline as console --}}
                @if($deploy->status === 'failed' && $deploy->output)
                <div class="mt-3 ms-5.5 bg-black/[0.02] border border-red-200/60 p-4 max-h-52 overflow-y-auto">
                    <pre class="text-xs font-mono text-red-500/70 whitespace-pre-wrap leading-relaxed">{{ $deploy->output }}</pre>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    <!-- ==================== CONSOLE TAB ==================== -->
    @if($activeTab === 'console')
    <div wire:poll.3s="refreshConsole">
        <div class="flex items-center justify-between mb-4">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Consola') }}</p>
            <a href="{{ route('bots.console', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">&rarr; {{ __('Pantalla completa') }}</a>
        </div>
        <div class="bg-black/[0.02] border border-black/10 p-5 max-h-[500px] overflow-y-auto"
             x-data="{ atBottom: true }"
             x-on:scroll="atBottom = ($el.scrollTop + $el.clientHeight >= $el.scrollHeight - 50)"
             x-effect="if(atBottom) $nextTick(() => $el.scrollTop = $el.scrollHeight)">
            <pre class="text-xs font-mono text-black/50 whitespace-pre-wrap leading-relaxed">{{ $consoleOutput ?: __('Sin output. Inicia el bot.') }}</pre>
        </div>
    </div>
    @endif

    <!-- ==================== SETTINGS TAB ==================== -->
    @if($activeTab === 'settings')
    <div>
        <!-- Webhook / Auto-deploy -->
        @if($bot->deploy_method === 'github')
        <div class="border border-black/10 p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Auto-deploy') }}</p>
                <button wire:click="toggleAutoDeploy" wire:loading.attr="disabled"
                    class="border px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] transition-colors disabled:opacity-30
                    {{ $bot->auto_deploy ? 'border-black bg-black text-white hover:bg-transparent hover:text-black' : 'border-black/15 text-black/40 hover:border-black hover:text-black' }}">
                    <span wire:loading.remove wire:target="toggleAutoDeploy">{{ $bot->auto_deploy ? __('Activado') : __('Desactivado') }}</span>
                    <span wire:loading wire:target="toggleAutoDeploy">...</span>
                </button>
            </div>

            <p class="text-[10px] text-black/30 mb-4 leading-relaxed">{{ __('Al activar, cada push a GitHub ejecutara un deploy automatico. El bot se arranca para verificar que funciona y si falla, se hace rollback.') }}</p>

            @if($bot->auto_deploy && $bot->webhook_secret)
            <div class="space-y-4 pt-4 border-t border-black/5">
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

        <!-- Database User -->
        <div class="border border-black/10 p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <p class="text-[10px] uppercase tracking-[0.25em] text-black/30">{{ __('Base de datos') }}</p>
                @if($dbSupported)
                    <span class="text-[9px] uppercase tracking-[0.15em] px-1.5 py-0.5 border border-green-200 text-green-600">{{ $dbDriverLabel }}</span>
                @else
                    <span class="text-[9px] uppercase tracking-[0.15em] px-1.5 py-0.5 border border-black/10 text-black/30">SQLite</span>
                @endif
            </div>

            @if(!$dbSupported)
                <p class="text-[10px] text-black/30 leading-relaxed">{{ __('La gestion de usuarios de base de datos requiere MySQL, MariaDB o PostgreSQL. El driver actual es SQLite.') }}</p>
            @elseif(!$bot->db_user)
                <p class="text-[10px] text-black/30 mb-4 leading-relaxed">{{ __('Crea un usuario de base de datos para que este bot pueda conectarse a :driver. Se inyectaran las credenciales automaticamente al iniciar el bot.', ['driver' => $dbDriverLabel]) }}</p>
                <button wire:click="createDbUser" wire:loading.attr="disabled" wire:confirm="{{ __('Crear usuario de base de datos para este bot?') }}"
                    class="border border-black px-4 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors disabled:opacity-30">
                    <span wire:loading.remove wire:target="createDbUser">{{ __('Crear usuario de BD') }}</span>
                    <span wire:loading wire:target="createDbUser">...</span>
                </button>
            @else
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-black/40">{{ __('Usuario') }}</span>
                        <span class="text-xs font-mono">{{ $bot->db_user }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-black/40">{{ __('Base de datos') }}</span>
                        <span class="text-xs font-mono">{{ $bot->db_name }}</span>
                    </div>
                    <div x-data="{ show: false }">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-black/40">{{ __('Contrasena') }}</span>
                            <div class="flex items-center gap-2">
                                <input :type="show ? 'text' : 'password'" readonly value="{{ $bot->db_password }}"
                                    class="border-0 bg-transparent p-0 text-xs font-mono text-black/50 focus:ring-0 w-64 text-right">
                                <button @click="show = !show" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black" x-text="show ? '{{ __('Ocultar') }}' : '{{ __('Mostrar') }}'"></button>
                                <button onclick="navigator.clipboard.writeText('{{ $bot->db_password }}')"
                                    class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">{{ __('Copiar') }}</button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-black/40">{{ __('Host') }}</span>
                        <span class="text-xs font-mono">{{ config('database.connections.' . config('database.default') . '.host', '127.0.0.1') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-black/40">{{ __('Puerto') }}</span>
                        <span class="text-xs font-mono">{{ config('database.connections.' . config('database.default') . '.port') }}</span>
                    </div>
                </div>

                <p class="text-[10px] text-black/25 mt-4 leading-relaxed">{{ __('Las credenciales se inyectan automaticamente como variables de entorno (DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD) al iniciar el bot.') }}</p>

                <div class="mt-4 pt-4 border-t border-black/5">
                    <button wire:click="revokeDbUser" wire:loading.attr="disabled"
                        wire:confirm="{{ __('Revocar el usuario de base de datos? Se eliminara el usuario y su base de datos.') }}"
                        class="border border-red-200 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-red-400 hover:border-red-500 hover:text-red-600 transition-colors disabled:opacity-30">
                        <span wire:loading.remove wire:target="revokeDbUser">{{ __('Revocar usuario') }}</span>
                        <span wire:loading wire:target="revokeDbUser">...</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Bot Info -->
        <div class="border border-black/10 p-6 mb-8">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-4">{{ __('Info') }}</p>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-black/40">{{ __('Metodo') }}</span>
                    <span class="text-xs">{{ $bot->deploy_method === 'github' ? 'GitHub' : 'ZIP' }}</span>
                </div>
                @if($bot->repo_url)
                <div class="flex items-center justify-between">
                    <span class="text-xs text-black/40">{{ __('Repo') }}</span>
                    <span class="text-xs font-mono truncate ms-4">{{ $bot->repo_url }}</span>
                </div>
                @endif
                <div class="flex items-center justify-between">
                    <span class="text-xs text-black/40">{{ __('Archivo de entrada') }}</span>
                    <span class="text-xs font-mono">{{ $bot->entry_file }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-black/40">{{ __('Ruta') }}</span>
                    <span class="text-xs font-mono">{{ $bot->path }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-black/40">{{ __('Creado') }}</span>
                    <span class="text-xs">{{ $bot->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-black/5">
                <a href="{{ route('bots.edit', $bot) }}" class="text-[10px] uppercase tracking-[0.15em] text-black/30 hover:text-black">{{ __('Editar') }} &rarr;</a>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="border border-red-200 p-6">
            <p class="text-[10px] uppercase tracking-[0.25em] text-red-400 mb-4">{{ __('Zona peligrosa') }}</p>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs text-black/50">{{ __('Eliminar bot') }}</p>
                    <p class="text-[10px] text-black/25 mt-0.5">{{ __('Se eliminaran todos los archivos y registros.') }}</p>
                </div>
                <button wire:click="deleteBot" wire:confirm="{{ __('Eliminar este bot?') }}" class="border border-red-200 px-3 py-1.5 text-[10px] uppercase tracking-[0.15em] text-red-400 hover:border-red-500 hover:text-red-600 transition-colors">{{ __('Eliminar') }}</button>
            </div>
        </div>
    </div>
    @endif

    <!-- ==================== LOGS TAB ==================== -->
    @if($activeTab === 'logs')
    <div>
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-4">{{ __('Registro de actividad') }}</p>
        <div class="border-t border-black/10 max-h-[500px] overflow-y-auto">
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
    @endif
</div>
