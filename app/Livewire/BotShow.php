<?php

namespace App\Livewire;

use App\Models\Bot;
use App\Services\BotProcessService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BotShow extends Component
{
    public Bot $bot;
    public string $consoleOutput = '';
    public string $notification = '';
    public string $notificationType = 'success';

    public function mount(Bot $bot): void
    {
        if ($bot->user_id !== Auth::id()) {
            abort(403);
        }

        $this->bot = $bot;
        $this->syncAndLoad();
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
    }

    public function installDeps(): void
    {
        $service = app(BotProcessService::class);
        $service->installDependencies($this->bot);
        $this->notify(__('Dependencias instaladas.'));
        $this->bot->refresh();
    }

    public function configureGitSsh(): void
    {
        $service = app(BotProcessService::class);
        $result = $service->configureGit($this->bot);
        $this->notify($result['message'], $result['success'] ? 'success' : 'error');
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
        return view('livewire.bot-show', ['logs' => $logs]);
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
