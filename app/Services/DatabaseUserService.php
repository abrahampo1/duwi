<?php

namespace App\Services;

use App\Models\Bot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseUserService
{
    /**
     * Check if the current database driver supports user management.
     */
    public function isSupported(): bool
    {
        return in_array($this->getDriver(), ['mysql', 'mariadb', 'pgsql']);
    }

    /**
     * Get the current database driver name.
     */
    public function getDriver(): string
    {
        return config('database.default');
    }

    /**
     * Get a human-readable label for the current driver.
     */
    public function getDriverLabel(): string
    {
        return match ($this->getDriver()) {
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            'pgsql' => 'PostgreSQL',
            default => $this->getDriver(),
        };
    }

    /**
     * Create a database user and database for a bot.
     * Returns the credentials array or throws on failure.
     */
    public function createUser(Bot $bot): array
    {
        if (!$this->isSupported()) {
            throw new \RuntimeException(__('El driver de base de datos actual no soporta gestion de usuarios.'));
        }

        $username = 'bot_' . $bot->id;
        $password = Str::random(32);
        $database = 'bot_' . $bot->id;

        $driver = $this->getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $this->createMysqlUser($username, $password, $database);
        } else {
            $this->createPgsqlUser($username, $password, $database);
        }

        return [
            'username' => $username,
            'password' => $password,
            'database' => $database,
        ];
    }

    /**
     * Drop the database user and database for a bot.
     */
    public function dropUser(Bot $bot): void
    {
        if (!$this->isSupported() || !$bot->db_user) {
            return;
        }

        $username = $bot->db_user;
        $database = $bot->db_name;
        $driver = $this->getDriver();

        if (in_array($driver, ['mysql', 'mariadb'])) {
            $this->dropMysqlUser($username, $database);
        } else {
            $this->dropPgsqlUser($username, $database);
        }
    }

    /**
     * Get the connection info that should be injected into the bot's environment.
     */
    public function getConnectionEnv(Bot $bot): array
    {
        if (!$bot->db_user || !$bot->db_password) {
            return [];
        }

        $driver = $this->getDriver();
        $host = config("database.connections.{$driver}.host", '127.0.0.1');
        $port = config("database.connections.{$driver}.port");

        return [
            'DB_HOST' => $host,
            'DB_PORT' => (string) $port,
            'DB_DATABASE' => $bot->db_name,
            'DB_USERNAME' => $bot->db_user,
            'DB_PASSWORD' => $bot->db_password,
            'DB_DRIVER' => in_array($driver, ['mysql', 'mariadb']) ? 'mysql' : 'postgres',
        ];
    }

    // ── MySQL / MariaDB ──────────────────────────────────────────

    private function createMysqlUser(string $username, string $password, string $database): void
    {
        $quotedUser = $this->quoteMysqlIdentifier($username);
        $quotedDb = $this->quoteMysqlIdentifier($database);

        DB::statement("CREATE DATABASE IF NOT EXISTS {$quotedDb} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        DB::statement("CREATE USER IF NOT EXISTS {$quotedUser}@'%' IDENTIFIED BY " . DB::getPdo()->quote($password));
        DB::statement("GRANT ALL PRIVILEGES ON {$quotedDb}.* TO {$quotedUser}@'%'");
        DB::statement("FLUSH PRIVILEGES");
    }

    private function dropMysqlUser(string $username, ?string $database): void
    {
        $quotedUser = $this->quoteMysqlIdentifier($username);

        if ($database) {
            $quotedDb = $this->quoteMysqlIdentifier($database);
            DB::statement("DROP DATABASE IF EXISTS {$quotedDb}");
        }

        DB::statement("DROP USER IF EXISTS {$quotedUser}@'%'");
        DB::statement("FLUSH PRIVILEGES");
    }

    private function quoteMysqlIdentifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    // ── PostgreSQL ───────────────────────────────────────────────

    private function createPgsqlUser(string $username, string $password, string $database): void
    {
        $quotedUser = $this->quotePgsqlIdentifier($username);
        $quotedDb = $this->quotePgsqlIdentifier($database);

        // Create role if not exists
        $exists = DB::selectOne("SELECT 1 FROM pg_roles WHERE rolname = ?", [$username]);
        if (!$exists) {
            DB::statement("CREATE ROLE {$quotedUser} WITH LOGIN PASSWORD " . DB::getPdo()->quote($password));
        }

        // Create database if not exists
        $dbExists = DB::selectOne("SELECT 1 FROM pg_database WHERE datname = ?", [$database]);
        if (!$dbExists) {
            DB::statement("CREATE DATABASE {$quotedDb} OWNER {$quotedUser}");
        }

        DB::statement("GRANT ALL PRIVILEGES ON DATABASE {$quotedDb} TO {$quotedUser}");
    }

    private function dropPgsqlUser(string $username, ?string $database): void
    {
        $quotedUser = $this->quotePgsqlIdentifier($username);

        if ($database) {
            $quotedDb = $this->quotePgsqlIdentifier($database);

            // Terminate active connections to the database
            DB::statement("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ?", [$database]);
            DB::statement("DROP DATABASE IF EXISTS {$quotedDb}");
        }

        $exists = DB::selectOne("SELECT 1 FROM pg_roles WHERE rolname = ?", [$username]);
        if ($exists) {
            DB::statement("DROP ROLE {$quotedUser}");
        }
    }

    private function quotePgsqlIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }
}
