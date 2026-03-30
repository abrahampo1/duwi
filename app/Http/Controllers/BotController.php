<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Services\BotProcessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use ZipArchive;

class BotController extends Controller
{
    public function __construct(
        private BotProcessService $processService
    ) {}

    public function create()
    {
        return view('bots.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'deploy_method' => ['required', 'in:github,zip'],
            'repo_url' => ['required_if:deploy_method,github', 'nullable', 'string', 'max:500'],
            'deploy_key' => ['nullable', 'string'],
            'zip_file' => ['required_if:deploy_method,zip', 'nullable', 'file', 'mimes:zip', 'max:102400'],
            'entry_file' => ['nullable', 'string', 'max:255'],
            'env_vars' => ['nullable', 'string'],
        ]);

        $userId = Auth::id();
        $botSlug = Str::slug($validated['name']) . '-' . Str::random(8);
        $botPath = "{$userId}/{$botSlug}";
        $fullPath = storage_path('app/bots/' . $botPath);

        File::ensureDirectoryExists($fullPath);

        $bot = Bot::create([
            'user_id' => $userId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'deploy_method' => $validated['deploy_method'],
            'repo_url' => $validated['repo_url'] ?? null,
            'deploy_key' => $validated['deploy_key'] ?? null,
            'entry_file' => $validated['entry_file'] ?: 'index.js',
            'path' => $botPath,
            'env_vars' => $validated['env_vars'] ?? null,
            'status' => 'deploying',
        ]);

        try {
            if ($validated['deploy_method'] === 'github') {
                $this->processService->deployFromGithub($bot, $validated['repo_url']);
            } else {
                $this->deployFromZip($bot, $request->file('zip_file'));
            }
        } catch (\Exception $e) {
            $bot->update(['status' => 'error']);
            $bot->logs()->create([
                'type' => 'system',
                'content' => __('Error en deploy: :message', ['message' => $e->getMessage()]),
            ]);

            return redirect()->route('bots.show', $bot)
                ->with('error', __('Error durante el deploy: :message', ['message' => $e->getMessage()]));
        }

        return redirect()->route('bots.show', $bot);
    }

    public function edit(Bot $bot)
    {
        $this->authorize($bot);
        return view('bots.edit', compact('bot'));
    }

    public function update(Request $request, Bot $bot)
    {
        $this->authorize($bot);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'entry_file' => ['nullable', 'string', 'max:255'],
            'repo_url' => ['nullable', 'string', 'max:500'],
            'env_vars' => ['nullable', 'string'],
            'deploy_key' => ['nullable', 'string'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'entry_file' => $validated['entry_file'] ?: 'index.js',
            'env_vars' => $validated['env_vars'] ?? null,
        ];

        if ($bot->deploy_method === 'github' && !empty($validated['repo_url'])) {
            $updateData['repo_url'] = $validated['repo_url'];
        }

        if (!empty($validated['deploy_key'])) {
            $updateData['deploy_key'] = $validated['deploy_key'];
        }

        $bot->update($updateData);

        if (!empty($validated['deploy_key']) && File::isDirectory($bot->getFullPath() . '/.git')) {
            $this->processService->writeDeployKeyToBot($bot);
        }

        return redirect()->route('bots.show', $bot);
    }

    public function generateSshKey()
    {
        $tempDir = storage_path('app/temp');
        File::ensureDirectoryExists($tempDir);

        $keyFile = $tempDir . '/sshkey_' . Str::random(16);

        $result = Process::run("ssh-keygen -t ed25519 -C \"bothost-deploy\" -f \"{$keyFile}\" -N \"\" 2>&1");

        if (!$result->successful() || !file_exists($keyFile)) {
            return response()->json([
                'error' => __('No se pudo generar la clave SSH.'),
            ], 500);
        }

        $privateKey = file_get_contents($keyFile);
        $publicKey = file_get_contents($keyFile . '.pub');

        @unlink($keyFile);
        @unlink($keyFile . '.pub');

        return response()->json([
            'private_key' => $privateKey,
            'public_key' => trim($publicKey),
        ]);
    }

    private function deployFromZip(Bot $bot, $zipFile): void
    {
        $fullPath = $bot->getFullPath();
        $tempPath = $zipFile->store('temp');
        $tempFullPath = storage_path('app/' . $tempPath);

        $zip = new ZipArchive();
        if ($zip->open($tempFullPath) === true) {
            $firstEntry = $zip->getNameIndex(0);
            $hasRootDir = str_contains($firstEntry, '/') &&
                          dirname($firstEntry) === explode('/', $firstEntry)[0];

            if ($hasRootDir) {
                $tempExtract = storage_path('app/temp/' . Str::random(16));
                File::ensureDirectoryExists($tempExtract);
                $zip->extractTo($tempExtract);
                $zip->close();

                $rootDir = $tempExtract . '/' . explode('/', $firstEntry)[0];
                if (File::isDirectory($rootDir)) {
                    File::copyDirectory($rootDir, $fullPath);
                } else {
                    File::copyDirectory($tempExtract, $fullPath);
                }
                File::deleteDirectory($tempExtract);
            } else {
                $zip->extractTo($fullPath);
                $zip->close();
            }

            File::delete($tempFullPath);

            $bot->logs()->create([
                'type' => 'system',
                'content' => __('Archivo ZIP extraido correctamente'),
            ]);

            $this->processService->installDependencies($bot);
        } else {
            File::delete($tempFullPath);
            throw new \RuntimeException(__('No se pudo abrir el archivo ZIP'));
        }
    }

    private function authorize(Bot $bot): void
    {
        if ($bot->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
