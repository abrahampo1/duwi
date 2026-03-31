<?php

namespace App\Livewire;

use App\Models\Bot;
use App\Services\BotProcessService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BotConsole extends Component
{
    public Bot $bot;
    public string $consoleOutput = '';

    public function mount(Bot $bot): void
    {
        if ($bot->user_id !== Auth::id()) {
            abort(403);
        }

        $this->bot = $bot;
        $service = app(BotProcessService::class);
        $service->syncStatus($this->bot);
        $this->bot->refresh();
        $this->consoleOutput = $service->getRecentOutput($this->bot, 200);
    }

    public function startBot(): void
    {
        $this->consoleOutput = '';
        $service = app(BotProcessService::class);
        $service->start($this->bot);
        $this->bot->refresh();
    }

    public function stopBot(): void
    {
        $service = app(BotProcessService::class);
        $service->stop($this->bot);
        $this->bot->refresh();
    }

    public function restartBot(): void
    {
        $this->consoleOutput = '';
        $service = app(BotProcessService::class);
        $service->restart($this->bot);
        $this->bot->refresh();
    }

    public function refreshConsole(): void
    {
        $service = app(BotProcessService::class);
        $service->syncStatus($this->bot);
        $this->bot->refresh();
        $this->consoleOutput = $service->getRecentOutput($this->bot, 200);
    }

    public function render()
    {
        $logs = $this->bot->logs()->latest()->take(100)->get()->reverse();
        return view('livewire.bot-console', ['logs' => $logs]);
    }
}
