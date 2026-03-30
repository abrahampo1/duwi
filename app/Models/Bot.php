<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'entry_file',
        'node_version',
        'pid',
        'path',
        'env_vars',
        'last_started_at',
    ];

    protected $hidden = [
        'deploy_key',
    ];

    protected function casts(): array
    {
        return [
            'last_started_at' => 'datetime',
            'deploy_key' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BotLog::class);
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
