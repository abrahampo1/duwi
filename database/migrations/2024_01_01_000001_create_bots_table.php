<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('stopped'); // stopped, running, error, deploying
            $table->string('deploy_method'); // github, zip
            $table->string('repo_url')->nullable();
            $table->string('entry_file')->default('index.js');
            $table->string('node_version')->default('18');
            $table->integer('pid')->nullable();
            $table->string('path');
            $table->text('env_vars')->nullable();
            $table->timestamp('last_started_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bots');
    }
};
