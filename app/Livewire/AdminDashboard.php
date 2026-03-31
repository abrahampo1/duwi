<?php

namespace App\Livewire;

use App\Models\Bot;
use App\Models\Deployment;
use App\Models\User;
use App\Services\BotProcessService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AdminDashboard extends Component
{
    public string $notification = '';
    public string $notificationType = 'success';

    public function startBot(int $botId): void
    {
        $bot = Bot::findOrFail($botId);
        $service = app(BotProcessService::class);

        if ($service->start($bot)) {
            $this->notify(__(':name iniciado.', ['name' => $bot->name]));
        } else {
            $this->notify(__('No se pudo iniciar :name', ['name' => $bot->name]), 'error');
        }
    }

    public function stopBot(int $botId): void
    {
        $bot = Bot::findOrFail($botId);
        app(BotProcessService::class)->stop($bot);
        $this->notify(__(':name detenido.', ['name' => $bot->name]));
    }

    public function restartBot(int $botId): void
    {
        $bot = Bot::findOrFail($botId);
        $service = app(BotProcessService::class);

        if ($service->restart($bot)) {
            $this->notify(__(':name reiniciado.', ['name' => $bot->name]));
        } else {
            $this->notify(__('No se pudo reiniciar :name', ['name' => $bot->name]), 'error');
        }
    }

    public function syncAll(): void
    {
        $service = app(BotProcessService::class);
        foreach (Bot::where('status', 'running')->get() as $bot) {
            $service->syncStatus($bot);
        }
        $this->notify(__('Estado sincronizado.'));
    }

    public function render()
    {
        $service = app(BotProcessService::class);
        foreach (Bot::where('status', 'running')->get() as $bot) {
            $service->syncStatus($bot);
        }

        $allBots = Bot::with(['user', 'latestDeployment'])->get();

        $overview = [
            'users' => User::count(),
            'bots' => $allBots->count(),
            'running' => $allBots->where('status', 'running')->count(),
            'stopped' => $allBots->where('status', 'stopped')->count(),
            'error' => $allBots->where('status', 'error')->count(),
            'deploying' => $allBots->where('status', 'deploying')->count(),
            'deployments' => Deployment::count(),
            'deploys_success' => Deployment::where('status', 'success')->count(),
            'deploys_failed' => Deployment::where('status', 'failed')->count(),
            'deploys_rolled_back' => Deployment::where('status', 'rolled_back')->count(),
        ];

        // Deploys per day (last 30 days)
        $thirtyDaysAgo = Carbon::now()->subDays(29)->startOfDay();
        $deploysPerDay = Deployment::where('created_at', '>=', $thirtyDaysAgo)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $deployChartLabels = [];
        $deployChartData = [];
        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays(29 - $i)->format('Y-m-d');
            $deployChartLabels[] = Carbon::parse($date)->format('d M');
            $deployChartData[] = $deploysPerDay[$date] ?? 0;
        }

        // Deploys by status
        $deploysByStatus = Deployment::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Deploys by trigger
        $deploysByTrigger = Deployment::select('triggered_by', DB::raw('COUNT(*) as count'))
            ->groupBy('triggered_by')
            ->pluck('count', 'triggered_by')
            ->toArray();

        // Bots per user (top 10)
        $botsPerUser = User::withCount('bots')
            ->orderByDesc('bots_count')
            ->limit(10)
            ->get()
            ->map(fn ($u) => ['name' => $u->name, 'count' => $u->bots_count]);

        // Recent deployments (last 15)
        $recentDeploys = Deployment::with(['bot.user'])
            ->latest()
            ->limit(15)
            ->get();

        return view('livewire.admin-dashboard', [
            'overview' => $overview,
            'allBots' => $allBots,
            'deployChartLabels' => $deployChartLabels,
            'deployChartData' => $deployChartData,
            'deploysByStatus' => $deploysByStatus,
            'deploysByTrigger' => $deploysByTrigger,
            'botsPerUser' => $botsPerUser,
            'recentDeploys' => $recentDeploys,
        ]);
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->notification = $message;
        $this->notificationType = $type;
    }
}
