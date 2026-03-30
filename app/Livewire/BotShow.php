<?php

namespace App\Livewire;

use App\Models\Bot;
use App\Models\Deployment;
use App\Services\BotProcessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class BotShow extends Component
{
    public Bot $bot;
    public string $consoleOutput = '';
    public string $notification = '';
    public string $notificationType = 'success';

    #[Url(as: 'tab')]
    public string $activeTab = 'general';

    public function mount(Bot $bot): void
    {
        if ($bot->user_id !== Auth::id()) {
            abort(403);
        }

        $this->bot = $bot;
        $this->syncAndLoad();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;

        if ($tab === 'console') {
            $this->refreshConsole();
        }
    }

    public function startBot(): void
    {
        $service = app(BotProcessService::class);
        if ($service->start($this->bot)) {
            $this->notify(__('Bot iniciado.'));
        } else {
            $this->notify(__('No se pudo iniciar el bot.'), 'error');
        }
        $this->bot->refresh();
    }

    public function stopBot(): void
    {
        $service = app(BotProcessService::class);
        $service->stop($this->bot);
        $this->notify(__('Bot detenido.'));
        $this->bot->refresh();
    }

    public function restartBot(): void
    {
        $service = app(BotProcessService::class);
        if ($service->restart($this->bot)) {
            $this->notify(__('Bot reiniciado.'));
        } else {
            $this->notify(__('No se pudo reiniciar el bot.'), 'error');
        }
        $this->bot->refresh();
    }

    public function redeployBot(): void
    {
        $service = app(BotProcessService::class);
        $result = $service->redeploy($this->bot);
        $this->notify($result['message'], $result['success'] ? 'success' : 'error');
        $this->bot->refresh();
        $this->activeTab = 'deploys';
    }

    public function installDeps(): void
    {
        $service = app(BotProcessService::class);
        $service->installDependencies($this->bot);
        $this->notify(__('Dependencias instaladas.'));
        $this->bot->refresh();
    }

    public function toggleAutoDeploy(): void
    {
        if (!$this->bot->auto_deploy && !$this->bot->webhook_secret) {
            $this->bot->webhook_secret = Str::random(40);
        }

        $this->bot->auto_deploy = !$this->bot->auto_deploy;
        $this->bot->save();

        $status = $this->bot->auto_deploy ? __('Auto-deploy activado') : __('Auto-deploy desactivado');
        $this->notify($status);
    }

    public function regenerateWebhookSecret(): void
    {
        $this->bot->webhook_secret = Str::random(40);
        $this->bot->save();
        $this->notify(__('Webhook secret regenerado'));
    }

    public function configureGitSsh(): void
    {
        $service = app(BotProcessService::class);
        $result = $service->configureGit($this->bot);
        $this->notify($result['message'], $result['success'] ? 'success' : 'error');
    }

    public function rollbackTo(int $deploymentId): void
    {
        $deployment = Deployment::where('id', $deploymentId)
            ->where('bot_id', $this->bot->id)
            ->firstOrFail();

        $service = app(BotProcessService::class);
        $result = $service->rollbackToDeployment($this->bot, $deployment);
        $this->notify($result['message'], $result['success'] ? 'success' : 'error');
        $this->bot->refresh();
    }

    public function deleteBot(): void
    {
        $service = app(BotProcessService::class);

        if ($this->bot->isRunning()) {
            $service->stop($this->bot);
        }

        $fullPath = $this->bot->getFullPath();
        if (File::isDirectory($fullPath)) {
            File::deleteDirectory($fullPath);
        }

        $this->bot->delete();
        $this->redirect(route('bots.index'));
    }

    public function refreshConsole(): void
    {
        $service = app(BotProcessService::class);
        $service->syncStatus($this->bot);
        $this->bot->refresh();
        $this->consoleOutput = $service->getRecentOutput($this->bot);
    }

    public function render()
    {
        $logs = $this->bot->logs()->latest()->take(50)->get()->reverse();
        $deployments = $this->bot->deployments()->latest()->take(20)->get();
        $currentCommit = null;

        if ($this->bot->deploy_method === 'github') {
            $service = app(BotProcessService::class);
            $currentCommit = $service->getCurrentCommit($this->bot);
        }

        return view('livewire.bot-show', [
            'logs' => $logs,
            'deployments' => $deployments,
            'currentCommit' => $currentCommit,
        ]);
    }

    private function syncAndLoad(): void
    {
        $service = app(BotProcessService::class);
        $service->syncStatus($this->bot);
        $this->bot->refresh();
        $this->consoleOutput = $service->getRecentOutput($this->bot);
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->notification = $message;
        $this->notificationType = $type;
    }
}
