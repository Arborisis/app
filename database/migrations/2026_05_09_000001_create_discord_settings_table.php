<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discord_settings', function (Blueprint $table) {
            $table->id();
            $table->string('guild_id')->nullable();
            $table->string('notification_channel_id')->nullable();
            $table->string('welcome_channel_id')->nullable();
            $table->string('radio_channel_id')->nullable();
            $table->string('moderation_channel_id')->nullable();
            $table->boolean('bot_enabled')->default(false);
            $table->text('internal_api_token')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discord_settings');
    }
};
