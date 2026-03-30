<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class RuntimeManager
{
    private const NODE_VERSION = '20.18.1';
    private const CACHE_KEY = 'runtime_paths';
    private const CACHE_TTL = 3600;

    public function nodePath(): string
    {
        return $this->resolve('node');
    }

    public function npmPath(): string
    {
        return $this->resolve('npm');
    }

    public function gitPath(): string
    {
        return $this->resolve('git');
    }

    /**
     * Return env array with PATH prepended so that #!/usr/bin/env node works.
     */
    public function env(): array
    {
        $nodeBin = $this->nodePath();
        $binDir = dirname($nodeBin);

        $path = getenv('PATH') ?: '';
        if (!str_contains($path, $binDir)) {
            $separator = $this->isWindows() ? ';' : ':';
            $path = $binDir . $separator . $path;
        }

        return ['PATH' => $path];
    }

    public function check(): array
    {
        Cache::forget(self::CACHE_KEY);

        $results = [];

        foreach (['node', 'npm', 'git'] as $bin) {
            $path = $this->findBinary($bin);
            $version = null;

            if ($path) {
                $result = Process::run("\"{$path}\" --version 2>&1");
                $version = trim($result->output());
            }

            $results[$bin] = [
                'path' => $path,
                'version' => $version,
                'available' => (bool) $path,
            ];
        }

        return $results;
    }

    public function ensureNodeInstalled(): bool
    {
        $nodePath = $this->findBinary('node');
        if ($nodePath) {
            $this->ensurePortablePackageJson();
            return true;
        }

        return $this->installPortableNode();
    }

    private function resolve(string $binary): string
    {
        $paths = Cache::get(self::CACHE_KEY, []);

        if (!empty($paths[$binary])) {
            if ($this->binaryExists($paths[$binary])) {
                return $paths[$binary];
            }
        }

        $path = $this->findBinary($binary);

        if (!$path && in_array($binary, ['node', 'npm'])) {
            $this->installPortableNode();
            $path = $this->findBinary($binary);
        }

        if ($path) {
            $paths[$binary] = $path;
            Cache::put(self::CACHE_KEY, $paths, self::CACHE_TTL);
        }

        return $path ?: $binary;
    }

    private function findBinary(string $binary): ?string
    {
        $isWindows = $this->isWindows();

        // Check portable install first (within storage/, always accessible)
        $portablePath = $this->getPortablePath($binary);
        if ($portablePath && file_exists($portablePath)) {
            $this->ensurePortablePackageJson();
            return $portablePath;
        }

        // Use where/which to find in PATH
        // On Plesk, open_basedir blocks PHP's file_exists() for system paths,
        // so we rely on shell commands to verify binaries instead.
        if ($isWindows) {
            $suffix = in_array($binary, ['npm']) ? '.cmd' : '.exe';
            $result = Process::run("where {$binary}{$suffix} 2>NUL");
            if ($result->successful()) {
                $firstLine = strtok(trim($result->output()), "\n");
                if ($firstLine) {
                    return trim($firstLine);
                }
            }
            $result = Process::run("where {$binary} 2>NUL");
            if ($result->successful()) {
                $lines = explode("\n", trim($result->output()));
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line && (str_ends_with($line, '.exe') || str_ends_with($line, '.cmd'))) {
                        return $line;
                    }
                }
            }
        } else {
            // `which` only returns existing executables, no need for file_exists()
            $result = Process::run("which {$binary} 2>/dev/null");
            if ($result->successful()) {
                $path = trim($result->output());
                if ($path) {
                    return $path;
                }
            }

            // Check common locations using shell test -x (bypasses open_basedir)
            $commonPaths = $this->getCommonPaths($binary);
            foreach ($commonPaths as $path) {
                $check = Process::run("test -x \"{$path}\" && echo ok 2>/dev/null");
                if (trim($check->output()) === 'ok') {
                    return $path;
                }
            }
        }

        return null;
    }

    private function getCommonPaths(string $binary): array
    {
        $isWindows = $this->isWindows();

        if ($isWindows) {
            $home = getenv('USERPROFILE') ?: 'C:\\Users\\' . get_current_user();
            $paths = match ($binary) {
                'node' => [
                    'C:\\Program Files\\nodejs\\node.exe',
                    "{$home}\\AppData\\Local\\fnm_multishells\\*\\node.exe",
                    "{$home}\\.nvm\\current\\node.exe",
                ],
                'npm' => [
                    'C:\\Program Files\\nodejs\\npm.cmd',
                    "{$home}\\AppData\\Roaming\\npm\\npm.cmd",
                ],
                'git' => [
                    'C:\\Program Files\\Git\\cmd\\git.exe',
                    'C:\\Program Files (x86)\\Git\\cmd\\git.exe',
                ],
                default => [],
            };
        } else {
            $paths = match ($binary) {
                'node' => ['/usr/local/bin/node', '/usr/bin/node', '/opt/homebrew/bin/node'],
                'npm' => ['/usr/local/bin/npm', '/usr/bin/npm', '/opt/homebrew/bin/npm'],
                'git' => ['/usr/bin/git', '/usr/local/bin/git'],
                default => [],
            };
        }

        return $paths;
    }

    private function getPortablePath(string $binary): ?string
    {
        $runtimeDir = $this->portableDir();
        $isWindows = $this->isWindows();

        return match ($binary) {
            'node' => $isWindows ? "{$runtimeDir}\\node.exe" : "{$runtimeDir}/bin/node",
            'npm' => $isWindows ? "{$runtimeDir}\\npm.cmd" : "{$runtimeDir}/bin/npm",
            default => null,
        };
    }

    private function portableDir(): string
    {
        return storage_path('app/runtime/node');
    }

    /**
     * Ensure the portable node dir has a package.json with "type":"commonjs"
     * so that npm scripts are not treated as ESM by a parent "type":"module".
     */
    private function ensurePortablePackageJson(): void
    {
        $dir = $this->portableDir();
        $file = $dir . DIRECTORY_SEPARATOR . 'package.json';
        if (File::isDirectory($dir) && !file_exists($file)) {
            File::put($file, '{"type":"commonjs"}' . "\n");
        }
    }

    private function installPortableNode(): bool
    {
        $targetDir = $this->portableDir();
        File::ensureDirectoryExists($targetDir);

        $version = self::NODE_VERSION;
        $isWindows = $this->isWindows();

        if ($isWindows) {
            $arch = php_uname('m') === 'ARM64' ? 'arm64' : 'x64';
            $filename = "node-v{$version}-win-{$arch}";
            $url = "https://nodejs.org/dist/v{$version}/{$filename}.zip";
            $tempFile = storage_path("app/temp/{$filename}.zip");
        } else {
            $machine = php_uname('m');
            $arch = str_contains($machine, 'aarch64') || str_contains($machine, 'arm64') ? 'arm64' : 'x64';
            $filename = "node-v{$version}-linux-{$arch}";
            $url = "https://nodejs.org/dist/v{$version}/{$filename}.tar.xz";
            $tempFile = storage_path("app/temp/{$filename}.tar.xz");
        }

        File::ensureDirectoryExists(storage_path('app/temp'));

        // Download
        $result = Process::timeout(300)->run("curl -fsSL -o \"{$tempFile}\" \"{$url}\" 2>&1");
        if (!$result->successful() || !file_exists($tempFile)) {
            return false;
        }

        // Extract
        if ($isWindows) {
            $extractDir = storage_path('app/temp/node_extract');
            File::ensureDirectoryExists($extractDir);

            $zip = new \ZipArchive();
            if ($zip->open($tempFile) !== true) {
                @unlink($tempFile);
                return false;
            }
            $zip->extractTo($extractDir);
            $zip->close();

            // Move contents from extracted directory
            $sourceDir = "{$extractDir}/{$filename}";
            if (File::isDirectory($sourceDir)) {
                if (File::isDirectory($targetDir)) {
                    File::cleanDirectory($targetDir);
                }
                File::copyDirectory($sourceDir, $targetDir);
                File::deleteDirectory($extractDir);
            }
        } else {
            Process::run("tar -xf \"{$tempFile}\" -C \"" . storage_path('app/temp') . "\" 2>&1");
            $sourceDir = storage_path("app/temp/{$filename}");
            if (File::isDirectory($sourceDir)) {
                if (File::isDirectory($targetDir)) {
                    File::cleanDirectory($targetDir);
                }
                File::copyDirectory($sourceDir, $targetDir);
                File::deleteDirectory($sourceDir);

                // Ensure binaries are executable (copyDirectory doesn't preserve permissions)
                foreach (['bin/node', 'bin/npm', 'bin/npx'] as $bin) {
                    $binPath = "{$targetDir}/{$bin}";
                    if (file_exists($binPath)) {
                        chmod($binPath, 0755);
                    }
                }
            }
        }

        @unlink($tempFile);

        // Prevent the parent project's "type":"module" from affecting npm scripts
        File::put("{$targetDir}/package.json", '{"type":"commonjs"}' . "\n");

        // Clear cache so paths are re-detected
        Cache::forget(self::CACHE_KEY);

        // Verify
        $nodePath = $this->getPortablePath('node');
        return $nodePath && file_exists($nodePath);
    }

    private function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Check if a binary exists, using shell test on Linux to bypass open_basedir.
     */
    private function binaryExists(string $path): bool
    {
        if ($this->isWindows()) {
            return @file_exists($path);
        }

        $result = Process::run("test -x \"{$path}\" && echo ok 2>/dev/null");
        return trim($result->output()) === 'ok';
    }
}
