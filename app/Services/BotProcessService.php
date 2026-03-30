<?php

namespace App\Services;

use App\Models\Bot;
use App\Models\BotLog;
use App\Models\Deployment;
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

        $npmCmd = $this->runtime->npmCommand();
        $result = Process::path($botPath)->env($this->runtime->env())->timeout(300)->run("{$npmCmd} install 2>&1");
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

    public function redeploy(Bot $bot, string $triggeredBy = 'manual'): array
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

            // Get current commit before pull
            $prevResult = Process::path($fullPath)->run("\"{$gitBin}\" rev-parse HEAD 2>&1");
            $previousCommit = trim($prevResult->output());
            if (!$prevResult->successful() || strlen($previousCommit) !== 40) {
                $previousCommit = null;
            }

            $result = Process::path($fullPath)->timeout(120)->run("\"{$gitBin}\" pull 2>&1");

            if ($result->successful()) {
                // Get new commit hash and message
                $hashResult = Process::path($fullPath)->run("\"{$gitBin}\" rev-parse HEAD 2>&1");
                $commitHash = trim($hashResult->output());
                $msgResult = Process::path($fullPath)->run("\"{$gitBin}\" log -1 --pretty=%s 2>&1");
                $commitMessage = trim($msgResult->output());

                $deployment = $this->createDeployment($bot, [
                    'commit_hash' => strlen($commitHash) === 40 ? $commitHash : null,
                    'commit_message' => $commitMessage ?: null,
                    'previous_commit' => $previousCommit,
                    'triggered_by' => $triggeredBy,
                    'status' => 'running',
                    'started_at' => now(),
                ]);

                $this->installDependencies($bot);
                $bot->refresh();

                if ($bot->status === 'error') {
                    $deployment->update(['status' => 'failed', 'output' => __('npm install fallo'), 'finished_at' => now()]);
                    return ['success' => false, 'message' => __('Error al actualizar: npm install fallo')];
                }

                // Verify bot starts
                $verifyResult = $this->verifyBotStarts($bot, $deployment);
                if ($verifyResult) {
                    $this->log($bot, 'system', __('Repositorio actualizado (git pull)'));
                    return ['success' => true, 'message' => __('Deploy verificado y exitoso.')];
                }

                return ['success' => false, 'message' => __('El bot no arranco correctamente tras el deploy.')];
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

            // Record initial deployment
            $gitBin = $this->runtime->gitPath();
            $hashResult = Process::path($fullPath)->run("\"{$gitBin}\" rev-parse HEAD 2>&1");
            $commitHash = trim($hashResult->output());
            $msgResult = Process::path($fullPath)->run("\"{$gitBin}\" log -1 --pretty=%s 2>&1");
            $commitMessage = trim($msgResult->output());

            $this->createDeployment($bot, [
                'commit_hash' => strlen($commitHash) === 40 ? $commitHash : null,
                'commit_message' => $commitMessage ?: null,
                'triggered_by' => $triggeredBy,
                'status' => 'success',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            return ['success' => true, 'message' => __('Repositorio clonado correctamente.')];
        } catch (\Exception $e) {
            $bot->update(['status' => 'error']);
            $this->log($bot, 'system', __('Error en deploy: :message', ['message' => $e->getMessage()]));
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function webhookDeploy(Bot $bot): array
    {
        if ($bot->deploy_method !== 'github' || !$bot->repo_url) {
            return ['success' => false, 'message' => __('Este bot no usa GitHub.')];
        }

        $wasRunning = $bot->isRunning();

        if ($wasRunning) {
            $this->stop($bot);
        }

        $fullPath = $bot->getFullPath();

        if (!File::isDirectory($fullPath . '/.git')) {
            return $this->redeploy($bot, 'webhook');
        }

        $gitBin = $this->runtime->gitPath();

        // Save current commit for rollback
        $result = Process::path($fullPath)->run("\"{$gitBin}\" rev-parse HEAD 2>&1");
        $previousCommit = trim($result->output());

        if (!$result->successful() || strlen($previousCommit) !== 40) {
            return $this->redeploy($bot, 'webhook');
        }

        $bot->update(['status' => 'deploying']);
        $this->log($bot, 'system', __('Webhook recibido, desplegando...'));

        if ($bot->deploy_key) {
            $this->writeDeployKeyToBot($bot);
        }

        // Git pull
        $result = Process::path($fullPath)->timeout(120)->run("\"{$gitBin}\" pull 2>&1");

        if (!$result->successful()) {
            $this->log($bot, 'stderr', __('Error al actualizar: :output', ['output' => $result->output()]));

            $deployment = $this->createDeployment($bot, [
                'previous_commit' => $previousCommit,
                'triggered_by' => 'webhook',
                'status' => 'failed',
                'output' => 'git pull failed: ' . $result->output(),
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->rollback($bot, $fullPath, $gitBin, $previousCommit);
            return ['success' => false, 'message' => __('Git pull fallo, rollback a :commit', ['commit' => substr($previousCommit, 0, 7)])];
        }

        // Get new commit info
        $hashResult = Process::path($fullPath)->run("\"{$gitBin}\" rev-parse HEAD 2>&1");
        $commitHash = trim($hashResult->output());
        $msgResult = Process::path($fullPath)->run("\"{$gitBin}\" log -1 --pretty=%s 2>&1");
        $commitMessage = trim($msgResult->output());

        $deployment = $this->createDeployment($bot, [
            'commit_hash' => strlen($commitHash) === 40 ? $commitHash : null,
            'commit_message' => $commitMessage ?: null,
            'previous_commit' => $previousCommit,
            'triggered_by' => 'webhook',
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Install dependencies
        $this->installDependencies($bot);
        $bot->refresh();

        if ($bot->status === 'error') {
            $deployment->update(['status' => 'failed', 'output' => 'npm install failed', 'finished_at' => now()]);
            $this->rollback($bot, $fullPath, $gitBin, $previousCommit);
            return ['success' => false, 'message' => __('npm install fallo, rollback a :commit', ['commit' => substr($previousCommit, 0, 7)])];
        }

        // Verify bot starts correctly
        $deployment->update(['status' => 'verifying']);
        $this->log($bot, 'system', __('Verificando que el bot arranca...'));

        // Clear log files so we only capture output from this verification run
        $this->clearBotLogs($bot);

        $started = $this->start($bot);
        if (!$started) {
            $nodeOutput = $this->captureNodeOutput($bot);
            $deployment->update(['status' => 'failed', 'output' => $nodeOutput ?: __('El bot no pudo arrancar'), 'finished_at' => now()]);
            $this->log($bot, 'stderr', __('Verificacion fallida: el bot no arranco'));
            $this->rollback($bot, $fullPath, $gitBin, $previousCommit);

            if ($wasRunning) {
                $this->start($bot);
            }

            return ['success' => false, 'message' => __('El bot no arranco, rollback a :commit', ['commit' => substr($previousCommit, 0, 7)])];
        }

        // Wait and check if process stays alive
        sleep(3);
        $bot->refresh();

        if (!$this->isProcessRunning($bot)) {
            $nodeOutput = $this->captureNodeOutput($bot);
            $deployment->update([
                'status' => 'failed',
                'output' => $nodeOutput ?: __('El bot se detuvo tras arrancar'),
                'finished_at' => now(),
            ]);
            $this->log($bot, 'stderr', __('Verificacion fallida: el bot se detuvo tras arrancar'));
            $bot->update(['status' => 'stopped', 'pid' => null]);
            $this->rollback($bot, $fullPath, $gitBin, $previousCommit);

            if ($wasRunning) {
                $this->start($bot);
            }

            return ['success' => false, 'message' => __('El bot se detuvo, rollback a :commit', ['commit' => substr($previousCommit, 0, 7)])];
        }

        // Deploy verified!
        $nodeOutput = $this->captureNodeOutput($bot);
        $deployment->update(['status' => 'success', 'output' => $nodeOutput, 'finished_at' => now()]);
        $this->log($bot, 'system', __('Deploy verificado y exitoso (webhook)'));

        if (!$wasRunning) {
            $this->stop($bot);
        }

        return ['success' => true, 'message' => __('Deploy verificado y exitoso.')];
    }

    /**
     * Verify bot starts after a manual deploy (redeploy).
     * Returns true if verification passed.
     */
    private function verifyBotStarts(Bot $bot, Deployment $deployment): bool
    {
        $deployment->update(['status' => 'verifying']);
        $this->log($bot, 'system', __('Verificando que el bot arranca...'));

        // Clear log files so we only capture output from this verification run
        $this->clearBotLogs($bot);

        $started = $this->start($bot);
        if (!$started) {
            $nodeOutput = $this->captureNodeOutput($bot);
            $deployment->update([
                'status' => 'failed',
                'output' => $nodeOutput ?: __('El bot no pudo arrancar'),
                'finished_at' => now(),
            ]);
            $this->log($bot, 'stderr', __('Verificacion fallida: el bot no arranco'));
            return false;
        }

        sleep(3);
        $bot->refresh();

        if (!$this->isProcessRunning($bot)) {
            $nodeOutput = $this->captureNodeOutput($bot);
            $deployment->update([
                'status' => 'failed',
                'output' => $nodeOutput ?: __('El bot se detuvo tras arrancar'),
                'finished_at' => now(),
            ]);
            $this->log($bot, 'stderr', __('Verificacion fallida: el bot se detuvo tras arrancar'));
            $bot->update(['status' => 'stopped', 'pid' => null]);
            return false;
        }

        // Verification passed - stop the bot (user can start manually)
        $nodeOutput = $this->captureNodeOutput($bot);
        $this->stop($bot);
        $deployment->update(['status' => 'success', 'output' => $nodeOutput, 'finished_at' => now()]);
        $this->log($bot, 'system', __('Verificacion exitosa: el bot arranca correctamente'));
        return true;
    }

    /**
     * Rollback to a specific deployment by commit hash.
     */
    public function rollbackToDeployment(Bot $bot, Deployment $deployment): array
    {
        if (!$deployment->commit_hash) {
            return ['success' => false, 'message' => __('Este deploy no tiene commit hash.')];
        }

        $fullPath = $bot->getFullPath();
        if (!File::isDirectory($fullPath . '/.git')) {
            return ['success' => false, 'message' => __('No hay repositorio git en este bot.')];
        }

        $wasRunning = $bot->isRunning();
        if ($wasRunning) {
            $this->stop($bot);
        }

        $gitBin = $this->runtime->gitPath();

        // Get current commit
        $currentResult = Process::path($fullPath)->run("\"{$gitBin}\" rev-parse HEAD 2>&1");
        $currentCommit = trim($currentResult->output());

        $rollbackDeployment = $this->createDeployment($bot, [
            'commit_hash' => $deployment->commit_hash,
            'commit_message' => 'Rollback → ' . $deployment->shortCommit(),
            'previous_commit' => strlen($currentCommit) === 40 ? $currentCommit : null,
            'triggered_by' => 'rollback',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->log($bot, 'system', __('Rollback manual a :commit...', ['commit' => $deployment->shortCommit()]));
        $bot->update(['status' => 'deploying']);

        $result = Process::path($fullPath)->run("\"{$gitBin}\" reset --hard {$deployment->commit_hash} 2>&1");

        if (!$result->successful()) {
            $rollbackDeployment->update([
                'status' => 'failed',
                'output' => $result->output(),
                'finished_at' => now(),
            ]);
            $bot->update(['status' => 'error']);
            $this->log($bot, 'stderr', __('Rollback fallo: :output', ['output' => $result->output()]));
            return ['success' => false, 'message' => __('Rollback fallo: :output', ['output' => $result->output()])];
        }

        $this->installDependencies($bot);
        $bot->refresh();

        if ($bot->status === 'error') {
            $rollbackDeployment->update([
                'status' => 'failed',
                'output' => 'npm install failed after rollback',
                'finished_at' => now(),
            ]);
            return ['success' => false, 'message' => __('Rollback completo pero npm install fallo')];
        }

        // Verify the rolled-back version works
        $verifyResult = $this->verifyBotStarts($bot, $rollbackDeployment);

        if ($verifyResult) {
            if ($wasRunning) {
                $this->start($bot);
            }
            return ['success' => true, 'message' => __('Rollback verificado a :commit', ['commit' => $deployment->shortCommit()])];
        }

        return ['success' => false, 'message' => __('Rollback a :commit completado pero verificacion fallo', ['commit' => $deployment->shortCommit()])];
    }

    private function rollback(Bot $bot, string $fullPath, string $gitBin, string $commit): void
    {
        $this->log($bot, 'system', __('Revirtiendo a :commit...', ['commit' => substr($commit, 0, 7)]));

        $result = Process::path($fullPath)->run("\"{$gitBin}\" reset --hard {$commit} 2>&1");

        if ($result->successful()) {
            $this->installDependencies($bot);
            $bot->refresh();

            if ($bot->status !== 'error') {
                $bot->update(['status' => 'stopped']);
                $this->log($bot, 'system', __('Rollback exitoso a :commit', ['commit' => substr($commit, 0, 7)]));
            } else {
                $this->log($bot, 'stderr', __('Rollback completo pero npm install fallo'));
            }
        } else {
            $bot->update(['status' => 'error']);
            $this->log($bot, 'stderr', __('Rollback fallo: :output', ['output' => $result->output()]));
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

    /**
     * Get current commit hash for a bot.
     */
    public function getCurrentCommit(Bot $bot): ?string
    {
        $fullPath = $bot->getFullPath();
        if (!File::isDirectory($fullPath . '/.git')) {
            return null;
        }

        $gitBin = $this->runtime->gitPath();
        $result = Process::path($fullPath)->run("\"{$gitBin}\" rev-parse HEAD 2>&1");
        $hash = trim($result->output());

        return ($result->successful() && strlen($hash) === 40) ? $hash : null;
    }

    /**
     * Clear bot stdout/stderr log files before a verification run.
     */
    private function clearBotLogs(Bot $bot): void
    {
        $botPath = $bot->getFullPath();
        $logFile = $botPath . '/bot_output.log';
        $errorLogFile = $botPath . '/bot_error.log';

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        if (file_exists($errorLogFile)) {
            file_put_contents($errorLogFile, '');
        }
    }

    /**
     * Capture Node.js stdout + stderr output from bot log files.
     */
    private function captureNodeOutput(Bot $bot): string
    {
        $botPath = $bot->getFullPath();
        $output = '';

        $logFile = $botPath . '/bot_output.log';
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if ($content) {
                $output .= trim($content);
            }
        }

        $errorLogFile = $botPath . '/bot_error.log';
        if (file_exists($errorLogFile)) {
            $content = file_get_contents($errorLogFile);
            if ($content) {
                $stderr = trim($content);
                if ($stderr) {
                    $output .= ($output ? "\n\n" : '') . "[STDERR]\n" . $stderr;
                }
            }
        }

        // Limit to last 5000 chars to avoid storing huge outputs
        if (strlen($output) > 5000) {
            $output = '...' . substr($output, -5000);
        }

        return $output;
    }

    private function createDeployment(Bot $bot, array $data): Deployment
    {
        return Deployment::create(array_merge(['bot_id' => $bot->id], $data));
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
