<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deployment extends Model
{
    protected $fillable = [
        'bot_id',
        'commit_hash',
        'commit_message',
        'previous_commit',
        'status',
        'triggered_by',
        'output',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    public function shortCommit(): string
    {
        return $this->commit_hash ? substr($this->commit_hash, 0, 7) : '---';
    }

    public function shortPreviousCommit(): string
    {
        return $this->previous_commit ? substr($this->previous_commit, 0, 7) : '---';
    }

    public function duration(): ?string
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        $seconds = $this->started_at->diffInSeconds($this->finished_at);

        if ($seconds < 60) {
            return $seconds . 's';
        }

        return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    }
}
