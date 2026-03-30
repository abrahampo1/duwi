<?php

namespace App\Console\Commands;

use App\Services\RuntimeManager;
use Illuminate\Console\Command;

class CheckDepsCommand extends Command
{
    protected $signature = 'duwi:deps {--install : Instalar Node.js si no se encuentra}';

    protected $description = 'Verificar dependencias del sistema (node, npm, git)';

    public function handle(RuntimeManager $runtime): int
    {
        $this->line('');
        $this->line('  DUWI — Dependencias del sistema');
        $this->line('');

        $results = $runtime->check();
        $allOk = true;

        foreach ($results as $name => $info) {
            if ($info['available']) {
                $this->line("  <fg=green>✓</> {$name} {$info['version']}");
                $this->line("    <fg=gray>{$info['path']}</>");
            } else {
                $this->line("  <fg=red>✗</> {$name} — no encontrado");
                $allOk = false;
            }
        }

        $this->line('');

        if (!$allOk && $this->option('install')) {
            if (!$results['node']['available'] || !$results['npm']['available']) {
                $this->line('  Instalando Node.js portable...');
                if ($runtime->ensureNodeInstalled()) {
                    $updated = $runtime->check();
                    $this->line("  <fg=green>✓</> node instalado: {$updated['node']['path']}");
                    $this->line("  <fg=green>✓</> npm instalado: {$updated['npm']['path']}");
                } else {
                    $this->error('  No se pudo instalar Node.js');
                    return self::FAILURE;
                }
            }

            if (!$results['git']['available']) {
                $this->warn('  git debe instalarse manualmente.');
            }

            $this->line('');
        } elseif (!$allOk) {
            $this->line('  Usa --install para instalar Node.js automaticamente.');
            $this->line('');
        }

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
