<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bot_id')->constrained()->cascadeOnDelete();
            $table->string('commit_hash', 40)->nullable();
            $table->string('commit_message')->nullable();
            $table->string('previous_commit', 40)->nullable();
            $table->string('status')->default('pending'); // pending, running, verifying, success, failed, rolled_back
            $table->string('triggered_by')->default('manual'); // manual, webhook, rollback
            $table->text('output')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
