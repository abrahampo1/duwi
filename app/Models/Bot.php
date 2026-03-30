<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
        'deploy_method',
        'repo_url',
        'deploy_key',
        'webhook_secret',
        'auto_deploy',
        'last_webhook_at',
        'entry_file',
        'node_version',
        'pid',
        'path',
        'env_vars',
        'last_started_at',
        'db_user',
        'db_password',
        'db_name',
    ];

    protected $hidden = [
        'deploy_key',
        'webhook_secret',
        'db_password',
    ];

    protected function casts(): array
    {
        return [
            'last_started_at' => 'datetime',
            'last_webhook_at' => 'datetime',
            'deploy_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
            'db_password' => 'encrypted',
            'auto_deploy' => 'boolean',
        ];
    }

    public function getWebhookUrl(): ?string
    {
        if (!$this->webhook_secret) {
            return null;
        }

        return url("/webhook/bot/{$this->id}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BotLog::class);
    }

    public function deployments(): HasMany
    {
        return $this->hasMany(Deployment::class);
    }

    public function lastSuccessfulDeployment(): HasOne
    {
        return $this->hasOne(Deployment::class)->where('status', 'success')->latest();
    }

    public function latestDeployment(): HasOne
    {
        return $this->hasOne(Deployment::class)->latestOfMany();
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isStopped(): bool
    {
        return $this->status === 'stopped';
    }

    public function getFullPath(): string
    {
        return storage_path('app/bots/' . $this->path);
    }
}
