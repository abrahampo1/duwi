<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class UserManagement extends Component
{
    #[Validate('required|string|max:255')]
    public string $newName = '';

    #[Validate('required|email|max:255|unique:users,email')]
    public string $newEmail = '';

    #[Validate('required|string|min:8')]
    public string $newPassword = '';

    public bool $showForm = false;
    public string $notification = '';
    public string $notificationType = 'success';

    public function createUser(): void
    {
        $this->validate();

        User::create([
            'name' => $this->newName,
            'email' => $this->newEmail,
            'password' => Hash::make($this->newPassword),
            'is_admin' => false,
        ]);

        $this->notify(__('Usuario :name creado.', ['name' => $this->newName]));
        $this->reset('newName', 'newEmail', 'newPassword', 'showForm');
    }

    public function toggleAdmin(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->notify(__('No puedes quitarte admin a ti mismo.'), 'error');
            return;
        }

        $user->update(['is_admin' => !$user->is_admin]);
        $this->notify($user->is_admin
            ? __(':name es ahora admin.', ['name' => $user->name])
            : __(':name ya no es admin.', ['name' => $user->name])
        );
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            $this->notify(__('No puedes eliminarte a ti mismo.'), 'error');
            return;
        }

        // Stop and delete all user bots
        $service = app(\App\Services\BotProcessService::class);
        foreach ($user->bots as $bot) {
            if ($bot->isRunning()) {
                $service->stop($bot);
            }
            $fullPath = $bot->getFullPath();
            if (\Illuminate\Support\Facades\File::isDirectory($fullPath)) {
                \Illuminate\Support\Facades\File::deleteDirectory($fullPath);
            }
        }

        $name = $user->name;
        $user->delete();
        $this->notify(__('Usuario :name eliminado.', ['name' => $name]));
    }

    public function render()
    {
        $users = User::withCount('bots')->orderByDesc('is_admin')->orderBy('name')->get();
        return view('livewire.user-management', ['users' => $users]);
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->notification = $message;
        $this->notificationType = $type;
    }
}
