<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotLog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class BotProcessService
{
    private RuntimeManager $runtime;

    public function __construct(RuntimeManager $runtime)
    {
        $this->runtime = $runtime;
    }

    public function start(Bot $bot): bool
    {
        if ($bot->isRunning()) {
            return false;
        }

        $botPath = $bot->getFullPath();
        $entryFile = $bot->entry_file;
        $fullEntry = $botPath . '/' . $entryFile;

        if (!file_exists($fullEntry)) {
            $bot->update(['status' => 'error']);
            $this->log($bot, 'system', __('Error: No se encontro el archivo de entrada: :file', ['file' => $entryFile]));
            return false;
        }

        $envVars = $this->parseEnvVars($bot->env_vars);

        if (!empty($envVars)) {
            $envContent = '';
            foreach ($envVars as $key => $value) {
                $envContent .= "{$key}={$value}\n";
            }
            file_put_contents($botPath . '/.env', $envContent);
        }

        $runtimeEnv = $this->runtime->env();
        $envVars = array_merge($runtimeEnv, $envVars);

        $envString = '';
        foreach ($envVars as $key => $value) {
            $envString .= "$key=\"$value\" ";
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $logFile = $botPath . '/bot_output.log';
        $errorLogFile = $botPath . '/bot_error.log';
        $nodeBin = $this->runtime->nodePath();

        if ($isWindows) {
            $cmd = "start /B \"{$nodeBin}\" \"{$fullEntry}\" > \"{$logFile}\" 2> \"{$errorLogFile}\"";
            pclose(popen($cmd, 'r'));

            sleep(1);
            $result = Process::run("wmic process where \"commandline like '%{$entryFile}%' and name='node.exe'\" get processid /format:list");
            $output = $result->output();
            if (preg_match('/ProcessId=(\d+)/', $output, $matches)) {
                $pid = (int) $matches[1];
            } else {
                $result = Process::run("powershell -Command \"Get-Process node -ErrorAction SilentlyContinue | Select-Object -Last 1 -ExpandProperty Id\"");
                $pid = (int) trim($result->output());
            }
        } else {
            $cmd = "cd \"{$botPath}\" && {$envString}nohup \"{$nodeBin}\" \"{$entryFile}\" > \"{$logFile}\" 2> \"{$errorLogFile}\" & echo $!";
            $result = Process::run($cmd);
            $pid = (int) trim($result->output());
        }

        if ($pid > 0) {
            $bot->update([
                'status' => 'running',
                'pid' => $pid,
                'last_started_at' => now(),
            ]);
            $this->log($bot, 'system', __('Bot iniciado con PID: :pid', ['pid' => $pid]));
            return true;
        }

        $bot->update(['status' => 'error']);
        $this->log($bot, 'system', __('Error al iniciar el bot: no se pudo obtener el PID'));
        return false;
    }

    public function stop(Bot $bot): bool
    {
        if (!$bot->isRunning() || !$bot->pid) {
            $bot->update(['status' => 'stopped', 'pid' => null]);
            return true;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            Process::run("taskkill /F /PID {$bot->pid}");
        } else {
            Process::run("kill -9 {$bot->pid}");
        }

        $bot->update(['status' => 'stopped', 'pid' => null]);
        $this->log($bot, 'system', __('Bot detenido (log)'));
        return true;
    }

    public function restart(Bot $bot): bool
    {
        $this->stop($bot);
        sleep(1);
        return $this->start($bot);
    }

    public function isProcessRunning(Bot $bot): bool
    {
        if (!$bot->pid) {
            return false;
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $result = Process::run("tasklist /FI \"PID eq {$bot->pid}\" /NH");
            return str_contains($result->output(), (string) $bot->pid);
        }

        $result = Process::run("kill -0 {$bot->pid} 2>/dev/null; echo $?");
        return trim($result->output()) === '0';
    }

    public function syncStatus(Bot $bot): void
    {
        if ($bot->isRunning() && !$this->isProcessRunning($bot)) {
            $bot->update(['status' => 'stopped', 'pid' => null]);
            $this->log($bot, 'system', __('El proceso del bot se detuvo inesperadamente'));
        }
    }

    public function getRecentOutput(Bot $bot, int $lines = 100): string
    {
        $logFile = $bot->getFullPath() . '/bot_output.log';
        $errorLogFile = $bot->getFullPath() . '/bot_error.log';

        $output = '';

        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if ($content) {
                $allLines = explode("\n", $content);
                $output .= implode("\n", array_slice($allLines, -$lines));
            }
        }

        if (file_exists($errorLogFile)) {
            $content = file_get_contents($errorLogFile);
            if ($content) {
                $allLines = explode("\n", $content);
                $errors = implode("\n", array_slice($allLines, -$lines));
                if ($errors) {
                    $output .= "\n[STDERR]\n" . $errors;
                }
            }
        }

        return $output;
    }

    public function installDependencies(Bot $bot): string
    {
        $botPath = $bot->getFullPath();
        $packageJson = $botPath . '/package.json';

        if (!file_exists($packageJson)) {
            return __('No se encontro package.json');
        }

        $this->log($bot, 'system', __('Instalando dependencias npm...'));
        $bot->update(['status' => 'deploying']);

        $npmBin = $this->runtime->npmPath();
        $result = Process::path($botPath)->env($this->runtime->env())->timeout(300)->run("\"{$npmBin}\" install 2>&1");
        $output = $result->output();

        if ($result->successful()) {
            $bot->update(['status' => 'stopped']);
            $this->log($bot, 'system', __('Dependencias instaladas correctamente'));
        } else {
            $bot->update(['status' => 'error']);
            $this->log($bot, 'stderr', __('Error instalando dependencias: :output', ['output' => $output]));
        }

        return $output;
    }

    // --- Deploy methods ---

    public function deployFromGithub(Bot $bot, string $repoUrl): void
    {
        $fullPath = $bot->getFullPath();
        $keyFile = null;

        try {
            $env = [];

            if ($bot->deploy_key) {
                $keyFile = $this->writeTempDeployKey($bot);
                $keyPath = str_replace('\\', '/', $keyFile);
                $env['GIT_SSH_COMMAND'] = "ssh -i \"{$keyPath}\" -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null";
            }

            $clonePath = str_replace('\\', '/', $fullPath);
            $gitBin = $this->runtime->gitPath();
            $result = Process::env($env)->timeout(120)->run("\"{$gitBin}\" clone \"{$repoUrl}\" \"{$clonePath}\" 2>&1");

            if (!$result->successful()) {
                throw new \RuntimeException(__('Git clone fallo: :output', ['output' => $result->output()]));
            }

            $this->log($bot, 'system', __('Repositorio clonado desde: :url', ['url' => $repoUrl]) . ($bot->deploy_key ? ' (con deploy key)' : ''));

            if ($bot->deploy_key) {
                $this->writeDeployKeyToBot($bot);
            }

            $this->installDependencies($bot);
        } finally {
            if ($keyFile && file_exists($keyFile)) {
                @unlink($keyFile);
            }
        }
    }

    public function redeploy(Bot $bot): array
    {
        if ($bot->deploy_method !== 'github' || !$bot->repo_url) {
            return ['success' => false, 'message' => __('Este bot no usa GitHub.')];
        }

        if ($bot->isRunning()) {
            $this->stop($bot);
        }

        $fullPath = $bot->getFullPath();

        if (File::isDirectory($fullPath . '/.git')) {
            $bot->update(['status' => 'deploying']);

            if ($bot->deploy_key) {
                $this->writeDeployKeyToBot($bot);
            }

            $gitBin = $this->runtime->gitPath();
            $result = Process::path($fullPath)->timeout(120)->run("\"{$gitBin}\" pull 2>&1");

            if ($result->successful()) {
                $this->installDependencies($bot);
                $this->log($bot, 'system', __('Repositorio actualizado (git pull)'));
                return ['success' => true, 'message' => __('Repositorio actualizado.')];
            }

            $bot->update(['status' => 'error']);
            return ['success' => false, 'message' => __('Error al actualizar: :output', ['output' => $result->output()])];
        }

        // No .git — fresh clone
        $bot->update(['status' => 'deploying']);

        if (File::isDirectory($fullPath)) {
            File::cleanDirectory($fullPath);
        }
        File::ensureDirectoryExists($fullPath);

        try {
            $this->deployFromGithub($bot, $bot->repo_url);
            return ['success' => true, 'message' => __('Repositorio clonado correctamente.')];
        } catch (\Exception $e) {
            $bot->update(['status' => 'error']);
            $this->log($bot, 'system', __('Error en deploy: :message', ['message' => $e->getMessage()]));
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function configureGit(Bot $bot): array
    {
        if ($bot->deploy_method !== 'github' || !$bot->deploy_key) {
            return ['success' => false, 'message' => __('Este bot no usa deploy key.')];
        }

        if (!File::isDirectory($bot->getFullPath() . '/.git')) {
            return ['success' => false, 'message' => __('No hay repositorio git en este bot.')];
        }

        $this->writeDeployKeyToBot($bot);
        $this->log($bot, 'system', __('Configuracion git/SSH actualizada'));
        return ['success' => true, 'message' => __('Git reconfigurado.')];
    }

    public function writeDeployKeyToBot(Bot $bot): void
    {
        $fullPath = $bot->getFullPath();
        $botKeyPath = $fullPath . '/.deploy_key';

        $key = str_replace("\r", '', $bot->deploy_key);
        $key = rtrim($key) . "\n";

        file_put_contents($botKeyPath, $key);

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            chmod($botKeyPath, 0600);
        }

        $absKeyPath = str_replace('\\', '/', realpath($botKeyPath) ?: $botKeyPath);

        $gitBin = $this->runtime->gitPath();
        Process::path($fullPath)->run(
            "\"{$gitBin}\" config core.sshCommand \"ssh -i \\\"{$absKeyPath}\\\" -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null\""
        );
    }

    public function writeTempDeployKey(Bot $bot): string
    {
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        $keyFile = $tempDir . '/deploy_key_' . $bot->id . '_' . Str::random(8);

        $key = str_replace("\r", '', $bot->deploy_key);
        $key = rtrim($key) . "\n";

        file_put_contents($keyFile, $key);

        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            chmod($keyFile, 0600);
        }

        return $keyFile;
    }

    private function log(Bot $bot, string $type, string $content): void
    {
        BotLog::create([
            'bot_id' => $bot->id,
            'type' => $type,
            'content' => $content,
        ]);
    }

    private function parseEnvVars(?string $envVars): array
    {
        if (!$envVars) {
            return [];
        }

        $vars = [];
        $lines = explode("\n", $envVars);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line && str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $vars[trim($key)] = trim($value);
            }
        }

        return $vars;
    }
}
