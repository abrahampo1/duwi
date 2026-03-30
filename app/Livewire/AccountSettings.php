<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AccountSettings extends Component
{
    public string $email = '';
    public string $currentPasswordForEmail = '';

    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPassword_confirmation = '';

    public string $notification = '';
    public string $notificationType = 'success';

    public function mount(): void
    {
        $this->email = Auth::user()->email;
    }

    public function updateEmail(): void
    {
        $this->validate([
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
            'currentPasswordForEmail' => ['required'],
        ]);

        if (!Hash::check($this->currentPasswordForEmail, Auth::user()->password)) {
            $this->addError('currentPasswordForEmail', __('La contrasena actual no es correcta.'));
            return;
        }

        Auth::user()->update(['email' => $this->email]);
        $this->reset('currentPasswordForEmail');
        $this->notify(__('Email actualizado.'));
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword' => ['required'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (!Hash::check($this->currentPassword, Auth::user()->password)) {
            $this->addError('currentPassword', __('La contrasena actual no es correcta.'));
            return;
        }

        Auth::user()->update(['password' => Hash::make($this->newPassword)]);
        $this->reset('currentPassword', 'newPassword', 'newPassword_confirmation');
        $this->notify(__('Contrasena actualizada.'));
    }

    public function render()
    {
        return view('livewire.account-settings');
    }

    private function notify(string $message, string $type = 'success'): void
    {
        $this->notification = $message;
        $this->notificationType = $type;
    }
}
