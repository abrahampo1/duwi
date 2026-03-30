<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'duwi:admin
                            {--name= : Nombre del administrador}
                            {--email= : Email del administrador}
                            {--password= : Contrasena}';

    protected $description = 'Crear un usuario administrador de DUWI';

    public function handle(): int
    {
        $this->line('');
        $this->line('  DUWI — Crear administrador');
        $this->line('');

        $name = $this->option('name') ?? $this->ask('Nombre');
        $email = $this->option('email') ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Contrasena (min 8 caracteres)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error("  {$error}");
            }
            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();

        if ($existing) {
            if ($existing->is_admin) {
                $this->warn("  {$email} ya es administrador.");
                return self::SUCCESS;
            }

            if ($this->confirm("  {$email} ya existe. Promover a administrador?")) {
                $existing->update(['is_admin' => true]);
                $this->info("  {$existing->name} promovido a administrador.");
                return self::SUCCESS;
            }

            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => true,
        ]);

        $this->info("  Admin creado: {$user->name} <{$user->email}>");
        $this->line('');

        return self::SUCCESS;
    }
}
