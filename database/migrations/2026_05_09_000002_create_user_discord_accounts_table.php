<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_discord_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('discord_id')->unique();
            $table->string('discord_username')->nullable();
            $table->string('discord_avatar')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('linked_at')->nullable();
            $table->timestamps();

            $table->index('discord_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_discord_accounts');
    }
};
