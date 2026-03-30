<?php

namespace App\Http\Controllers;

use App\Services\BotProcessService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(BotProcessService $processService)
    {
        $user = Auth::user();
        $bots = $user->bots()->latest()->get();

        foreach ($bots->where('status', 'running') as $bot) {
            $processService->syncStatus($bot);
        }

        $stats = [
            'total' => $bots->count(),
            'running' => $bots->where('status', 'running')->count(),
            'stopped' => $bots->where('status', 'stopped')->count(),
            'error' => $bots->where('status', 'error')->count(),
        ];

        return view('dashboard', compact('bots', 'stats'));
    }
}
