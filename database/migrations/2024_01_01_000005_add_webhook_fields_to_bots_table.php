<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->text('webhook_secret')->nullable()->after('deploy_key');
            $table->boolean('auto_deploy')->default(false)->after('webhook_secret');
            $table->timestamp('last_webhook_at')->nullable()->after('auto_deploy');
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn(['webhook_secret', 'auto_deploy', 'last_webhook_at']);
        });
    }
};
