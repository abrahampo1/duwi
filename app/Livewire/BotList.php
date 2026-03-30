<?php

namespace App\Livewire;

use App\Models\Bot;
use App\Services\BotProcessService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BotList extends Component
{
    public string $notification = '';
    public string $notificationType = 'success';

    public function startBot(int $botId): void
    {
        $bot = $this->findBot($botId);
        $service = app(BotProcessService::class);

        if ($service->start($bot)) {
            $this->notify(__(':name iniciado.', ['name' => $bot->name]));
        } else {
            $this->notify(__('No se pudo iniciar :name', ['name' => $bot->name]), 'error');
        }
    }

    public function stopBot(int $botId): void
    {
        $bot = $this->findBot($botId);
        app(BotProcessService::class)->stop($bot);
        $this->notify(__(':name detenido.', ['name' => $bot->name]));
    }

    public function restartBot(int $botId): void
    {
        $bot = $this->findBot($botId);
        $service = app(BotProcessService::class);

        if ($service->restart($bot)) {
            $this->notify(__(':name reiniciado.', ['name' => $bot->name]));
        } else {
            $this->notify(__('No se pudo reiniciar :name', ['name' => $bot->name]), 'error');
        }
    }

    public function refreshBots(): void
    {
        $service = app(BotProcessService::class);
        foreach (Auth::user()->bots()->where('status', 'running')->get() as $bot) {
            $service->syncStatus($bot);
        }
    }

    public function render()
    {
        $bots = Auth::user()->bots()->latest()->get();
        return view('livewire.bot-list', ['bots' => $bots]);
    }

    private function findBot(int $botId): Bot
    {
        return Auth::user()->bots()->findOrFail($botId);
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->notification = $message;
        $this->notificationType = $type;
    }
}
