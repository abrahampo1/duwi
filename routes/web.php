<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebhookController;
use App\Livewire\BotConsole;
use App\Livewire\BotList;
use App\Livewire\BotShow;
use App\Livewire\AccountSettings;
use App\Livewire\UserManagement;
use Illuminate\Support\Facades\Route;

// Locale switcher
Route::get('/locale/{locale}', function (string $locale) {
    if (in_array($locale, config('app.supported_locales'))) {
        session(['locale' => $locale]);
    }
    return redirect()->back()->withInput();
})->name('locale.set');

// Webhooks (no auth, CSRF excluded)
Route::post('/webhook/bot/{botId}', [WebhookController::class, 'handleGithub'])->name('webhook.bot');

// Landing
Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('welcome');
});

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Bots
    Route::get('/bots', BotList::class)->name('bots.index');
    Route::get('/bots/create', [BotController::class, 'create'])->name('bots.create');
    Route::post('/bots', [BotController::class, 'store'])->name('bots.store');
    Route::get('/bots/{bot}', BotShow::class)->name('bots.show');
    Route::get('/bots/{bot}/edit', [BotController::class, 'edit'])->name('bots.edit');
    Route::put('/bots/{bot}', [BotController::class, 'update'])->name('bots.update');
    Route::get('/bots/{bot}/console', BotConsole::class)->name('bots.console');

    Route::post('/generate-ssh-key', [BotController::class, 'generateSshKey'])->name('generate-ssh-key');

    // Account
    Route::get('/account', AccountSettings::class)->name('account.settings');

    // Admin
    Route::middleware('admin')->group(function () {
        Route::get('/admin/users', UserManagement::class)->name('admin.users');
    });
});
