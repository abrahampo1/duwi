<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->string('db_user')->nullable()->after('env_vars');
            $table->text('db_password')->nullable()->after('db_user');
            $table->string('db_name')->nullable()->after('db_password');
        });
    }

    public function down(): void
    {
        Schema::table('bots', function (Blueprint $table) {
            $table->dropColumn(['db_user', 'db_password', 'db_name']);
        });
    }
};
