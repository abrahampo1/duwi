<div>
    <div class="flex items-end justify-between mb-12">
        <div>
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-3">{{ __('Admin') }}</p>
            <h1 class="font-serif text-3xl">{{ __('Panel de control') }}</h1>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="syncAll" wire:loading.attr="disabled" class="border border-black/15 px-4 py-2 text-[10px] uppercase tracking-[0.15em] text-black/40 hover:border-black hover:text-black transition-colors disabled:opacity-30">
                <span wire:loading.remove wire:target="syncAll">{{ __('Sincronizar') }}</span>
                <span wire:loading wire:target="syncAll">...</span>
            </button>
            <a href="{{ route('admin.users') }}" class="border border-black px-4 py-2 text-[10px] uppercase tracking-[0.15em] hover:bg-black hover:text-white transition-colors">
                {{ __('Usuarios') }}
            </a>
        </div>
    </div>

    @if($notification)
    <div class="mb-6" x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" x-transition.opacity>
        <p class="text-xs {{ $notificationType === 'error' ? 'text-red-600 border-s-2 border-red-600' : 'text-black/60 border-s-2 border-black' }} ps-3">{{ $notification }}</p>
    </div>
    @endif

    {{-- Overview Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-black/10 border border-black/10 mb-10">
        <div class="bg-white p-6 text-center">
            <p class="font-serif text-3xl">{{ $overview['users'] }}</p>
            <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Usuarios') }}</p>
        </div>
        <div class="bg-white p-6 text-center">
            <p class="font-serif text-3xl">{{ $overview['bots'] }}</p>
            <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Bots') }}</p>
        </div>
        <div class="bg-white p-6 text-center">
            <p class="font-serif text-3xl">{{ $overview['running'] }}</p>
            <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Online') }}</p>
        </div>
        <div class="bg-white p-6 text-center">
            <p class="font-serif text-3xl">{{ $overview['deployments'] }}</p>
            <p class="text-[10px] uppercase tracking-[0.2em] text-black/30 mt-2">{{ __('Deploys') }}</p>
        </div>
    </div>

    {{-- Secondary Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-px bg-black/5 border border-black/10 mb-16">
        <div class="bg-white p-4 text-center">
            <p class="font-serif text-xl">{{ $overview['running'] }}</p>
            <p class="text-[9px] uppercase tracking-[0.2em] text-black/25 mt-1">Running</p>
        </div>
        <div class="bg-white p-4 text-center">
            <p class="font-serif text-xl">{{ $overview['stopped'] }}</p>
            <p class="text-[9px] uppercase tracking-[0.2em] text-black/25 mt-1">Stopped</p>
        </div>
        <div class="bg-white p-4 text-center">
            <p class="font-serif text-xl">{{ $overview['error'] }}</p>
            <p class="text-[9px] uppercase tracking-[0.2em] text-black/25 mt-1 {{ $overview['error'] > 0 ? 'text-red-400' : '' }}">Error</p>
        </div>
        <div class="bg-white p-4 text-center">
            <p class="font-serif text-xl">{{ $overview['deploys_success'] }}</p>
            <p class="text-[9px] uppercase tracking-[0.2em] text-black/25 mt-1">{{ __('Exitosos') }}</p>
        </div>
        <div class="bg-white p-4 text-center">
            <p class="font-serif text-xl">{{ $overview['deploys_failed'] + $overview['deploys_rolled_back'] }}</p>
            <p class="text-[9px] uppercase tracking-[0.2em] text-black/25 mt-1">{{ __('Fallidos') }}</p>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10 mb-16">
        {{-- Deploy Activity Chart (spans 2 cols) --}}
        <div class="lg:col-span-2 border border-black/10 p-6">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Actividad de deploys') }} &mdash; {{ __('ultimos 30 dias') }}</p>
            <div class="h-48">
                <canvas id="deployActivityChart"></canvas>
            </div>
        </div>

        {{-- Deploy Status Breakdown --}}
        <div class="border border-black/10 p-6">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Deploys por estado') }}</p>
            <div class="h-48 flex items-center justify-center">
                <canvas id="deployStatusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 mb-16">
        {{-- Deploy Trigger Breakdown --}}
        <div class="border border-black/10 p-6">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Deploys por origen') }}</p>
            <div class="h-40 flex items-center justify-center">
                <canvas id="deployTriggerChart"></canvas>
            </div>
        </div>

        {{-- Bots per User --}}
        <div class="border border-black/10 p-6">
            <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Bots por usuario') }}</p>
            <div class="h-40">
                <canvas id="botsPerUserChart"></canvas>
            </div>
        </div>
    </div>

    {{-- All Bots Table --}}
    <div class="mb-16">
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Todos los bots') }}</p>
        <div class="border-t border-black/10">
            @forelse($allBots->sortByDesc(fn($b) => $b->status === 'running' ? 2 : ($b->status === 'error' ? 1 : 0)) as $bot)
            <div class="flex items-center justify-between py-4 border-b border-black/10" wire:key="admin-bot-{{ $bot->id }}">
                <div class="flex items-center gap-4 min-w-0">
                    @if($bot->status === 'running')
                        <span class="h-1.5 w-1.5 rounded-full bg-black shrink-0"></span>
                    @elseif($bot->status === 'error')
                        <span class="h-1.5 w-1.5 rounded-full bg-red-500 shrink-0"></span>
                    @elseif($bot->status === 'deploying')
                        <span class="h-1.5 w-1.5 rounded-full bg-black/40 animate-pulse shrink-0"></span>
                    @else
                        <span class="h-1.5 w-1.5 rounded-full bg-black/15 shrink-0"></span>
                    @endif
                    <a href="{{ route('bots.show', $bot) }}" class="text-sm hover:text-black/50 transition-colors truncate">{{ $bot->name }}</a>
                    <span class="text-[10px] text-black/20 shrink-0">{{ $bot->user->name }}</span>
                    <span class="text-[10px] uppercase tracking-[0.15em] text-black/20 shrink-0">{{ $bot->deploy_method }}</span>
                    <span class="text-[10px] text-black/15 font-mono shrink-0">{{ $bot->entry_file }}</span>
                    @if($bot->latestDeployment)
                        <span class="text-[10px] font-mono shrink-0
                            {{ match($bot->latestDeployment->status) {
                                'success' => 'text-green-600',
                                'failed', 'rolled_back' => 'text-red-500',
                                'running', 'verifying', 'pending' => 'text-black/40',
                                default => 'text-black/20',
                            } }}">{{ $bot->latestDeployment->status }}{{ $bot->latestDeployment->shortCommit() !== '---' ? ' · ' . $bot->latestDeployment->shortCommit() : '' }}</span>
                    @endif
                </div>

                <div class="flex items-center gap-3 shrink-0">
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
            @empty
            <div class="py-10 text-center">
                <p class="text-xs text-black/25">{{ __('Sin bots') }}</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Recent Deployments --}}
    <div>
        <p class="text-[10px] uppercase tracking-[0.25em] text-black/30 mb-6">{{ __('Deploys recientes') }}</p>
        <div class="border-t border-black/10">
            @forelse($recentDeploys as $deploy)
            <div class="flex items-center justify-between py-4 border-b border-black/10" wire:key="deploy-{{ $deploy->id }}">
                <div class="flex items-center gap-4">
                    <span class="h-1.5 w-1.5 rounded-full shrink-0
                        {{ match($deploy->status) {
                            'success' => 'bg-green-500',
                            'failed', 'rolled_back' => 'bg-red-500',
                            'running', 'verifying' => 'bg-black/40 animate-pulse',
                            default => 'bg-black/15',
                        } }}"></span>
                    <a href="{{ route('bots.show', $deploy->bot) }}" class="text-sm hover:text-black/50 transition-colors">{{ $deploy->bot->name }}</a>
                    <span class="text-[10px] text-black/20">{{ $deploy->bot->user->name }}</span>
                    <span class="text-[10px] font-mono text-black/30">{{ $deploy->shortCommit() }}</span>
                    @if($deploy->commit_message)
                        <span class="text-[10px] text-black/20 truncate max-w-48">{{ $deploy->commit_message }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-[10px] uppercase tracking-[0.15em] text-black/20">{{ $deploy->triggered_by }}</span>
                    <span class="text-[10px] font-mono
                        {{ match($deploy->status) {
                            'success' => 'text-green-600',
                            'failed', 'rolled_back' => 'text-red-500',
                            default => 'text-black/30',
                        } }}">{{ $deploy->status }}</span>
                    @if($deploy->duration())
                        <span class="text-[10px] text-black/15 font-mono">{{ $deploy->duration() }}</span>
                    @endif
                    <span class="text-[10px] text-black/15">{{ $deploy->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @empty
            <div class="py-10 text-center">
                <p class="text-xs text-black/25">{{ __('Sin deploys') }}</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('livewire:navigated', initCharts);
        document.addEventListener('DOMContentLoaded', initCharts);

        function initCharts() {
            const fontFamily = "'Inter', sans-serif";
            const gridColor = 'rgba(0,0,0,0.04)';
            const tickColor = 'rgba(0,0,0,0.25)';

            Chart.defaults.font.family = fontFamily;
            Chart.defaults.font.size = 10;
            Chart.defaults.color = tickColor;

            // Deploy Activity (Line)
            const actCtx = document.getElementById('deployActivityChart');
            if (actCtx && !actCtx._chartInit) {
                actCtx._chartInit = true;
                new Chart(actCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($deployChartLabels),
                        datasets: [{
                            data: @json($deployChartData),
                            backgroundColor: 'rgba(0,0,0,0.7)',
                            hoverBackgroundColor: 'rgba(0,0,0,0.9)',
                            borderRadius: 2,
                            barPercentage: 0.6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 10 }
                            },
                            y: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { stepSize: 1 }
                            }
                        }
                    }
                });
            }

            // Deploy Status (Doughnut)
            const statusCtx = document.getElementById('deployStatusChart');
            if (statusCtx && !statusCtx._chartInit) {
                statusCtx._chartInit = true;
                const statusData = @json($deploysByStatus);
                const statusLabels = Object.keys(statusData);
                const statusValues = Object.values(statusData);
                const statusColors = statusLabels.map(s => {
                    switch(s) {
                        case 'success': return '#16a34a';
                        case 'failed': return '#ef4444';
                        case 'rolled_back': return '#f97316';
                        case 'running': case 'verifying': return '#a3a3a3';
                        default: return '#d4d4d4';
                    }
                });

                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusValues,
                            backgroundColor: statusColors,
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 8, padding: 12 }
                            }
                        }
                    }
                });
            }

            // Deploy Trigger (Doughnut)
            const trigCtx = document.getElementById('deployTriggerChart');
            if (trigCtx && !trigCtx._chartInit) {
                trigCtx._chartInit = true;
                const trigData = @json($deploysByTrigger);
                const trigLabels = Object.keys(trigData);
                const trigValues = Object.values(trigData);
                const trigColors = trigLabels.map(t => {
                    switch(t) {
                        case 'manual': return '#000000';
                        case 'webhook': return '#6b7280';
                        case 'rollback': return '#d4d4d4';
                        default: return '#e5e5e5';
                    }
                });

                new Chart(trigCtx, {
                    type: 'doughnut',
                    data: {
                        labels: trigLabels,
                        datasets: [{
                            data: trigValues,
                            backgroundColor: trigColors,
                            borderWidth: 0,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { boxWidth: 8, padding: 12 }
                            }
                        }
                    }
                });
            }

            // Bots per User (Horizontal Bar)
            const userCtx = document.getElementById('botsPerUserChart');
            if (userCtx && !userCtx._chartInit) {
                userCtx._chartInit = true;
                const userData = @json($botsPerUser);
                new Chart(userCtx, {
                    type: 'bar',
                    data: {
                        labels: userData.map(u => u.name),
                        datasets: [{
                            data: userData.map(u => u.count),
                            backgroundColor: 'rgba(0,0,0,0.6)',
                            hoverBackgroundColor: 'rgba(0,0,0,0.85)',
                            borderRadius: 2,
                            barPercentage: 0.5,
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: {
                                beginAtZero: true,
                                grid: { color: gridColor },
                                ticks: { stepSize: 1 }
                            },
                            y: {
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        }
    </script>
</div>
